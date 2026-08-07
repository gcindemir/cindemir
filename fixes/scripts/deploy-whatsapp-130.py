#!/usr/bin/env python3
"""Deploy cindemir-contact-fixes.php 1.3.0 (WhatsApp phone + visible button)."""
import os, re, shutil, subprocess, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
FILE = ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php"
PHONE = "902165506775"
OLD = "905325680647"


def log(m):
    print(m, flush=True)


def fetch(path="/"):
    return subprocess.run(
        ["curl", "-sS", "-A", UA, f"{SITE}{path}"],
        capture_output=True,
        text=True,
        timeout=60,
    ).stdout


def code(path="/"):
    return subprocess.run(
        ["curl", "-sS", "-o", "/dev/null", "-w", "%{http_code}", "-A", UA, f"{SITE}{path}"],
        capture_output=True,
        text=True,
        timeout=45,
    ).stdout.strip()


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
                    page.wait_for_timeout(1600)
                    log(f"nav {name}")
                    break
                except Exception:
                    pass
    for frame in [page, *page.frames]:
        for sel in ["[title='Upload files']", ".elfinder-button-icon-upload"]:
            if frame.locator(sel).count():
                try:
                    frame.locator(sel).first.click(force=True, timeout=4000)
                    page.wait_for_timeout(1000)
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
                        page.wait_for_timeout(1200)
                        log("YES overwrite")
                    except Exception:
                        pass
        page.wait_for_timeout(9000)
        log(f"uploaded {fpath.name}")
        return True
    return False


def purge(page):
    page.goto(f"{SITE}/wp-admin/", timeout=60000)
    page.wait_for_timeout(1500)
    if page.locator("#wp-admin-bar-wp-rocket").count():
        page.hover("#wp-admin-bar-wp-rocket")
        page.wait_for_timeout(500)
        if page.locator("text=Clear cache").count():
            try:
                page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                page.wait_for_timeout(4000)
                log("rocket purged")
            except Exception:
                pass


def verify_html(h):
    return {
        "old": h.count(OLD),
        "new": h.count(PHONE),
        "fallback": "cindemir-wa-fallback" in h,
        "flex": "display:flex!important" in h and "cindemir-wa-fallback" in h,
        "telephone": f'"telephone":"{PHONE}"' in h or f'"telephone":"{PHONE}"' in h.replace("'", '"'),
        "wa_me_new": f"wa.me/{PHONE}" in h,
        "wa_me_old": f"wa.me/{OLD}" in h,
        "version": "1.3.0" in h or "Contact & WhatsApp" in h,
    }


def main():
    log(f"START home={code('/')} file={FILE.exists()} size={FILE.stat().st_size}")
    before = verify_html(fetch(f"/?t={int(time.time())}"))
    log(f"before {before}")

    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="wa130-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            shutil.copytree(s, d) if s.is_dir() else shutil.copy2(s, d)
    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp),
                headless=False,
                channel="chrome",
                args=["--no-sandbox", "--disable-dev-shm-usage"],
                env={**os.environ, "DISPLAY": ":1"},
                viewport={"width": 1280, "height": 800},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            if not login(page):
                log("LOGIN FAIL")
                page.screenshot(path=str(SHOT / "wa130-login-fail.png"))
                return 2
            log("login ok")
            if not fm_upload(page, FILE):
                log("UPLOAD FAIL")
                page.screenshot(path=str(SHOT / "wa130-upload-fail.png"))
                return 3
            page.screenshot(path=str(SHOT / "wa130-uploaded.png"))
            purge(page)
            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    time.sleep(2)
    h = fetch(f"/?nocache={int(time.time())}")
    after = verify_html(h)
    log(f"after {after} health={code('/')}")

    # Visual: button visible
    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        page = b.new_page(viewport={"width": 1440, "height": 900}, user_agent=UA)
        page.goto(f"{SITE}/?nocache={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(2500)
        fb = page.locator("#cindemir-wa-fallback")
        jc = page.locator(".joinchat.joinchat--show")
        fb_vis = fb.count() > 0 and fb.first.is_visible()
        jc_vis = jc.count() > 0 and jc.first.is_visible()
        href = fb.first.get_attribute("href") if fb.count() else None
        settings = page.locator(".joinchat").first.get_attribute("data-settings") if page.locator(".joinchat").count() else None
        log(f"visible fallback={fb_vis} joinchat={jc_vis} href={href}")
        log(f"settings={settings}")
        page.screenshot(path=str(SHOT / "wa130-home.png"))
        b.close()

    ok = after["old"] == 0 and after["wa_me_new"] and after["flex"] and (fb_vis or jc_vis)
    log("PASS" if ok else "FAIL")
    return 0 if ok else 4


if __name__ == "__main__":
    raise SystemExit(main())
