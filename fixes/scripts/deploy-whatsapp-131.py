#!/usr/bin/env python3
"""Deploy contact-fixes 1.3.1 + fatal tracer; purge caches; verify nocache HTML."""
import os, re, shutil, subprocess, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
ACC = "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
FILES = [
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php",
    ROOT / "fixes/mu-plugins/aaa-cindemir-fatal-trace.php",
]
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
                    page.wait_for_timeout(1500)
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


def purge_all(page):
    page.goto(f"{SITE}/wp-admin/", timeout=60000)
    page.wait_for_timeout(1500)
    # WP Rocket
    if page.locator("#wp-admin-bar-wp-rocket").count():
        page.hover("#wp-admin-bar-wp-rocket")
        page.wait_for_timeout(500)
        if page.locator("text=Clear cache").count():
            try:
                page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                page.wait_for_timeout(4000)
                log("rocket purged")
            except Exception as e:
                log(f"rocket fail {e}")
    # Bluehost / Hosting cache if present
    for sel in [
        "#wp-admin-bar-bluehost clear_cache",
        "text=Clear All Caches",
        "text=Clear cache",
        "#wp-admin-bar-wp-cloudflare-super-page-cache",
    ]:
        if page.locator(sel).count():
            try:
                page.locator(sel).first.click(force=True, timeout=3000)
                page.wait_for_timeout(2000)
                log(f"extra purge {sel}")
            except Exception:
                pass
    # Visit hosting panel page commonly used by Newfold
    for path in [
        "/wp-admin/admin.php?page=bluehost#/home",
        "/wp-admin/admin.php?page=hosting",
        "/wp-admin/options-general.php?page=clear-cache",
    ]:
        try:
            page.goto(f"{SITE}{path}", timeout=30000)
            page.wait_for_timeout(1500)
            for label in ["Clear All Caches", "Clear Cache", "Purge All", "Empty Cache"]:
                if page.locator(f"text={label}").count():
                    try:
                        page.locator(f"text={label}").first.click(force=True, timeout=3000)
                        page.wait_for_timeout(3000)
                        log(f"host purge {label} via {path}")
                    except Exception:
                        pass
        except Exception:
            pass


def verify(tag):
    # nocache query bypasses bluehost page cache → exercises PHP
    h = fetch(f"{SITE}/?wa_verify={int(time.time())}&{tag}=1")
    info = {
        "len": len(h),
        "old": h.count(OLD),
        "new_tel": h.count(f'telephone":"{PHONE}"') + h.count(f"telephone':'{PHONE}'"),
        "wa_new": h.count(f"wa.me/{PHONE}"),
        "wa_old": h.count(f"wa.me/{OLD}"),
        "flex": "display:flex!important" in h and "cindemir-wa-fallback" in h,
        "fallback": "cindemir-wa-fallback" in h,
    }
    log(f"verify[{tag}] {info}")
    return info, h


def main():
    log("START deploy 1.3.1")
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="wa131-"))
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
                log("LOGIN FAIL"); return 2
            for f in FILES:
                if not fm_upload(page, f):
                    log(f"UPLOAD FAIL {f}"); return 3
            purge_all(page)

            # Update JoinChat settings UI if needed
            try:
                page.goto(f"{SITE}/wp-admin/admin.php?page=joinchat", timeout=60000)
                page.wait_for_timeout(2500)
                page.screenshot(path=str(SHOT / "wa131-joinchat-admin.png"))
                tel = page.locator("#joinchat_settings\\\\[telephone\\\\], input[name*='telephone']")
                if tel.count():
                    tel.first.fill(PHONE)
                    if page.locator("#submit, input[type=submit]").count():
                        page.locator("#submit, input[type=submit]").first.click()
                        page.wait_for_timeout(3000)
                        log("joinchat admin saved")
            except Exception as e:
                log(f"joinchat admin skip {e}")

            # Front as logged-in
            page.goto(f"{SITE}/?logged={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
            page.wait_for_timeout(4000)
            html = page.content()
            Path("/tmp/wa131_logged.html").write_text(html)
            log(f"logged_len={len(html)} old={html.count(OLD)} wa_new={html.count('wa.me/'+PHONE)} flex={'display:flex!important' in html}")
            fb = page.locator("#cindemir-wa-fallback")
            log(f"fb_vis={fb.first.is_visible() if fb.count() else None} href={fb.first.get_attribute('href') if fb.count() else None}")
            page.screenshot(path=str(SHOT / "wa131-home.png"))
            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    time.sleep(2)
    info, h = verify("anon")
    Path("/tmp/wa131_anon.html").write_text(h)

    # Check fatal log via FM? skip; use anon health
    ok = info["len"] > 10000 and info["wa_old"] == 0 and info["wa_new"] > 0 and info["flex"]
    log("PASS" if ok else "FAIL")
    return 0 if ok else 4


if __name__ == "__main__":
    raise SystemExit(main())
