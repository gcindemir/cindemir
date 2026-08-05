#!/usr/bin/env python3
"""Deploy 1.9.21 and verify mobile sticky burger header."""
import os, shutil, tempfile, time, subprocess, json
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
FILE = ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php"
UA = "Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15"


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="hdr21-"))
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

    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        page = b.new_page(viewport={"width": 390, "height": 844}, user_agent=UA)
        page.goto(f"{SITE}/?cindemir_lang=en&t={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(3500)
        page.evaluate("window.scrollTo(0, 900)")
        page.wait_for_timeout(1200)
        d = page.evaluate(
            """() => {
              const hdr=document.querySelector('#header');
              const burger=document.querySelector('.av-burger-menu-main');
              const br=burger.getBoundingClientRect();
              const hr=hdr.getBoundingClientRect();
              return {
                pos: getComputedStyle(hdr).position,
                headerTop: hr.top,
                burgerTop: br.top,
                burgerVis: br.width>0 && br.height>0 && br.top>=0 && br.top<120,
                headerH: hr.height,
                varH: getComputedStyle(document.documentElement).getPropertyValue('--cindemir-header-h').trim(),
                scrollY: scrollY
              };
            }"""
        )
        page.screenshot(path=str(SHOT / "mobile-sticky-scrolled.png"))
        print("scrolled", json.dumps(d), flush=True)
        b.close()

    html = subprocess.run(
        ["curl", "-sS", "-A", "Mozilla/5.0", f"{SITE}/?v={int(time.time())}"],
        capture_output=True, text=True, timeout=60,
    ).stdout
    version_ok = "1.9.21" in html
    ok = version_ok and d["pos"] == "fixed" and d["burgerVis"] and abs(d["headerTop"]) < 2
    print("version_ok", version_ok, "PASS" if ok else "FAIL")
    return 0 if ok else 4


if __name__ == "__main__":
    raise SystemExit(main())
