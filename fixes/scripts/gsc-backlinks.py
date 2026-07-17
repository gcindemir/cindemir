#!/usr/bin/env python3
"""Pull Google Search Console external links for cindemirlaw.com."""
import os
import re
import shutil
import tempfile
import time
from pathlib import Path
from urllib.parse import quote

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
SHOT = ROOT / "fixes"
LOG = ROOT / "fixes/gsc-backlinks.log"
RESOURCE = quote("https://cindemirlaw.com/", safe="")
# Prefer session that worked earlier
PREFERRED = Path("/tmp/gscwait-hb0x7jdi")
PROFILE = Path.home() / ".config/google-chrome"


def log(m):
    print(m, flush=True)
    with LOG.open("a") as f:
        f.write(m + "\n")


def click_text(page, texts, timeout=6000):
    for t in texts:
        loc = page.get_by_text(t, exact=False)
        try:
            if loc.count() == 0:
                continue
            loc.first.scroll_into_view_if_needed(timeout=3000)
            loc.first.click(timeout=timeout)
            return t
        except Exception:
            continue
    return None


def in_gsc(page):
    body = page.locator("body").inner_text()
    return "Bağlantılar" in body or "Links" in body or "Genel Bakış" in body or "URL Denetimi" in body


def launch(p):
    if PREFERRED.exists():
        profile = PREFERRED
        log(f"using preferred {profile}")
    else:
        tmp = Path(tempfile.mkdtemp(prefix="gscbl-"))
        for item in ["Default", "Local State"]:
            s = PROFILE / item
            if not s.exists():
                continue
            d = tmp / item
            if s.is_dir():
                shutil.copytree(
                    s,
                    d,
                    dirs_exist_ok=True,
                    ignore=shutil.ignore_patterns(
                        "Singleton*", "GPUCache", "Code Cache", "Cache", "GrShaderCache"
                    ),
                )
            else:
                shutil.copy2(s, d)
        profile = tmp
        log(f"using copy {profile}")

    for lock in ["SingletonLock", "SingletonCookie", "SingletonSocket"]:
        lp = profile / lock
        if lp.exists() or lp.is_symlink():
            try:
                lp.unlink()
            except Exception:
                pass

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
        viewport={"width": 1500, "height": 950},
    )
    page = ctx.pages[0] if ctx.pages else ctx.new_page()
    page.add_init_script(
        "Object.defineProperty(navigator, 'webdriver', {get: () => undefined});"
    )
    return ctx, page


def main():
    LOG.write_text("")
    os.system("pkill -9 -f google-chrome 2>/dev/null; pkill -9 chrome 2>/dev/null")
    time.sleep(3)

    with sync_playwright() as p:
        ctx, page = launch(p)
        # Direct links report URLs (TR UI)
        urls = [
            f"https://search.google.com/search-console/links?resource_id={RESOURCE}",
            f"https://search.google.com/search-console/links/drilldown?resource_id={RESOURCE}&type=EXTERNAL&domain=",
            f"https://search.google.com/search-console?resource_id={RESOURCE}",
        ]
        page.goto(urls[0], wait_until="domcontentloaded", timeout=90000)
        time.sleep(6)
        page.screenshot(path=str(SHOT / "gsc-backlinks-0.png"), full_page=True)
        log(f"url={page.url}")
        body = page.locator("body").inner_text()
        log("body0=" + body[:800].replace("\n", " | "))

        if not in_gsc(page) or "about" in page.url:
            log("NEED_LOGIN")
            # try overview then click Links
            page.goto(urls[2], wait_until="domcontentloaded", timeout=90000)
            time.sleep(5)
            page.screenshot(path=str(SHOT / "gsc-backlinks-login.png"), full_page=True)
            if not in_gsc(page):
                log("AUTH_FAIL")
                ctx.close()
                return 2
            click_text(page, ["Bağlantılar", "Links"])
            time.sleep(5)

        page.screenshot(path=str(SHOT / "gsc-backlinks-1.png"), full_page=True)
        body = page.locator("body").inner_text()
        log("links_page=" + body[:1500].replace("\n", " | "))

        # Click external linking sites / top linked pages
        for label in [
            "En çok bağlantı veren siteler",
            "Top linking sites",
            "Dış bağlantılar",
            "External links",
            "More",
            "Diğer",
            "En çok bağlantı verilen sayfalar",
            "Top linked pages",
        ]:
            if click_text(page, [label], timeout=3000):
                log(f"clicked {label}")
                time.sleep(4)
                page.screenshot(
                    path=str(SHOT / f"gsc-backlinks-{re.sub(r'[^a-z0-9]+', '-', label.lower())[:40]}.png"),
                    full_page=True,
                )
                body = page.locator("body").inner_text()
                log(f"after_{label}=" + body[:2000].replace("\n", " | "))

        # Try export if available
        click_text(page, ["Dışa aktar", "Export", "İndir", "Download"])
        time.sleep(3)
        page.screenshot(path=str(SHOT / "gsc-backlinks-final.png"), full_page=True)

        # Save full text dump
        (SHOT / "gsc-backlinks-dump.txt").write_text(page.locator("body").inner_text())
        ctx.close()
    log("DONE")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
