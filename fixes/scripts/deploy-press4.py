#!/usr/bin/env python3
"""Overwrite mu-plugins + plugin pack files via FM, purge Yoast press redirect in UI, setup-press."""
import os
import re
import shutil
import subprocess
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"

FILES_MU = [
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php",
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php",
]
FILES_PACK = [
    (ROOT / "fixes/plugins/cindemir-seo-pack/cindemir-seo-pack.php", ["wp-content", "plugins", "cindemir-seo-pack"]),
    (ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php", ["wp-content", "plugins", "cindemir-seo-pack", "includes"]),
    (ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php", ["wp-content", "plugins", "cindemir-seo-pack", "includes"]),
]


def log(m):
    print(m, flush=True)


def code(path="/"):
    r = subprocess.run(
        ["curl", "-sS", "-o", "/dev/null", "-w", "%{http_code}", f"{SITE}{path}"],
        capture_output=True,
        text=True,
        timeout=45,
    )
    return r.stdout.strip()


def redirect_target(path):
    r = subprocess.run(
        ["curl", "-sS", "-o", "/dev/null", "-w", "%{http_code} %{redirect_url}", f"{SITE}{path}"],
        capture_output=True,
        text=True,
        timeout=45,
    )
    return r.stdout.strip()


def site_version():
    r = subprocess.run(
        ["curl", "-sS", "-H", "Cache-Control: no-cache", f"{SITE}/?t={int(time.time())}"],
        capture_output=True,
        text=True,
        timeout=45,
    )
    m = re.search(r"cindemir-seo-fixes ([0-9.]+)", r.stdout)
    return m.group(1) if m else None


def curl_text(path, timeout=180):
    r = subprocess.run(["curl", "-sS", f"{SITE}{path}"], capture_output=True, text=True, timeout=timeout)
    return r.stdout


def shot(page, name):
    try:
        page.screenshot(path=str(SHOT / name), full_page=True)
        log(f"shot {name}")
    except Exception as e:
        log(f"shot fail {name}: {e}")


def login(page):
    page.goto(f"{SITE}/wp-admin/", timeout=120000)
    page.wait_for_timeout(3000)
    if "login" in page.url.lower():
        page.goto(f"{SITE}/wp-login.php", timeout=120000)
        page.wait_for_timeout(1500)
        page.click("#wp-submit")
        page.wait_for_timeout(8000)
    ok = "login" not in page.url.lower()
    log(f"login ok={ok}")
    return ok


def fm_goto(page, folders):
    page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
    page.wait_for_timeout(5500)
    for name in folders:
        ok = False
        for frame in [page, *page.frames]:
            loc = frame.locator(f"text={name}")
            if not loc.count():
                continue
            try:
                loc.first.dblclick(timeout=5000)
                page.wait_for_timeout(1800)
                log(f"nav {name}")
                ok = True
                break
            except Exception:
                continue
        if not ok:
            log(f"WARN miss {name}")


def click_yes(page):
    for _ in range(3):
        for frame in [page, *page.frames]:
            for sel in ["button:has-text('YES')", "text=YES", ".ui-button:has-text('YES')", "button:has-text('Yes')"]:
                loc = frame.locator(sel)
                if loc.count():
                    try:
                        loc.first.click(timeout=3000)
                        page.wait_for_timeout(1500)
                        log("YES")
                        return True
                    except Exception:
                        pass
        page.wait_for_timeout(500)
    return False


def upload_here(page, fpath: Path) -> bool:
    for frame in [page, *page.frames]:
        for sel in ["[title='Upload files']", ".elfinder-button-icon-upload", "text=Upload"]:
            btn = frame.locator(sel)
            if btn.count():
                try:
                    btn.first.click(force=True, timeout=4000)
                    page.wait_for_timeout(1200)
                except Exception:
                    pass
    for frame in [page, *page.frames]:
        ins = frame.locator('input[type="file"]')
        if not ins.count():
            continue
        ins.first.set_input_files(str(fpath))
        page.wait_for_timeout(2500)
        click_yes(page)
        page.wait_for_timeout(10000)
        log(f"uploaded {fpath.name} ({fpath.stat().st_size})")
        return True
    return False


def delete_yoast_press(page):
    # Yoast SEO → Redirects
    for url in [
        f"{SITE}/wp-admin/admin.php?page=wpseo_redirects",
        f"{SITE}/wp-admin/admin.php?page=wpseo_page_settings#/redirects",
    ]:
        page.goto(url, timeout=120000)
        page.wait_for_timeout(5000)
        shot(page, "press4-yoast.png")
        body = page.locator("body").inner_text()
        if "Redirect" not in body and "redirect" not in body.lower():
            continue
        # search box
        for sel in ["input[placeholder*='Search']", "input[type='search']", "input[name='search']", "input.filter"]:
            if page.locator(sel).count():
                try:
                    page.locator(sel).first.fill("press")
                    page.keyboard.press("Enter")
                    page.wait_for_timeout(2500)
                    break
                except Exception:
                    pass
        shot(page, "press4-yoast-press.png")
        # try delete rows containing press / we-are-in-news
        for needle in ["press", "we-are-in-news", "link9"]:
            rows = page.locator("tr, li, [class*='redirect']").filter(has_text=needle)
            for i in range(min(rows.count(), 15)):
                row = rows.nth(i)
                for sel in [
                    "button:has-text('Delete')",
                    "a:has-text('Delete')",
                    "[aria-label*='Delete']",
                    "text=Delete",
                    "button[aria-label='Delete']",
                ]:
                    if row.locator(sel).count():
                        try:
                            row.locator(sel).first.click(timeout=3000)
                            page.wait_for_timeout(800)
                            for conf in ["button:has-text('Delete redirect')", "button:has-text('Delete')", "text=Delete redirect"]:
                                if page.locator(conf).count():
                                    page.locator(conf).first.click(timeout=3000)
                                    page.wait_for_timeout(1500)
                                    log(f"deleted yoast row {needle}")
                                    break
                        except Exception:
                            pass
        shot(page, "press4-yoast-after.png")
        break


def main():
    # sync copies
    for src in FILES_MU:
        dst = ROOT / "fixes/mu-plugins" / src.name
        shutil.copy2(src, dst)
    log(f"START home={code('/')} ver={site_version()} press={redirect_target('/press/')}")

    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="press4-"))
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
                viewport={"width": 1400, "height": 900},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            if not login(page):
                return 2

            # 1. overwrite mu-plugins (these load first and gate CINDEMIR_*_LOADED)
            fm_goto(page, ["wp-content", "mu-plugins"])
            shot(page, "press4-mu-before.png")
            for f in FILES_MU:
                if not upload_here(page, f):
                    log(f"FAIL mu upload {f.name}")
                    return 3
                if code("/") == "500":
                    log("SITE 500 after mu upload — stop")
                    return 10
                log(f"health after {f.name}: {code('/')} ver={site_version()}")
            shot(page, "press4-mu-after.png")

            # 2. overwrite pack includes too
            for fpath, folders in FILES_PACK:
                fm_goto(page, folders)
                if not upload_here(page, fpath):
                    log(f"FAIL pack upload {fpath.name}")
                if code("/") == "500":
                    return 11
                log(f"pack health {fpath.name}: {code('/')}")

            routes = re.findall(r"/cindemir/v1[^\"]*", curl_text("/wp-json/"))
            log(f"routes={routes} ver={site_version()}")

            # 3. Yoast UI cleanup
            try:
                delete_yoast_press(page)
            except Exception as e:
                log(f"yoast ui: {e}")

            # 4. setup-press
            sp = curl_text("/wp-json/cindemir/v1/setup-press?key=seo-pack-2026")
            log("setup-press: " + sp[:1500])
            if "rest_no_route" in sp:
                page.goto(f"{SITE}/wp-json/cindemir/v1/setup-press?key=seo-pack-2026", timeout=180000)
                page.wait_for_timeout(12000)
                log("setup browser: " + page.locator("body").inner_text()[:1500])

            # 5. rocket
            page.goto(f"{SITE}/wp-admin/", timeout=60000)
            page.wait_for_timeout(2000)
            if page.locator("#wp-admin-bar-wp-rocket").count():
                page.hover("#wp-admin-bar-wp-rocket")
                page.wait_for_timeout(600)
                if page.locator("text=Clear cache").count():
                    try:
                        page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                        page.wait_for_timeout(4000)
                        log("rocket purged")
                    except Exception as e:
                        log(f"rocket {e}")

            # 6. fix-ahrefs (also calls setup_press in new code)
            fix = curl_text("/wp-json/cindemir/v1/fix-ahrefs?key=seo-pack-2026")
            log("fix-ahrefs: " + fix[:900])

            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    time.sleep(2)
    for path in ["/press/", "/press/?lang=ru", "/press/?lang=zh-hans", "/link9/"]:
        log(f"{path} -> {redirect_target(path)}")
    for path in ["/press/", "/press/?lang=ru", "/press/?lang=zh-hans"]:
        sep = "&" if "?" in path else "?"
        html = curl_text(f"{path}{sep}t={int(time.time())}")
        title = re.search(r"<title>([^<]+)", html)
        log(
            f"check {path}: {redirect_target(path)} title={(title.group(1)[:80] if title else '?')} "
            f"avtr={('we-are-in-news' in html)} ru={'пресс' in html.lower()} zh={'媒体' in html} "
            f"critical={'critical error' in html.lower()}"
        )
    log(f"FINAL home={code('/')} ver={site_version()} routes={re.findall(r'/cindemir/v1[^\"]*', curl_text('/wp-json/'))}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
