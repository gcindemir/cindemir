#!/usr/bin/env python3
"""Deploy cindemir-seo-fixes 1.9.20 and verify header_meta is gone."""
import os, shutil, tempfile, time, subprocess, json
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
FILE = ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="hdr20-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            shutil.copytree(s, d) if s.is_dir() else shutil.copy2(s, d)
    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp), headless=False, channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"}, viewport={"width": 1280, "height": 800},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        page.goto(f"{SITE}/wp-admin/", timeout=120000)
        page.wait_for_timeout(2000)
        if "login" in page.url.lower():
            page.goto(f"{SITE}/wp-login.php", timeout=120000)
            page.wait_for_timeout(1000)
            page.click("#wp-submit")
            page.wait_for_timeout(7000)
        page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
        page.wait_for_timeout(4500)
        for name in ["wp-content", "mu-plugins"]:
            for frame in [page, *page.frames]:
                loc = frame.locator(f"text={name}")
                if loc.count():
                    try:
                        loc.first.dblclick(timeout=4000)
                        page.wait_for_timeout(1200)
                        break
                    except Exception:
                        pass
        for frame in [page, *page.frames]:
            for sel in ["[title='Upload files']", ".elfinder-button-icon-upload"]:
                if frame.locator(sel).count():
                    try:
                        frame.locator(sel).first.click(force=True, timeout=3000)
                        page.wait_for_timeout(600)
                    except Exception:
                        pass
        for frame in [page, *page.frames]:
            ins = frame.locator('input[type="file"]')
            if not ins.count():
                continue
            ins.first.set_input_files(str(FILE))
            page.wait_for_timeout(1800)
            for fr in [page, *page.frames]:
                for sel in ["button:has-text('YES')", "text=YES"]:
                    if fr.locator(sel).count():
                        try:
                            fr.locator(sel).first.click(timeout=2500)
                            page.wait_for_timeout(800)
                        except Exception:
                            pass
            page.wait_for_timeout(8000)
            print("uploaded", flush=True)
            break
        page.goto(f"{SITE}/wp-admin/", timeout=60000)
        page.wait_for_timeout(1000)
        if page.locator("#wp-admin-bar-wp-rocket").count():
            page.hover("#wp-admin-bar-wp-rocket")
            page.wait_for_timeout(400)
            if page.locator("text=Clear cache").count():
                page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                page.wait_for_timeout(4000)
                print("purged", flush=True)
        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)
    time.sleep(2)

    results = {}
    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        page = b.new_page(viewport={"width": 1440, "height": 900}, user_agent=UA)
        page.goto(f"{SITE}/?cindemir_lang=en&t={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(3500)
        d = page.evaluate(
            """() => {
              const meta=document.querySelector('#header_meta');
              const metaH=meta?meta.getBoundingClientRect().height:0;
              const metaVis=meta?getComputedStyle(meta).display!=='none':false;
              const socialInMeta=meta?meta.querySelectorAll('.social_bookmarks li').length:0;
              const headerH=document.querySelector('#header')?.getBoundingClientRect().height||0;
              const langVis=[...document.querySelectorAll('#avia-menu > li.cindemir-lang-item')]
                .filter(li=>getComputedStyle(li).display!=='none').length;
              return {metaH, metaVis, socialInMeta, headerH, langVis};
            }"""
        )
        results["en"] = d
        page.screenshot(path=str(SHOT / "hdr20-en.png"))
        print("en", json.dumps(d), flush=True)
        b.close()

    html = subprocess.run(
        ["curl", "-sS", "-A", UA, f"{SITE}/?v={int(time.time())}"],
        capture_output=True, text=True, timeout=60,
    ).stdout
    version_ok = "1.9.20" in html
    ok = (
        version_ok
        and not results["en"]["metaVis"]
        and results["en"]["metaH"] == 0
        and results["en"]["langVis"] >= 3
        and results["en"]["headerH"] <= 130
    )
    print("version_ok", version_ok, "PASS" if ok else "FAIL")
    return 0 if ok else 4


if __name__ == "__main__":
    raise SystemExit(main())
