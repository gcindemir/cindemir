#!/usr/bin/env python3
"""Deploy 1.9.8 language-switcher fix and verify EN/RU/ZH flag switching."""
import os, re, shutil, subprocess, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
FILES = [ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php"]


def log(m):
    print(m, flush=True)


def code(path="/"):
    return subprocess.run(
        ["curl", "-sS", "-o", "/dev/null", "-w", "%{http_code}", "-A", UA, f"{SITE}{path}"],
        capture_output=True, text=True, timeout=45,
    ).stdout.strip()


def curl(path):
    sep = "&" if "?" in path else "?"
    return subprocess.run(
        ["curl", "-sS", "-A", UA, "-H", "Accept: text/html", f"{SITE}{path}{sep}t={int(time.time())}"],
        capture_output=True, text=True, timeout=60,
    ).stdout


def ver():
    m = re.search(r"cindemir-seo-fixes ([0-9.]+)", curl("/"))
    return m.group(1) if m else None


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


def purge_rocket(page):
    page.goto(f"{SITE}/wp-admin/", timeout=60000)
    page.wait_for_timeout(1500)
    if page.locator("#wp-admin-bar-wp-rocket").count():
        page.hover("#wp-admin-bar-wp-rocket")
        page.wait_for_timeout(500)
        if page.locator("text=Clear cache").count():
            try:
                page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                page.wait_for_timeout(4000)
                log("rocket purged")
                return True
            except Exception:
                pass
    log("rocket purge skipped")
    return False


def switcher_hrefs(html):
    out = {}
    for m in re.finditer(
        r"language_([a-z0-9\-]+)[^>]*>\s*<a href=(['\"])([^'\"]*)\2",
        html,
        re.I,
    ):
        out[m.group(1).lower()] = m.group(3)
    return out


def switcher_meta(html):
    return {
        "ver": (re.search(r"cindemir-seo-fixes ([0-9.]+)", html) or [None, None])[1]
        if False
        else (m.group(1) if (m := re.search(r"cindemir-seo-fixes ([0-9.]+)", html)) else None),
        "swfix": bool(re.search(r"cindemir-swfix:", html)),
        "boot_js": "cindemir-lang-switch" in html,
        "hrefs": switcher_hrefs(html),
    }


def main():
    log(f"START home={code('/')} ver={ver()}")
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="lang198-"))
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
                log("LOGIN FAIL")
                return 2
            for f in FILES:
                if not fm_upload(page, f):
                    log(f"FAIL {f.name}")
                    return 3
                log(f"health {f.name}: {code('/')} ver={ver()}")
            purge_rocket(page)
            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    time.sleep(2)
    meta = switcher_meta(curl("/"))
    log(f"EN page switcher meta: {meta}")
    hrefs = meta["hrefs"]
    hrefs_ok = (
        "lang=ru" in hrefs.get("ru", "")
        and "lang=zh-hans" in hrefs.get("zh-hans", "")
        and "lang=" not in hrefs.get("en", "")
    ) or meta["boot_js"]

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page(viewport={"width": 1280, "height": 900}, user_agent=UA)
        page.goto(f"{SITE}/?t={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(2500)
        # click Russian flag (boot script intercepts even if href bare)
        page.click("li.language_ru a, li.language_ru")
        page.wait_for_timeout(3500)
        ru_url, ru_lang = page.url, page.eval_on_selector("html", "el => el.lang")
        log(f"after RU flag: url={ru_url} lang={ru_lang}")
        page.screenshot(path=str(SHOT / "lang199-ru.png"), full_page=False)
        # Chinese
        page.click("li.language_zh-hans a, li.language_zh-hans")
        page.wait_for_timeout(3500)
        zh_url, zh_lang = page.url, page.eval_on_selector("html", "el => el.lang")
        log(f"after ZH flag: url={zh_url} lang={zh_lang}")
        page.screenshot(path=str(SHOT / "lang199-zh.png"), full_page=False)
        # English
        page.click("li.language_en a, li.language_en")
        page.wait_for_timeout(3500)
        en_url, en_lang = page.url, page.eval_on_selector("html", "el => el.lang")
        log(f"after EN flag: url={en_url} lang={en_lang}")
        page.screenshot(path=str(SHOT / "lang199-en.png"), full_page=False)
        browser.close()

    ok = ru_lang.lower().startswith("ru") and zh_lang.lower().startswith("zh") and en_lang.lower().startswith("en")
    log(f"FINAL home={code('/')} ver={ver()} hrefs_ok={hrefs_ok} switch_ok={ok}")
    return 0 if ok and code("/") == "200" else 1


if __name__ == "__main__":
    raise SystemExit(main())
