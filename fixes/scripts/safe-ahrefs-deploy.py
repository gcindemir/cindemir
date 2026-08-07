#!/usr/bin/env python3
"""
Safe Ahrefs deploy:
1. Upload ONLY cindemir-contact-fixes.php via WP File Manager
2. Health-check after upload
3. Server pulls remaining plugins from GitHub via REST (no large browser upload)
4. Run fix-ahrefs + purge cache
"""
import os
import re
import shutil
import subprocess
import sys
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
DISPLAY = os.environ.get("DISPLAY", ":1")
CONTACT = ROOT / "fixes/mu-plugins/cindemir-contact-fixes.php"
SITE = "https://cindemirlaw.com"


def log(msg):
    print(msg, flush=True)


def health() -> dict:
    out = {}
    for name, path in [("home", "/"), ("admin", "/wp-admin/"), ("json", "/wp-json/")]:
        r = subprocess.run(
            ["curl", "-sS", "-o", "/dev/null", "-w", "%{http_code}", f"{SITE}{path}"],
            capture_output=True,
            text=True,
            timeout=30,
        )
        out[name] = r.stdout.strip()
    r = subprocess.run(
        ["curl", "-sS", "-H", "Cache-Control: no-cache", f"{SITE}/?t={int(time.time())}"],
        capture_output=True,
        text=True,
        timeout=30,
    )
    html = r.stdout
    out["critical"] = "critical error" in html.lower()
    m = re.search(r"cindemir-seo-fixes ([0-9.]+)", html)
    out["version"] = m.group(1) if m else None
    out["barobirlik"] = "d.barobirlik" in html
    return out


def assert_healthy(h, step):
    if h.get("critical") or h.get("home") == "500" or h.get("admin") == "500":
        log(f"ABORT at {step}: site broken {h}")
        sys.exit(10)
    log(f"health@{step}: home={h['home']} admin={h['admin']} critical={h['critical']}")


def curl_json(path):
    r = subprocess.run(
        ["curl", "-sS", f"{SITE}{path}"],
        capture_output=True,
        text=True,
        timeout=120,
    )
    log(f"GET {path} -> {r.stdout[:400]}")
    return r.stdout


def login(page):
    page.goto(f"{SITE}/wp-admin/", timeout=120000)
    page.wait_for_timeout(4000)
    if "login" in page.url.lower():
        page.goto(f"{SITE}/wp-login.php", timeout=120000)
        page.wait_for_timeout(2000)
        page.click("#wp-submit")
        page.wait_for_timeout(8000)
    ok = "login" not in page.url.lower()
    log(f"login ok={ok}")
    return ok


def nav_mu(page):
    page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
    page.wait_for_timeout(6000)
    for name in ["wp-content", "mu-plugins"]:
        for frame in [page, *page.frames]:
            loc = frame.locator(f"text={name}")
            if loc.count():
                try:
                    loc.first.dblclick(timeout=5000)
                    page.wait_for_timeout(2000)
                    break
                except Exception:
                    pass


def upload_contact(page) -> bool:
    for frame in [page, *page.frames]:
        for sel in ["[title='Upload files']", ".elfinder-button-icon-upload"]:
            btn = frame.locator(sel)
            if btn.count():
                btn.first.click(force=True, timeout=5000)
                page.wait_for_timeout(2000)
                break
    for frame in [page, *page.frames]:
        ins = frame.locator('input[type="file"]')
        if ins.count():
            ins.first.set_input_files(str(CONTACT))
            page.wait_for_timeout(2000)
            for frame in [page, *page.frames]:
                for sel in ["button:has-text('YES')", "text=YES"]:
                    loc = frame.locator(sel)
                    if loc.count():
                        try:
                            loc.first.click(timeout=4000)
                            page.wait_for_timeout(2500)
                            break
                        except Exception:
                            pass
            page.wait_for_timeout(15000)
            log(f"uploaded contact-fixes ({CONTACT.stat().st_size}b)")
            return True
    return False


def clear_rocket(page):
    page.goto(f"{SITE}/wp-admin/admin.php?page=wprocket", timeout=60000)
    page.wait_for_timeout(3000)
    for sel in ["text=Clear cache", "button:has-text('Clear cache')"]:
        loc = page.locator(sel)
        if loc.count():
            try:
                loc.first.click(timeout=5000)
                page.wait_for_timeout(5000)
                log("rocket cache cleared")
                return
            except Exception:
                pass


def main():
    h0 = health()
    assert_healthy(h0, "start")

    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="safe-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            if s.is_dir():
                shutil.copytree(s, d, dirs_exist_ok=True)
            else:
                shutil.copy2(s, d)

    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp), headless=False, channel="chrome",
                args=["--no-sandbox"], env={**os.environ, "DISPLAY": DISPLAY},
                viewport={"width": 1500, "height": 950},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            if not login(page):
                return 2
            nav_mu(page)
            if not upload_contact(page):
                log("upload failed")
                return 3
            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    time.sleep(3)
    h1 = health()
    assert_healthy(h1, "after-contact-upload")

    curl_json("/wp-json/cindemir/v1/pull-plugins?key=seo-pack-2026")
    time.sleep(5)
    h2 = health()
    assert_healthy(h2, "after-pull")

    curl_json("/wp-json/cindemir/v1/fix-ahrefs?key=seo-pack-2026")
    time.sleep(3)

    # second pull to ensure seo-fixes landed
    curl_json("/wp-json/cindemir/v1/pull-plugins?key=seo-pack-2026")
    time.sleep(5)

    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)
    tmp2 = Path(tempfile.mkdtemp(prefix="purge-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp2 / item
            if s.is_dir():
                shutil.copytree(s, d, dirs_exist_ok=True)
            else:
                shutil.copy2(s, d)
    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp2), headless=False, channel="chrome",
                args=["--no-sandbox"], env={**os.environ, "DISPLAY": DISPLAY},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            if login(page):
                clear_rocket(page)
            ctx.close()
    finally:
        shutil.rmtree(tmp2, ignore_errors=True)

    h3 = health()
    log(f"FINAL: version={h3.get('version')} barobirlik={h3.get('barobirlik')} {h3}")
    routes = subprocess.run(
        ["curl", "-sS", f"{SITE}/wp-json/"],
        capture_output=True, text=True, timeout=30,
    )
    if "cindemir/v1" in routes.stdout:
        log("cindemir REST routes: OK")
    else:
        log("WARN: no cindemir routes in index")

    if h3.get("version", "").startswith("1.8") and not h3.get("barobirlik"):
        log("SUCCESS")
        return 0
    if not h3.get("critical") and h3.get("home") == "200":
        log("PARTIAL — site up, verify version/cache")
        return 0
    return 4


if __name__ == "__main__":
    sys.exit(main())
