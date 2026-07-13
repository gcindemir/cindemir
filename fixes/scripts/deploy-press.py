#!/usr/bin/env python3
"""Replace Cindemir SEO Pack with 1.8.4 (proper zip folder), then setup-press."""
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
SHOT.mkdir(parents=True, exist_ok=True)


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
        timeout=120,
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
    log(f"login ok={ok} url={page.url}")
    shot(page, "press-deploy-login.png")
    return ok


def deactivate_delete(page):
    page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
    page.wait_for_timeout(4000)
    shot(page, "press-deploy-plugins-before.png")
    row = page.locator("tr").filter(has_text="Cindemir SEO Pack")
    if not row.count():
        log("plugin not listed")
        return
    if row.locator(".deactivate a").count():
        row.locator(".deactivate a").first.click(timeout=5000)
        page.wait_for_timeout(5000)
        log("deactivated")
    page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
    page.wait_for_timeout(3000)
    row = page.locator("tr").filter(has_text="Cindemir SEO Pack")
    if row.count() and row.locator(".delete a").count():
        row.locator(".delete a").first.click(timeout=5000)
        page.wait_for_timeout(2500)
        shot(page, "press-deploy-delete-confirm.png")
        for sel in [
            "#submit",
            "input[value='Yes, delete these files']",
            "button:has-text('Delete')",
            "input[type='submit']",
        ]:
            if page.locator(sel).count():
                try:
                    page.locator(sel).first.click(timeout=5000)
                    page.wait_for_timeout(6000)
                    log(f"confirmed delete via {sel}")
                    break
                except Exception:
                    pass
    page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
    page.wait_for_timeout(3000)
    still = page.locator("tr").filter(has_text="Cindemir SEO Pack").count()
    log(f"after delete still_listed={still}")
    shot(page, "press-deploy-plugins-after-delete.png")


def install(page):
    log(f"uploading zip size={ZIP.stat().st_size}")
    page.goto(f"{SITE}/wp-admin/plugin-install.php?tab=upload", timeout=120000)
    page.wait_for_timeout(3000)
    page.set_input_files("#pluginzip", str(ZIP))
    page.wait_for_timeout(1000)
    page.click("#install-plugin-submit")
    page.wait_for_timeout(25000)
    shot(page, "press-deploy-install-result.png")
    body = page.content()
    body_l = body.lower()
    log("install page snippet: " + re.sub(r"\s+", " ", page.locator("body").inner_text())[:500])

    if page.locator("a.activate-now").count():
        page.locator("a.activate-now").first.click(timeout=8000)
        page.wait_for_timeout(10000)
        log("installed+activated via activate-now")
        return True

    if "destination folder already exists" in body_l:
        log("destination folder already exists — try activate existing then fail for FM overwrite")
        page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
        page.wait_for_timeout(3000)
        row = page.locator("tr").filter(has_text="Cindemir SEO Pack")
        if row.count() and row.locator("a:has-text('Activate')").count():
            row.locator("a:has-text('Activate')").first.click(timeout=5000)
            page.wait_for_timeout(8000)
        return False

    if "plugin installed successfully" in body_l or "installed successfully" in body_l:
        page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
        page.wait_for_timeout(3000)
        row = page.locator("tr").filter(has_text="Cindemir SEO Pack")
        if row.count() and row.locator("a:has-text('Activate')").count():
            row.locator("a:has-text('Activate')").first.click(timeout=5000)
            page.wait_for_timeout(8000)
            log("activated from plugins list")
        return True

    return False


def fm_overwrite(page):
    """Overwrite plugin PHP files via File Manager as fallback."""
    files = [
        (ROOT / "fixes/plugins/cindemir-seo-pack/cindemir-seo-pack.php", ["wp-content", "plugins", "cindemir-seo-pack"]),
        (ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php", ["wp-content", "plugins", "cindemir-seo-pack", "includes"]),
        (ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php", ["wp-content", "plugins", "cindemir-seo-pack", "includes"]),
    ]
    for fpath, folders in files:
        page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
        page.wait_for_timeout(5000)
        for name in folders:
            for frame in [page, *page.frames]:
                loc = frame.locator(f"span:text-is('{name}'), a:text-is('{name}'), text={name}")
                if loc.count():
                    try:
                        loc.first.dblclick(timeout=5000)
                        page.wait_for_timeout(1500)
                        log(f"nav {name}")
                        break
                    except Exception:
                        pass
        uploaded = False
        for frame in [page, *page.frames]:
            for sel in ["[title='Upload files']", ".elfinder-button-icon-upload"]:
                btn = frame.locator(sel)
                if btn.count():
                    try:
                        btn.first.click(force=True, timeout=5000)
                        page.wait_for_timeout(1500)
                    except Exception:
                        pass
        for frame in [page, *page.frames]:
            ins = frame.locator('input[type="file"]')
            if not ins.count():
                continue
            ins.first.set_input_files(str(fpath))
            page.wait_for_timeout(2000)
            for fr in [page, *page.frames]:
                for sel in ["button:has-text('YES')", "text=YES", "button:has-text('Replace')"]:
                    loc = fr.locator(sel)
                    if loc.count():
                        try:
                            loc.first.click(timeout=4000)
                            page.wait_for_timeout(1500)
                        except Exception:
                            pass
            page.wait_for_timeout(8000)
            log(f"FM uploaded {fpath.name}")
            uploaded = True
            break
        if not uploaded:
            log(f"FM upload FAILED {fpath.name}")
            return False
        if code("/") == "500":
            log("BROKEN after FM upload")
            return False
    return True


def main():
    if code("/") != "200":
        log(f"ABORT site down {code('/')}")
        return 1
    log(f"start version={site_version()} zip={ZIP}")

    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="pressdep-"))
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
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            if not login(page):
                log("login fail")
                return 2

            deactivate_delete(page)
            ok = install(page)
            ver = site_version()
            log(f"after install version={ver} home={code('/')}")
            if code("/") == "500":
                log("site broke — deactivate")
                page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
                page.wait_for_timeout(3000)
                row = page.locator("tr").filter(has_text="Cindemir SEO Pack")
                if row.count() and row.locator(".deactivate a").count():
                    row.locator(".deactivate a").first.click(timeout=5000)
                return 4

            if not ok or ver != "1.8.4":
                log("install incomplete — File Manager overwrite")
                # ensure plugin active first
                page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
                page.wait_for_timeout(3000)
                row = page.locator("tr").filter(has_text="Cindemir SEO Pack")
                if row.count() and row.locator("a:has-text('Activate')").count():
                    row.locator("a:has-text('Activate')").first.click(timeout=5000)
                    page.wait_for_timeout(6000)
                if not fm_overwrite(page):
                    log("FM overwrite failed")
                    return 5
                # soft reload by visiting plugins page
                page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
                page.wait_for_timeout(3000)
                ver = site_version()
                log(f"after FM version={ver} home={code('/')}")

            sp = curl_text("/wp-json/cindemir/v1/setup-press?key=seo-pack-2026")
            log("setup-press curl: " + sp[:800])
            if "rest_no_route" in sp or '"ok":true' not in sp.replace(" ", ""):
                page.goto(f"{SITE}/wp-json/cindemir/v1/setup-press?key=seo-pack-2026", timeout=180000)
                page.wait_for_timeout(8000)
                log("setup-press page: " + page.locator("body").inner_text()[:800])
                shot(page, "press-deploy-setup.png")

            # purge rocket
            page.goto(f"{SITE}/wp-admin/", timeout=60000)
            page.wait_for_timeout(2000)
            if page.locator("#wp-admin-bar-wp-rocket").count():
                page.hover("#wp-admin-bar-wp-rocket")
                page.wait_for_timeout(800)
                if page.locator("#wp-admin-bar-wp-rocket >> text=Clear cache").count():
                    page.locator("#wp-admin-bar-wp-rocket >> text=Clear cache").first.click(force=True, timeout=5000)
                    page.wait_for_timeout(4000)
                    log("rocket purged")

            # also fix-ahrefs for meta/press side effects
            fix = curl_text("/wp-json/cindemir/v1/fix-ahrefs?key=seo-pack-2026")
            log("fix-ahrefs: " + fix[:500])

            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    time.sleep(3)
    for path in ["/press/", "/press/?lang=ru", "/press/?lang=zh-hans", "/link9/"]:
        log(f"{path} -> {redirect_target(path)}")

    for path, needle in [
        ("/press/", "Press"),
        ("/press/?lang=ru", "пресс"),
        ("/press/?lang=zh-hans", "媒体"),
    ]:
        html = curl_text(f"{path}&nocache={int(time.time())}" if "?" in path else f"{path}?nocache={int(time.time())}")
        # fix url
        html = curl_text(path if path.startswith("http") else path)
        # bust cache
        sep = "&" if "?" in path else "?"
        html = curl_text(f"{path}{sep}t={int(time.time())}")
        final = redirect_target(path)
        log(
            f"content {path}: code={code(path)} redir={final} needle={needle in html} "
            f"avtr={html.count('we-are-in-news')} critical={'critical error' in html.lower()}"
        )

    log(f"FINAL home={code('/')} version={site_version()}")
    return 0 if site_version() == "1.8.4" and "av.tr" not in redirect_target("/press/") else 6


if __name__ == "__main__":
    raise SystemExit(main())
