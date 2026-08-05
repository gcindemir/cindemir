#!/usr/bin/env python3
"""Emergency: disable broken mu-plugins via Bluehost/WP file manager, upload safe v1.7.2."""
import os
import re
import shutil
import sys
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome")
SAFE = Path("/tmp/cindemir-seo-fixes-v172-safe.php")
CONTACT_SAFE = ROOT / "fixes/mu-plugins/cindemir-contact-fixes.php"

# copy safe contact from git v1.7 era - use repo contact but might be 1.2.1
# For emergency use seo-fixes v1.7.2 only first


def click(page, labels):
    for frame in [page, *page.frames]:
        for label in labels:
            loc = frame.locator(label)
            if loc.count():
                try:
                    loc.first.click(timeout=5000)
                    page.wait_for_timeout(1500)
                    print("click", label, flush=True)
                    return True
                except Exception:
                    pass
    return False


def open_fm(page):
    urls = [
        "https://cindemirlaw.com/wp-admin/admin.php?page=wp_file_manager",
        "https://my.bluehost.com/hosting/app/cindemirlaw.com/cpanel/filemanager",
    ]
    for url in urls:
        try:
            page.goto(url, timeout=120000)
            page.wait_for_timeout(8000)
            print("open", page.url[:80], page.title()[:50], flush=True)
            if "critical error" not in page.content().lower() and "login" not in page.url.lower():
                return True
            if "file manager" in page.title().lower() or "wp file manager" in page.title().lower():
                return True
        except Exception as e:
            print("nav err", e, flush=True)
    return False


def nav_mu(page):
    for name in ["public_html", "wp-content", "mu-plugins"]:
        for frame in [page, *page.frames]:
            loc = frame.locator(f"text={name}")
            if loc.count():
                try:
                    loc.first.dblclick(timeout=5000)
                    page.wait_for_timeout(2000)
                    print("folder", name, flush=True)
                except Exception:
                    try:
                        loc.first.click(timeout=2000)
                        page.wait_for_timeout(1000)
                    except Exception:
                        pass


def main():
    if not SAFE.exists():
        print("missing safe file", flush=True)
        return 1

    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="emfix-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if not s.exists():
            continue
        d = tmp / item
        if s.is_dir():
            shutil.copytree(s, d, dirs_exist_ok=True)
        else:
            shutil.copy2(s, d)

    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp),
                headless=False,
                channel="chrome",
                args=["--no-sandbox", "--disable-dev-shm-usage"],
                env={**os.environ, "DISPLAY": os.environ.get("DISPLAY", ":1")},
                viewport={"width": 1500, "height": 950},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()

            # wp-admin first
            page.goto("https://cindemirlaw.com/wp-admin/", timeout=120000)
            page.wait_for_timeout(4000)
            print("admin:", page.url, flush=True)

            if not open_fm(page):
                page.screenshot(path=str(ROOT / "fixes/emergency-fail.png"), full_page=True)
                print("ERROR: cannot open file manager", flush=True)
                return 2

            page.screenshot(path=str(ROOT / "fixes/emergency-1.png"), full_page=True)
            nav_mu(page)

            # disable broken seo-fixes by renaming via remove+upload safe
            for fname in [
                "cindemir-seo-fixes.php",
                "cindemir-contact-fixes.php",
            ]:
                for frame in [page, *page.frames]:
                    loc = frame.locator(f"text={fname}")
                    if loc.count():
                        try:
                            loc.first.click(timeout=3000)
                            page.wait_for_timeout(800)
                        except Exception:
                            pass
                click(page, ["text=Remove", "[title='Remove']"])
                click(page, ["button:has-text('YES')", "text=YES"])

            page.wait_for_timeout(2000)
            nav_mu(page)

            # upload safe seo-fixes v1.7.2
            click(page, ["text=Upload", "[title='Upload files']"])
            for frame in [page, *page.frames]:
                ins = frame.locator('input[type="file"]')
                if ins.count():
                    ins.first.set_input_files(str(SAFE))
                    page.wait_for_timeout(2000)
                    click(page, ["button:has-text('YES')", "text=YES"])
                    page.wait_for_timeout(10000)
                    print("uploaded safe seo-fixes v1.7.2", flush=True)
                    break

            page.screenshot(path=str(ROOT / "fixes/emergency-2.png"), full_page=True)

            page.goto("https://cindemirlaw.com/wp-admin/", timeout=120000)
            page.wait_for_timeout(4000)
            body = page.content().lower()
            ok = "critical error" not in body and ("dashboard" in body or "wp-admin" in page.url)
            print("admin_ok:", ok, flush=True)
            page.screenshot(path=str(ROOT / "fixes/emergency-3.png"), full_page=True)
            ctx.close()
            return 0 if ok else 3
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
