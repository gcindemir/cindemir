#!/usr/bin/env python3
"""Upload mu-plugins via open cPanel File Manager in Chrome."""
import json
import os
import re
import sys
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[2]
ZIP_PATH = ROOT / "fixes/deploy-package/mu-plugins.zip"
PHP_FILES = [
    ROOT / "fixes/mu-plugins/cindemir-seo-fixes.php",
    ROOT / "fixes/mu-plugins/cindemir-expose-yoast-meta.php",
]
PROFILE = Path.home() / ".config/google-chrome"
DISPLAY = os.environ.get("DISPLAY", ":1")
LOG = ROOT / "fixes/fm-upload.log"


def log(msg: str):
    line = f"[{time.strftime('%H:%M:%S')}] {msg}"
    print(line, flush=True)
    with LOG.open("a") as f:
        f.write(line + "\n")


def find_fm_page(browser):
    for ctx in browser.contexts:
        for page in ctx.pages:
            url = page.url.lower()
            title = (page.title() or "").lower()
            if any(
                k in url or k in title
                for k in ("filemanager", "file manager", "cpanel", "hosting")
            ):
                return page
    return browser.contexts[0].pages[0] if browser.contexts and browser.contexts[0].pages else None


def click_first(page, selectors):
    for sel in selectors:
        loc = page.locator(sel)
        if loc.count():
            try:
                loc.first.click(timeout=5000)
                log(f"clicked: {sel}")
                return True
            except Exception as e:
                log(f"click fail {sel}: {e}")
    return False


def open_folder(page, name: str) -> bool:
    for sel in [
        f"a:has-text('{name}')",
        f"td:has-text('{name}')",
        f"span:has-text('{name}')",
        f"text={name}",
    ]:
        loc = page.locator(sel)
        for i in range(min(loc.count(), 5)):
            try:
                loc.nth(i).dblclick(timeout=4000)
                page.wait_for_timeout(2000)
                log(f"opened folder {name} via {sel}")
                return True
            except Exception:
                continue
    return False


def upload_files(page) -> bool:
    click_first(
        page,
        [
            "text=Upload",
            "button:has-text('Upload')",
            "#uploadbtn",
            "a:has-text('Upload')",
        ],
    )
    page.wait_for_timeout(2000)

    targets = [str(ZIP_PATH)] + [str(p) for p in PHP_FILES]
    for frame in [page, *page.frames]:
        try:
            inputs = frame.locator('input[type="file"]')
            count = inputs.count()
            if not count:
                continue
            for idx in range(count):
                inp = inputs.nth(idx)
                try:
                    inp.set_input_files(targets if idx == 0 else str(PHP_FILES[0]))
                    log(f"set_input_files on frame via input #{idx}")
                    page.wait_for_timeout(10000)
                    return True
                except Exception as e:
                    log(f"input #{idx} error: {e}")
        except Exception as e:
            log(f"frame scan: {e}")
    return False


def extract_zip(page) -> bool:
    for sel in ["text=mu-plugins.zip", "td:has-text('mu-plugins.zip')"]:
        loc = page.locator(sel)
        if loc.count():
            try:
                loc.first.click(timeout=3000)
                log("selected mu-plugins.zip")
                break
            except Exception:
                pass

    return click_first(
        page,
        [
            "text=Extract",
            "button:has-text('Extract')",
            "a:has-text('Extract')",
            "#extractbtn",
        ],
    )


def main():
    if not ZIP_PATH.exists():
        log(f"missing zip: {ZIP_PATH}")
        return 1

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            user_data_dir=str(PROFILE),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": DISPLAY},
            viewport={"width": 1500, "height": 950},
        )

        page = find_fm_page(ctx)
        if not page:
            page = ctx.new_page()
            page.goto("https://my.bluehost.com/hosting/app", timeout=90000)
            page.wait_for_timeout(5000)

        log(f"start url: {page.url} | {page.title()}")
        page.bring_to_front()
        page.screenshot(path=str(ROOT / "fixes/fm-auto-0.png"), full_page=True)

        # Tree shortcuts if visible
        for folder in ["wp-content", "mu-plugins"]:
            if not open_folder(page, folder):
                log(f"could not dblclick {folder}, trying tree")
                click_first(page, [f"text={folder}"])

        page.wait_for_timeout(2000)
        page.screenshot(path=str(ROOT / "fixes/fm-auto-1-muplugins.png"), full_page=True)
        body = page.inner_text("body")[:2000]
        log(f"body snippet: {body[:300]}")

        uploaded = upload_files(page)
        page.screenshot(path=str(ROOT / "fixes/fm-auto-2-uploaded.png"), full_page=True)

        extracted = False
        if uploaded:
            page.wait_for_timeout(3000)
            extracted = extract_zip(page)
            page.wait_for_timeout(5000)
            page.screenshot(path=str(ROOT / "fixes/fm-auto-3-extracted.png"), full_page=True)

        result = {
            "uploaded": uploaded,
            "extracted": extracted,
            "url": page.url,
        }
        log(json.dumps(result))
        (ROOT / "fixes/fm-upload-result.json").write_text(json.dumps(result, indent=2))

        page.wait_for_timeout(3000)
        ctx.close()

    return 0 if uploaded else 2


if __name__ == "__main__":
    sys.exit(main())
