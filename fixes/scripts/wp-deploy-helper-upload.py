#!/usr/bin/env python3
"""Upload cindemir-deploy-helper.zip via WordPress plugin installer."""
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
ZIP = ROOT / "fixes/deploy-package/cindemir-deploy-helper.zip"
PROFILE = Path.home() / ".config/google-chrome"
DISPLAY = ":1"
LOG = ROOT / "fixes/wp-deploy.log"


def log(msg: str) -> None:
    line = f"{msg}\n"
    print(msg, flush=True)
    with LOG.open("a") as fh:
        fh.write(line)


def main() -> int:
    LOG.write_text("")
    log(f"zip={ZIP.stat().st_size} bytes")

    os.system("pkill -f 'google-chrome-stable' 2>/dev/null")
    time.sleep(2)

    tmp = Path(tempfile.mkdtemp(prefix="wpdep-"))
    for item in ["Default", "Local State"]:
        src = PROFILE / item
        if not src.exists():
            continue
        dst = tmp / item
        if src.is_dir():
            shutil.copytree(src, dst, dirs_exist_ok=True)
        else:
            shutil.copy2(src, dst)

    uploaded = activated = False
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

        page.goto("https://cindemirlaw.com/wp-admin/", wait_until="domcontentloaded", timeout=120000)
        for i in range(30):
            time.sleep(2)
            log(f"[{i*2}s] {page.url[:90]} | {page.title()[:60]}")
            if "wp-admin" in page.url and "login" not in page.url:
                break

        page.screenshot(path=str(ROOT / "fixes/wpdep-0.png"), full_page=True)

        if "login" in page.url.lower():
            log("NOT LOGGED IN")
            ctx.close()
            shutil.rmtree(tmp, ignore_errors=True)
            return 2

        page.goto(
            "https://cindemirlaw.com/wp-admin/plugin-install.php?tab=upload",
            wait_until="domcontentloaded",
            timeout=90000,
        )
        time.sleep(3)
        page.screenshot(path=str(ROOT / "fixes/wpdep-1.png"), full_page=True)
        log(f"upload page: {page.url}")

        for frame in [page, *page.frames]:
            ins = frame.locator('input[type="file"]#pluginzip, input[name="pluginzip"], input[type="file"]')
            if ins.count():
                try:
                    ins.first.set_input_files(str(ZIP))
                    uploaded = True
                    log("file selected")
                    break
                except Exception as exc:
                    log(f"set_input_files err: {exc}")

        if uploaded:
            for sel in ["#install-plugin-submit", "input[type='submit'][value*='Install']", "button:has-text('Install')"]:
                loc = page.locator(sel)
                if loc.count():
                    try:
                        loc.first.click(timeout=8000)
                        log(f"clicked {sel}")
                        time.sleep(20)
                        break
                    except Exception as exc:
                        log(f"click err {sel}: {exc}")

        page.screenshot(path=str(ROOT / "fixes/wpdep-2.png"), full_page=True)

        for sel in ["a:has-text('Activate Plugin')", "a.activate-now", "text=Activate Plugin"]:
            loc = page.locator(sel)
            if loc.count():
                try:
                    loc.first.click(timeout=8000)
                    activated = True
                    log("activated")
                    time.sleep(15)
                    break
                except Exception as exc:
                    log(f"activate err: {exc}")

        page.goto("https://cindemirlaw.com/wp-admin/plugins.php", wait_until="domcontentloaded", timeout=60000)
        time.sleep(3)
        page.screenshot(path=str(ROOT / "fixes/wpdep-3.png"), full_page=True)
        log(f"plugins: {page.content()[:500]}")

        ctx.close()

    shutil.rmtree(tmp, ignore_errors=True)
    log(f"RESULT uploaded={uploaded} activated={activated}")
    return 0 if uploaded and activated else 3


if __name__ == "__main__":
    raise SystemExit(main())
