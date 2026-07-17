#!/usr/bin/env python3
"""Request indexing via GSC URL Inspection UI (Turkish)."""
import os
import shutil
import tempfile
import time
from pathlib import Path
from urllib.parse import quote

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
LOG = ROOT / "fixes/gsc-request-indexing3.log"
SHOT = ROOT / "fixes"
RESOURCE = quote("https://cindemirlaw.com/", safe="")

URLS = [
    "https://cindemirlaw.com/",
    "https://cindemirlaw.com/about-us/",
    "https://cindemirlaw.com/onas/",
    "https://cindemirlaw.com/articles/",
    "https://cindemirlaw.com/stati/",
    "https://cindemirlaw.com/team/",
    "https://cindemirlaw.com/komanda/",
    "https://cindemirlaw.com/services/",
    "https://cindemirlaw.com/nashiyurist/",
    "https://cindemirlaw.com/divorce/",
    "https://cindemirlaw.com/debt-recovery/",
    "https://cindemirlaw.com/deportation/",
    "https://cindemirlaw.com/criminal-record/",
    "https://cindemirlaw.com/company-for-foreigners/",
    "https://cindemirlaw.com/contacts/",
]


def log(m: str) -> None:
    print(m, flush=True)
    with LOG.open("a") as f:
        f.write(m + "\n")


def shot(page, name: str) -> None:
    try:
        page.screenshot(path=str(SHOT / name), full_page=True)
    except Exception as e:
        log(f"shot err {name}: {e}")


def click_first(page, selectors, timeout=4000) -> str | None:
    for sel in selectors:
        loc = page.locator(sel)
        try:
            n = loc.count()
        except Exception:
            n = 0
        if not n:
            continue
        try:
            loc.first.click(timeout=timeout)
            return sel
        except Exception as e:
            log(f"click miss {sel}: {e}")
    return None


def open_property(page) -> None:
    urls = [
        f"https://search.google.com/search-console?resource_id={RESOURCE}",
        f"https://search.google.com/search-console/index?resource_id={RESOURCE}",
        "https://search.google.com/search-console",
    ]
    for u in urls:
        page.goto(u, wait_until="domcontentloaded", timeout=90000)
        time.sleep(5)
        body = page.locator("body").inner_text()[:800]
        log(f"open {page.url}")
        if "cindemirlaw" in body.lower() or "Genel Bakış" in body or "Overview" in body:
            # If property picker, choose cindemirlaw
            if "Web sitesi ekle" in body and "cindemirlaw" not in page.url:
                pass
            else:
                return
        # Try property switcher
        clicked = click_first(
            page,
            [
                'text=https://cindemirlaw.com/',
                '[aria-label*="cindemirlaw"]',
                'text=cindemirlaw.com',
            ],
        )
        if clicked:
            time.sleep(4)
            return


def find_inspect_input(page):
    candidates = [
        'input[aria-label*="URL"]',
        'input[aria-label*="Inspect"]',
        'input[aria-label*="Denet"]',
        'input[placeholder*="URL"]',
        'input[placeholder*="Inspect"]',
        'input[placeholder*="Denet"]',
        'input[type="search"]',
        'header input[type="text"]',
        'input[type="text"]',
    ]
    for sel in candidates:
        loc = page.locator(sel)
        try:
            n = loc.count()
        except Exception:
            continue
        for i in range(min(n, 6)):
            el = loc.nth(i)
            try:
                if not el.is_visible():
                    continue
                box = el.bounding_box()
                if not box or box["width"] < 120:
                    continue
                return el
            except Exception:
                continue
    return None


def request_one(page, url: str, idx: int) -> str:
    # Prefer inspect page with query param used by current GSC UI
    inspect_urls = [
        f"https://search.google.com/search-console/inspect?resource_id={RESOURCE}",
        f"https://search.google.com/search-console/index/drilldown?resource_id={RESOURCE}",
    ]

    # Click URL Inspection in sidebar first
    open_property(page)
    time.sleep(2)
    nav = click_first(
        page,
        [
            'a:has-text("URL Denetimi")',
            'a:has-text("URL Inspection")',
            '[aria-label="URL Denetimi"]',
            '[aria-label="URL Inspection"]',
            'text=URL Denetimi',
            'text=URL Inspection',
        ],
    )
    log(f"{url}: nav={nav} url={page.url}")
    time.sleep(3)

    el = find_inspect_input(page)
    if el is None:
        # Try direct inspect page
        page.goto(inspect_urls[0], wait_until="domcontentloaded", timeout=90000)
        time.sleep(4)
        el = find_inspect_input(page)

    if el is None:
        shot(page, f"gsc-idx3-{idx:02d}-noinput.png")
        return "no_inspect_input"

    el.click(timeout=5000)
    # Clear + fill
    try:
        el.fill("")
    except Exception:
        pass
    el.fill(url)
    el.press("Enter")
    log(f"{url}: submitted to inspect")
    time.sleep(10)
    shot(page, f"gsc-idx3-{idx:02d}-inspect.png")

    # Live test helps freshest request
    live = click_first(
        page,
        [
            'text=Canlı URL\'yi test et',
            'text=Test live URL',
            'button:has-text("Canlı")',
            'button:has-text("Test live")',
            'span:has-text("Canlı URL")',
        ],
        timeout=6000,
    )
    if live:
        log(f"{url}: live={live}")
        time.sleep(15)
        shot(page, f"gsc-idx3-{idx:02d}-live.png")

    req = click_first(
        page,
        [
            'text=Dizin oluşturma isteğinde bulun',
            'text=Request indexing',
            'button:has-text("Dizin oluşturma")',
            'button:has-text("Request indexing")',
            'span:has-text("Dizin oluşturma isteğinde bulun")',
            'span:has-text("Request indexing")',
        ],
        timeout=8000,
    )
    if not req:
        body = page.locator("body").inner_text()[:700].replace("\n", " | ")
        shot(page, f"gsc-idx3-{idx:02d}-nobtn.png")
        return f"no_request_btn body={body}"

    log(f"{url}: request_clicked={req}")
    time.sleep(6)
    # Confirm dialogs
    click_first(
        page,
        [
            'button:has-text("Tamam")',
            'button:has-text("OK")',
            'button:has-text("Got it")',
            '[role="button"]:has-text("Tamam")',
            '[role="button"]:has-text("OK")',
        ],
        timeout=4000,
    )
    time.sleep(3)
    shot(page, f"gsc-idx3-{idx:02d}-done.png")
    body = page.locator("body").inner_text()[:500].replace("\n", " | ")
    return f"ok body={body}"


def main() -> int:
    LOG.write_text("")
    os.system("pkill -9 -f google-chrome 2>/dev/null")
    time.sleep(3)

    tmp = Path(tempfile.mkdtemp(prefix="gscidx3-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if not s.exists():
            continue
        d = tmp / item
        if s.is_dir():
            shutil.copytree(s, d, dirs_exist_ok=True)
        else:
            shutil.copy2(s, d)

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1500, "height": 950},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        open_property(page)
        shot(page, "gsc-idx3-00-home.png")
        log(f"start url={page.url}")

        ok = 0
        for i, url in enumerate(URLS, start=1):
            try:
                result = request_one(page, url, i)
                log(f"[{i}/{len(URLS)}] {url} -> {result}")
                if result.startswith("ok"):
                    ok += 1
            except Exception as e:
                log(f"[{i}/{len(URLS)}] {url} ERR={e}")
            time.sleep(2)

        ctx.close()
    log(f"DONE ok={ok}/{len(URLS)}")
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
