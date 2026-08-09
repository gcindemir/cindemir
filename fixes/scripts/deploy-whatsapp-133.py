#!/usr/bin/env python3
"""Quick deploy contact-fixes + purge + verify single WA button."""
import os, re, shutil, subprocess, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
ACC = "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
FILE = ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php"


def log(m):
    print(m, flush=True)


def login(page):
    page.goto(f"{SITE}/wp-admin/", timeout=120000)
    page.wait_for_timeout(2500)
    if "login" in page.url.lower():
        page.goto(f"{SITE}/wp-login.php", timeout=120000)
        page.wait_for_timeout(1200)
        page.click("#wp-submit")
        page.wait_for_timeout(7000)
    return "login" not in page.url.lower()


def fm_upload(page, fpath):
    page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
    page.wait_for_timeout(5000)
    for name in ["wp-content", "mu-plugins"]:
        for frame in [page, *page.frames]:
            loc = frame.locator(f"text={name}")
            if loc.count():
                try:
                    loc.first.dblclick(timeout=5000)
                    page.wait_for_timeout(1400)
                    break
                except Exception:
                    pass
    for frame in [page, *page.frames]:
        for sel in ["[title='Upload files']", ".elfinder-button-icon-upload"]:
            if frame.locator(sel).count():
                try:
                    frame.locator(sel).first.click(force=True, timeout=4000)
                    page.wait_for_timeout(800)
                except Exception:
                    pass
    for frame in [page, *page.frames]:
        ins = frame.locator('input[type="file"]')
        if not ins.count():
            continue
        ins.first.set_input_files(str(fpath))
        page.wait_for_timeout(2000)
        for fr in [page, *page.frames]:
            for sel in ["button:has-text('YES')", "text=YES"]:
                if fr.locator(sel).count():
                    try:
                        fr.locator(sel).first.click(timeout=3000)
                        page.wait_for_timeout(1000)
                    except Exception:
                        pass
        page.wait_for_timeout(8000)
        return True
    return False


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="wa133-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            shutil.copytree(s, d) if s.is_dir() else shutil.copy2(s, d)
    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp), headless=False, channel="chrome",
                args=["--no-sandbox", "--disable-dev-shm-usage"],
                env={**os.environ, "DISPLAY": ":1"},
                viewport={"width": 1440, "height": 900},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            if not login(page):
                return 2
            if not fm_upload(page, FILE):
                return 3
            page.goto(f"{SITE}/wp-admin/", timeout=60000)
            page.wait_for_timeout(1200)
            if page.locator("#wp-admin-bar-wp-rocket").count():
                page.hover("#wp-admin-bar-wp-rocket")
                page.wait_for_timeout(400)
                if page.locator("text=Clear cache").count():
                    page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                    page.wait_for_timeout(4000)
            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    h = subprocess.run(
        ["curl", "-sS", "-A", UA, "-H", f"Accept: {ACC}", f"{SITE}/?wa133={int(time.time())}"],
        capture_output=True, text=True, timeout=60,
    ).stdout
    hide = ".joinchat{display:none!important" in h
    one = h.count("cindemir-wa-fallback") >= 1
    log(f"hide_joinchat={hide} fallback={one} wa=902165506775")

    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        page = b.new_page(viewport={"width": 1440, "height": 900}, user_agent=UA)
        page.goto(f"{SITE}/?v={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(4000)
        fb = page.locator("#cindemir-wa-fallback")
        jc = page.locator(".joinchat")
        fb_vis = fb.count() and fb.first.is_visible()
        jc_vis = jc.count() and jc.first.is_visible()
        log(f"fb_vis={fb_vis} jc_vis={jc_vis}")
        page.screenshot(path=str(SHOT / "wa133-single.png"))
        b.close()

    ok = hide and one and fb_vis and not jc_vis
    log("PASS" if ok else "FAIL")
    return 0 if ok else 4


if __name__ == "__main__":
    raise SystemExit(main())
