#!/usr/bin/env python3
import os, shutil, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
PHONE = "902165506775"


def log(m):
    print(m, flush=True)


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="jcfix-"))
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
                viewport={"width": 1280, "height": 900},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            page.goto(f"{SITE}/wp-admin/", timeout=120000)
            page.wait_for_timeout(2000)
            if "login" in page.url.lower():
                page.goto(f"{SITE}/wp-login.php", timeout=120000)
                page.wait_for_timeout(1000)
                page.click("#wp-submit")
                page.wait_for_timeout(7000)
            page.goto(f"{SITE}/wp-admin/admin.php?page=joinchat", timeout=60000)
            page.wait_for_timeout(3000)

            # Open country dropdown
            for sel in [".iti__selected-flag", ".iti__selected-country", "button.iti__selected-country"]:
                if page.locator(sel).count():
                    page.locator(sel).first.click(timeout=3000)
                    page.wait_for_timeout(800)
                    log(f"opened {sel}")
                    break
            page.screenshot(path=str(SHOT / "wa132-country-open.png"))

            # Search Turkey
            for sel in [".iti__search-input", "input.iti__search-input", ".iti__country-list input"]:
                if page.locator(sel).count():
                    page.locator(sel).first.fill("Turkey")
                    page.wait_for_timeout(600)
                    log("searched Turkey")
                    break

            clicked = False
            for sel in [
                "li.iti__country[data-country-code='tr']",
                "li.iti__country[data-dial-code='90']",
                ".iti__country-name:text-is('Turkey')",
                "text=Turkey (Türkiye)",
                "text=+90",
            ]:
                if page.locator(sel).count():
                    try:
                        page.locator(sel).first.click(timeout=3000)
                        clicked = True
                        log(f"country click {sel}")
                        page.wait_for_timeout(800)
                        break
                    except Exception as e:
                        log(f"country fail {sel} {e}")
            if not clicked:
                # JS fallback
                page.evaluate(
                    """() => {
                      const items = [...document.querySelectorAll('li.iti__country')];
                      const tr = items.find(i => i.getAttribute('data-country-code') === 'tr');
                      if (tr) tr.click();
                    }"""
                )
                log("js country click")
                page.wait_for_timeout(800)

            # Set national number and force hidden to +90...
            page.evaluate(
                """(phone) => {
                  const hidden = document.querySelector('input[name="joinchat[telephone]"]');
                  const visible = document.querySelector('.iti input[type="tel"], input.iti__tel-input');
                  if (visible) {
                    visible.focus();
                    visible.value = '';
                    visible.value = '2165506775';
                    visible.dispatchEvent(new Event('input', {bubbles:true}));
                    visible.dispatchEvent(new Event('change', {bubbles:true}));
                    visible.dispatchEvent(new Event('blur', {bubbles:true}));
                  }
                  if (hidden) {
                    hidden.value = '+' + phone;
                    hidden.setAttribute('value', '+' + phone);
                    hidden.dispatchEvent(new Event('input', {bubbles:true}));
                    hidden.dispatchEvent(new Event('change', {bubbles:true}));
                  }
                  return {
                    hidden: hidden && hidden.value,
                    visible: visible && visible.value,
                    flag: (document.querySelector('.iti__selected-flag')||{}).getAttribute('title')
                      || (document.querySelector('.iti__selected-country')||{}).getAttribute('title')
                      || (document.querySelector('.iti__selected-country-primary')||{}).textContent
                  };
                }""",
                PHONE,
            )
            page.wait_for_timeout(500)
            # Save via form submit
            page.evaluate(
                """(phone) => {
                  const hidden = document.querySelector('input[name="joinchat[telephone]"]');
                  if (hidden) hidden.value = '+' + phone;
                }""",
                PHONE,
            )
            if page.locator("#submit").count():
                page.locator("#submit").first.click()
                page.wait_for_timeout(4000)
                log("saved")
            page.screenshot(path=str(SHOT / "wa132-joinchat-tr.png"))
            val = page.evaluate(
                """() => {
                  const hidden = document.querySelector('input[name="joinchat[telephone]"]');
                  const visible = document.querySelector('.iti input[type="tel"], input.iti__tel-input');
                  return {hidden: hidden && hidden.value, visible: visible && visible.value};
                }"""
            )
            log(f"after {val}")

            # Delete fatal tracer if present
            page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
            page.wait_for_timeout(4000)
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
                loc = frame.locator("text=aaa-cindemir-fatal-trace.php")
                if not loc.count():
                    continue
                loc.first.click(button="right", timeout=3000)
                page.wait_for_timeout(500)
                for fr in [page, *page.frames]:
                    if fr.locator("text=Delete").count():
                        fr.locator("text=Delete").first.click(timeout=2000)
                        page.wait_for_timeout(700)
                        for fr2 in [page, *page.frames]:
                            for sel in ["button:has-text('YES')", "text=YES"]:
                                if fr2.locator(sel).count():
                                    try:
                                        fr2.locator(sel).first.click(timeout=2000)
                                        log("tracer deleted")
                                    except Exception:
                                        pass
                        break
                break

            # Final front check
            page.goto(f"{SITE}/?final={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
            page.wait_for_timeout(3000)
            fb = page.locator("#cindemir-wa-fallback")
            jc = page.locator(".joinchat")
            log(f"fb_vis={fb.first.is_visible() if fb.count() else None} href={fb.first.get_attribute('href') if fb.count() else None}")
            log(f"settings={jc.first.get_attribute('data-settings') if jc.count() else None}")
            page.screenshot(path=str(SHOT / "wa132-final-home.png"))
            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)
    log("DONE")


if __name__ == "__main__":
    main()
