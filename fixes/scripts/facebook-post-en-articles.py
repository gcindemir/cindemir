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
PROFILE = Path.home() / ".config/google-chrome"
ASSET = "336218871992"
BIZ = "1161971660662915"
COMPOSER = f"https://business.facebook.com/latest/composer/?asset_id={ASSET}&business_id={BIZ}"
ARTS_PATH = ROOT / "fixes/facebook-en-articles-enriched.json"
FALLBACK_ARTS = ROOT / "fixes/facebook-en-articles-clean.json"
PROGRESS = ROOT / "fixes/facebook-post-progress.json"
SHOT = ROOT / "fixes"
BATCH = int(os.environ.get("FB_BATCH", "10"))
DELAY = float(os.environ.get("FB_DELAY", "18"))
MODE = os.environ.get("FB_MODE", "pending")  # pending | repost | last7
REPOST_ALL = os.environ.get("FB_REPOST", "").lower() in ("1", "true", "yes")


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


def post_one(page, art: dict) -> tuple[bool, str]:
    title = art.get("title", "")
    link = art["link"].strip()
    text = art.get("post_text") or f"{title}\n\nRead more: {link}"

    page.goto(COMPOSER, timeout=120000)
    page.wait_for_timeout(5000)
    dismiss(page)
    uncheck_instagram_if_present(page)

    box = find_composer_box(page)
    if not box:
        return False, "no_textbox"

    try:
        paste_text(page, box, text)
        page.wait_for_timeout(4500)

        if not composer_has_link(page, link):
            # Retry: append link on fresh line
            box.click(timeout=2000)
            page.keyboard.press("End")
            page.keyboard.press("Enter")
            page.keyboard.press("Enter")
            page.evaluate("async (u) => { await navigator.clipboard.writeText(u); }", link)
            page.keyboard.press("Control+v")
            page.wait_for_timeout(5000)

        if not composer_has_link(page, link):
            try:
                val = box.inner_text()
                if link.rstrip("/").split("/")[-1].lower() not in val.lower():
                    return False, "link_missing_in_composer"
            except Exception:
                return False, "link_not_verified"

        publish = find_publish_button(page)
        if publish is None:
            return False, "no_publish_button"

        for _ in range(12):
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

        body = ""
        try:
            body = page.inner_text("body")[:3000].lower()
        except Exception:
            pass
        if any(
            x in body
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
            return True, "ok"
        if "composer" not in page.url:
            return True, "ok"
        return True, "clicked_publish"
    except Exception as e:
        return False, str(e)


def select_batch(arts, progress):
    quality = {x.rstrip("/") for x in progress.get("posted_quality_links", [])}

    if MODE == "last7":
        old_posted = {x.rstrip("/") for x in progress.get("posted_links", [])}
        never = [a for a in arts if a["link"].rstrip("/") not in quality]
        # Prefer articles that were never auto-posted in the broken run
        pending7 = [a for a in never if a["link"].rstrip("/") not in old_posted]
        return (pending7 or never)[:7]

    if MODE == "repost" or REPOST_ALL:
        # Repost everything not yet posted with quality format
        return [a for a in arts if a["link"].rstrip("/") not in quality]

    return [a for a in arts if a["link"].rstrip("/") not in quality]


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(3)

    arts = load_articles()
    progress = load_progress()
    quality_links = set(progress.get("posted_quality_links", []))
    batch = select_batch(arts, progress)[:BATCH]
    print(f"mode={MODE} quality_done={len(quality_links)} batch={len(batch)} total={len(arts)}", flush=True)

    if not batch:
        print("nothing to post", flush=True)
        return 0

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

        page.goto(f"https://business.facebook.com/latest/home?asset_id={ASSET}&business_id={BIZ}", timeout=120000)
        page.wait_for_timeout(4000)
        dismiss(page)
        page.screenshot(path=str(SHOT / "fb-biz-warm.png"))
        print("warm", page.url, flush=True)

        for idx, art in enumerate(batch, 1):
            title = art.get("title", "")
            link = art["link"]
            print(f"[{idx}/{len(batch)}] {title[:75]}", flush=True)
            try:
                ok, err = post_one(page, art)
                page.screenshot(path=str(SHOT / f"fb-biz-post-{idx}.png"))
                if ok:
                    quality_links.add(link.rstrip("/"))
                    progress["posted_quality_links"] = sorted(quality_links)
                    progress["last_quality"] = {
                        "title": title,
                        "link": link,
                        "status": err,
                        "summary_len": len(art.get("summary") or ""),
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
        progress["last_quality_results"] = results
        progress["quality_remaining"] = len(arts) - len(quality_links)
        save_progress(progress)
        page.screenshot(path=str(SHOT / "fb-biz-final.png"))
        ctx.close()

    summary = {
        "mode": MODE,
        "results": results,
        "quality_posted_total": len(quality_links),
        "quality_remaining": len(arts) - len(quality_links),
        "ok_in_batch": sum(1 for r in results if r.get("ok")),
    }
    print(json.dumps(summary, indent=2, ensure_ascii=False), flush=True)
    return 0 if summary["ok_in_batch"] else 1


if __name__ == "__main__":
    raise SystemExit(main())
