#!/usr/bin/env python3
"""Overwrite cindemir-seo-pack plugin files via WP File Manager, then setup-press."""
import os, re, shutil, subprocess, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
FILES = [
    ROOT / "fixes/plugins/cindemir-seo-pack/cindemir-seo-pack.php",
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php",
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php",
]


def log(m):
    print(m, flush=True)


def code(path="/"):
    r = subprocess.run(["curl","-sS","-o","/dev/null","-w","%{http_code}",f"{SITE}{path}"],capture_output=True,text=True,timeout=30)
    return r.stdout.strip()


def redirect_target(path):
    r = subprocess.run(["curl","-sS","-o","/dev/null","-w","%{http_code} %{redirect_url}",f"{SITE}{path}"],capture_output=True,text=True,timeout=30)
    return r.stdout.strip()


def login(page):
    page.goto(f"{SITE}/wp-admin/", timeout=120000)
    page.wait_for_timeout(3000)
    if "login" in page.url.lower():
        page.goto(f"{SITE}/wp-login.php", timeout=120000)
        page.wait_for_timeout(1500)
        page.click("#wp-submit")
        page.wait_for_timeout(8000)
    return "login" not in page.url.lower()


def nav_to(page, folders):
    page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
    page.wait_for_timeout(6000)
    for name in folders:
        for frame in [page, *page.frames]:
            loc = frame.locator(f"text={name}")
            if loc.count():
                try:
                    loc.first.dblclick(timeout=5000)
                    page.wait_for_timeout(2000)
                    log(f"nav {name}")
                    break
                except Exception:
                    pass


def upload(page, fpath: Path):
    for frame in [page, *page.frames]:
        for sel in ["[title='Upload files']", ".elfinder-button-icon-upload"]:
            btn = frame.locator(sel)
            if btn.count():
                btn.first.click(force=True, timeout=5000)
                page.wait_for_timeout(1500)
                break
    for frame in [page, *page.frames]:
        ins = frame.locator('input[type="file"]')
        if ins.count():
            ins.first.set_input_files(str(fpath))
            page.wait_for_timeout(2000)
            for frame in [page, *page.frames]:
                for sel in ["button:has-text('YES')", "text=YES"]:
                    loc = frame.locator(sel)
                    if loc.count():
                        try:
                            loc.first.click(timeout=4000)
                            page.wait_for_timeout(2000)
                        except Exception:
                            pass
            page.wait_for_timeout(10000)
            log(f"uploaded {fpath.name} ({fpath.stat().st_size}b)")
            return True
    return False


def main():
    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="pressfm-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            shutil.copytree(s, d) if s.is_dir() else shutil.copy2(s, d)

    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp), headless=False, channel="chrome",
                args=["--no-sandbox"], env={**os.environ, "DISPLAY": ":1"},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            if not login(page):
                return 2

            # Ensure plugin folder exists & activated
            page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
            page.wait_for_timeout(3000)
            row = page.locator("tr").filter(has_text="Cindemir SEO Pack")
            if not row.count():
                # reinstall zip first
                page.goto(f"{SITE}/wp-admin/plugin-install.php?tab=upload", timeout=120000)
                page.wait_for_timeout(2000)
                page.set_input_files("#pluginzip", str(ROOT / "fixes/plugins/cindemir-seo-pack.zip"))
                page.click("#install-plugin-submit")
                page.wait_for_timeout(20000)
                if page.locator("a.activate-now").count():
                    page.locator("a.activate-now").first.click()
                    page.wait_for_timeout(8000)
            else:
                if row.locator("a:has-text('Activate')").count():
                    row.locator("a:has-text('Activate')").first.click()
                    page.wait_for_timeout(6000)

            # upload main plugin file
            nav_to(page, ["wp-content", "plugins", "cindemir-seo-pack"])
            upload(page, FILES[0])
            # upload includes
            nav_to(page, ["wp-content", "plugins", "cindemir-seo-pack", "includes"])
            upload(page, FILES[1])
            upload(page, FILES[2])

            if code("/") == "500":
                log("BROKEN after upload")
                return 4

            page.goto(f"{SITE}/wp-json/cindemir/v1/setup-press?key=seo-pack-2026", timeout=180000)
            page.wait_for_timeout(8000)
            log("setup-press: " + page.locator("body").inner_text()[:800])

            page.goto(f"{SITE}/wp-json/cindemir/v1/fix-ahrefs?key=seo-pack-2026", timeout=120000)
            page.wait_for_timeout(5000)
            log("fix-ahrefs: " + page.locator("body").inner_text()[:400])

            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    time.sleep(2)
    for path in ["/press/", "/press/?lang=ru", "/press/?lang=zh-hans", "/link9/"]:
        log(f"{path} -> {redirect_target(path)}")
    html = subprocess.run(["curl","-sS",f"{SITE}/press/?t={int(time.time())}"],capture_output=True,text=True).stdout
    log("press page sample: " + re.sub(r"\s+"," ", html)[2000:2600][:400])
    m = re.search(r"cindemir-seo-fixes ([0-9.]+)", subprocess.run(["curl","-sS",f"{SITE}/?t={int(time.time())}"],capture_output=True,text=True).stdout)
    log(f"version={m.group(1) if m else '?'} home={code('/')}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
