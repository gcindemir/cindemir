#!/usr/bin/env python3
"""Stealth GSC login attempt + request indexing for remaining URLs."""
import os
import shutil
import tempfile
import time
from pathlib import Path
from urllib.parse import quote

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
LOG = ROOT / "fixes/gsc-request-indexing5.log"
SHOT = ROOT / "fixes"
EMAIL = "gokhancindemir44@gmail.com"
RESOURCE = quote("https://cindemirlaw.com/", safe="")

URLS = [
    "https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/",
    "https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/",
    "https://cindemirlaw.com/debt-recovery-in-turkey/",
    "https://cindemirlaw.com/criminal-record-deletion-in-turkey-for-foreign-nationals/",
    "https://cindemirlaw.com/contacts/",
    "https://cindemirlaw.com/",
    "https://cindemirlaw.com/kontak/",
    "https://cindemirlaw.com/support/",
    "https://cindemirlaw.com/pod/",
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


def launch_ctx(p):
    tmp = Path(tempfile.mkdtemp(prefix="gscstealth-"))
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
    ctx = p.chromium.launch_persistent_context(
        str(tmp),
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
        locale="en-US",
        user_agent=(
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36"
        ),
    )
    page = ctx.pages[0] if ctx.pages else ctx.new_page()
    page.add_init_script(
        "Object.defineProperty(navigator, 'webdriver', {get: () => undefined});"
    )
    return ctx, page


def ensure_gsc(page) -> bool:
    page.goto(
        f"https://search.google.com/search-console?resource_id={RESOURCE}",
        wait_until="domcontentloaded",
        timeout=90000,
    )
    time.sleep(5)
    body = page.locator("body").inner_text()
    log(f"open {page.url}")
    shot(page, "gsc5s-0.png")
    if "URL Denetimi" in body or "URL denetimi" in body or "Genel Bakış" in body:
        log("already in GSC")
        return True

    # Force login URL
    cont = quote(
        f"https://search.google.com/search-console?resource_id={RESOURCE}",
        safe="",
    )
    page.goto(
        f"https://accounts.google.com/ServiceLogin?service=sitemaps&continue={cont}",
        wait_until="domcontentloaded",
        timeout=90000,
    )
    time.sleep(5)
    shot(page, "gsc5s-1.png")
    log(f"login page {page.url}")

    # Email
    for sel in ['input[type="email"]', "#identifierId", 'input[name="identifier"]']:
        loc = page.locator(sel)
        if loc.count() and loc.first.is_visible():
            loc.first.click()
            loc.first.fill(EMAIL)
            log("email filled")
            break
    time.sleep(1)
    try:
        page.get_by_role("button", name="Next").click(timeout=8000)
    except Exception:
        try:
            page.locator("#identifierNext").click(timeout=8000)
        except Exception as e:
            log(f"next fail: {e}")
    time.sleep(8)
    shot(page, "gsc5s-2.png")
    body = page.locator("body").inner_text()
    log(f"after email url={page.url}")
    log("after email body=" + body[:500].replace("\n", " | "))

    if "may not be secure" in body or "Couldn’t sign you in" in body or "Couldn't sign you in" in body:
        # Try again button once
        click_text(page, ["Try again", "Tekrar dene"])
        time.sleep(5)
        shot(page, "gsc5s-2b.png")
        body = page.locator("body").inner_text()
        log("retry body=" + body[:400].replace("\n", " | "))

    # Password field — only proceed if Chrome autofills; we do not have the password.
    pw = page.locator('input[type="password"]')
    if pw.count() and pw.first.is_visible():
        log("password field present — waiting for autofill (15s)")
        pw.first.click()
        time.sleep(15)
        shot(page, "gsc5s-3.png")
        # If autofilled, click Next
        try:
            val = pw.first.input_value()
        except Exception:
            val = ""
        log(f"password_autofill={'yes' if val else 'no'}")
        if val:
            try:
                page.get_by_role("button", name="Next").click(timeout=8000)
            except Exception:
                page.locator("#passwordNext").click(timeout=8000)
            time.sleep(10)
            shot(page, "gsc5s-4.png")

    # Final check
    page.goto(
        f"https://search.google.com/search-console?resource_id={RESOURCE}",
        wait_until="domcontentloaded",
        timeout=90000,
    )
    time.sleep(6)
    body = page.locator("body").inner_text()
    shot(page, "gsc5s-final.png")
    ok = "URL Denetimi" in body or "URL denetimi" in body or "Genel Bakış" in body
    log(f"gsc_ok={ok} url={page.url}")
    return ok


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
                    if box and box["width"] >= 120:
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
    time.sleep(3)
    click_text(page, ["URL Denetimi", "URL denetimi", "URL Inspection"])
    time.sleep(3)
    el = inspect_input(page)
    if el is None:
        shot(page, f"gsc5s-{idx:02d}-noinput.png")
        return "no_input"
    el.click()
    el.fill("")
    el.fill(url)
    el.press("Enter")
    for _ in range(30):
        time.sleep(1)
        body = page.locator("body").inner_text()
        if "URL Google'da" in body or "URL is on Google" in body or "URL, Google" in body:
            break
    time.sleep(2)
    shot(page, f"gsc5s-{idx:02d}-inspect.png")
    clicked = click_text(
        page,
        [
            "DİZİNE EKLENMESİNİ İSTE",
            "Dizine eklenmesini iste",
            "Request indexing",
            "REQUEST INDEXING",
        ],
    )
    if not clicked:
        return "no_btn"
    log(f"{url}: clicked={clicked}")
    time.sleep(8)
    click_text(page, ["Tamam", "OK", "Anladım", "Kapat"])
    time.sleep(3)
    shot(page, f"gsc5s-{idx:02d}-done.png")
    body = page.locator("body").inner_text()
    if "öncelikli bir tarama" in body or "Dizine eklenmesi istendi" in body:
        return "QUEUED"
    if "Kota Aşıldı" in body or "günlük kotanızı" in body:
        return "QUOTA"
    if "reddedildi" in body:
        return "REJECTED"
    return "OTHER"


def main() -> int:
    LOG.write_text("")
    os.system("pkill -9 -f google-chrome 2>/dev/null; pkill -9 chrome 2>/dev/null")
    time.sleep(3)
    with sync_playwright() as p:
        ctx, page = launch_ctx(p)
        if not ensure_gsc(page):
            log("LOGIN_BLOCKED — Google session expired; automated sign-in rejected")
            ctx.close()
            return 2
        ok = 0
        for i, url in enumerate(URLS, start=1):
            try:
                result = request_one(page, url, i)
                log(f"[{i}/{len(URLS)}] {url} -> {result}")
                if result == "QUEUED":
                    ok += 1
                if result == "QUOTA":
                    break
            except Exception as e:
                log(f"[{i}/{len(URLS)}] {url} ERR={e}")
            time.sleep(2)
        ctx.close()
    log(f"DONE queued={ok}")
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
