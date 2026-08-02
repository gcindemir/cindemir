#!/usr/bin/env python3
"""Deploy SEO 1.9.55 homepage mobile hero fix and verify team photo visibility."""
import os, shutil, tempfile, time, json
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
FILE = ROOT / "fixes/mu-plugins/cindemir-seo-fixes.php"
UA_M = "Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1"
UA_D = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"


def upload():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="hero1954-"))
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


def verify():
    t = int(time.time())
    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        page = b.new_page(viewport={"width": 390, "height": 844}, user_agent=UA_M, is_mobile=True, has_touch=True)
        page.goto(f"{SITE}/?lang=en&nocache={t}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(4000)
        mob = page.evaluate(
            """() => {
              const ver=(document.documentElement.innerHTML.match(/cindemir-seo-fixes\\s+([0-9.]+)/)||[])[1]||'';
              const photo=document.querySelector('.cindemir-mobile-hero-photo img');
              const pr=photo?photo.getBoundingClientRect():null;
              const s2col=document.querySelector('#av_section_2 .av-kb0bnfzj-6b756727d2887e26a4cf2233375d0c98');
              const c2=s2col?s2col.getBoundingClientRect():null;
              const paras=[...document.querySelectorAll('#av_section_1 .avia_textblock p')];
              const visibleParas=paras.filter(p=>getComputedStyle(p).display!=='none' && p.getBoundingClientRect().height>0).length;
              const h1=document.querySelector('#av_section_1 h1');
              return {
                ver,
                hasPhotoBand:!!photo,
                photoInView: !!(pr && pr.top < window.innerHeight && pr.height > 120),
                photoH: pr?Math.round(pr.height):0,
                photoW: pr?Math.round(pr.width):0,
                s2colH: c2?Math.round(c2.height):0,
                visibleParas,
                h1: h1?(h1.innerText||'').trim():'',
                styleTag: !!document.getElementById('cindemir-home-hero')
              };
            }"""
        )
        page.screenshot(path=str(ROOT / "fixes/home-en-mobile-after.png"), full_page=False)
        print("MOB", json.dumps(mob), flush=True)

        desk = b.new_page(viewport={"width": 1440, "height": 900}, user_agent=UA_D)
        desk.goto(f"{SITE}/?lang=en&nocache={t}", wait_until="domcontentloaded", timeout=120000)
        desk.wait_for_timeout(3000)
        d = desk.evaluate(
            """() => {
              const photo=document.querySelector('.cindemir-mobile-hero-photo');
              const cs=photo?getComputedStyle(photo):null;
              const s1=document.querySelector('#av_section_1');
              return {
                photoHidden: !photo || cs.display==='none',
                s1bgPos: s1?getComputedStyle(s1).backgroundPosition:'',
                h1:(document.querySelector('#av_section_1 h1')||{}).innerText||''
              };
            }"""
        )
        desk.screenshot(path=str(ROOT / "fixes/home-en-desktop-after.png"), full_page=False)
        print("DESK", json.dumps(d), flush=True)
        b.close()

    ok = (
        mob.get("ver") == "1.9.55"
        and mob.get("photoInView")
        and mob.get("photoH", 0) >= 140
        and mob.get("s2colH", 0) >= 160
        and mob.get("visibleParas", 99) <= 3
        and d.get("photoHidden")
    )
    print("OK" if ok else "FAIL", flush=True)
    return 0 if ok else 1


def main():
    upload()
    time.sleep(2)
    return verify()


if __name__ == "__main__":
    raise SystemExit(main())
