#!/usr/bin/env python3
"""Deploy KVKK privacy notices (1.9.14 + 1.3.5), create RU/ZH pages, verify."""
import json
import os
import shutil
import subprocess
import tempfile
import time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
ACC = "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
FILES = [
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php",
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php",
]


def log(m):
    print(m, flush=True)


def curl(url):
    return subprocess.run(
        ["curl", "-sS", "-A", UA, "-H", f"Accept: {ACC}", url],
        capture_output=True,
        text=True,
        timeout=90,
    )


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
    log("START kvkk deploy")
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="kvkk-"))
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
                return 2
            for f in FILES:
                if not fm_upload(page, f):
                    return 3
                log(f"uploaded {f.name}")
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

    time.sleep(2)
    api = curl(f"{SITE}/wp-json/cindemir/v1/setup-privacy-i18n?key=seo-pack-2026")
    log(f"setup-api {api.returncode} {api.stdout[:500]}")
    try:
        data = json.loads(api.stdout)
    except Exception:
        data = {}
    if not data.get("ok"):
        return 4

    checks = {}
    for label, url in [
        ("en_home", f"{SITE}/?kvkk={int(time.time())}"),
        ("ru_home", f"{SITE}/?lang=ru&kvkk={int(time.time())}"),
        ("zh_home", f"{SITE}/?lang=zh-hans&kvkk={int(time.time())}"),
        ("en_pp", f"{SITE}/privacy-policy/?kvkk={int(time.time())}"),
        ("ru_pp", f"{SITE}/privacy-policy/?lang=ru&kvkk={int(time.time())}"),
        ("zh_pp", f"{SITE}/privacy-policy/?lang=zh-hans&kvkk={int(time.time())}"),
        ("ru_contacts", f"{SITE}/contacts/?lang=ru&kvkk={int(time.time())}"),
        ("zh_contacts", f"{SITE}/contacts/?lang=zh-hans&kvkk={int(time.time())}"),
    ]:
        r = curl(url)
        h = r.stdout
        checks[label] = {
            "len": len(h),
            "notice": "cindemir-privacy-notice" in h,
            "form_notice": "cindemir-privacy-form-notice" in h,
            "checkbox": 'type="checkbox"' in h and "kvkk" in h.lower(),
            "ver": "1.9.14" in h,
            "404": "Page not found" in h,
        }
    log(json.dumps(checks, indent=2))

    ok = (
        checks["ru_pp"]["len"] > 10000
        and not checks["ru_pp"]["404"]
        and checks["zh_pp"]["len"] > 10000
        and not checks["zh_pp"]["404"]
        and checks["ru_home"]["notice"]
        and checks["zh_home"]["notice"]
        and checks["ru_contacts"]["form_notice"]
        and checks["zh_contacts"]["form_notice"]
    )
    log("PASS" if ok else "FAIL")
    return 0 if ok else 5


if __name__ == "__main__":
    raise SystemExit(main())
