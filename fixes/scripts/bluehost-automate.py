#!/usr/bin/env python3
"""Automate Bluehost File Manager upload using existing Chrome login cookies."""
import json
import os
import sys
import time
from pathlib import Path

from playwright.sync_api import sync_playwright, TimeoutError as PWTimeout

ROOT = Path(__file__).resolve().parents[2]
ZIP_PATH = ROOT / "fixes/deploy-package/mu-plugins.zip"
PLUGIN_DIR = ROOT / "fixes/deploy-package/mu-plugins"
PAGES_JSON = ROOT / "fixes/meta-descriptions/pages-14.json"
USER_DATA = Path.home() / ".config/google-chrome"
DISPLAY = os.environ.get("DISPLAY", ":1")
LOG = ROOT / "fixes/bluehost-automation.log"


def log(msg: str):
    line = f"[{time.strftime('%H:%M:%S')}] {msg}"
    print(line)
    with LOG.open("a") as f:
        f.write(line + "\n")


def kill_chrome():
    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)


def main():
    LOG.write_text("")
    log("Starting Bluehost automation")

    with sync_playwright() as p:
        context = p.chromium.launch_persistent_context(
            user_data_dir=str(USER_DATA),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": DISPLAY},
            viewport={"width": 1440, "height": 900},
            accept_downloads=True,
        )
        page = context.pages[0] if context.pages else context.new_page()

        # Bluehost hosting panel
        targets = [
            "https://my.bluehost.com/hosting/app",
            "https://www.bluehost.com/my-account/login",
        ]
        for url in targets:
            log(f"goto {url}")
            try:
                page.goto(url, wait_until="domcontentloaded", timeout=60000)
                time.sleep(3)
                log(f"url now: {page.url} | title: {page.title()[:60]}")
                if "login" not in page.url.lower() or "hosting" in page.url.lower():
                    break
            except PWTimeout as e:
                log(f"timeout: {e}")

        page.screenshot(path=str(ROOT / "fixes/auto-01-hosting.png"), full_page=True)

        # Try to click File Manager
        fm_selectors = [
            "text=File Manager",
            "text=FILE MANAGER",
            "a:has-text('File Manager')",
            "button:has-text('File Manager')",
            "[data-testid*='file']",
        ]
        clicked = False
        for sel in fm_selectors:
            try:
                loc = page.locator(sel).first
                if loc.count() and loc.is_visible(timeout=2000):
                    log(f"click {sel}")
                    loc.click(timeout=5000)
                    clicked = True
                    time.sleep(4)
                    break
            except Exception as e:
                log(f"no {sel}: {e}")

        page.screenshot(path=str(ROOT / "fixes/auto-02-after-fm-click.png"), full_page=True)
        log(f"after FM click: {page.url}")

        # cPanel fallback in new tab
        cp = context.new_page()
        cp_urls = [
            "https://cindemirlaw.com:2083/cpsess0000000000/frontend/jupiter/filemanager/index.html",
            "https://cindemirlaw.com/cpanel",
        ]
        for url in cp_urls:
            try:
                log(f"cpanel try {url}")
                cp.goto(url, wait_until="domcontentloaded", timeout=30000)
                time.sleep(3)
                log(f"cpanel url: {cp.url}")
                cp.screenshot(path=str(ROOT / "fixes/auto-03-cpanel.png"), full_page=True)
                if "login" not in cp.url.lower():
                    break
            except Exception as e:
                log(f"cpanel fail: {e}")

        # WP admin + REST meta if logged in
        wp = context.new_page()
        wp.goto("https://cindemirlaw.com/wp-admin/", wait_until="domcontentloaded", timeout=60000)
        time.sleep(3)
        wp.screenshot(path=str(ROOT / "fixes/auto-04-wpadmin.png"), full_page=True)
        log(f"wp-admin: {wp.url}")

        if "wp-admin" in wp.url and "login" not in wp.url:
            pages = json.loads(PAGES_JSON.read_text())
            for row in pages[:3]:  # test first 3
                edit = f"https://cindemirlaw.com/wp-admin/post.php?post={row['id']}&action=edit"
                log(f"edit page {row['id']}")
                wp.goto(edit, wait_until="domcontentloaded", timeout=60000)
                time.sleep(2)
                # Yoast metabox
                for sel in ["#yoast_wpseo_metadesc", "textarea[name='_yoast_wpseo_metadesc']", "#yoast-snippet-editor-metadesc"]:
                    try:
                        box = wp.locator(sel).first
                        if box.count():
                            box.fill(row["metadesc"])
                            log(f"filled meta {row['id']}")
                            wp.locator("#publish, button.editor-post-publish-button").first.click(timeout=5000)
                            time.sleep(2)
                            break
                    except Exception as e:
                        log(f"meta fill fail {row['id']}: {e}")

        log("Done — check fixes/auto-*.png screenshots")
        time.sleep(5)
        context.close()


if __name__ == "__main__":
    kill_chrome()
    try:
        main()
    except Exception as e:
        log(f"FATAL: {e}")
        raise
