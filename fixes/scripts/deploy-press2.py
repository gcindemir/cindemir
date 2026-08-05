#!/usr/bin/env python3
"""Click Replace on plugin upload OR overwrite via File Manager, then setup-press."""
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
ZIP = ROOT / "fixes/plugins/cindemir-seo-pack.zip"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"


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


def curl_text(path):
    r = subprocess.run(
        ["curl", "-sS", f"{SITE}{path}"],
        capture_output=True,
        text=True,
        timeout=180,
    )
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


def upload_replace(page):
    page.goto(f"{SITE}/wp-admin/plugin-install.php?tab=upload", timeout=120000)
    page.wait_for_timeout(2500)
    page.set_input_files("#pluginzip", str(ZIP))
    page.wait_for_timeout(800)
    page.click("#install-plugin-submit")
    page.wait_for_timeout(12000)
    shot(page, "press2-install.png")
    body = page.locator("body").inner_text()
    log("install: " + re.sub(r"\s+", " ", body)[:700])

    # WordPress "Replace current with uploaded"
    for sel in [
        "a:has-text('Replace current with uploaded')",
        "button:has-text('Replace current with uploaded')",
        "text=Replace current with uploaded",
        "#overwrite-existing-plugin",
        "a.replace-the-plugin",
    ]:
        loc = page.locator(sel)
        if loc.count():
            log(f"clicking {sel}")
            loc.first.click(timeout=8000)
            page.wait_for_timeout(25000)
            shot(page, "press2-replaced.png")
            log("after replace: " + re.sub(r"\s+", " ", page.locator("body").inner_text())[:500])
            break

    if page.locator("a.activate-now").count():
        page.locator("a.activate-now").first.click(timeout=8000)
        page.wait_for_timeout(10000)
        log("activated")

    # ensure active
    page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
    page.wait_for_timeout(3000)
    row = page.locator("tr").filter(has_text="Cindemir SEO Pack")
    if row.count() and row.locator(".activate a, a:has-text('Activate')").count():
        row.locator(".activate a, a:has-text('Activate')").first.click(timeout=5000)
        page.wait_for_timeout(8000)
        log("activated from list")
    shot(page, "press2-plugins.png")
    return True


def fm_overwrite_includes(page):
    """Overwrite includes via File Manager using text= selectors."""
    files_dirs = [
        (ROOT / "fixes/plugins/cindemir-seo-pack/cindemir-seo-pack.php", ["wp-content", "plugins", "cindemir-seo-pack"]),
        (ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php", ["wp-content", "plugins", "cindemir-seo-pack", "includes"]),
        (ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php", ["wp-content", "plugins", "cindemir-seo-pack", "includes"]),
    ]
    for fpath, folders in files_dirs:
        page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
        page.wait_for_timeout(5000)
        for name in folders:
            navigated = False
            for frame in [page, *page.frames]:
                for sel in [f"text={name}", f".elfinder-cwd-filename:has-text('{name}')"]:
                    loc = frame.locator(sel)
                    if not loc.count():
                        continue
                    try:
                        loc.first.dblclick(timeout=5000)
                        page.wait_for_timeout(1800)
                        log(f"nav {name}")
                        navigated = True
                        break
                    except Exception:
                        continue
                if navigated:
                    break
            if not navigated:
                log(f"WARN could not nav {name}")
        for frame in [page, *page.frames]:
            for sel in ["[title='Upload files']", ".elfinder-button-icon-upload", "text=Upload"]:
                btn = frame.locator(sel)
                if btn.count():
                    try:
                        btn.first.click(force=True, timeout=5000)
                        page.wait_for_timeout(1200)
                    except Exception:
                        pass
        uploaded = False
        for frame in [page, *page.frames]:
            ins = frame.locator('input[type="file"]')
            if not ins.count():
                continue
            ins.first.set_input_files(str(fpath))
            page.wait_for_timeout(2000)
            for fr in [page, *page.frames]:
                for sel in ["button:has-text('YES')", "text=YES", ".ui-button:has-text('YES')"]:
                    loc = fr.locator(sel)
                    if loc.count():
                        try:
                            loc.first.click(timeout=4000)
                            page.wait_for_timeout(1500)
                            log("clicked YES")
                        except Exception:
                            pass
            page.wait_for_timeout(9000)
            log(f"uploaded {fpath.name}")
            uploaded = True
            break
        if not uploaded:
            log(f"FAIL upload {fpath.name}")
            return False
        if code("/") == "500":
            log("site 500 after upload")
            return False
        shot(page, f"press2-fm-{fpath.name}.png")
    return True


def main():
    log(f"start home={code('/')} ver={site_version()} press={redirect_target('/press/')}")
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="press2-"))
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

            upload_replace(page)
            log(f"after replace ver={site_version()} setup={code('/wp-json/cindemir/v1/setup-press?key=seo-pack-2026')}")

            if code("/wp-json/cindemir/v1/setup-press?key=seo-pack-2026") == "404":
                log("setup still 404 — FM overwrite contact-fixes")
                if not fm_overwrite_includes(page):
                    return 5

            # soft bounce plugin: visit plugins to ensure loaded
            page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
            page.wait_for_timeout(2000)

            sp = curl_text("/wp-json/cindemir/v1/setup-press?key=seo-pack-2026")
            log("setup-press: " + sp[:1000])
            if "rest_no_route" in sp:
                page.goto(f"{SITE}/wp-json/cindemir/v1/setup-press?key=seo-pack-2026", timeout=180000)
                page.wait_for_timeout(10000)
                log("setup browser: " + page.locator("body").inner_text()[:1000])

            # purge rocket
            page.goto(f"{SITE}/wp-admin/", timeout=60000)
            page.wait_for_timeout(2000)
            if page.locator("#wp-admin-bar-wp-rocket").count():
                page.hover("#wp-admin-bar-wp-rocket")
                page.wait_for_timeout(700)
                loc = page.locator("#wp-admin-bar-purge-all, #wp-admin-bar-wp-rocket >> text=Clear cache")
                if loc.count():
                    try:
                        loc.first.click(force=True, timeout=5000)
                        page.wait_for_timeout(4000)
                        log("rocket purged")
                    except Exception as e:
                        log(f"rocket purge skip: {e}")

            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    time.sleep(2)
    for path in ["/press/", "/press/?lang=ru", "/press/?lang=zh-hans", "/link9/"]:
        log(f"{path} -> {redirect_target(path)}")
    for path in ["/press/", "/press/?lang=ru", "/press/?lang=zh-hans"]:
        sep = "&" if "?" in path else "?"
        html = curl_text(f"{path}{sep}t={int(time.time())}")
        log(
            f"check {path}: code={code(path)} avtr={('we-are-in-news' in html)} "
            f"ru={'пресс' in html.lower()} zh={'媒体' in html} len={len(html)}"
        )
    log(f"FINAL home={code('/')} ver={site_version()}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
