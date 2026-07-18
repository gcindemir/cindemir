#!/usr/bin/env python3
"""Bluehost deploy automation using copied Chrome profile."""
import json
import shutil
import sys
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[2]
ZIP_PATH = ROOT / "fixes/deploy-package/mu-plugins.zip"
SRC_PROFILE = Path.home() / ".config/google-chrome"
DISPLAY = ":1"


def log(msg):
    print(msg, flush=True)


def main():
    if not ZIP_PATH.exists():
        log(f"Missing {ZIP_PATH}")
        return 1

    tmp_profile = Path(tempfile.mkdtemp(prefix="bh-profile-"))
    log(f"Copying profile to {tmp_profile}")
    for item in ["Default", "Local State"]:
        src = SRC_PROFILE / item
        if src.exists():
            dest = tmp_profile / item
            if src.is_dir():
                shutil.copytree(src, dest, dirs_exist_ok=True)
            else:
                shutil.copy2(src, dest)

    with sync_playwright() as p:
        browser = p.chromium.launch_persistent_context(
            user_data_dir=str(tmp_profile),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**__import__("os").environ, "DISPLAY": DISPLAY},
            viewport={"width": 1500, "height": 950},
        )
        page = browser.pages[0] if browser.pages else browser.new_page()

        page.goto("https://my.bluehost.com/hosting/app", wait_until="networkidle", timeout=90000)
        log(f"URL1: {page.url} | {page.title()}")
        page.screenshot(path=str(ROOT / "fixes/auto-1-hosting.png"), full_page=True)

        if "login" in page.url.lower():
            log("NOT LOGGED IN - session cookies not usable in this environment")
            browser.close()
            shutil.rmtree(tmp_profile, ignore_errors=True)
            return 3

        # click file manager
        for sel in ["text=File Manager", "text=FILE MANAGER", "a:has-text('File Manager')"]:
            loc = page.locator(sel)
            if loc.count():
                try:
                    loc.first.click(timeout=5000)
                    page.wait_for_timeout(4000)
                    log(f"Clicked {sel}")
                    break
                except Exception:
                    pass

        page.screenshot(path=str(ROOT / "fixes/auto-2-fm.png"), full_page=True)
        log(f"URL2: {page.url}")

        uploaded = False
        for frame in [page, *page.frames]:
            try:
                fi = frame.locator('input[type="file"]')
                if fi.count():
                    fi.first.set_input_files(str(ZIP_PATH))
                    uploaded = True
                    log("Uploaded via file input")
                    page.wait_for_timeout(8000)
                    break
            except Exception as e:
                log(f"frame upload: {e}")

        page.screenshot(path=str(ROOT / "fixes/auto-3-done.png"), full_page=True)
        log(json.dumps({"uploaded": uploaded, "url": page.url}))
        browser.close()

    shutil.rmtree(tmp_profile, ignore_errors=True)
    return 0 if uploaded else 2


if __name__ == "__main__":
    sys.exit(main())
