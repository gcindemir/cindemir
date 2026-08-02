#!/usr/bin/env python3
import os, shutil, subprocess, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
FILES = [
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php",
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php",
]
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"


def log(m):
    print(m, flush=True)


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="kvkk16-"))
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
        for f in FILES:
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
            uploaded = False
            for frame in [page, *page.frames]:
                ins = frame.locator('input[type="file"]')
                if not ins.count():
                    continue
                ins.first.set_input_files(str(f))
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
                uploaded = True
                log(f"uploaded {f.name}")
                break
            if not uploaded:
                return 3
        page.goto(f"{SITE}/wp-admin/", timeout=60000)
        page.wait_for_timeout(1000)
        if page.locator("#wp-admin-bar-wp-rocket").count():
            page.hover("#wp-admin-bar-wp-rocket")
            page.wait_for_timeout(400)
            if page.locator("text=Clear cache").count():
                page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                page.wait_for_timeout(4000)
                log("purged")
        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)
    time.sleep(2)
    h = subprocess.run(["curl", "-sS", "-A", UA, f"{SITE}/?k={int(time.time())}"], capture_output=True, text=True, timeout=60).stdout
    log(f"ver={'1.9.16' in h} link={'KVKK / Privacy Policy' in h} note={'processed under KVKK' in h}")
    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        page = b.new_page(viewport={"width": 1440, "height": 900}, user_agent=UA)
        page.goto(f"{SITE}/?v={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(2500)
        page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
        page.wait_for_timeout(800)
        txt = page.evaluate("""() => {
          const m=document.querySelector('#socket .cindemir-footer-meta');
          return m?m.innerText.replace(/\\s+/g,' ').trim():null;
        }""")
        log(f"visible: {txt}")
        page.screenshot(path=str(SHOT / "footer-kvkk-visible.png"))
        b.close()
    ok = bool(txt) and "KVKK" in txt and "Privacy Policy" in txt
    log("PASS" if ok else "FAIL")
    return 0 if ok else 4


if __name__ == "__main__":
    raise SystemExit(main())
