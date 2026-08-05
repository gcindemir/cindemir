#!/usr/bin/env python3
"""Deploy cindemir-seo-fixes 1.9.29 and verify EN Articles hides Russian posts."""
import os, shutil, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
FILE = ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="art1929-"))
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
            # Upload as cindemir-seo-fixes.php into mu-plugins
            upload_name = str(tmp / "cindemir-seo-fixes.php")
            shutil.copy2(FILE, upload_name)
            ins.first.set_input_files(upload_name)
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

    t = int(time.time())
    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        page = b.new_page(viewport={"width": 1440, "height": 900}, user_agent=UA)
        page.goto(f"{SITE}/articles/?lang=en&nocache={t}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(3500)
        data = page.evaluate(
            """() => {
              const ver = (document.documentElement.innerHTML.match(/cindemir-seo-fixes\\s+([0-9.]+)/)||[])[1]||'';
              const arts = [...document.querySelectorAll('article.post-entry')];
              const titles = arts.map(a=>{
                const h = a.querySelector('h2 a, h1 a, .entry-title a');
                return (h&&h.textContent||'').trim();
              }).filter(Boolean);
              const cyr = titles.filter(t=>/\\p{Script=Cyrillic}/u.test(t));
              const cjk = titles.filter(t=>/[\\u4e00-\\u9fff]/.test(t));
              const latin = titles.filter(t=>!/\\p{Script=Cyrillic}/u.test(t) && !/[\\u4e00-\\u9fff]/.test(t));
              return {
                ver, count: arts.length, cyr: cyr.length, cjk: cjk.length, latin: latin.length,
                firstCyr: cyr.slice(0,3), firstLatin: latin.slice(0,3),
                lang: document.documentElement.lang
              };
            }"""
        )
        page.screenshot(path=str(ROOT / "fixes/articles-en-1929.png"), full_page=False)
        print("EN_RESULT", data, flush=True)

        page.goto(f"{SITE}/articles/?lang=ru&nocache={t}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(3500)
        ru = page.evaluate(
            """() => {
              const ver = (document.documentElement.innerHTML.match(/cindemir-seo-fixes\\s+([0-9.]+)/)||[])[1]||'';
              const arts = [...document.querySelectorAll('article.post-entry')];
              const titles = arts.map(a=>{
                const h = a.querySelector('h2 a, h1 a, .entry-title a');
                return (h&&h.textContent||'').trim();
              }).filter(Boolean);
              const cyr = titles.filter(t=>/\\p{Script=Cyrillic}/u.test(t));
              const latin = titles.filter(t=>!/\\p{Script=Cyrillic}/u.test(t));
              return {ver, count: arts.length, cyr: cyr.length, latin: latin.length, lang: document.documentElement.lang};
            }"""
        )
        print("RU_RESULT", ru, flush=True)
        b.close()

    ok = (
        data.get("ver") == "1.9.29"
        and data.get("cyr", 1) == 0
        and data.get("cjk", 1) == 0
        and data.get("latin", 0) > 0
        and ru.get("latin", 1) == 0
        and ru.get("cyr", 0) > 0
    )
    print("OK" if ok else "FAIL", flush=True)
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
