#!/usr/bin/env python3
"""Deploy contact-fixes 1.3.2, fix JoinChat admin to TR +90, verify button visible."""
import os, re, shutil, subprocess, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
ACC = "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
FILE = ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php"
PHONE = "902165506775"
OLD = "905325680647"


def log(m):
    print(m, flush=True)


def fetch(url):
    return subprocess.run(
        ["curl", "-sS", "-A", UA, "-H", f"Accept: {ACC}", url],
        capture_output=True, text=True, timeout=60,
    ).stdout


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
                    page.wait_for_timeout(1400)
                    log(f"nav {name}")
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
        ins.first.set_input_files(str(fpath))
        page.wait_for_timeout(2000)
        for fr in [page, *page.frames]:
            for sel in ["button:has-text('YES')", "text=YES"]:
                if fr.locator(sel).count():
                    try:
                        fr.locator(sel).first.click(timeout=3000)
                        page.wait_for_timeout(1000)
                        log("YES")
                    except Exception:
                        pass
        page.wait_for_timeout(8000)
        log(f"uploaded {fpath.name}")
        return True
    return False


def fix_joinchat_admin(page):
    page.goto(f"{SITE}/wp-admin/admin.php?page=joinchat", timeout=60000)
    page.wait_for_timeout(3000)
    page.screenshot(path=str(SHOT / "wa132-joinchat-before.png"))
    # Force hidden/api telephone field to the office number with +90 country.
    changed = page.evaluate(
        """(phone) => {
          const hidden = document.querySelector('input[name="joinchat[telephone]"]');
          const visible = document.querySelector('.iti input[type="tel"], input.iti__tel-input, #joinchat_phone');
          if (hidden) {
            hidden.value = '+' + phone;
            hidden.dispatchEvent(new Event('input', {bubbles:true}));
            hidden.dispatchEvent(new Event('change', {bubbles:true}));
          }
          if (visible) {
            // Local TR national number after +90
            visible.value = '216 550 6775';
            visible.dispatchEvent(new Event('input', {bubbles:true}));
            visible.dispatchEvent(new Event('change', {bubbles:true}));
          }
          // Prefer Turkey in intl-tel-input if present
          const flag = document.querySelector('.iti__selected-flag, .iti__selected-country');
          if (flag) flag.click();
          return {hidden: hidden ? hidden.value : null, visible: visible ? visible.value : null};
        }""",
        PHONE,
    )
    log(f"joinchat fields {changed}")
    page.wait_for_timeout(800)
    # Click Turkey in country list if open
    for sel in [
        "li.iti__country[data-country-code='tr']",
        "text=Turkey",
        "text=Türkiye",
        "[data-country-code='tr']",
    ]:
        if page.locator(sel).count():
            try:
                page.locator(sel).first.click(timeout=2000)
                log(f"picked country {sel}")
                page.wait_for_timeout(500)
                break
            except Exception:
                pass
    # Re-set values after country change
    page.evaluate(
        """(phone) => {
          const hidden = document.querySelector('input[name="joinchat[telephone]"]');
          const visible = document.querySelector('.iti input[type="tel"], input.iti__tel-input, #joinchat_phone');
          if (hidden) hidden.value = '+' + phone;
          if (visible) {
            visible.value = '2165506775';
            visible.dispatchEvent(new Event('input', {bubbles:true}));
            visible.dispatchEvent(new Event('change', {bubbles:true}));
          }
          if (hidden) {
            hidden.value = '+' + phone;
            hidden.dispatchEvent(new Event('change', {bubbles:true}));
          }
        }""",
        PHONE,
    )
    if page.locator("#submit").count():
        page.locator("#submit").first.click()
        page.wait_for_timeout(3500)
        log("joinchat saved")
    page.screenshot(path=str(SHOT / "wa132-joinchat-after.png"))


def main():
    log("START 1.3.2")
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="wa132-"))
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
                viewport={"width": 1440, "height": 900},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            if not login(page):
                return 2
            if not fm_upload(page, FILE):
                return 3
            # purge rocket
            page.goto(f"{SITE}/wp-admin/", timeout=60000)
            page.wait_for_timeout(1200)
            if page.locator("#wp-admin-bar-wp-rocket").count():
                page.hover("#wp-admin-bar-wp-rocket")
                page.wait_for_timeout(400)
                if page.locator("text=Clear cache").count():
                    try:
                        page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                        page.wait_for_timeout(4000)
                        log("rocket purged")
                    except Exception:
                        pass
            try:
                fix_joinchat_admin(page)
            except Exception as e:
                log(f"joinchat admin err {e}")

            # Anonymous-like front via clean context page in same browser... use logged front.
            page.goto(f"{SITE}/?v={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
            page.wait_for_timeout(3500)
            html = page.content()
            Path("/tmp/wa132_logged.html").write_text(html)
            fb = page.locator("#cindemir-wa-fallback")
            jc = page.locator(".joinchat")
            log(f"old={html.count(OLD)} wa_new={html.count('wa.me/'+PHONE)} flex={'right:20px' in html and 'cindemir-wa-fallback' in html}")
            log(f"fb_count={fb.count()} fb_vis={fb.first.is_visible() if fb.count() else None} href={fb.first.get_attribute('href') if fb.count() else None}")
            log(f"jc_count={jc.count()} jc_vis={jc.first.is_visible() if jc.count() else None}")
            if jc.count():
                log(f"settings={jc.first.get_attribute('data-settings')}")
            page.screenshot(path=str(SHOT / "wa132-home.png"))
            # Scroll bottom-right into view explicitly
            page.evaluate("window.scrollTo(0,0)")
            page.wait_for_timeout(500)
            page.screenshot(path=str(SHOT / "wa132-home-top.png"))
            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    h = fetch(f"{SITE}/?wa132={int(time.time())}")
    Path("/tmp/wa132_anon.html").write_text(h)
    info = {
        "len": len(h),
        "old": h.count(OLD),
        "wa_new": h.count(f"wa.me/{PHONE}"),
        "wa_old": h.count(f"wa.me/{OLD}"),
        "tel": f'"telephone":"{PHONE}"' in h,
        "right": "right:20px" in h and "cindemir-wa-fallback" in h,
    }
    log(f"anon {info}")

    # Headless visual check without login cookies
    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        page = b.new_page(viewport={"width": 1440, "height": 900}, user_agent=UA)
        page.goto(f"{SITE}/?anon={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(4000)
        fb = page.locator("#cindemir-wa-fallback")
        jc = page.locator(".joinchat")
        log(f"headless fb_vis={fb.first.is_visible() if fb.count() else None} jc_vis={jc.first.is_visible() if jc.count() else None}")
        if fb.count():
            box = fb.first.bounding_box()
            log(f"fb_box={box} href={fb.first.get_attribute('href')}")
        page.screenshot(path=str(SHOT / "wa132-anon.png"))
        b.close()

    ok = info["len"] > 10000 and info["wa_old"] == 0 and info["wa_new"] > 0 and info["tel"] and info["right"]
    log("PASS" if ok else "FAIL")
    return 0 if ok else 4


if __name__ == "__main__":
    raise SystemExit(main())
