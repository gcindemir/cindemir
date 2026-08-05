#!/usr/bin/env python3
import os, shutil, tempfile, time, re, traceback
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
LOG = SHOT / "wa130-verify2.log"


def log(msg):
    print(msg, flush=True)
    with LOG.open("a") as f:
        f.write(str(msg) + "\n")


def main():
    LOG.write_text("")
    log("START")
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="wa-v4-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            shutil.copytree(s, d) if s.is_dir() else shutil.copy2(s, d)
    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp),
                headless=False,
                channel="chrome",
                args=["--no-sandbox", "--disable-dev-shm-usage"],
                env={**os.environ, "DISPLAY": ":1"},
                viewport={"width": 1280, "height": 800},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            page.goto(f"{SITE}/wp-admin/", timeout=120000)
            page.wait_for_timeout(2500)
            if "login" in page.url.lower():
                page.goto(f"{SITE}/wp-login.php", timeout=120000)
                page.wait_for_timeout(1200)
                page.click("#wp-submit")
                page.wait_for_timeout(7000)
            log(f"admin={page.url}")

            # Open live file in FM editor and read content
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

            opened = False
            for frame in [page, *page.frames]:
                loc = frame.locator("text=cindemir-contact-fixes.php")
                if not loc.count():
                    continue
                try:
                    loc.first.dblclick(timeout=5000)
                    page.wait_for_timeout(3500)
                    opened = True
                    log("opened contact-fixes editor")
                    break
                except Exception as e:
                    log(f"open fail {e}")
            page.screenshot(path=str(SHOT / "wa130-editor.png"))

            # Try grab ace/textarea content
            content = ""
            for frame in [page, *page.frames]:
                for sel in [".ace_content", "textarea", ".CodeMirror"]:
                    if frame.locator(sel).count():
                        try:
                            content = frame.locator(sel).first.inner_text(timeout=3000)
                            if content and "WHATSAPP" in content:
                                log(f"editor content len={len(content)}")
                                break
                        except Exception:
                            pass
                if content:
                    break
            if not content:
                # JS fallback
                for frame in [page, *page.frames]:
                    try:
                        content = frame.evaluate(
                            """() => {
                              if (window.ace && document.querySelector('.ace_editor')) {
                                const el = document.querySelector('.ace_editor');
                                const ed = ace.edit(el);
                                return ed.getValue();
                              }
                              const ta = document.querySelector('textarea');
                              return ta ? ta.value : '';
                            }"""
                        )
                        if content:
                            log(f"js content len={len(content)}")
                            break
                    except Exception as e:
                        log(f"js read fail {e}")
            if content:
                Path(SHOT / "live-cindemir-contact-fixes.php").write_text(content)
                ver = re.search(r"Version:\s*([\d.]+)", content)
                phone = re.search(r"WHATSAPP_PHONE\s*=\s*'(\d+)'", content)
                log(f"LIVE ver={ver.group(1) if ver else None} phone={phone.group(1) if phone else None}")
                log(f"rewrite={'rewrite_whatsapp_numbers' in content} flex={'display:flex!important' in content}")

            # Purge rocket
            page.goto(f"{SITE}/wp-admin/", timeout=60000)
            page.wait_for_timeout(1500)
            if page.locator("#wp-admin-bar-wp-rocket").count():
                page.hover("#wp-admin-bar-wp-rocket")
                page.wait_for_timeout(500)
                if page.locator("text=Clear cache").count():
                    page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                    page.wait_for_timeout(4500)
                    log("rocket cleared")

            # Also hit rocket admin ajax style if available
            page.goto(f"{SITE}/wp-admin/admin.php?page=wprocket", timeout=60000)
            page.wait_for_timeout(2500)
            page.screenshot(path=str(SHOT / "wa130-rocket2.png"))
            for sel in ["#wpr-action-clear_cache", "button:has-text('Clear cache')", "text=Clear cache"]:
                if page.locator(sel).count():
                    try:
                        page.locator(sel).first.click(force=True, timeout=4000)
                        page.wait_for_timeout(4000)
                        log(f"rocket page {sel}")
                    except Exception as e:
                        log(f"rocket page fail {e}")

            # Logged-in front
            page.goto(f"{SITE}/?check={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
            page.wait_for_timeout(5000)
            html = page.content()
            Path("/tmp/wa_browser.html").write_text(html)
            log(f"html_len={len(html)} old={html.count('905325680647')} new={html.count('902165506775')}")
            log(f"fallback={'cindemir-wa-fallback' in html}")
            log(f"flex_css={'display:flex!important' in html}")
            m = re.search(r"data-settings='([^']+)'", html)
            if m:
                log(f"settings={m.group(1)[:260]}")
            for m in re.finditer(r"wa\.me/[0-9]+", html):
                log(f"wa={m.group(0)}")
            fb = page.locator("#cindemir-wa-fallback")
            jc = page.locator(".joinchat.joinchat--show, .joinchat")
            log(f"fb={fb.count()} vis={fb.first.is_visible() if fb.count() else None} href={fb.first.get_attribute('href') if fb.count() else None}")
            log(f"jc={jc.count()}")
            page.screenshot(path=str(SHOT / "wa130-home2.png"))

            # Wait for joinchat delay and recheck
            page.wait_for_timeout(4000)
            html2 = page.content()
            log(f"after4s old={html2.count('905325680647')} new={html2.count('902165506775')} jc_show={page.locator('.joinchat.joinchat--show').count()}")
            page.screenshot(path=str(SHOT / "wa130-home3.png"))
            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)
    log("DONE")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception:
        traceback.print_exc()
        with LOG.open("a") as f:
            f.write(traceback.format_exc())
        raise SystemExit(1)
