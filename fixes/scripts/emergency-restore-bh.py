#!/usr/bin/env python3
"""Emergency restore: Bluehost cPanel File Manager — disable broken mu-plugins, upload safe v1.7.2."""
import os
import shutil
import sys
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
DISPLAY = os.environ.get("DISPLAY", ":1")
SAFE = Path("/tmp/cindemir-seo-fixes-v172-safe.php")
REMOTE = ROOT / "fixes/mu-plugins/cindemir-remote-deploy.php"
FM = "https://my.bluehost.com/hosting/app/cindemirlaw.com/cpanel/filemanager"
DISABLE = [
    "cindemir-seo-fixes.php",
    "cindemir-contact-fixes.php",
    "cindemir-expose-yoast-meta.php",
    "cindemir-purge-cache.php",
    "cindemir-force-upgrade.php",
    "cindemir-remote-deploy.php",
]


def log(msg: str) -> None:
    print(msg, flush=True)


def click_any(page, selectors, timeout=5000):
    for frame in [page, *page.frames]:
        for sel in selectors:
            loc = frame.locator(sel)
            if not loc.count():
                continue
            try:
                loc.first.click(timeout=timeout)
                page.wait_for_timeout(1200)
                log(f"click {sel}")
                return True
            except Exception:
                pass
    return False


def open_folder(page, name: str) -> bool:
    for frame in [page, *page.frames]:
        for sel in [
            f"text={name}",
            f"td:has-text('{name}')",
            f"a:has-text('{name}')",
            f"span:has-text('{name}')",
        ]:
            loc = frame.locator(sel)
            if not loc.count():
                continue
            for i in range(min(loc.count(), 4)):
                try:
                    loc.nth(i).dblclick(timeout=5000)
                    page.wait_for_timeout(2000)
                    log(f"opened {name}")
                    return True
                except Exception:
                    try:
                        loc.nth(i).click(timeout=3000)
                        page.wait_for_timeout(1500)
                        log(f"clicked {name}")
                        return True
                    except Exception:
                        pass
    return False


def select_file(page, fname: str) -> bool:
    for frame in [page, *page.frames]:
        for sel in [f"text={fname}", f"td:has-text('{fname}')"]:
            loc = frame.locator(sel)
            if loc.count():
                try:
                    loc.first.click(timeout=4000)
                    page.wait_for_timeout(800)
                    log(f"selected {fname}")
                    return True
                except Exception:
                    pass
    return False


def rename_file(page, old: str, new: str) -> bool:
    if not select_file(page, old):
        return False
    if not click_any(page, ["text=Rename", "button:has-text('Rename')", "#renamebtn"]):
        return False
    for frame in [page, *page.frames]:
        for sel in ['input[type="text"]', "input.rename", "#newname"]:
            loc = frame.locator(sel)
            if loc.count():
                try:
                    loc.first.fill(new)
                    page.wait_for_timeout(500)
                    break
                except Exception:
                    pass
    click_any(page, ["text=Rename File", "button:has-text('Rename File')", "text=Save", "button:has-text('OK')"])
    page.wait_for_timeout(2000)
    log(f"renamed {old} -> {new}")
    return True


def delete_file(page, fname: str) -> bool:
    if not select_file(page, fname):
        return False
    if not click_any(page, ["text=Delete", "button:has-text('Delete')", "#deletebtn"]):
        return False
    click_any(page, ["text=Confirm", "button:has-text('Confirm')", "text=Yes", "button:has-text('Yes')"])
    page.wait_for_timeout(2500)
    log(f"deleted {fname}")
    return True


def upload_file(page, path: Path) -> bool:
    click_any(page, ["text=Upload", "button:has-text('Upload')", "#uploadbtn", "[title='Upload']"])
    page.wait_for_timeout(1500)
    for frame in [page, *page.frames]:
        ins = frame.locator('input[type="file"]')
        if not ins.count():
            continue
        try:
            ins.first.set_input_files(str(path))
            page.wait_for_timeout(12000)
            log(f"uploaded {path.name} ({path.stat().st_size} bytes)")
            return True
        except Exception as e:
            log(f"upload err {path.name}: {e}")
    return False


def nav_mu(page) -> None:
    for folder in ["public_html", "wp-content", "mu-plugins"]:
        open_folder(page, folder)
        page.screenshot(path=str(ROOT / f"fixes/em-{folder}.png"), full_page=True)


def check_admin(page) -> bool:
    page.goto("https://cindemirlaw.com/wp-admin/", timeout=120000)
    page.wait_for_timeout(5000)
    body = page.content().lower()
    ok = "critical error" not in body and ("dashboard" in body or "wp-admin" in page.url)
    log(f"admin_ok={ok} url={page.url}")
    return ok


def main() -> int:
    if not SAFE.exists():
        log(f"missing {SAFE}")
        return 1

    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)

    tmp = Path(tempfile.mkdtemp(prefix="emrestore-"))
    for item in ["Default", "Local State"]:
        src = PROFILE / item
        if not src.exists():
            continue
        dst = tmp / item
        if src.is_dir():
            shutil.copytree(src, dst, dirs_exist_ok=True)
        else:
            shutil.copy2(src, dst)

    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp),
                headless=False,
                channel="chrome",
                args=["--no-sandbox", "--disable-dev-shm-usage"],
                env={**os.environ, "DISPLAY": DISPLAY},
                viewport={"width": 1600, "height": 1000},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()

            page.goto(FM, wait_until="domcontentloaded", timeout=120000)
            for i in range(45):
                page.wait_for_timeout(3000)
                log(f"[{i * 3}s] {page.url[:90]} | {(page.title() or '')[:50]}")
                if "login" in page.url.lower():
                    break
                if "filemanager" in page.url.lower() or "file manager" in (page.title() or "").lower():
                    break
                if "dashboard" in page.url.lower() and i > 3:
                    page.goto(FM, wait_until="domcontentloaded", timeout=90000)

            page.screenshot(path=str(ROOT / "fixes/em-0-start.png"), full_page=True)
            if "login" in page.url.lower():
                log("ERROR: Bluehost login required")
                return 2

            nav_mu(page)

            # Disable all cindemir plugins (rename to .off)
            for fname in DISABLE:
                off = fname + ".off"
                if select_file(page, fname):
                    rename_file(page, fname, off)
                elif select_file(page, off):
                    log(f"already off: {off}")

            # Also disable any .bak that might be loaded (shouldn't, but clean)
            for extra in ["cindemir-seo-fixes.php.bak-broken", "cindemir-seo-fixes.php.bak-b"]:
                if select_file(page, extra):
                    rename_file(page, extra, extra + ".off")

            page.screenshot(path=str(ROOT / "fixes/em-1-disabled.png"), full_page=True)

            # Upload known-good v1.7.2
            nav_mu(page)
            if not upload_file(page, SAFE):
                log("ERROR: safe upload failed")
                page.screenshot(path=str(ROOT / "fixes/em-fail-upload.png"), full_page=True)
                return 3

            page.screenshot(path=str(ROOT / "fixes/em-2-uploaded.png"), full_page=True)

            ok = check_admin(page)
            page.screenshot(path=str(ROOT / "fixes/em-3-admin.png"), full_page=True)

            if ok and REMOTE.exists():
                log("admin restored — uploading remote-deploy for full GitHub pull")
                nav_mu(page)
                upload_file(page, REMOTE)
                page.goto("https://cindemirlaw.com/", timeout=120000)
                page.wait_for_timeout(15000)
                page.goto("https://cindemirlaw.com/wp-admin/", timeout=120000)
                page.wait_for_timeout(5000)
                page.screenshot(path=str(ROOT / "fixes/em-4-after-remote.png"), full_page=True)

            ctx.close()
            return 0 if ok else 4
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
