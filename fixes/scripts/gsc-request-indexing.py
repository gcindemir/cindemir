#!/usr/bin/env python3
"""Request Google indexing for cornerstone URLs via Search Console URL Inspection."""
import os
import shutil
import tempfile
import time
from pathlib import Path
from urllib.parse import quote

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
LOG = ROOT / "fixes/gsc-request-indexing.log"
BASE = "https://search.google.com/search-console"
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
    "https://cindemirlaw.com/contacts/",
    "https://cindemirlaw.com/kontak/",
    "https://cindemirlaw.com/divorce/",
    "https://cindemirlaw.com/debt-recovery/",
    "https://cindemirlaw.com/deportation/",
    "https://cindemirlaw.com/criminal-record/",
]


def log(m):
    print(m, flush=True)
    with LOG.open("a") as f:
        f.write(m + "\n")


def request_one(page, url: str) -> str:
    inspect = f"{BASE}/inspect?resource_id={RESOURCE}&id={quote(url, safe='')}"
    page.goto(inspect, wait_until="domcontentloaded", timeout=90000)
    time.sleep(5)

    # Click "Test live URL" / "Canlı URL'yi test et" if present
    for sel in [
        'text=Test live URL',
        'text=Canlı URL\'yi test et',
        'button:has-text("Test live")',
        'button:has-text("Canlı")',
    ]:
        loc = page.locator(sel)
        if loc.count():
            try:
                loc.first.click(timeout=5000)
                log(f"{url}: clicked live test")
                time.sleep(12)
                break
            except Exception as e:
                log(f"{url}: live test err={e}")

    # Request indexing
    for sel in [
        'text=Request indexing',
        'text=Dizin oluşturma isteğinde bulun',
        'button:has-text("Request indexing")',
        'button:has-text("Dizin")',
    ]:
        loc = page.locator(sel)
        if loc.count():
            try:
                loc.first.click(timeout=5000)
                log(f"{url}: clicked request indexing")
                time.sleep(8)
                # Confirm OK / Gönder
                for ok in ['button:has-text("OK")', 'button:has-text("Tamam")', '[role="button"]:has-text("OK")']:
                    btn = page.locator(ok)
                    if btn.count():
                        try:
                            btn.first.click(timeout=3000)
                            time.sleep(2)
                        except Exception:
                            pass
                body = page.locator("body").inner_text()[:500]
                return f"requested: {body[:200].replace(chr(10),' ')}"
            except Exception as e:
                return f"click_fail={e}"

    body = page.locator("body").inner_text()[:400].replace("\n", " ")
    return f"no_button body={body}"


def main():
    LOG.write_text("")
    os.system("pkill -9 -f google-chrome 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="gscidx-"))
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
        page.goto(
            f"{BASE}/welcome?resource_id={RESOURCE}",
            wait_until="domcontentloaded",
            timeout=90000,
        )
        time.sleep(5)
        page.screenshot(path=str(ROOT / "fixes/gsc-idx-home.png"), full_page=True)
        log(f"home={page.url}")

        for i, url in enumerate(URLS):
            try:
                result = request_one(page, url)
                log(f"[{i+1}/{len(URLS)}] {url} -> {result}")
                page.screenshot(
                    path=str(ROOT / f"fixes/gsc-idx-{i+1:02d}.png"),
                    full_page=True,
                )
            except Exception as e:
                log(f"[{i+1}/{len(URLS)}] {url} ERR={e}")
            time.sleep(2)

        ctx.close()
    log("DONE")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
