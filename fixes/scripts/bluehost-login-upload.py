#!/usr/bin/env python3
"""Login to Bluehost with saved credentials and upload remote-deploy."""
import os, shutil, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

FILE = Path("/workspace/fixes/mu-plugins/cindemir-remote-deploy.php")
ZIP = Path("/workspace/fixes/deploy-package/mu-plugins.zip")
PROFILE = Path.home() / ".config/google-chrome"
DISPLAY = ":1"

os.system("pkill -f google-chrome-stable 2>/dev/null")
time.sleep(2)

tmp = Path(tempfile.mkdtemp(prefix="bhlogin-"))
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
    page.goto("https://www.bluehost.com/my-account/login", timeout=120000)
    time.sleep(4)
    page.screenshot(path="/workspace/fixes/login-0.png", full_page=True)

    # Click Login (saved credentials)
    for sel in [
        "button:has-text('Login')",
        "button[type='submit']",
        "text=Login",
        "#btn_login",
    ]:
        loc = page.locator(sel)
        if loc.count():
            try:
                loc.first.click(timeout=5000)
                print("clicked login", sel, flush=True)
                break
            except Exception as e:
                print("login click fail", sel, e, flush=True)

    for i in range(30):
        time.sleep(3)
        print(f"[{i*3}s] {page.url[:90]} | {page.title()[:50]}", flush=True)
        if "dashboard" in page.url or "hosting" in page.url:
            break

    page.screenshot(path="/workspace/fixes/login-1.png", full_page=True)

    page.goto("https://my.bluehost.com/hosting/app/cindemirlaw.com/cpanel/filemanager", timeout=120000)
    time.sleep(15)
    page.screenshot(path="/workspace/fixes/login-2-fm.png", full_page=True)
    print("FM", page.url, page.title(), flush=True)

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

    page.screenshot(path="/workspace/fixes/login-3-dir.png", full_page=True)

    # delete corrupt
    for sel in ["text=cindemir-seo-fixes.php", "td:has-text('cindemir-seo-fixes.php')"]:
        loc = page.locator(sel)
        if loc.count():
            loc.first.click(timeout=3000)
            for dsel in ["text=Delete", "button:has-text('Delete')"]:
                d = page.locator(dsel)
                if d.count():
                    d.first.click(timeout=3000)
                    time.sleep(2)
                    for csel in ["text=Confirm", "button:has-text('Confirm')", "text=Yes"]:
                        c = page.locator(csel)
                        if c.count():
                            c.first.click(timeout=3000)
                    print("deleted corrupt", flush=True)
            break

    uploaded = False
    uploaded_name = ""
    for path in [FILE, ZIP]:
        for frame in [page, *page.frames]:
            ins = frame.locator('input[type="file"]')
            if ins.count():
                try:
                    ins.first.set_input_files(str(path))
                    uploaded = True
                    uploaded_name = path.name
                    print("uploaded", path.name, flush=True)
                    time.sleep(12)
                    break
                except Exception as e:
                    print("up err", e, flush=True)
            if uploaded:
                break
        if uploaded:
            break

    if uploaded and uploaded_name == "mu-plugins.zip":
        for sel in ["text=mu-plugins.zip", "td:has-text('mu-plugins.zip')"]:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=3000)
        for sel in ["text=Extract", "button:has-text('Extract')"]:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=5000)
                time.sleep(8)

    page.screenshot(path="/workspace/fixes/login-4-done.png", full_page=True)
    print("RESULT uploaded=", uploaded, flush=True)
    ctx.close()

shutil.rmtree(tmp, ignore_errors=True)
