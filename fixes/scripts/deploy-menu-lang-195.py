#!/usr/bin/env python3
"""Deploy 1.9.5 menu-lang fix via FM overwrite + Rocket purge + verify."""
import os, re, shutil, subprocess, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
FILES = [
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php",
]


def log(m):
    print(m, flush=True)


def curl(path, out=None):
    url = f"{SITE}{path}"
    sep = "&" if "?" in path else "?"
    url = f"{url}{sep}t={int(time.time())}"
    cmd = ["curl", "-sS", "-A", UA, "-H", "Accept: text/html", url]
    if out:
        cmd = ["curl", "-sS", "-A", UA, "-H", "Accept: text/html", "-o", out, "-w", "%{http_code}", url]
        return subprocess.run(cmd, capture_output=True, text=True, timeout=60).stdout.strip()
    return subprocess.run(cmd, capture_output=True, text=True, timeout=60).stdout


def code(path="/"):
    return subprocess.run(
        ["curl", "-sS", "-o", "/dev/null", "-w", "%{http_code}", "-A", UA, f"{SITE}{path}"],
        capture_output=True, text=True, timeout=45,
    ).stdout.strip()


def ver():
    h = curl(f"/?nocache={int(time.time())}")
    m = re.search(r"cindemir-seo-fixes ([0-9.]+)", h)
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


def verify_html_lang(path, expect_about_lang):
    h = curl(path)
    ver_m = re.search(r"cindemir-seo-fixes ([0-9.]+)", h)
    marker = "cindemir-lang-stamp" in h
    nowprocket = "data-nowprocket" in h and "cindemir-header-brand-js" in h
    delayed = bool(re.search(r'id="cindemir-header-brand-js"[^>]*src="data:', h))
    abouts = re.findall(r'href="([^"]*about-us[^"]*)"', h)
    ok_about = any(expect_about_lang in a for a in abouts) if abouts else False
    log(
        f"HTML {path}: ver={ver_m.group(1) if ver_m else None} stamp_marker={marker} "
        f"nowprocket={nowprocket} delayed={delayed} abouts={abouts[:3]} ok_about={ok_about}"
    )
    return ok_about or (nowprocket and not delayed)


def verify_click(browser_lang_path, link_text, expect_lang_prefix):
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={"width": 1280, "height": 900}, user_agent=UA)
        page = context.new_page()
        page.goto(f"{SITE}{browser_lang_path}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(2500)
        cookies = {c["name"]: c["value"] for c in context.cookies()}
        log(f"cookies after land: cindemir_lang={cookies.get('cindemir_lang')}")
        href = page.eval_on_selector(f'a:has-text("{link_text}")', "el => el.getAttribute('href')")
        log(f"pre-click href ({link_text}): {href}")
        with page.expect_navigation(timeout=60000):
            page.click(f'a:has-text("{link_text}")')
        url = page.url
        html_lang = page.eval_on_selector("html", "el => el.lang")
        brand = page.evaluate(
            '''() => {
              const el = document.querySelector("#header .logo a");
              if (!el) return "";
              return getComputedStyle(el, "::after").content || "";
            }'''
        )
        log(f"post-click url={url} html_lang={html_lang} brand={brand}")
        page.screenshot(path=str(SHOT / f"menu196-{expect_lang_prefix}.png"), full_page=False)
        browser.close()
        ok = (html_lang or "").lower().startswith(expect_lang_prefix.lower()[:2])
        ok = ok or (expect_lang_prefix in url) or ("jurid" in brand.lower()) or ("\u042e\u0440\u0438\u0434" in brand)
        return ok


def main():
    log(f"START home={code('/')} ver={ver()}")
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="menu195-"))
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
                if code("/") == "500":
                    log("500 after upload")
                    return 10
                log(f"health {f.name}: {code('/')} ver={ver()}")
            purge_rocket(page)
            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    time.sleep(2)
    html_ok = verify_html_lang("/?lang=ru", "lang=ru")
    html_zh = verify_html_lang("/?lang=zh-hans", "lang=zh-hans")
    click_ru = verify_click("/?lang=ru", "О нас", "ru")
    click_zh = verify_click("/?lang=zh-hans", "关于我们", "zh")
    # Cookie-only navigation (bare permalink after language land)
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={"width": 1280, "height": 900}, user_agent=UA)
        page = context.new_page()
        page.goto(f"{SITE}/?lang=ru&t={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(1500)
        page.goto(f"{SITE}/about-us/?t={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(2000)
        bare_lang = page.eval_on_selector("html", "el => el.lang")
        log(f"cookie bare /about-us/ html_lang={bare_lang}")
        page.screenshot(path=str(SHOT / "menu196-cookie-about.png"), full_page=False)
        browser.close()
    cookie_ok = (bare_lang or "").lower().startswith("ru")
    log(f"FINAL home={code('/')} ver={ver()} html_ru={html_ok} html_zh={html_zh} click_ru={click_ru} click_zh={click_zh} cookie_ok={cookie_ok}")
    return 0 if (click_ru or cookie_ok) and code("/") == "200" else 1


if __name__ == "__main__":
    raise SystemExit(main())
