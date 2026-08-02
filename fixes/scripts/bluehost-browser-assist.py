#!/usr/bin/env python3
"""Bluehost + WP deploy helper. Run after user logs into Bluehost in the browser."""
import json
import os
import sys
import time
from pathlib import Path

try:
    from playwright.sync_api import sync_playwright
except ImportError:
    print("playwright not installed", file=sys.stderr)
    sys.exit(1)

ROOT = Path(__file__).resolve().parents[2]
DEPLOY_HTML = ROOT / "fixes/bluehost-deploy.html"
ZIP_PATH = ROOT / "fixes/deploy-package/mu-plugins.zip"
PAGES_JSON = ROOT / "fixes/meta-descriptions/pages-14.json"

DISPLAY = os.environ.get("DISPLAY", ":1")


def shot(page, name: str):
    out = ROOT / "fixes" / f"screenshot-{name}.png"
    page.screenshot(path=str(out), full_page=True)
    print(f"screenshot: {out}")


def main():
    pages = json.loads(PAGES_JSON.read_text())
    with sync_playwright() as p:
        browser = p.chromium.launch(
            headless=False,
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": DISPLAY},
        )
        context = browser.new_context(viewport={"width": 1400, "height": 900})
        pg = context.new_page()

        print("Opening deploy guide...")
        pg.goto(DEPLOY_HTML.as_uri(), wait_until="domcontentloaded")
        shot(pg, "00-guide")

        print("Opening Bluehost login — log in manually if needed.")
        pg.goto("https://www.bluehost.com/my-account/login", wait_until="domcontentloaded")
        shot(pg, "01-bluehost")

        # Wait up to 3 minutes for user to reach hosting panel
        for i in range(36):
            url = pg.url
            title = pg.title()
            print(f"[{i*5}s] {url[:80]} | {title[:50]}")
            if any(x in url for x in ("hosting", "my.bluehost", "cpanel", "filemanager")):
                print("Looks logged in / in hosting area.")
                break
            time.sleep(5)
        shot(pg, "02-after-login")

        # Open wp-admin for first page edit
        pg2 = context.new_page()
        pg2.goto("https://cindemirlaw.com/wp-admin/", wait_until="domcontentloaded")
        shot(pg2, "03-wp-admin")

        print("\nReady. Zip path:", ZIP_PATH)
        print("Pages to update:", len(pages))
        print("Leave browser open. Press Ctrl+C when done.")

        try:
            while True:
                time.sleep(30)
        except KeyboardInterrupt:
            pass
        browser.close()


if __name__ == "__main__":
    main()
