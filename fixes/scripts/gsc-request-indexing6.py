#!/usr/bin/env python3
"""Open GSC Google login, wait for interactive password, then request indexing."""
import os
import shutil
import tempfile
import time
from pathlib import Path
from urllib.parse import quote

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
LOG = ROOT / "fixes/gsc-request-indexing6.log"
SHOT = ROOT / "fixes"
EMAIL = "gokhancindemir44@gmail.com"
RESOURCE = quote("https://cindemirlaw.com/", safe="")
WAIT_LOGIN_SEC = 300  # 5 minutes for interactive login

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


def in_gsc(page) -> bool:
    try:
        body = page.locator("body").inner_text()
    except Exception:
        return False
    return (
        "URL Denetimi" in body
        or "URL denetimi" in body
        or "Genel Bakış" in body
        or ("Overview" in body and "cindemirlaw" in body.lower())
    )


def launch(p):
    tmp = Path(tempfile.mkdtemp(prefix="gscwait-"))
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
    )
    page = ctx.pages[0] if ctx.pages else ctx.new_page()
    page.add_init_script(
        "Object.defineProperty(navigator, 'webdriver', {get: () => undefined});"
    )
    return ctx, page


def wait_for_login(page) -> bool:
    page.goto(
        f"https://search.google.com/search-console?resource_id={RESOURCE}",
        wait_until="domcontentloaded",
        timeout=90000,
    )
    time.sleep(4)
    if in_gsc(page):
        log("already logged in")
        return True

    cont = quote(
        f"https://search.google.com/search-console?resource_id={RESOURCE}",
        safe="",
    )
    page.goto(
        f"https://accounts.google.com/ServiceLogin?service=sitemaps&continue={cont}",
        wait_until="domcontentloaded",
        timeout=90000,
    )
    time.sleep(4)
    shot(page, "gsc6-login-0.png")

    # Prefill email to save a step
    for sel in ['input[type="email"]', "#identifierId", 'input[name="identifier"]']:
        loc = page.locator(sel)
        if loc.count() and loc.first.is_visible():
            loc.first.click()
            loc.first.fill(EMAIL)
            log(f"prefilled {EMAIL}")
            time.sleep(1)
            try:
                page.get_by_role("button", name="Next").click(timeout=5000)
            except Exception:
                try:
                    page.locator("#identifierNext").click(timeout=5000)
                except Exception:
                    pass
            break

    time.sleep(5)
    shot(page, "gsc6-login-wait.png")
    log(
        f"WAITING_FOR_PASSWORD up to {WAIT_LOGIN_SEC}s — "
        "please enter Google password in the cloud browser"
    )

    deadline = time.time() + WAIT_LOGIN_SEC
    last_shot = 0
    while time.time() < deadline:
        # If still on about/marketing after password, navigate to property
        if "search-console" in page.url and "accounts.google" not in page.url:
            if "about" in page.url:
                page.goto(
                    f"https://search.google.com/search-console?resource_id={RESOURCE}",
                    wait_until="domcontentloaded",
                    timeout=90000,
                )
                time.sleep(4)
            if in_gsc(page):
                shot(page, "gsc6-login-ok.png")
                log("LOGIN_OK")
                return True
        # Periodic screenshot so user sees progress in artifacts
        if time.time() - last_shot > 30:
            shot(page, "gsc6-login-live.png")
            log(f"still waiting… url={page.url}")
            last_shot = time.time()
        time.sleep(3)

    shot(page, "gsc6-login-timeout.png")
    log("LOGIN_TIMEOUT")
    return False


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
        shot(page, f"gsc6-{idx:02d}-noinput.png")
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
    shot(page, f"gsc6-{idx:02d}-inspect.png")
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
    # Request indexing runs a live indexability test first (1–2 min).
    for _ in range(90):
        time.sleep(2)
        body = page.locator("body").inner_text()
        if any(
            k in body
            for k in [
                "öncelikli bir tarama",
                "Dizine eklenmesi istendi",
                "Kota Aşıldı",
                "günlük kotanızı",
                "reddedildi",
                "priority crawl",
            ]
        ):
            break
        # Dismiss intermediate "testing" wait if cancel not needed
    click_text(page, ["Tamam", "OK", "Anladım", "Kapat"])
    time.sleep(2)
    shot(page, f"gsc6-{idx:02d}-done.png")
    body = page.locator("body").inner_text()
    if "öncelikli bir tarama" in body or "Dizine eklenmesi istendi" in body or "priority crawl" in body.lower():
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
        ctx, page = launch(p)
        if not wait_for_login(page):
            log("FAILED — no interactive login within timeout")
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
                    log("Stopping: daily quota hit")
                    break
            except Exception as e:
                log(f"[{i}/{len(URLS)}] {url} ERR={e}")
            time.sleep(2)
        # Persist cookies back? can't easily merge into main profile; session is enough for this run
        ctx.close()
    log(f"DONE queued={ok}")
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
