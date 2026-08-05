#!/usr/bin/env python3
"""Upload mu-plugins.zip via Bluehost File Manager (saved Chrome session)."""
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ZIP = Path("/workspace/fixes/deploy-package/mu-plugins.zip")
FORCE = Path("/workspace/fixes/mu-plugins/cindemir-force-upgrade.php")
PROFILE = Path.home() / "/.config/google-chrome".lstrip("/")
DISPLAY = ":1"
FM = "https://my.bluehost.com/hosting/app/cindemirlaw.com/cpanel/filemanager"

os.system("pkill -f google-chrome-stable 2>/dev/null")
time.sleep(2)

tmp = Path(tempfile.mkdtemp(prefix="bhdeploy-"))
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
    page.goto("https://www.bluehost.com/my-account/login", timeout=120000)
    time.sleep(4)

    for sel in ["button:has-text('Login')", "button[type='submit']", "text=Login"]:
        loc = page.locator(sel)
        if loc.count():
            try:
                loc.first.click(timeout=5000)
                break
            except Exception:
                pass

    for i in range(25):
        time.sleep(3)
        if "dashboard" in page.url or "hosting" in page.url:
            break

    page.goto(FM, timeout=120000)
    time.sleep(12)

    for folder in ["public_html", "wp-content", "mu-plugins"]:
        for sel in [f"text={folder}", f"td:has-text('{folder}')"]:
            loc = page.locator(sel)
            if loc.count():
                try:
                    loc.first.dblclick(timeout=5000)
                    time.sleep(2)
                    break
                except Exception:
                    pass

    for path in [FORCE, ZIP]:
        for frame in [page, *page.frames]:
            ins = frame.locator('input[type="file"]')
            if not ins.count():
                continue
            try:
                ins.first.set_input_files(str(path))
                uploaded = True
                print(f"UPLOADED {path.name}", flush=True)
                time.sleep(15)
                break
            except Exception as e:
                print(f"upload err {path.name}: {e}", flush=True)
        if uploaded and path == ZIP:
            for sel in ["text=mu-plugins.zip", "td:has-text('mu-plugins.zip')"]:
                loc = page.locator(sel)
                if loc.count():
                    loc.first.click(timeout=3000)
            for sel in ["text=Extract", "button:has-text('Extract')"]:
                loc = page.locator(sel)
                if loc.count():
                    loc.first.click(timeout=5000)
                    time.sleep(10)
            break

    page.screenshot(path="/workspace/fixes/deploy-v181-done.png", full_page=True)
    ctx.close()

shutil.rmtree(tmp, ignore_errors=True)
print("RESULT uploaded=", uploaded, flush=True)
raise SystemExit(0 if uploaded else 1)
