#!/usr/bin/env python3
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
ZIP = ROOT / "fixes/deploy-package/cindemir-llms-txt.zip"
BASE = "https://cindemirlaw.com"
LOG = ROOT / "fixes/llms-classic-deploy.log"


def log(m):
    print(m, flush=True)
    with LOG.open("a") as f:
        f.write(m + "\n")


def main():
    LOG.write_text("")
    os.system("pkill -9 -f google-chrome 2>/dev/null")
    time.sleep(3)
    tmp = Path(tempfile.mkdtemp(prefix="llms3-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if not s.exists():
            continue
        d = tmp / item
        if s.is_dir():
            shutil.copytree(s, d, dirs_exist_ok=True)
        else:
            shutil.copy2(s, d)

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1500, "height": 950},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        page.goto(f"{BASE}/wp-login.php", wait_until="domcontentloaded", timeout=90000)
        time.sleep(4)
        page.screenshot(path=str(ROOT / "fixes/llms-classic-0.png"), full_page=True)
        user = page.locator("#user_login")
        if user.count():
            log(f"user={user.input_value()!r}")
        page.locator("#wp-submit").click(timeout=10000)
        time.sleep(10)
        log(f"after={page.url}")
        page.screenshot(path=str(ROOT / "fixes/llms-classic-1.png"), full_page=True)

        if "wp-admin" not in page.url or "login" in page.url.lower():
            log("LOGIN FAILED")
            ctx.close()
            return 1

        page.goto(f"{BASE}/wp-admin/plugin-install.php?tab=upload", timeout=90000)
        time.sleep(4)
        for frame in [page, *page.frames]:
            ins = frame.locator('input[type="file"]')
            if ins.count():
                ins.first.set_input_files(str(ZIP))
                log("zip selected")
                break
        time.sleep(6)
        page.locator("#install-plugin-submit").click(timeout=15000)
        log("install clicked")
        time.sleep(30)
        page.screenshot(path=str(ROOT / "fixes/llms-classic-2.png"), full_page=True)
        for sel in [
            'a:has-text("Replace current with uploaded")',
            'a:has-text("Activate Plugin")',
            "a.activate-now",
            'a:has-text("Activate")',
        ]:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=10000)
                log(f"clicked {sel}")
                time.sleep(15)
                break
        page.goto(f"{BASE}/?nocache=llms", timeout=60000)
        time.sleep(5)
        page.goto(f"{BASE}/llms.txt?t={int(time.time())}", timeout=60000)
        time.sleep(2)
        try:
            text = page.locator("body").inner_text()[:400]
        except Exception:
            text = page.content()[:400]
        log(f"llms={text}")
        page.screenshot(path=str(ROOT / "fixes/llms-classic-live.png"), full_page=True)
        ctx.close()
    log("DONE")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
