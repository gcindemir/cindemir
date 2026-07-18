#!/usr/bin/env python3
"""Rename mu-plugins folder via Bluehost File Manager — fastest wp-admin fix."""
import os, shutil, sys, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
DISPLAY = ":1"
FM = "https://my.bluehost.com/hosting/app/cindemirlaw.com/cpanel/filemanager"


def log(m):
    print(m, flush=True)


def click(page, sels):
    for frame in [page, *page.frames]:
        for s in sels:
            loc = frame.locator(s)
            if loc.count():
                try:
                    loc.first.click(timeout=5000)
                    page.wait_for_timeout(1500)
                    return True
                except Exception:
                    pass
    return False


def dblclick_folder(page, name):
    for frame in [page, *page.frames]:
        for s in [f"text={name}", f"td:has-text('{name}')"]:
            loc = frame.locator(s)
            if loc.count():
                try:
                    loc.first.dblclick(timeout=5000)
                    page.wait_for_timeout(2500)
                    log(f"opened {name}")
                    return True
                except Exception:
                    pass
    return False


def main():
    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="bhren-"))
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
                str(tmp), headless=False, channel="chrome",
                args=["--no-sandbox", "--disable-dev-shm-usage"],
                env={**os.environ, "DISPLAY": DISPLAY},
                viewport={"width": 1600, "height": 1000},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            page.goto(FM, timeout=180000)
            for i in range(60):
                page.wait_for_timeout(3000)
                title = (page.title() or "")[:60]
                url = page.url[:90]
                log(f"[{i*3}s] {url} | {title}")
                if "just a moment" in title.lower() or "cloudflare" in page.content().lower()[:2000]:
                    log("WAIT cloudflare — complete captcha in VNC if visible")
                if "login" in url.lower():
                    if i > 40:
                        page.screenshot(path=str(ROOT / "fixes/bh-login-needed.png"), full_page=True)
                        log("NEED LOGIN")
                        return 2
                    continue
                if "filemanager" in url.lower() or "file manager" in title.lower():
                    break
            page.screenshot(path=str(ROOT / "fixes/bh-fm-0.png"), full_page=True)

            for folder in ["public_html", "wp-content"]:
                dblclick_folder(page, folder)

            # select mu-plugins
            for frame in [page, *page.frames]:
                loc = frame.locator("text=mu-plugins")
                if loc.count():
                    loc.first.click(timeout=4000)
                    page.wait_for_timeout(1000)
                    break

            renamed = False
            if click(page, ["text=Rename", "#renamebtn", "button:has-text('Rename')"]):
                for frame in [page, *page.frames]:
                    ins = frame.locator('input[type="text"]')
                    if ins.count():
                        ins.first.fill("mu-plugins-OFF")
                        break
                click(page, ["text=Rename File", "button:has-text('Rename File')", "text=Save"])
                page.wait_for_timeout(3000)
                renamed = True
                log("RENAMED mu-plugins -> mu-plugins-OFF")

            page.screenshot(path=str(ROOT / "fixes/bh-fm-done.png"), full_page=True)

            page2 = ctx.new_page()
            page2.goto("https://cindemirlaw.com/wp-admin/", timeout=120000)
            page2.wait_for_timeout(6000)
            ok = "critical error" not in page2.content().lower()
            log(f"wp-admin ok={ok}")
            page2.screenshot(path=str(ROOT / "fixes/bh-wpadmin.png"), full_page=True)
            ctx.close()
            return 0 if ok else 3
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
