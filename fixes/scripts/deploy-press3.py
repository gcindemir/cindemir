#!/usr/bin/env python3
"""
1) Delete live mu-plugins cindemir-*.php (old copy blocks pack via CINDEMIR_*_LOADED)
2) Replace SEO Pack 1.8.4
3) Run setup-press + clear redirect/cache
"""
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


def curl_text(path, timeout=180):
    r = subprocess.run(
        ["curl", "-sS", f"{SITE}{path}"],
        capture_output=True,
        text=True,
        timeout=timeout,
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


def open_fm(page, folders):
    page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
    page.wait_for_timeout(6000)
    for name in folders:
        ok = False
        for frame in [page, *page.frames]:
            for sel in [f"text={name}", f".elfinder-cwd-filename[title='{name}']"]:
                loc = frame.locator(sel)
                if not loc.count():
                    continue
                try:
                    loc.first.dblclick(timeout=5000)
                    page.wait_for_timeout(2000)
                    log(f"nav {name}")
                    ok = True
                    break
                except Exception:
                    continue
            if ok:
                break
        if not ok:
            log(f"WARN nav miss {name}")
    shot(page, f"press3-fm-{'/'.join(folders)}.png")


def delete_matching(page, substr="cindemir"):
    deleted = 0
    for _ in range(40):
        hit = False
        for frame in [page, *page.frames]:
            loc = frame.locator(".elfinder-cwd-filename")
            n = loc.count()
            for i in range(n):
                try:
                    t = loc.nth(i).inner_text(timeout=1000)
                except Exception:
                    continue
                if substr.lower() not in t.lower():
                    continue
                try:
                    loc.nth(i).click(timeout=2000)
                    page.wait_for_timeout(400)
                except Exception:
                    continue
                for sel in ["[title='Remove']", ".elfinder-button-icon-rm", "text=Delete"]:
                    btn = frame.locator(sel)
                    if not btn.count():
                        continue
                    try:
                        btn.first.click(force=True, timeout=3000)
                        page.wait_for_timeout(800)
                    except Exception:
                        continue
                    for yes in ["button:has-text('YES')", "text=YES", ".ui-button:has-text('Yes')"]:
                        y = frame.locator(yes) if yes.startswith(".") or yes.startswith("button") else page.locator(yes)
                        # search all frames
                        found = False
                        for fr in [page, *page.frames]:
                            yy = fr.locator(yes)
                            if yy.count():
                                try:
                                    yy.first.click(timeout=3000)
                                    page.wait_for_timeout(2000)
                                    deleted += 1
                                    hit = True
                                    found = True
                                    log(f"deleted #{deleted}: {t}")
                                    break
                                except Exception:
                                    pass
                        if found:
                            break
                    if hit:
                        break
                if hit:
                    break
            if hit:
                break
        if not hit:
            break
    return deleted


def replace_plugin(page):
    page.goto(f"{SITE}/wp-admin/plugin-install.php?tab=upload", timeout=120000)
    page.wait_for_timeout(2500)
    page.set_input_files("#pluginzip", str(ZIP))
    page.click("#install-plugin-submit")
    page.wait_for_timeout(12000)
    shot(page, "press3-install.png")
    for sel in [
        "a:has-text('Replace current with uploaded')",
        "text=Replace current with uploaded",
    ]:
        if page.locator(sel).count():
            page.locator(sel).first.click(timeout=8000)
            page.wait_for_timeout(25000)
            shot(page, "press3-replaced.png")
            log("replaced")
            break
    if page.locator("a.activate-now").count():
        page.locator("a.activate-now").first.click(timeout=8000)
        page.wait_for_timeout(10000)
        log("activated now")
    page.goto(f"{SITE}/wp-admin/plugins.php", timeout=120000)
    page.wait_for_timeout(3000)
    row = page.locator("tr").filter(has_text="Cindemir SEO Pack")
    if row.count():
        txt = row.first.inner_text()
        log("plugin row: " + re.sub(r"\s+", " ", txt)[:300])
        if row.locator(".activate a").count():
            row.locator(".activate a").first.click(timeout=5000)
            page.wait_for_timeout(8000)
            log("activated from list")
    shot(page, "press3-plugins.png")


def remove_redirection_ui(page):
    page.goto(f"{SITE}/wp-admin/tools.php?page=redirection.php", timeout=120000)
    page.wait_for_timeout(5000)
    shot(page, "press3-redir.png")
    # search press
    for sel in ["input[name='filter']", "input[placeholder*='Search']", "#search_filter", "input[type='search']"]:
        if page.locator(sel).count():
            try:
                page.locator(sel).first.fill("press")
                page.keyboard.press("Enter")
                page.wait_for_timeout(3000)
                break
            except Exception:
                pass
    shot(page, "press3-redir-press.png")
    # try delete matching rows
    for needle in ["press", "link9", "we-are-in-news"]:
        rows = page.locator("tr").filter(has_text=needle)
        for i in range(min(rows.count(), 10)):
            try:
                row = rows.nth(i)
                if row.locator("input[type=checkbox]").count():
                    row.locator("input[type=checkbox]").first.check()
            except Exception:
                pass
    # bulk delete
    if page.locator("select[name='action']").count():
        try:
            page.locator("select[name='action']").first.select_option(label="Delete")
            if page.locator("#doaction").count():
                page.locator("#doaction").first.click()
                page.wait_for_timeout(3000)
                log("bulk delete redirected")
        except Exception as e:
            log(f"bulk delete skip: {e}")
    shot(page, "press3-redir-after.png")


def main():
    log(f"START home={code('/')} ver={site_version()} press={redirect_target('/press/')}")
    routes = curl_text("/wp-json/")
    log("routes before: " + str(re.findall(r"/cindemir/v1[^\"]*", routes)))

    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="press3-"))
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

            # 1. delete mu-plugins cindemir*
            open_fm(page, ["wp-content", "mu-plugins"])
            n = delete_matching(page, "cindemir")
            log(f"mu-plugins deleted={n}")
            shot(page, "press3-mu-after.png")
            time.sleep(2)
            h = code("/")
            log(f"health after mu delete home={h}")
            if h == "500":
                log("ABORT site 500 after mu delete")
                return 10

            # 2. replace plugin
            replace_plugin(page)
            time.sleep(2)
            h = code("/")
            log(f"health after replace home={h} ver={site_version()}")
            if h == "500":
                return 11

            routes = curl_text("/wp-json/")
            log("routes after: " + str(re.findall(r"/cindemir/v1[^\"]*", routes)))

            # 3. setup-press
            sp = curl_text("/wp-json/cindemir/v1/setup-press?key=seo-pack-2026")
            log("setup-press: " + sp[:1200])
            if "rest_no_route" in sp or '"ok"' not in sp:
                page.goto(f"{SITE}/wp-json/cindemir/v1/setup-press?key=seo-pack-2026", timeout=180000)
                page.wait_for_timeout(12000)
                log("setup browser: " + page.locator("body").inner_text()[:1200])
                shot(page, "press3-setup.png")

            # 4. try UI redirection cleanup too
            try:
                remove_redirection_ui(page)
            except Exception as e:
                log(f"redir UI skip: {e}")

            # 5. purge rocket
            page.goto(f"{SITE}/wp-admin/", timeout=60000)
            page.wait_for_timeout(2000)
            if page.locator("#wp-admin-bar-wp-rocket").count():
                page.hover("#wp-admin-bar-wp-rocket")
                page.wait_for_timeout(700)
                loc = page.locator("text=Clear cache")
                if loc.count():
                    try:
                        loc.first.click(force=True, timeout=5000)
                        page.wait_for_timeout(4000)
                        log("rocket purged")
                    except Exception as e:
                        log(f"rocket: {e}")

            fix = curl_text("/wp-json/cindemir/v1/fix-ahrefs?key=seo-pack-2026")
            log("fix-ahrefs: " + fix[:700])
            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    time.sleep(3)
    for path in ["/press/", "/press/?lang=ru", "/press/?lang=zh-hans", "/link9/"]:
        log(f"{path} -> {redirect_target(path)}")
    for path in ["/press/", "/press/?lang=ru", "/press/?lang=zh-hans"]:
        sep = "&" if "?" in path else "?"
        html = curl_text(f"{path}{sep}t={int(time.time())}")
        log(
            f"check {path}: redir={redirect_target(path)} avtr_redirect={'av.tr' in redirect_target(path)} "
            f"needle_ru={'пресс' in html.lower()} zh={'媒体' in html} critical={'critical' in html.lower()} len={len(html)}"
        )
    log(f"FINAL home={code('/')} ver={site_version()} routes=" + str(re.findall(r"/cindemir/v1[^\"]*", curl_text("/wp-json/"))))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
