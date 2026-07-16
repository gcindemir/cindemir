#!/usr/bin/env python3
"""Drill GSC linking sites for cindemirlaw.com; try multiple Chrome profiles."""
import os
import time
from pathlib import Path
from urllib.parse import quote

from playwright.sync_api import sync_playwright

OUT = Path("/workspace/fixes")
LOG = OUT / "gsc-bl-sites-run.log"
RESOURCE = quote("https://cindemirlaw.com/", safe="")
PROFILES = [
    Path("/tmp/gscwait-hb0x7jdi"),
    Path("/tmp/gscstealth-i5fbjqkk"),
    Path("/tmp/gscquota-458zmmob"),
    Path("/tmp/gscidx5c-crrjbsyd"),
    Path("/tmp/gsclogin-e1st_r2k"),
]


def log(m):
    print(m, flush=True)
    with LOG.open("a") as f:
        f.write(m + "\n")


def unlock(profile: Path):
    for lock in ["SingletonLock", "SingletonCookie", "SingletonSocket"]:
        lp = profile / lock
        if lp.exists() or lp.is_symlink():
            try:
                lp.unlink()
            except Exception:
                pass


def in_gsc(page) -> bool:
    try:
        body = page.locator("body").inner_text(timeout=8000)
    except Exception:
        return False
    return any(
        x in body
        for x in ("Bağlantılar", "Genel Bakış", "URL Denetimi", "Overview", "Links", "Performance")
    ) and "search-console/about" not in page.url


def try_login(page) -> bool:
    """Click account chooser / passwordless path if present."""
    body = page.locator("body").inner_text()
    if "Choose an account" in body or "Hesap seçin" in body or "gokhancindemir44" in body:
        for label in [
            "gokhancindemir44@gmail.com",
            "Gökhan Cindemir",
            "Gokhan Cindemir",
        ]:
            try:
                page.get_by_text(label, exact=False).first.click(timeout=4000)
                time.sleep(5)
                break
            except Exception:
                continue
    # Signed out may need password — detect
    body = page.locator("body").inner_text()
    if "Enter your password" in body or "Şifrenizi girin" in body:
        log("PASSWORD_REQUIRED")
        return False
    return in_gsc(page)


def launch(p, profile: Path):
    unlock(profile)
    ctx = p.chromium.launch_persistent_context(
        str(profile),
        headless=False,
        channel="chrome",
        ignore_default_args=["--enable-automation"],
        args=[
            "--no-sandbox",
            "--disable-dev-shm-usage",
            "--disable-blink-features=AutomationControlled",
        ],
        env={**os.environ, "DISPLAY": ":1"},
        viewport={"width": 1500, "height": 1000},
        accept_downloads=True,
    )
    page = ctx.pages[0] if ctx.pages else ctx.new_page()
    page.add_init_script(
        "Object.defineProperty(navigator, 'webdriver', {get: () => undefined});"
    )
    return ctx, page


def scrape(page):
    page.goto(
        f"https://search.google.com/search-console/links?resource_id={RESOURCE}",
        wait_until="domcontentloaded",
        timeout=90000,
    )
    time.sleep(7)
    if not in_gsc(page):
        try_login(page)
        time.sleep(4)
        page.goto(
            f"https://search.google.com/search-console/links?resource_id={RESOURCE}",
            wait_until="domcontentloaded",
            timeout=90000,
        )
        time.sleep(7)
    if not in_gsc(page):
        return False

    body = page.locator("body").inner_text()
    (OUT / "gsc-bl-overview.txt").write_text(body)
    page.screenshot(path=str(OUT / "gsc-bl-overview.png"), full_page=True)
    log("overview_snip " + body[:2000].replace("\n", " | "))

    digers = page.get_by_text("DİĞER", exact=False)
    # fallback English
    if digers.count() == 0:
        digers = page.get_by_text("MORE", exact=False)
    log("diger_count " + str(digers.count()))

    # 0 = top linked pages, 1 = linking sites, 2 = anchors (typical)
    if digers.count() >= 2:
        digers.nth(1).click(timeout=8000)
        time.sleep(6)
        try:
            page.get_by_text("100", exact=True).first.click(timeout=3000)
            time.sleep(3)
        except Exception:
            pass
        body = page.locator("body").inner_text()
        (OUT / "gsc-bl-sites-full.txt").write_text(body)
        page.screenshot(path=str(OUT / "gsc-bl-sites.png"), full_page=True)
        log("sites " + body[:3500].replace("\n", " | "))

    page.goto(
        f"https://search.google.com/search-console/links?resource_id={RESOURCE}",
        wait_until="domcontentloaded",
        timeout=90000,
    )
    time.sleep(6)
    digers = page.get_by_text("DİĞER", exact=False)
    if digers.count() == 0:
        digers = page.get_by_text("MORE", exact=False)
    if digers.count() >= 3:
        digers.nth(2).click(timeout=8000)
        time.sleep(5)
        body = page.locator("body").inner_text()
        (OUT / "gsc-bl-anchors.txt").write_text(body)
        page.screenshot(path=str(OUT / "gsc-bl-anchors.png"), full_page=True)
        log("anchors " + body[:2500].replace("\n", " | "))

    # Export CSV if possible
    page.goto(
        f"https://search.google.com/search-console/links?resource_id={RESOURCE}",
        wait_until="domcontentloaded",
        timeout=90000,
    )
    time.sleep(5)
    try:
        with page.expect_download(timeout=20000) as dl_info:
            for t in [
                "HARİCİ BAĞLANTILARI DIŞA AKTAR",
                "EXPORT EXTERNAL LINKS",
                "Export",
            ]:
                try:
                    page.get_by_text(t, exact=False).first.click(timeout=4000)
                    break
                except Exception:
                    continue
            time.sleep(1)
            for label in ["CSV indir", "Download CSV", "CSV"]:
                try:
                    page.get_by_text(label, exact=False).first.click(timeout=2500)
                    break
                except Exception:
                    pass
        d = dl_info.value
        path = OUT / ("gsc-export-" + d.suggested_filename)
        d.save_as(str(path))
        log("exported " + str(path) + " " + str(path.stat().st_size))
    except Exception as e:
        log("export_fail " + type(e).__name__ + " " + str(e)[:200])

    return True


def main():
    LOG.write_text("")
    # Soft close prior chrome using display :1 profiles only
    os.system(
        "pgrep -af 'chrome.*gsc' | awk '{print $1}' | xargs -r kill -9 2>/dev/null; sleep 2"
    )

    with sync_playwright() as p:
        for profile in PROFILES:
            if not profile.exists():
                continue
            log(f"TRY_PROFILE {profile}")
            ctx = None
            try:
                ctx, page = launch(p, profile)
                page.goto(
                    f"https://search.google.com/search-console?resource_id={RESOURCE}",
                    wait_until="domcontentloaded",
                    timeout=90000,
                )
                time.sleep(5)
                log("url " + page.url[:140])
                if "accounts.google.com" in page.url:
                    try_login(page)
                    time.sleep(4)
                    page.goto(
                        f"https://search.google.com/search-console?resource_id={RESOURCE}",
                        wait_until="domcontentloaded",
                        timeout=90000,
                    )
                    time.sleep(5)
                if in_gsc(page) or try_login(page):
                    log(f"AUTH_OK {profile}")
                    ok = scrape(page)
                    ctx.close()
                    if ok:
                        log("SCRAPE_OK")
                        return 0
                else:
                    log(f"AUTH_FAIL {profile}")
                    page.screenshot(
                        path=str(OUT / f"gsc-bl-authfail-{profile.name}.png"),
                        full_page=True,
                    )
            except Exception as e:
                log(f"ERR {profile}: {e}")
            finally:
                if ctx:
                    try:
                        ctx.close()
                    except Exception:
                        pass
    log("ALL_AUTH_FAIL")
    return 2


if __name__ == "__main__":
    raise SystemExit(main())
