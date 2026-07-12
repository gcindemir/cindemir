#!/usr/bin/env python3
"""Upload mobile-header branding via Bluehost File Manager."""
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
FILE = ROOT / "fixes/mu-plugins/cindemir-expose-yoast-meta.php"
PROFILE = Path.home() / ".config/google-chrome"
DISPLAY = ":1"
FM = "https://my.bluehost.com/hosting/app/cindemirlaw.com/cpanel/filemanager"
LOG = ROOT / "fixes/deploy-mobile-header.log"


def log(msg: str) -> None:
    line = f"{msg}\n"
    print(msg, flush=True)
    with LOG.open("a") as fh:
        fh.write(line)


def open_folder(page, name: str) -> bool:
    for sel in [f"text={name}", f"td:has-text('{name}')", f"a:has-text('{name}')"]:
        loc = page.locator(sel)
        if not loc.count():
            continue
        for i in range(min(loc.count(), 4)):
            try:
                loc.nth(i).dblclick(timeout=5000)
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


def upload_file(page, path: Path) -> bool:
    for sel in ["text=Upload", "button:has-text('Upload')", "#uploadbtn", "a:has-text('Upload')"]:
        loc = page.locator(sel)
        if loc.count():
            try:
                loc.first.click(timeout=5000)
                time.sleep(2)
                log("clicked Upload")
                break
            except Exception:
                pass

    for frame in [page, *page.frames]:
        inputs = frame.locator('input[type="file"]')
        if not inputs.count():
            continue
        for i in range(inputs.count()):
            try:
                inputs.nth(i).set_input_files(str(path))
                log(f"uploaded {path.name}")
                time.sleep(15)
                return True
            except Exception as exc:
                log(f"upload err {i}: {exc}")
    return False


def main() -> int:
    LOG.write_text("")
    log(f"start size={FILE.stat().st_size}")

    os.system("pkill -f 'google-chrome-stable' 2>/dev/null")
    time.sleep(2)

    tmp = Path(tempfile.mkdtemp(prefix="bh-mobile-"))
    for item in ["Default", "Local State"]:
        src = PROFILE / item
        if not src.exists():
            continue
        dst = tmp / item
        if src.is_dir():
            shutil.copytree(src, dst, dirs_exist_ok=True)
        else:
            shutil.copy2(src, dst)

    uploaded = False
    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": DISPLAY},
            viewport={"width": 1500, "height": 950},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        page.goto(FM, wait_until="domcontentloaded", timeout=120000)

        for i in range(40):
            time.sleep(3)
            log(f"[{i * 3}s] {page.url[:100]} | {page.title()[:60]}")
            if "filemanager" in page.url.lower() or "cpanel" in page.url.lower():
                break
            if "login" in page.url.lower() and i > 3:
                for sel in ["button:has-text('Login')", "button[type='submit']", "text=Login"]:
                    loc = page.locator(sel)
                    if loc.count():
                        try:
                            loc.first.click(timeout=5000)
                            log("clicked login")
                        except Exception as exc:
                            log(f"login click fail: {exc}")
                        break

        page.screenshot(path=str(ROOT / "fixes/deploy-mobile-0.png"), full_page=True)

        for folder in ["public_html", "wp-content", "mu-plugins"]:
            open_folder(page, folder)

        page.screenshot(path=str(ROOT / "fixes/deploy-mobile-1.png"), full_page=True)
        uploaded = upload_file(page, FILE)
        page.screenshot(path=str(ROOT / "fixes/deploy-mobile-2.png"), full_page=True)
        ctx.close()

    shutil.rmtree(tmp, ignore_errors=True)
    log(f"RESULT uploaded={uploaded}")
    return 0 if uploaded else 2


if __name__ == "__main__":
    raise SystemExit(main())
