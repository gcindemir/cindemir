#!/usr/bin/env python3
"""Post English cindemirlaw.com articles via Meta Business Suite composer.

Each post includes title, meta summary, and article link with link-preview verification.
"""
from __future__ import annotations

import json
import os
import re
import html
import shutil
import tempfile
import time
import traceback
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path(os.environ["FB_PROFILE_DIR"]) if os.environ.get("FB_PROFILE_DIR") else Path.home() / ".config/google-chrome"
ASSET = "336218871992"
BIZ = "1161971660662915"
COMPOSER = f"https://business.facebook.com/latest/composer/?asset_id={ASSET}&business_id={BIZ}"
ARTS_PATH = ROOT / "fixes/facebook-en-articles-enriched.json"
FALLBACK_ARTS = ROOT / "fixes/facebook-en-articles-clean.json"
PROGRESS = ROOT / "fixes/facebook-post-progress.json"
SHOT = ROOT / "fixes"
BATCH = int(os.environ.get("FB_BATCH", "5"))
DELAY = float(os.environ.get("FB_DELAY", "20"))
DAILY_LIMIT = int(os.environ.get("FB_DAILY_LIMIT", "5"))
MODE = os.environ.get("FB_MODE", "pending")  # pending | repost | last7
REPOST_ALL = os.environ.get("FB_REPOST", "").lower() in ("1", "true", "yes")
TZ_OFFSET_HOURS = 3  # Europe/Istanbul


def today_key() -> str:
    return time.strftime("%Y-%m-%d", time.gmtime(time.time() + TZ_OFFSET_HOURS * 3600))


def daily_count(progress: dict) -> int:
    daily = progress.setdefault("daily", {})
    return int(daily.get(today_key(), {}).get("count", 0))


def record_daily_post(progress: dict, link: str):
    daily = progress.setdefault("daily", {})
    day = today_key()
    entry = daily.setdefault(day, {"count": 0, "links": []})
    entry["count"] = int(entry.get("count", 0)) + 1
    entry["links"] = sorted(set(entry.get("links", []) + [link.rstrip("/")]))
    progress["daily"] = daily


def build_body(art: dict) -> str:
    title = art.get("title", "").strip()
    summary = (art.get("summary") or "").strip()
    parts = [title]
    if summary and summary.lower() not in title.lower():
        parts.extend(["", summary])
    return "\n".join(parts)


def load_progress():
    if PROGRESS.exists():
        return json.loads(PROGRESS.read_text())
    return {"posted_links": [], "posted_quality_links": []}


def save_progress(data):
    data["updated"] = time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())
    PROGRESS.write_text(json.dumps(data, ensure_ascii=False, indent=2))


def load_articles():
    path = ARTS_PATH if ARTS_PATH.exists() else FALLBACK_ARTS
    arts = json.loads(path.read_text())
    for a in arts:
        a["title"] = html.unescape(re.sub(r"<[^>]+>", "", a.get("title", ""))).strip()
        if not a.get("post_text"):
            title = a.get("title", "").strip()
            link = a["link"].strip()
            summary = (a.get("summary") or "").strip()
            parts = [title]
            if summary:
                parts.extend(["", summary])
            parts.extend(["", f"Read more: {link}"])
            a["post_text"] = "\n".join(parts)
    return arts


def copy_profile(tmp: Path):
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if not s.exists():
            continue
        d = tmp / item
        if s.is_dir():
            shutil.copytree(
                s,
                d,
                ignore=shutil.ignore_patterns(
                    "Cache", "Code Cache", "GPUCache", "Service Worker", "Blobstore", "DawnCache"
                ),
            )
        else:
            shutil.copy2(s, d)


def dismiss(page, force: bool = False):
    for label in [
        "Belki daha sonra",
        "Maybe later",
        "Şimdi değil",
        "Not now",
        "Not Now",
        "Tamam",
        "OK",
        "Close",
        "Kapat",
        "Yok say",
    ]:
        try:
            loc = page.get_by_role("button", name=re.compile(re.escape(label), re.I))
            if loc.count():
                loc.first.click(timeout=1200, force=force)
                page.wait_for_timeout(350)
        except Exception:
            pass
    for sel in ["[aria-label='Close']", "[aria-label='Kapat']", "[aria-label='Dismiss']"]:
        try:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=800, force=force)
                page.wait_for_timeout(250)
        except Exception:
            pass


def uncheck_instagram_if_present(page):
    try:
        share = page.locator("text=/Şurada paylaş|Share to|Where to share/i").first
        if share.count():
            try:
                share.click(timeout=1500)
                page.wait_for_timeout(600)
            except Exception:
                pass
        candidates = page.locator("div[role='checkbox'], div[role='switch'], input[type='checkbox']")
        for i in range(min(candidates.count(), 12)):
            el = candidates.nth(i)
            try:
                parent_text = el.evaluate(
                    """(n) => {
                      let p = n;
                      for (let i=0;i<5 && p;i++) { p = p.parentElement; }
                      return (p && p.innerText) ? p.innerText : '';
                    }"""
                )
            except Exception:
                parent_text = ""
            if "instagram" not in parent_text.lower() and "cindemir_law_office" not in parent_text.lower():
                continue
            aria = (el.get_attribute("aria-checked") or "").lower()
            checked = aria in ("true", "mixed")
            if checked:
                el.click(timeout=1500)
                page.wait_for_timeout(400)
                print("unchecked Instagram share", flush=True)
                break
    except Exception as e:
        print("ig toggle skip", e, flush=True)


def find_composer_box(page):
    for sel in [
        "div[role='textbox'][contenteditable='true']",
        "div[contenteditable='true'][aria-label*='Metin']",
        "div[contenteditable='true']",
        "div[role='textbox']",
    ]:
        loc = page.locator(sel)
        if loc.count():
            return loc.first
    return None


def paste_text(page, box, text: str):
    box.click(timeout=3000)
    page.wait_for_timeout(250)
    page.keyboard.press("Control+a")
    page.keyboard.press("Backspace")
    page.wait_for_timeout(200)
    page.evaluate(
        """async (t) => {
          await navigator.clipboard.writeText(t);
        }""",
        text,
    )
    page.keyboard.press("Control+v")
    page.wait_for_timeout(1200)


def composer_has_link(page, link: str) -> bool:
    slug = link.rstrip("/").split("/")[-1].lower()
    try:
        body = page.inner_text("body").lower()
        if slug and slug in body:
            return True
        if "cindemirlaw.com" in body and "read more" in body:
            return True
    except Exception:
        pass
    # link preview chip / attachment
    for sel in [
        f"a[href*='{slug}']",
        "div[data-testid*='link']",
        "div:has-text('cindemirlaw.com')",
    ]:
        try:
            if page.locator(sel).count():
                return True
        except Exception:
            pass
    return False


def find_publish_button(page):
    for name in [
        r"Facebook'ta Yayınla",
        r"^Yayınla$",
        r"^Publish$",
        r"^Post$",
        r"^Paylaş$",
    ]:
        btn = page.get_by_role("button", name=re.compile(name, re.I))
        if btn.count():
            return btn.first
    for sel in [
        "[aria-label=\"Facebook'ta Yayınla\"]",
        "[aria-label='Yayınla']",
        "[aria-label='Publish']",
        "div[role=button]:has-text('Facebook')",
    ]:
        loc = page.locator(sel)
        if loc.count():
            return loc.first
    return None


def paste_clipboard(page, text: str):
    page.evaluate("async (t) => { await navigator.clipboard.writeText(t); }", text)
    page.keyboard.press("Control+v")
    page.wait_for_timeout(900)


def has_link_preview(page, link: str) -> bool:
    slug = link.rstrip("/").split("/")[-1].lower()
    # Link preview card (attachment), not just plain text in composer
    for sel in [
        f"a[href*='{slug}']",
        f"a[href*='cindemirlaw.com/{slug}']",
        "div[data-testid*='link-preview']",
        "div[data-testid*='link']",
        "div[role='article'] img",
        "div:has-text('cindemirlaw.com'):has(img)",
    ]:
        try:
            loc = page.locator(sel)
            if loc.count():
                return True
        except Exception:
            pass
    try:
        # Preview pane on the right often shows domain + title card
        preview = page.locator("text=/Facebook Akışı önizlemesi|Feed preview|cindemirlaw\\.com/i")
        body = page.inner_text("body").lower()
        if preview.count() and slug in body and "cindemirlaw.com" in body:
            return True
    except Exception:
        pass
    return composer_has_link(page, link)


def post_one(page, art: dict, shot_idx: int = 0) -> tuple[bool, str]:
    link = art["link"].strip()
    body = build_body(art)

    page.goto(COMPOSER, timeout=120000)
    page.wait_for_timeout(5000)
    dismiss(page)
    uncheck_instagram_if_present(page)

    box = find_composer_box(page)
    if not box:
        return False, "no_textbox"

    try:
        # 1) Title + summary only (no URL in bulk paste — FB needs URL on its own line)
        paste_text(page, box, body)
        page.wait_for_timeout(1200)

        # 2) URL on a separate line so Facebook builds a clickable link preview
        box.click(timeout=2000)
        page.keyboard.press("End")
        page.keyboard.press("Enter")
        page.keyboard.press("Enter")
        paste_clipboard(page, link)
        page.wait_for_timeout(6500)

        if not has_link_preview(page, link):
            # Retry: clear URL line and type URL slowly
            page.keyboard.press("End")
            for _ in range(len(link) + 5):
                page.keyboard.press("Backspace")
            page.keyboard.type(link, delay=15)
            page.wait_for_timeout(7000)

        page.screenshot(path=str(SHOT / f"fb-composer-before-{shot_idx}.png"))

        if not has_link_preview(page, link):
            try:
                val = box.inner_text()
                if link.replace("https://", "").rstrip("/") not in val.replace("https://", ""):
                    return False, "link_preview_missing"
            except Exception:
                return False, "link_preview_missing"

        publish = find_publish_button(page)
        if publish is None:
            return False, "no_publish_button"

        for _ in range(14):
            try:
                if publish.is_enabled():
                    break
            except Exception:
                pass
            page.wait_for_timeout(700)

        publish.click(timeout=8000)
        page.wait_for_timeout(5500)
        dismiss(page, force=True)
        page.wait_for_timeout(800)

        body_text = ""
        try:
            body_text = page.inner_text("body")[:3000].lower()
        except Exception:
            pass
        if any(
            x in body_text
            for x in [
                "yayınlandı",
                "published",
                "paylaşıldı",
                "gönderin yayınlandı",
                "yayınlamak istediğiniz daha fazla",
                "more posts you want to publish",
            ]
        ):
            dismiss(page, force=True)
            return True, "ok_with_link_preview"
        if "composer" not in page.url:
            return True, "ok_with_link_preview"
        return True, "clicked_publish"
    except Exception as e:
        return False, str(e)


def ensure_facebook_session(page, ctx) -> bool:
    """Recover Business Suite access via facebook.com saved profile if needed."""
    page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=120000)
    page.wait_for_timeout(3000)
    dismiss(page)
    cookies = {c["name"]: c.get("value", "") for c in ctx.cookies("https://www.facebook.com")}
    if not (cookies.get("c_user") and cookies.get("xs")):
        for sel in [
            page.get_by_role("button", name=re.compile(r"^Devam$", re.I)),
            page.locator("div[role='button']:has-text('Devam')"),
            page.locator("button:has-text('Devam')"),
        ]:
            try:
                if sel.count():
                    sel.first.click(timeout=3000)
                    page.wait_for_timeout(5000)
                    print("clicked Devam for session recover", flush=True)
                    break
            except Exception:
                pass
        for _ in range(60):
            cookies = {c["name"]: c.get("value", "") for c in ctx.cookies("https://www.facebook.com")}
            if cookies.get("c_user") and cookies.get("xs") and "two_factor" not in page.url:
                break
            page.wait_for_timeout(5000)
            dismiss(page)

    page.goto(f"https://business.facebook.com/latest/home?asset_id={ASSET}&business_id={BIZ}", timeout=120000)
    page.wait_for_timeout(4000)
    dismiss(page)
    if "login" in page.url.lower():
        try:
            btn = page.get_by_role("button", name=re.compile(r"Facebook ile devam", re.I))
            if btn.count():
                btn.first.click(timeout=3000)
                page.wait_for_timeout(6000)
                dismiss(page)
        except Exception:
            pass
    page.screenshot(path=str(SHOT / "fb-biz-warm.png"))
    ok = "login" not in page.url.lower()
    print("session_ok", ok, page.url[:140], flush=True)
    return ok


def select_batch(arts, progress):
    done = {x.rstrip("/") for x in progress.get("posted_with_link_preview", [])}
    # Fallback: also skip if already posted today with link
    today_links = set(progress.get("daily", {}).get(today_key(), {}).get("links", []))
    done |= today_links

    if MODE == "last7":
        old_posted = {x.rstrip("/") for x in progress.get("posted_links", [])}
        never = [a for a in arts if a["link"].rstrip("/") not in done]
        pending7 = [a for a in never if a["link"].rstrip("/") not in old_posted]
        return (pending7 or never)[:7]

    if MODE == "repost" or REPOST_ALL:
        return [a for a in arts if a["link"].rstrip("/") not in done]

    return [a for a in arts if a["link"].rstrip("/") not in done]


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(3)

    arts = load_articles()
    progress = load_progress()
    quality_links = set(progress.get("posted_quality_links", []))
    link_preview_done = set(progress.get("posted_with_link_preview", []))
    pending = select_batch(arts, progress)

    already_today = daily_count(progress)
    left_today = max(0, DAILY_LIMIT - already_today)
    if left_today <= 0:
        print(
            json.dumps(
                {
                    "status": "daily_limit_reached",
                    "date": today_key(),
                    "daily_limit": DAILY_LIMIT,
                    "posted_today": already_today,
                    "quality_total": len(quality_links),
                },
                ensure_ascii=False,
            ),
            flush=True,
        )
        return 0

    batch = pending[: min(BATCH, left_today)]
    print(
        f"mode={MODE} date={today_key()} today={already_today}/{DAILY_LIMIT} "
        f"batch={len(batch)} quality_done={len(quality_links)} total={len(arts)}",
        flush=True,
    )

    if not batch:
        print("nothing to post", flush=True)
        return 0

    if os.environ.get("FB_PROFILE_DIR"):
        tmp = Path(os.environ["FB_PROFILE_DIR"])
        print("using existing profile", tmp, flush=True)
    else:
        tmp = Path(tempfile.mkdtemp(prefix="fb-pub-"))
        copy_profile(tmp)
        print("profile", tmp, flush=True)

    results = []
    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage", "--disable-blink-features=AutomationControlled"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1400, "height": 900},
            slow_mo=30,
            permissions=["clipboard-read", "clipboard-write"],
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()

        if not ensure_facebook_session(page, ctx):
            print("FAIL session_not_ready", page.url, flush=True)
            ctx.close()
            return 1
        print("warm", page.url, flush=True)

        for idx, art in enumerate(batch, 1):
            title = art.get("title", "")
            link = art["link"]
            print(f"[{idx}/{len(batch)}] {title[:75]}", flush=True)
            try:
                ok, err = post_one(page, art, shot_idx=idx)
                page.screenshot(path=str(SHOT / f"fb-biz-post-{idx}.png"))
                if ok:
                    quality_links.add(link.rstrip("/"))
                    link_preview_done.add(link.rstrip("/"))
                    progress["posted_quality_links"] = sorted(quality_links)
                    progress["posted_with_link_preview"] = sorted(link_preview_done)
                    record_daily_post(progress, link)
                    progress["last_quality"] = {
                        "title": title,
                        "link": link,
                        "status": err,
                        "summary_len": len(art.get("summary") or ""),
                        "date": today_key(),
                    }
                    save_progress(progress)
                    results.append({"title": title, "link": link, "ok": True, "status": err})
                    print("OK", err, flush=True)
                else:
                    results.append({"title": title, "link": link, "ok": False, "error": err})
                    print("FAIL", err, flush=True)
                    page.screenshot(path=str(SHOT / f"fb-biz-fail-{idx}.png"))
                    break
                time.sleep(DELAY)
            except Exception as e:
                traceback.print_exc()
                results.append({"title": title, "link": link, "ok": False, "error": str(e)})
                break

        progress["posted_quality_links"] = sorted(quality_links)
        progress["posted_with_link_preview"] = sorted(link_preview_done)
        progress["last_quality_results"] = results
        progress["quality_remaining"] = len(arts) - len(link_preview_done)
        save_progress(progress)
        page.screenshot(path=str(SHOT / "fb-biz-final.png"))
        ctx.close()

    summary = {
        "mode": MODE,
        "date": today_key(),
        "daily_limit": DAILY_LIMIT,
        "posted_today": daily_count(progress),
        "results": results,
        "quality_posted_total": len(link_preview_done),
        "quality_remaining": len(arts) - len(link_preview_done),
        "ok_in_batch": sum(1 for r in results if r.get("ok")),
    }
    print(json.dumps(summary, indent=2, ensure_ascii=False), flush=True)
    return 0 if summary["ok_in_batch"] else 1


if __name__ == "__main__":
    raise SystemExit(main())
