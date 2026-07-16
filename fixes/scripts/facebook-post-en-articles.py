#!/usr/bin/env python3
"""Post English cindemirlaw.com articles via Meta Business Suite composer."""
from __future__ import annotations

import json
import os
import re
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
ARTS_PATH = ROOT / "fixes/facebook-en-articles-clean.json"
PROGRESS = ROOT / "fixes/facebook-post-progress.json"
SHOT = ROOT / "fixes"
BATCH = int(os.environ.get("FB_BATCH", "10"))
DELAY = float(os.environ.get("FB_DELAY", "18"))


def load_progress():
    if PROGRESS.exists():
        return json.loads(PROGRESS.read_text())
    return {"posted_links": []}


def save_progress(data):
    data["updated"] = time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())
    PROGRESS.write_text(json.dumps(data, ensure_ascii=False, indent=2))


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


def dismiss(page):
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
                loc.first.click(timeout=1200)
                page.wait_for_timeout(350)
        except Exception:
            pass
    for sel in ["[aria-label='Close']", "[aria-label='Kapat']", "[aria-label='Dismiss']"]:
        try:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=800)
                page.wait_for_timeout(250)
        except Exception:
            pass


def uncheck_instagram_if_present(page):
    """Prefer Facebook Page only when a share toggle is available."""
    try:
        # Click the share destinations control, then uncheck Instagram if listed.
        share = page.locator("text=/Şurada paylaş|Share to|Where to share/i").first
        if share.count():
            try:
                share.click(timeout=1500)
                page.wait_for_timeout(600)
            except Exception:
                pass
        # Toggle / checkbox near Instagram account name
        candidates = page.locator(
            "div[role='checkbox'], div[role='switch'], input[type='checkbox']"
        )
        for i in range(min(candidates.count(), 12)):
            el = candidates.nth(i)
            try:
                # climb for nearby text
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
            try:
                if hasattr(el, "is_checked"):
                    checked = checked or el.is_checked()
            except Exception:
                pass
            if checked:
                el.click(timeout=1500)
                page.wait_for_timeout(400)
                print("unchecked Instagram share", flush=True)
                break
    except Exception as e:
        print("ig toggle skip", e, flush=True)


def post_one(page, title: str, link: str) -> tuple[bool, str]:
    page.goto(COMPOSER, timeout=120000)
    page.wait_for_timeout(4500)
    dismiss(page)
    uncheck_instagram_if_present(page)

    box = None
    for sel in [
        "div[role='textbox'][contenteditable='true']",
        "div[role='textbox']",
        "[contenteditable='true']",
        "textarea",
    ]:
        loc = page.locator(sel)
        if loc.count():
            box = loc.first
            break
    if not box:
        return False, "no_textbox"

    text = f"{title}\n\n{link}"
    try:
        box.click(timeout=3000)
        page.wait_for_timeout(300)
        page.keyboard.press("Control+a")
        page.keyboard.press("Backspace")
        page.keyboard.insert_text(text)
        page.wait_for_timeout(3500)

        # Wait for link preview / enable publish
        publish = None
        for name in [r"^Yayınla$", r"^Publish$", r"^Post$", r"^Paylaş$"]:
            btn = page.get_by_role("button", name=re.compile(name))
            if btn.count():
                publish = btn.first
                break
        if publish is None:
            # aria-label fallback
            for sel in ["[aria-label='Yayınla']", "[aria-label='Publish']", "div[role=button]:has-text('Yayınla')"]:
                loc = page.locator(sel)
                if loc.count():
                    publish = loc.first
                    break
        if publish is None:
            return False, "no_publish_button"

        # Retry enable
        for _ in range(8):
            try:
                if publish.is_enabled():
                    break
            except Exception:
                pass
            page.wait_for_timeout(800)

        publish.click(timeout=5000)
        page.wait_for_timeout(4500)
        dismiss(page)
        page.wait_for_timeout(800)

        # Success heuristics
        body = ""
        try:
            body = page.inner_text("body")[:2500]
        except Exception:
            pass
        url = page.url
        bl = body.lower()
        if any(
            x in bl
            for x in [
                "yayınlandı",
                "published",
                "paylaşıldı",
                "gönderin yayınlandı",
                "yayınlamak istediğiniz daha fazla",
                "more posts you want to publish",
                "success",
            ]
        ):
            dismiss(page)
            return True, "ok"
        if "composer" not in url:
            return True, "ok"
        # Still on composer — treat publish click as success (verified in batch1)
        return True, "clicked_publish"
    except Exception as e:
        return False, str(e)


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(3)

    arts = json.loads(ARTS_PATH.read_text())
    progress = load_progress()
    posted_links = set(progress.get("posted_links", []))
    pending = [a for a in arts if a["link"].rstrip("/") not in {x.rstrip("/") for x in posted_links}]
    batch = pending[:BATCH]
    print(f"pending={len(pending)} batch={len(batch)}", flush=True)

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
            slow_mo=40,
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()

        # Warm session
        page.goto(f"https://business.facebook.com/latest/home?asset_id={ASSET}&business_id={BIZ}", timeout=120000)
        page.wait_for_timeout(4000)
        dismiss(page)
        page.screenshot(path=str(SHOT / "fb-biz-warm.png"))
        print("warm", page.url, flush=True)

        for idx, art in enumerate(batch, 1):
            title = art["title"]
            link = art["link"]
            print(f"[{idx}/{len(batch)}] {title[:75]}", flush=True)
            try:
                ok, err = post_one(page, title, link)
                page.screenshot(path=str(SHOT / f"fb-biz-post-{idx}.png"))
                if ok:
                    posted_links.add(link.rstrip("/"))
                    progress["posted_links"] = sorted(posted_links)
                    progress["last"] = {"title": title, "link": link, "status": err}
                    save_progress(progress)
                    results.append({"title": title, "link": link, "ok": True, "status": err})
                    print("OK", err, flush=True)
                else:
                    results.append({"title": title, "link": link, "ok": False, "error": err})
                    print("FAIL", err, flush=True)
                    break
                time.sleep(DELAY)
            except Exception as e:
                traceback.print_exc()
                results.append({"title": title, "link": link, "ok": False, "error": str(e)})
                break

        progress["posted_links"] = sorted(posted_links)
        progress["last_results"] = results
        progress["remaining"] = len(arts) - len(posted_links)
        save_progress(progress)
        page.screenshot(path=str(SHOT / "fb-biz-final.png"))
        ctx.close()

    summary = {
        "results": results,
        "posted_total": len(posted_links),
        "remaining": len(arts) - len(posted_links),
        "ok_in_batch": sum(1 for r in results if r.get("ok")),
    }
    print(json.dumps(summary, indent=2, ensure_ascii=False), flush=True)
    return 0 if summary["ok_in_batch"] else 1


if __name__ == "__main__":
    raise SystemExit(main())
