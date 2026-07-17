#!/usr/bin/env python3
"""Check FB login and attempt Continue with Facebook."""
import os
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

PROFILE = Path("/tmp/fb-jifkln9c")
ROOT = Path("/workspace")

def main():
    os.system("pkill -9 -f 'google-chrome.*fb-jifkln9c' 2>/dev/null")
    time.sleep(2)
    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(PROFILE),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1500, "height": 950},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()

        # Check facebook.com session
        page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=120000)
        time.sleep(8)
        page.screenshot(path=str(ROOT / "fixes/fb-check-facebook.png"), full_page=True)
        print("facebook.com:", page.url, flush=True)

        # Business suite login
        page.goto(
            "https://business.facebook.com/latest/home?asset_id=100066585793269",
            wait_until="domcontentloaded",
            timeout=120000,
        )
        time.sleep(6)
        page.screenshot(path=str(ROOT / "fixes/fb-check-biz-1.png"), full_page=True)
        print("biz before:", page.url, flush=True)

        for sel in [
            'div[role="button"]:has-text("Continue with Facebook")',
            'div[role="button"]:has-text("Facebook ile devam et")',
            'a:has-text("Continue with Facebook")',
        ]:
            loc = page.locator(sel)
            if loc.count():
                print(f"clicking {sel}", flush=True)
                loc.first.click(timeout=10000)
                time.sleep(15)
                break

        page.screenshot(path=str(ROOT / "fixes/fb-check-biz-2.png"), full_page=True)
        print("biz after:", page.url, flush=True)
        print("title:", page.title(), flush=True)

        # Wait for user if checkpoint
        if "checkpoint" in page.url or "login" in page.url.lower():
            print("WAITING 120s for manual auth...", flush=True)
            time.sleep(120)
            page.screenshot(path=str(ROOT / "fixes/fb-check-biz-3.png"), full_page=True)
            print("after wait:", page.url, flush=True)

        ctx.close()

if __name__ == "__main__":
    main()
