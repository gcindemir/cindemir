#!/usr/bin/env python3
"""Emergency restore via direct cPanel :2083 (bypass Bluehost my-account Cloudflare)."""
import os
import shutil
import sys
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
DISPLAY = os.environ.get("DISPLAY", ":1")
SAFE = Path("/tmp/cindemir-seo-fixes-v172-safe.php")
USER = "cindemir"
PASS = os.environ.get("CINDEMIR_SSH_PASS", "")
if not PASS and Path("/tmp/pw_cindemirlaw_com.txt").exists():
    PASS = Path("/tmp/pw_cindemirlaw_com.txt").read_text().strip()

DISABLE = [
    "cindemir-seo-fixes.php",
    "cindemir-contact-fixes.php",
    "cindemir-expose-yoast-meta.php",
    "cindemir-purge-cache.php",
]


def log(msg):
    print(msg, flush=True)


def click_any(page, selectors):
    for frame in [page, *page.frames]:
        for sel in selectors:
            loc = frame.locator(sel)
            if loc.count():
                try:
                    loc.first.click(timeout=5000)
                    page.wait_for_timeout(1200)
                    return True
                except Exception:
                    pass
    return False


def open_folder(page, name):
    for frame in [page, *page.frames]:
        for sel in [f"text={name}", f"td:has-text('{name}')"]:
            loc = frame.locator(sel)
            if loc.count():
                try:
                    loc.first.dblclick(timeout=5000)
                    page.wait_for_timeout(2000)
                    log(f"opened {name}")
                    return True
                except Exception:
                    pass
    return False


def select_file(page, fname):
    for frame in [page, *page.frames]:
        loc = frame.locator(f"text={fname}")
        if loc.count():
            try:
                loc.first.click(timeout=4000)
                page.wait_for_timeout(800)
                return True
            except Exception:
                pass
    return False


def rename_to_off(page, fname):
    if not select_file(page, fname):
        return False
    click_any(page, ["text=Rename", "#renamebtn"])
    for frame in [page, *page.frames]:
        ins = frame.locator('input[type="text"]')
        if ins.count():
            ins.first.fill(fname + ".off")
            break
    click_any(page, ["text=Rename File", "button:has-text('Rename File')", "text=Save"])
    page.wait_for_timeout(2000)
    log(f"disabled {fname}")
    return True


def upload(page, path):
    click_any(page, ["text=Upload", "#uploadbtn"])
    page.wait_for_timeout(1500)
    for frame in [page, *page.frames]:
        ins = frame.locator('input[type="file"]')
        if ins.count():
            ins.first.set_input_files(str(path))
            page.wait_for_timeout(12000)
            log(f"uploaded {path.name}")
            return True
    return False


def login_cpanel(page):
    page.goto("https://cindemirlaw.com:2083/", timeout=120000)
    page.wait_for_timeout(4000)
    log(f"cpanel url={page.url} title={page.title()}")
    page.screenshot(path=str(ROOT / "fixes/cp-0.png"), full_page=True)

    for sel in ['input[name="user"]', '#user']:
        if page.locator(sel).count():
            page.locator(sel).first.fill(USER)
            break
    for sel in ['input[name="pass"]', '#password', 'input[type="password"]']:
        if page.locator(sel).count():
            page.locator(sel).first.fill(PASS)
            break
    click_any(page, ['#login_submit', 'button:has-text("Log in")', 'input[type="submit"]'])
    page.wait_for_timeout(8000)
    log(f"after login url={page.url} title={page.title()}")
    page.screenshot(path=str(ROOT / "fixes/cp-1.png"), full_page=True)
    return "login" not in page.url.lower() and "cpanel" in page.content().lower()


def open_file_manager(page):
    # cPanel home: click File Manager
    for sel in ["text=File Manager", "a:has-text('File Manager')", "#icon-file_manager"]:
        if page.locator(sel).count():
            with page.expect_popup(timeout=15000) as pinfo:
                page.locator(sel).first.click()
            fm = pinfo.value
            fm.wait_for_timeout(8000)
            log(f"fm url={fm.url}")
            fm.screenshot(path=str(ROOT / "fixes/cp-fm.png"), full_page=True)
            return fm
    # direct jupiter filemanager if already logged in
    for url in page.frames[0].url, page.url:
        if "filemanager" in url:
            return page
    return None


def main():
    if not SAFE.exists():
        log("missing safe file")
        return 1
    if not PASS:
        log("no password")
        return 1

    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)

    with sync_playwright() as p:
        browser = p.chromium.launch(
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": DISPLAY},
        )
        ctx = browser.new_context(viewport={"width": 1600, "height": 1000})
        page = ctx.new_page()

        if not login_cpanel(page):
            log("cpanel login failed")
            ctx.close()
            browser.close()
            return 2

        fm = open_file_manager(page)
        if not fm:
            log("file manager not opened")
            ctx.close()
            browser.close()
            return 3

        for folder in ["public_html", "wp-content", "mu-plugins"]:
            open_folder(fm, folder)

        for fname in DISABLE:
            rename_to_off(fm, fname)

        upload(fm, SAFE)
        fm.screenshot(path=str(ROOT / "fixes/cp-done.png"), full_page=True)

        page2 = ctx.new_page()
        page2.goto("https://cindemirlaw.com/wp-admin/", timeout=120000)
        page2.wait_for_timeout(5000)
        ok = "critical error" not in page2.content().lower()
        log(f"admin_ok={ok}")
        page2.screenshot(path=str(ROOT / "fixes/cp-admin.png"), full_page=True)

        ctx.close()
        browser.close()
        return 0 if ok else 4


if __name__ == "__main__":
    sys.exit(main())
