#!/usr/bin/env python3
"""Autonomous: Bluehost File Manager cleanup + WP plugin deploy. No user steps."""
import os, re, shutil, subprocess, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
ZIP = ROOT / "fixes/plugins/cindemir-seo-pack.zip"
SITE = "https://cindemirlaw.com"
BH_FM = "https://my.bluehost.com/hosting/app/cindemirlaw.com/cpanel/filemanager"


def log(m):
    print(m, flush=True)


def code(path="/"):
    r = subprocess.run(["curl","-sS","-o","/dev/null","-w","%{http_code}",f"{SITE}{path}"],capture_output=True,text=True,timeout=20)
    return r.stdout.strip()


def open_fm(page):
    page.goto(BH_FM, timeout=180000)
    for i in range(50):
        page.wait_for_timeout(3000)
        u, t = page.url, (page.title() or "")[:50]
        log(f"[{i*3}s] {u[:85]} | {t}")
        if "just a moment" in t.lower():
            continue
        if "login" in u.lower() and i > 15:
            return False
        if "filemanager" in u.lower() or "file manager" in t.lower():
            return True
        if "hosting" in u.lower() and i > 5:
            page.goto(BH_FM, timeout=120000)
    return "filemanager" in page.url.lower()


def nav(page, folder):
    for frame in [page, *page.frames]:
        loc = frame.locator(f"text={folder}")
        if loc.count():
            try:
                loc.first.dblclick(timeout=5000)
                page.wait_for_timeout(2500)
                log(f"opened {folder}")
                return True
            except Exception:
                pass
    return False


def delete_cindemir_in_mu(page):
    n = 0
    for _ in range(25):
        found = False
        for frame in [page, *page.frames]:
            for sel in ["text=cindemir-seo-fixes.php", "text=cindemir-contact-fixes.php", "text=cindemir-force-upgrade.php", "text=cindemir-expose-yoast-meta.php", "text=cindemir-purge-cache.php"]:
                loc = frame.locator(sel)
                if not loc.count():
                    continue
                try:
                    loc.first.click(timeout=3000)
                    page.wait_for_timeout(500)
                    for rm in ["[title='Remove']", "text=Remove", "text=Delete"]:
                        b = frame.locator(rm)
                        if b.count():
                            b.first.click(force=True, timeout=3000)
                            page.wait_for_timeout(800)
                            for yes in ["button:has-text('YES')", "text=YES", "text=Confirm"]:
                                y = frame.locator(yes)
                                if y.count():
                                    y.first.click(timeout=3000)
                                    page.wait_for_timeout(2500)
                                    n += 1
                                    found = True
                                    log(f"deleted #{n}")
                                    break
                            break
                except Exception:
                    pass
            if found:
                break
        if not found:
            break
    return n


def wp_login(page):
    page.goto(f"{SITE}/wp-login.php", timeout=120000)
    page.wait_for_timeout(3000)
    if "login" not in page.url.lower():
        return True
    try:
        page.click("#wp-submit", timeout=8000)
        page.wait_for_timeout(8000)
    except Exception:
        pass
    return "login" not in page.url.lower()


def install_plugin(page):
    page.goto(f"{SITE}/wp-admin/plugin-install.php?tab=upload", timeout=120000)
    page.wait_for_timeout(4000)
    page.set_input_files("#pluginzip", str(ZIP))
    page.wait_for_timeout(1500)
    page.click("#install-plugin-submit")
    page.wait_for_timeout(20000)
    if page.locator("a.activate-now").count():
        page.locator("a.activate-now").first.click(timeout=8000)
        page.wait_for_timeout(8000)
        return True
    page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
    page.wait_for_timeout(3000)
    row = page.locator("tr").filter(has_text="Cindemir SEO Pack")
    if row.locator("a:has-text('Activate')").count():
        row.locator("a:has-text('Activate')").first.click(timeout=5000)
        page.wait_for_timeout(6000)
        return True
    return "cindemir-seo-pack" in page.content()


def run_fix(page):
    page.goto(f"{SITE}/wp-admin/tools.php?page=cindemir-seo-pack", timeout=120000)
    page.wait_for_timeout(3000)
    if page.locator('input[name="cindemir_run_ahrefs"]').count():
        page.click('input[name="cindemir_run_ahrefs"]')
        page.wait_for_timeout(8000)
    page.goto(f"{SITE}/wp-json/cindemir/v1/fix-ahrefs?key=seo-pack-2026", timeout=60000)
    page.wait_for_timeout(3000)
    log("fix: " + page.locator("body").inner_text()[:300])


def main():
    log(f"start home={code('/')} login={code('/wp-login.php')}")

    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="auto-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            shutil.copytree(s, d) if s.is_dir() else shutil.copy2(s, d)

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(str(tmp), headless=False, channel="chrome", args=["--no-sandbox"], env={**os.environ,"DISPLAY":":1"})
        page = ctx.pages[0] if ctx.pages else ctx.new_page()

        if code("/wp-login.php") == "500":
            log("recovering via bluehost FM")
            if open_fm(page):
                for f in ["public_html", "wp-content", "mu-plugins"]:
                    nav(page, f)
                delete_cindemir_in_mu(page)
                time.sleep(5)
                log(f"after cleanup home={code('/')}")

        if code("/wp-login.php") != "200":
            log("FAIL cannot recover site")
            page.screenshot(path=str(ROOT/"fixes/auto-fail.png"), full_page=True)
            ctx.close()
            return 10

        if not wp_login(page):
            log("FAIL wp login")
            return 11

        if not install_plugin(page):
            log("FAIL plugin install")
            return 12

        if code("/") == "500":
            log("plugin broke site — deactivate")
            page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
            page.locator("tr").filter(has_text="Cindemir SEO Pack").locator(".deactivate a").first.click(timeout=5000)
            return 13

        run_fix(page)
        page.goto(f"{SITE}/wp-admin/admin.php?page=wprocket", timeout=60000)
        page.wait_for_timeout(2000)
        if page.locator("text=Clear cache").count():
            page.locator("text=Clear cache").first.click(timeout=5000)
            page.wait_for_timeout(5000)

        r = subprocess.run(["curl","-sS",f"{SITE}/?t={int(time.time())}"],capture_output=True,text=True)
        m = re.search(r"cindemir-seo-fixes ([0-9.]+)", r.stdout)
        log(f"DONE version={m.group(1) if m else '?'} barobirlik={'d.barobirlik' in r.stdout} home={code('/')}")
        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)
    return 0 if code("/") == "200" else 14


if __name__ == "__main__":
    raise SystemExit(main())
