#!/usr/bin/env python3
"""
Autonomous Ahrefs deploy via WordPress admin only.
- Regular plugin zip (deactivatable if broken)
- No mu-plugins, no force-upgrade
"""
import os
import re
import shutil
import subprocess
import sys
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
ZIP = ROOT / "fixes/plugins/cindemir-seo-pack.zip"
SITE = "https://cindemirlaw.com"


def log(m):
    print(m, flush=True)


def curl_code(path="/"):
    r = subprocess.run(
        ["curl", "-sS", "-o", "/dev/null", "-w", "%{http_code}", f"{SITE}{path}"],
        capture_output=True,
        text=True,
        timeout=30,
    )
    return r.stdout.strip()


def site_ok():
    return curl_code("/") == "200" and curl_code("/wp-login.php") in ("200", "302")


def login(page) -> bool:
    page.goto(f"{SITE}/wp-admin/", timeout=120000)
    page.wait_for_timeout(4000)
    if "login" not in page.url.lower():
        return True
    page.goto(f"{SITE}/wp-login.php", timeout=120000)
    page.wait_for_timeout(2000)
    try:
        page.click("#wp-submit", timeout=8000)
        page.wait_for_timeout(8000)
    except Exception:
        pass
    return "login" not in page.url.lower()


def upload_plugin_zip(page) -> bool:
    page.goto(f"{SITE}/wp-admin/plugin-install.php?tab=upload", timeout=120000)
    page.wait_for_timeout(4000)
    if not ZIP.exists():
        log("zip missing")
        return False
    page.set_input_files("#pluginzip", str(ZIP))
    page.wait_for_timeout(2000)
    page.click("#install-plugin-submit")
    page.wait_for_timeout(15000)
    body = page.content().lower()
    if "plugin installed successfully" in body or "activate plugin" in body:
        log("plugin installed")
        for sel in ["a:has-text('Activate Plugin')", ".button.activate-now"]:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=8000)
                page.wait_for_timeout(8000)
                log("activated")
                return True
        return True
    if "destination folder already exists" in body or "already exists" in body:
        log("already installed — go activate")
        page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
        page.wait_for_timeout(4000)
        for sel in ["tr[data-plugin*='cindemir-seo-pack'] .activate a", "a:has-text('Activate')"]:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=5000)
                page.wait_for_timeout(6000)
                return True
    page.screenshot(path=str(ROOT / "fixes/plugin-install-fail.png"), full_page=True)
    log("install failed")
    return False


def run_ahrefs_fix(page):
    page.goto(f"{SITE}/wp-admin/tools.php?page=cindemir-seo-pack", timeout=120000)
    page.wait_for_timeout(4000)
    if page.locator('input[name="cindemir_run_ahrefs"]').count():
        page.click('input[name="cindemir_run_ahrefs"]')
        page.wait_for_timeout(10000)
        log("ran admin fix button")
    page.goto(f"{SITE}/wp-json/cindemir/v1/fix-ahrefs?key=seo-pack-2026", timeout=90000)
    page.wait_for_timeout(3000)
    log("fix-ahrefs: " + page.locator("body").inner_text()[:400])


def purge_cache(page):
    page.goto(f"{SITE}/wp-admin/admin.php?page=wprocket", timeout=60000)
    page.wait_for_timeout(3000)
    for sel in ["text=Clear cache", "button:has-text('Clear cache')"]:
        loc = page.locator(sel)
        if loc.count():
            try:
                loc.first.click(timeout=5000)
                page.wait_for_timeout(5000)
                log("cache cleared")
                return
            except Exception:
                pass


def verify():
    r = subprocess.run(
        ["curl", "-sS", "-H", "Cache-Control: no-cache", f"{SITE}/?t={int(time.time())}"],
        capture_output=True,
        text=True,
        timeout=30,
    )
    html = r.stdout
    m = re.search(r"cindemir-seo-fixes ([0-9.]+)", html)
    log(f"version={m.group(1) if m else 'none'} barobirlik={'d.barobirlik' in html} home={curl_code('/')}")


def try_delete_muplugins_via_fm(page):
    """If site broken, try WP File Manager to remove mu-plugins cindemir files."""
    page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
    page.wait_for_timeout(6000)
    if "critical error" in page.content().lower() or "login" in page.url.lower():
        return False
    for name in ["wp-content", "mu-plugins"]:
        for frame in [page, *page.frames]:
            loc = frame.locator(f"text={name}")
            if loc.count():
                try:
                    loc.first.dblclick(timeout=5000)
                    page.wait_for_timeout(2000)
                except Exception:
                    pass
    deleted = 0
    for _ in range(20):
        hit = False
        for frame in [page, *page.frames]:
            loc = frame.locator("text=cindemir")
            if not loc.count():
                continue
            try:
                loc.first.click(timeout=2000)
                for sel in ["[title='Remove']", "text=Remove"]:
                    btn = frame.locator(sel)
                    if btn.count():
                        btn.first.click(force=True, timeout=3000)
                        page.wait_for_timeout(800)
                        for yes in ["button:has-text('YES')", "text=YES"]:
                            y = frame.locator(yes)
                            if y.count():
                                y.first.click(timeout=3000)
                                page.wait_for_timeout(2000)
                                deleted += 1
                                hit = True
                                break
                        break
            except Exception:
                pass
        if not hit:
            break
    log(f"deleted mu-plugins files: {deleted}")
    return deleted > 0


def main():
    if not site_ok():
        log("site down — attempting mu-plugins cleanup via WP if possible")
        if curl_code("/wp-login.php") == "500":
            log("total lockout — cannot access WP; need hosting file access")
            return 10

    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="wpauto-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            if s.is_dir():
                shutil.copytree(s, d, dirs_exist_ok=True)
            else:
                shutil.copy2(s, d)

    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp), headless=False, channel="chrome",
                args=["--no-sandbox"], env={**os.environ, "DISPLAY": ":1"},
                viewport={"width": 1500, "height": 950},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()

            if not login(page):
                log("login failed")
                return 2

            if not site_ok():
                try_delete_muplugins_via_fm(page)
                time.sleep(3)
                if not site_ok():
                    log("still broken after cleanup")
                    return 3

            if not upload_plugin_zip(page):
                return 4

            if curl_code("/") == "500":
                log("broke after activate — deactivating")
                page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
                page.wait_for_timeout(4000)
                loc = page.locator("tr[data-plugin*='cindemir-seo-pack'] .deactivate a")
                if loc.count():
                    loc.first.click(timeout=5000)
                return 5

            run_ahrefs_fix(page)
            purge_cache(page)
            verify()
            ctx.close()
            return 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
