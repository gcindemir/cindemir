#!/usr/bin/env python3
"""Fix corrupt mu-plugin via Bluehost File Manager."""
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
ZIP = ROOT / "fixes/deploy-package/mu-plugins.zip"
PHP = ROOT / "fixes/mu-plugins/cindemir-seo-fixes.php"
PROFILE = Path.home() / ".config/google-chrome"
DISPLAY = ":1"
URLS = [
    "https://my.bluehost.com/hosting/app/cindemirlaw.com/cpanel/filemanager",
    "https://www.bluehost.com/my-account/hosting/details/sites/13219921/files",
]


def log(msg):
    print(msg, flush=True)


def click_if(page, selectors):
    for sel in selectors:
        loc = page.locator(sel)
        if loc.count():
            try:
                loc.first.click(timeout=4000)
                log(f"clicked {sel}")
                return True
            except Exception as e:
                log(f"fail {sel}: {e}")
    return False


def open_folder(page, name):
    for sel in [f"text={name}", f"td:has-text('{name}')", f"a:has-text('{name}')"]:
        loc = page.locator(sel)
        if loc.count():
            for i in range(min(loc.count(), 4)):
                try:
                    loc.nth(i).dblclick(timeout=4000)
                    time.sleep(2)
                    log(f"opened {name}")
                    return True
                except Exception:
                    try:
                        loc.nth(i).click(timeout=3000)
                        time.sleep(2)
                        log(f"clicked {name}")
                        return True
                    except Exception:
                        pass
    return False


def upload_to_page(page, path: Path) -> bool:
    click_if(page, ["text=Upload", "button:has-text('Upload')", "#uploadbtn", "a:has-text('Upload')"])
    time.sleep(2)
    for frame in [page, *page.frames]:
        inputs = frame.locator('input[type="file"]')
        if inputs.count():
            for i in range(inputs.count()):
                try:
                    inputs.nth(i).set_input_files(str(path))
                    log(f"uploaded {path.name}")
                    time.sleep(12)
                    return True
                except Exception as e:
                    log(f"upload err {i}: {e}")
    return False


def main():
    pkill = os.system("pkill -f 'google-chrome-stable' 2>/dev/null")
    time.sleep(2)

    tmp = Path(tempfile.mkdtemp(prefix="bhfix-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            if s.is_dir():
                shutil.copytree(s, d, dirs_exist_ok=True)
            else:
                shutil.copy2(s, d)

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            user_data_dir=str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": DISPLAY},
            viewport={"width": 1500, "height": 950},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()

        for url in URLS:
            try:
                page.goto(url, wait_until="domcontentloaded", timeout=120000)
            except Exception as e:
                log(f"goto fail {url}: {e}")
                continue
            for _ in range(30):
                time.sleep(2)
                if "login" not in page.url.lower() and "moment" not in page.title().lower():
                    break
            log(f"at {page.url} | {page.title()}")
            page.screenshot(path=str(ROOT / "fixes/fix-0.png"), full_page=True)
            if "login" in page.url.lower():
                continue

            # public_html/wp-content/mu-plugins
            for folder in ["public_html", "wp-content", "mu-plugins"]:
                open_folder(page, folder)
                page.screenshot(path=str(ROOT / f"fixes/fix-{folder}.png"), full_page=True)

            # delete corrupt file
            for sel in ["text=cindemir-seo-fixes.php", "td:has-text('cindemir-seo-fixes.php')"]:
                loc = page.locator(sel)
                if loc.count():
                    loc.first.click(timeout=3000)
                    log("selected corrupt file")
                    break
            click_if(page, ["text=Delete", "button:has-text('Delete')", "#deletebtn"])
            time.sleep(2)
            click_if(page, ["text=Confirm", "button:has-text('Confirm')", "text=Yes"])
            time.sleep(3)

            ok = upload_to_page(page, PHP)
            if not ok:
                ok = upload_to_page(page, ZIP)
                if ok:
                    for sel in ["text=mu-plugins.zip", "td:has-text('mu-plugins.zip')"]:
                        loc = page.locator(sel)
                        if loc.count():
                            loc.first.click(timeout=3000)
                            break
                    click_if(page, ["text=Extract", "button:has-text('Extract')"])
                    time.sleep(8)

            page.screenshot(path=str(ROOT / "fixes/fix-final.png"), full_page=True)
            log(f"DONE uploaded={ok}")
            break

        time.sleep(2)
        ctx.close()

    shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    main()
