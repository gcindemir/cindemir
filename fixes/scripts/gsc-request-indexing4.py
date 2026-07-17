#!/usr/bin/env python3
"""Request indexing via GSC — Turkish button: Dizine eklenmesini iste."""
import os
import shutil
import tempfile
import time
from pathlib import Path
from urllib.parse import quote

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
LOG = ROOT / "fixes/gsc-request-indexing4.log"
SHOT = ROOT / "fixes"
RESOURCE = quote("https://cindemirlaw.com/", safe="")

# Prioritize not-indexed / high-value URLs first (quota ~10-20/day)
URLS = [
    "https://cindemirlaw.com/onas/",
    "https://cindemirlaw.com/debt-recovery/",
    "https://cindemirlaw.com/deportation/",
    "https://cindemirlaw.com/criminal-record/",
    "https://cindemirlaw.com/divorce/",
    "https://cindemirlaw.com/company-for-foreigners/",
    "https://cindemirlaw.com/stati/",
    "https://cindemirlaw.com/komanda/",
    "https://cindemirlaw.com/nashiyurist/",
    "https://cindemirlaw.com/about-us/",
    "https://cindemirlaw.com/articles/",
    "https://cindemirlaw.com/team/",
    "https://cindemirlaw.com/services/",
    "https://cindemirlaw.com/contacts/",
    "https://cindemirlaw.com/",
]


def log(m: str) -> None:
    print(m, flush=True)
    with LOG.open("a") as f:
        f.write(m + "\n")


def shot(page, name: str) -> None:
    try:
        page.screenshot(path=str(SHOT / name), full_page=True)
    except Exception as e:
        log(f"shot {name}: {e}")


def click_text(page, texts, timeout=6000) -> str | None:
    for t in texts:
        # exact / contains via get_by_text
        for exact in (True, False):
            loc = page.get_by_text(t, exact=exact)
            try:
                if loc.count() == 0:
                    continue
                loc.first.scroll_into_view_if_needed(timeout=3000)
                loc.first.click(timeout=timeout)
                return t
            except Exception:
                continue
        # CSS fallback
        for sel in [f'button:has-text("{t}")', f'[role="button"]:has-text("{t}")', f'span:has-text("{t}")', f'a:has-text("{t}")']:
            loc = page.locator(sel)
            try:
                if loc.count() == 0:
                    continue
                loc.first.scroll_into_view_if_needed(timeout=3000)
                loc.first.click(timeout=timeout)
                return sel
            except Exception:
                continue
    return None


def inspect_input(page):
    for sel in [
        'input[aria-label*="URL"]',
        'input[aria-label*="Denet"]',
        'input[placeholder*="URL"]',
        'input[type="search"]',
        'input[type="text"]',
    ]:
        loc = page.locator(sel)
        for i in range(min(loc.count(), 8)):
            el = loc.nth(i)
            try:
                if el.is_visible():
                    box = el.bounding_box()
                    if box and box["width"] >= 150:
                        return el
            except Exception:
                continue
    return None


def request_one(page, url: str, idx: int) -> str:
    page.goto(
        f"https://search.google.com/search-console?resource_id={RESOURCE}",
        wait_until="domcontentloaded",
        timeout=90000,
    )
    time.sleep(4)
    click_text(page, ["URL Denetimi", "URL denetimi", "URL Inspection"], timeout=8000)
    time.sleep(3)

    el = inspect_input(page)
    if el is None:
        shot(page, f"gsc4-{idx:02d}-noinput.png")
        return "no_input"

    el.click()
    el.fill("")
    el.fill(url)
    el.press("Enter")
    # Wait for Google Index result panel
    for _ in range(30):
        time.sleep(1)
        body = page.locator("body").inner_text()
        if "URL Google'da" in body or "URL is on Google" in body or "URL, Google" in body:
            break
    time.sleep(2)
    shot(page, f"gsc4-{idx:02d}-inspect.png")

    # Do NOT start live test first — request button is on the Google Index view.
    clicked = click_text(
        page,
        [
            "DİZİNE EKLENMESİNİ İSTE",
            "Dizine eklenmesini iste",
            "REQUEST INDEXING",
            "Request indexing",
        ],
        timeout=8000,
    )
    if not clicked:
        # Maybe already queued / quota — read status
        body = page.locator("body").inner_text()
        snip = " | ".join(
            ln.strip()
            for ln in body.splitlines()
            if any(
                k in ln.lower()
                for k in [
                    "dizin",
                    "index",
                    "iste",
                    "quota",
                    "kota",
                    "google'da",
                    "keşfedildi",
                    "mevcut",
                    "yok",
                ]
            )
        )[:500]
        shot(page, f"gsc4-{idx:02d}-nobtn.png")
        return f"no_btn [{snip}]"

    log(f"{url}: clicked={clicked}")
    time.sleep(5)
    shot(page, f"gsc4-{idx:02d}-after.png")

    # Confirm / wait modal
    click_text(page, ["Tamam", "OK", "Got it", "Anladım"], timeout=5000)
    time.sleep(8)
    # Sometimes another confirm after indexing request finishes
    click_text(page, ["Tamam", "OK", "Got it", "Anladım"], timeout=3000)
    shot(page, f"gsc4-{idx:02d}-done.png")
    body = page.locator("body").inner_text()
    snip = " | ".join(
        ln.strip()
        for ln in body.splitlines()
        if any(k in ln.lower() for k in ["dizin", "index", "iste", "gönder", "kuyruk", "queue", "kota", "quota", "başar"])
    )[:500]
    return f"ok [{snip}]"


def main() -> int:
    LOG.write_text("")
    os.system("pkill -9 -f google-chrome 2>/dev/null")
    time.sleep(3)
    tmp = Path(tempfile.mkdtemp(prefix="gscidx4-"))
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
