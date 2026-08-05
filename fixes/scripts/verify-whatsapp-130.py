#!/usr/bin/env python3
import os, shutil, tempfile, time, re, sys, traceback
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
LOG = SHOT / "wa130-verify.log"


def log(msg):
    line = str(msg)
    print(line, flush=True)
    with LOG.open("a") as f:
        f.write(line + "\n")


def main():
    LOG.write_text("")
    log("START verify")
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="wa-v3-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            shutil.copytree(s, d) if s.is_dir() else shutil.copy2(s, d)
            log(f"copied {item}")
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
            log(f"admin url={page.url}")

            # Read live file via File Manager "Code Editor" or get file
            page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
            page.wait_for_timeout(5000)
            for name in ["wp-content", "mu-plugins"]:
                for frame in [page, *page.frames]:
                    loc = frame.locator(f"text={name}")
                    if loc.count():
                        try:
                            loc.first.dblclick(timeout=5000)
                            page.wait_for_timeout(1500)
                            log(f"nav {name}")
                            break
                        except Exception as e:
                            log(f"nav fail {name} {e}")

            # Right-click download contact fixes
            target = None
            for frame in [page, *page.frames]:
                loc = frame.locator("span:text-is('cindemir-contact-fixes.php'), div:text-is('cindemir-contact-fixes.php'), text=cindemir-contact-fixes.php")
                if loc.count():
                    target = loc.first
                    log("found contact-fixes in FM")
                    break
            if target is not None:
                try:
                    with page.expect_download(timeout=20000) as dlinfo:
                        target.click(button="right", timeout=5000)
                        page.wait_for_timeout(800)
                        clicked = False
                        for fr in [page, *page.frames]:
                            # elFinder context menu
                            for sel in [".elfinder-contextmenu-item:has-text('Download')", "text=Download", "text=Get files"]:
                                if fr.locator(sel).count():
                                    fr.locator(sel).first.click(timeout=3000)
                                    clicked = True
                                    log(f"clicked {sel}")
                                    break
                            if clicked:
                                break
                        if not clicked:
                            raise RuntimeError("no download menu")
                    dl = dlinfo.value
                    out = SHOT / "live-cindemir-contact-fixes.php"
                    dl.save_as(str(out))
                    text = out.read_text(errors="ignore")
                    ver = re.search(r"Version:\s*([\d.]+)", text)
                    phone = re.search(r"WHATSAPP_PHONE\s*=\s*'(\d+)'", text)
                    log(f"LIVE ver={ver.group(1) if ver else None} phone={phone.group(1) if phone else None} size={out.stat().st_size}")
                    log(f"LIVE rewrite={('rewrite_whatsapp_numbers' in text)} flex={('display:flex!important' in text)}")
                except Exception as e:
                    log(f"download error: {e}")
                    page.screenshot(path=str(SHOT / "wa130-fm.png"))

            # Purge WP Rocket thoroughly
            page.goto(f"{SITE}/wp-admin/", timeout=60000)
            page.wait_for_timeout(1500)
            if page.locator("#wp-admin-bar-wp-rocket").count():
                page.hover("#wp-admin-bar-wp-rocket")
                page.wait_for_timeout(600)
                for label in ["Clear cache", "Clear and preload cache", "Clear used CSS"]:
                    loc = page.locator(f"text={label}")
                    if loc.count():
                        try:
                            loc.first.click(force=True, timeout=4000)
                            page.wait_for_timeout(4000)
                            log(f"purged {label}")
                        except Exception as e:
                            log(f"purge fail {label} {e}")

            # Front check logged-in
            page.goto(f"{SITE}/?check={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
            page.wait_for_timeout(4000)
            html = page.content()
            Path("/tmp/wa_browser.html").write_text(html)
            log(f"html_len={len(html)} old={html.count('905325680647')} new={html.count('902165506775')}")
            log(f"fallback={'cindemir-wa-fallback' in html} flex={'display:flex!important' in html and 'cindemir-wa-fallback' in html}")
            m = re.search(r"data-settings='([^']+)'", html)
            if m:
                log(f"settings={m.group(1)[:240]}")
            for m in re.finditer(r"wa\.me/[0-9]+", html):
                log(f"wa={m.group(0)}")
            fb = page.locator("#cindemir-wa-fallback")
            jc_show = page.locator(".joinchat.joinchat--show")
            log(f"fb_count={fb.count()} fb_vis={fb.first.is_visible() if fb.count() else None}")
            log(f"jc_show={jc_show.count()} jc_vis={jc_show.first.is_visible() if jc_show.count() else None}")
            if fb.count():
                log(f"href={fb.first.get_attribute('href')}")
            page.screenshot(path=str(SHOT / "wa130-home2.png"))
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
        raise
