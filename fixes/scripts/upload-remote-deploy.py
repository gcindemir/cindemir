#!/usr/bin/env python3
"""Upload tiny cindemir-remote-deploy.php to fix corrupt main plugin."""
import os, shutil, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

FILE = Path("/workspace/fixes/mu-plugins/cindemir-remote-deploy.php")
PROFILE = Path.home() / ".config/google-chrome"
DISPLAY = ":1"
FM = "https://my.bluehost.com/hosting/app/cindemirlaw.com/cpanel/filemanager"

os.system("pkill -f google-chrome-stable 2>/dev/null")
time.sleep(2)

tmp = Path(tempfile.mkdtemp(prefix="bhmini-"))
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
        str(tmp), headless=False, channel="chrome",
        args=["--no-sandbox", "--disable-dev-shm-usage"],
        env={**os.environ, "DISPLAY": DISPLAY},
        viewport={"width": 1500, "height": 950},
    )
    page = ctx.pages[0] if ctx.pages else ctx.new_page()
    page.goto(FM, wait_until="domcontentloaded", timeout=120000)
    for i in range(40):
        time.sleep(3)
        print(f"[{i*3}s] {page.url[:80]} | {page.title()[:40]}", flush=True)
        if "filemanager" in page.url.lower() or "cpanel" in page.url.lower():
            break
        if "dashboard" in page.url.lower() and i > 5:
            page.goto(FM, wait_until="domcontentloaded", timeout=60000)
    page.screenshot(path="/workspace/fixes/mini-0.png", full_page=True)

    for folder in ["public_html", "wp-content", "mu-plugins"]:
        for sel in [f"text={folder}", f"td:has-text('{folder}')"]:
            loc = page.locator(sel)
            if loc.count():
                try:
                    loc.first.dblclick(timeout=5000)
                    time.sleep(2)
                    print("opened", folder, flush=True)
                    break
                except Exception:
                    pass

    page.screenshot(path="/workspace/fixes/mini-1.png", full_page=True)
    uploaded = False
    for frame in [page, *page.frames]:
        ins = frame.locator('input[type="file"]')
        if ins.count():
            try:
                ins.first.set_input_files(str(FILE))
                uploaded = True
                print("UPLOADED remote-deploy", flush=True)
                time.sleep(10)
                break
            except Exception as e:
                print("err", e, flush=True)

    page.screenshot(path="/workspace/fixes/mini-2.png", full_page=True)
    print("RESULT", uploaded, flush=True)
    ctx.close()

shutil.rmtree(tmp, ignore_errors=True)
