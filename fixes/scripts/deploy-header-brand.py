#!/usr/bin/env python3
"""Deploy header branding via FM overwrite of mu-plugins seo-fixes (+ branding helper)."""
import os, re, shutil, subprocess, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
FILES = [
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php",
    ROOT / "fixes/mu-plugins/cindemir-mobile-header-branding.php",
]


def log(m):
    print(m, flush=True)


def code(path="/"):
    return subprocess.run(["curl","-sS","-o","/dev/null","-w","%{http_code}",f"{SITE}{path}"],capture_output=True,text=True,timeout=45).stdout.strip()


def ver():
    h = subprocess.run(["curl","-sS",f"{SITE}/?t={int(time.time())}"],capture_output=True,text=True,timeout=45).stdout
    m = re.search(r"cindemir-seo-fixes ([0-9.]+)", h)
    return m.group(1) if m else None


def html(path):
    sep = "&" if "?" in path else "?"
    return subprocess.run(["curl","-sS",f"{SITE}{path}{sep}t={int(time.time())}"],capture_output=True,text=True,timeout=60).stdout


def shot(page, name):
    try:
        page.screenshot(path=str(SHOT / name), full_page=False)
    except Exception:
        pass


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
                        log("YES")
                    except Exception:
                        pass
        page.wait_for_timeout(9000)
        log(f"uploaded {fpath.name}")
        return True
    return False


def main():
    log(f"START home={code('/')} ver={ver()}")
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="brand-"))
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
                viewport={"width": 1280, "height": 800},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            if not login(page):
                return 2
            for f in FILES:
                if not fm_upload(page, f):
                    log(f"FAIL {f.name}")
                    return 3
                if code("/") == "500":
                    log("500 after upload")
                    return 10
                log(f"health {f.name}: {code('/')} ver={ver()}")
            # purge rocket
            page.goto(f"{SITE}/wp-admin/", timeout=60000)
            page.wait_for_timeout(1500)
            if page.locator("#wp-admin-bar-wp-rocket").count():
                page.hover("#wp-admin-bar-wp-rocket")
                page.wait_for_timeout(500)
                if page.locator("text=Clear cache").count():
                    try:
                        page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                        page.wait_for_timeout(3500)
                        log("rocket purged")
                    except Exception:
                        pass
            # visual check ru press mobile viewport
            page.set_viewport_size({"width": 390, "height": 844})
            page.goto(f"{SITE}/press/?lang=ru&t={int(time.time())}", timeout=120000)
            page.wait_for_timeout(4000)
            shot(page, "brand-ru-mobile.png")
            page.set_viewport_size({"width": 1280, "height": 800})
            page.goto(f"{SITE}/press/?lang=zh-hans&t={int(time.time())}", timeout=120000)
            page.wait_for_timeout(3500)
            shot(page, "brand-zh-desktop.png")
            page.goto(f"{SITE}/?t={int(time.time())}", timeout=120000)
            page.wait_for_timeout(3000)
            shot(page, "brand-home-desktop.png")
            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    for path in ["/", "/press/", "/press/?lang=ru", "/press/?lang=zh-hans"]:
        h = html(path)
        print_ok = "cindemir-header-brand" in h or "cindemir-header-brand-fallback" in h
        logo_ok = "cropped-logoicon" in h and "themes/enfold/images/layout/logo.png" not in h
        log(f"{path}: brand_css/html={print_ok} logo_fixed={logo_ok} has_text={'Cindemir Law Office' in h} ver_marker={'1.8.6' in h}")
    log(f"FINAL home={code('/')} ver={ver()}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
