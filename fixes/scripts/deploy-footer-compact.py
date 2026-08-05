#!/usr/bin/env python3
import os, shutil, subprocess, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
FILE = ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="foot-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            shutil.copytree(s, d) if s.is_dir() else shutil.copy2(s, d)
    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp), headless=False, channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"}, viewport={"width": 1440, "height": 900},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        page.goto(f"{SITE}/wp-admin/", timeout=120000)
        page.wait_for_timeout(2500)
        if "login" in page.url.lower():
            page.goto(f"{SITE}/wp-login.php", timeout=120000)
            page.wait_for_timeout(1200)
            page.click("#wp-submit")
            page.wait_for_timeout(7000)
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
            ins.first.set_input_files(str(FILE))
            page.wait_for_timeout(2000)
            for fr in [page, *page.frames]:
                for sel in ["button:has-text('YES')", "text=YES"]:
                    if fr.locator(sel).count():
                        try:
                            fr.locator(sel).first.click(timeout=3000)
                            page.wait_for_timeout(1000)
                        except Exception:
                            pass
            page.wait_for_timeout(9000)
            break
        page.goto(f"{SITE}/wp-admin/", timeout=60000)
        page.wait_for_timeout(1200)
        if page.locator("#wp-admin-bar-wp-rocket").count():
            page.hover("#wp-admin-bar-wp-rocket")
            page.wait_for_timeout(400)
            if page.locator("text=Clear cache").count():
                page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                page.wait_for_timeout(4000)
        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)

    time.sleep(2)
    h = subprocess.run(
        ["curl", "-sS", "-A", UA, f"{SITE}/?foot={int(time.time())}"],
        capture_output=True, text=True, timeout=60,
    ).stdout
    print("ver", "1.9.15" in h)
    print("old_privacy", "cindemir-privacy-notice" in h)
    print("meta", "cindemir-footer-meta" in h)
    print("css", "cindemir-footer-meta-css" in h)
    print("in_socket", h.find("cindemir-footer-meta") < h.find("cindemir-wa-fallback") if "cindemir-footer-meta" in h else False)

    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        page = b.new_page(viewport={"width": 1440, "height": 900}, user_agent=UA)
        page.goto(f"{SITE}/?v={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(3000)
        page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
        page.wait_for_timeout(1000)
        data = page.evaluate("""() => {
          const socket = document.querySelector('#socket .container');
          const meta = socket && socket.querySelector('.cindemir-footer-meta');
          const old = document.querySelector('.cindemir-privacy-notice');
          const r = socket && socket.getBoundingClientRect();
          return {
            hasMeta: !!meta,
            inSocket: !!meta,
            oldNotice: !!old,
            socketH: r && r.height,
            metaText: meta && meta.textContent.trim().slice(0,120)
          };
        }""")
        print(data)
        page.screenshot(path=str(SHOT / "footer-after.png"))
        b.close()
    ok = data.get("hasMeta") and data.get("inSocket") and not data.get("oldNotice")
    print("PASS" if ok else "FAIL")
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
