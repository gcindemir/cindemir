#!/usr/bin/env python3
"""Keep captcha/login pages open on cloud desktop for manual user interaction."""
import os
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

DISPLAY = os.environ.get("DISPLAY", ":1")
ROOT = Path(__file__).resolve().parents[2]


def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(
            headless=False,
            args=[
                "--no-sandbox",
                "--disable-dev-shm-usage",
                "--start-maximized",
            ],
            env={**os.environ, "DISPLAY": DISPLAY},
        )
        ctx = browser.new_context(viewport={"width": 1500, "height": 950})
        pages = []

        wp = ctx.new_page()
        wp.goto("https://cindemirlaw.com/wp-admin/", wait_until="domcontentloaded", timeout=90000)
        pages.append(("wp-admin", wp))

        bh = ctx.new_page()
        bh.goto("https://www.bluehost.com/my-account/login", wait_until="domcontentloaded", timeout=90000)
        pages.append(("bluehost", bh))

        bh.bring_to_front()
        time.sleep(1)
        shot = ROOT / "fixes" / "captcha-ready-desktop.png"
        os.system(f"DISPLAY={DISPLAY} scrot {shot}")

        print("CAPTCHA_BROWSER_READY")
        print("wp-admin:", wp.url, "|", wp.title())
        print("bluehost:", bh.url, "|", bh.title())
        print("screenshot:", shot)
        print("Tarayıcı açık — masaüstünden tıklayın. Kapatmak için Ctrl+C.")

        try:
            while True:
                time.sleep(60)
        except KeyboardInterrupt:
            pass
        browser.close()


if __name__ == "__main__":
    main()
