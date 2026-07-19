#!/usr/bin/env python3
"""Deploy SEO 1.9.56 stronger homepage hero and verify phone + tablet."""
import os, shutil, tempfile, time, json
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
FILE = ROOT / "fixes/mu-plugins/cindemir-seo-fixes.php"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"


def upload():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="hero1956-"))
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
                        page.wait_for_timeout(500)
                    except Exception:
                        pass
        for frame in [page, *page.frames]:
            ins = frame.locator('input[type="file"]')
            if not ins.count():
                continue
            local = tmp / "cindemir-seo-fixes.php"
            shutil.copy2(FILE, local)
            ins.first.set_input_files(str(local))
            page.wait_for_timeout(1500)
            for fr in [page, *page.frames]:
                for sel in ["button:has-text('YES')", "text=YES"]:
                    if fr.locator(sel).count():
                        try:
                            fr.locator(sel).first.click(timeout=2500)
                            page.wait_for_timeout(600)
                        except Exception:
                            pass
            page.wait_for_timeout(8000)
            print("uploaded", flush=True)
            break
        # Purge Rocket twice
        for _ in range(2):
            page.goto(f"{SITE}/wp-admin/", timeout=60000)
            page.wait_for_timeout(1200)
            if page.locator("#wp-admin-bar-wp-rocket").count():
                page.hover("#wp-admin-bar-wp-rocket")
                page.wait_for_timeout(400)
                if page.locator("text=Clear cache").count():
                    page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                    page.wait_for_timeout(3500)
                    print("purged", flush=True)
        # Also hit WP Rocket settings clear if present
        try:
            page.goto(f"{SITE}/wp-admin/options-general.php?page=wprocket#dashboard", timeout=60000)
            page.wait_for_timeout(2000)
            if page.locator("text=Clear and preload").count():
                page.locator("text=Clear and preload").first.click(force=True, timeout=3000)
                page.wait_for_timeout(3000)
                print("purged_dashboard", flush=True)
        except Exception as e:
            print("purge_dash_skip", e, flush=True)
        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)


def verify():
    t = int(time.time())
    results = {}
    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        for name, w, h in [("phone", 390, 844), ("tablet", 820, 1180), ("desk", 1440, 900)]:
            page = b.new_page(viewport={"width": w, "height": h}, user_agent=UA)
            page.goto(f"{SITE}/?cindemir_v=1956&t={t}", wait_until="domcontentloaded", timeout=120000)
            page.wait_for_timeout(3500)
            info = page.evaluate(
                """() => {
                  const ver=(document.documentElement.innerHTML.match(/cindemir-seo-fixes\\s+([0-9.]+)/)||[])[1]||'';
                  const photo=document.querySelector('.cindemir-mobile-hero-photo img');
                  const band=document.querySelector('.cindemir-mobile-hero-photo');
                  const pr=photo&&photo.getBoundingClientRect();
                  const bandDisp=band?getComputedStyle(band).display:'none';
                  const paras=[...document.querySelectorAll('#av_section_1 .avia_textblock p')]
                    .filter(p=>getComputedStyle(p).display!=='none' && p.getBoundingClientRect().height>4).length;
                  return {
                    ver, bandDisp,
                    photoH: pr?Math.round(pr.height):0,
                    photoTop: pr?Math.round(pr.top):null,
                    photoInView: !!(pr && pr.height>200 && pr.top < innerHeight),
                    paras, vh: innerHeight, vw: innerWidth
                  };
                }"""
            )
            page.screenshot(path=str(ROOT / f"fixes/home-1956-{name}.png"), full_page=False)
            results[name] = info
            print(name, json.dumps(info), flush=True)
            page.close()
        b.close()
    ok = (
        results["phone"]["ver"] == "1.9.56"
        and results["phone"]["photoInView"]
        and results["phone"]["photoH"] >= 300
        and results["phone"]["paras"] <= 1
        and results["tablet"]["photoInView"]
        and results["tablet"]["photoH"] >= 300
        and results["desk"]["bandDisp"] == "none"
    )
    print("OK" if ok else "FAIL", flush=True)
    return 0 if ok else 1


def main():
    upload()
    time.sleep(3)
    return verify()


if __name__ == "__main__":
    raise SystemExit(main())
