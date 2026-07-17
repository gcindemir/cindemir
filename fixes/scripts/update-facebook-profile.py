#!/usr/bin/env python3
"""FB remaining: post+pin, location, CTA. Skip bio if already updated."""
import os
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

PROFILE = Path("/tmp/fb-jifkln9c")
ROOT = Path("/workspace")
LOG = ROOT / "fixes/facebook-update.log"

PAGE_HOME = (
    "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
)
PAGE_ABOUT = "https://www.facebook.com/profile.php?id=100066585793269&sk=about"
PAGE_SETTINGS = "https://www.facebook.com/100066585793269/settings"

PINNED = """Foreign clients in Türkiye — practical English guides from Cindemir Law Office:

🏢 Company formation: https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/
✈️ Deportation: https://cindemirlaw.com/deportation-law-in-turkey/
💼 Debt recovery: https://cindemirlaw.com/debt-recovery-in-turkey/
📋 Criminal record: https://cindemirlaw.com/getting-criminal-record-in-turkey/

Istanbul counsel | English & Russian
https://cindemirlaw.com/about-us/"""


def log(m):
    print(m, flush=True)
    with LOG.open("a") as f:
        f.write(m + "\n")


def snap(page, n):
    page.screenshot(path=str(ROOT / f"fixes/fb-{n}.png"), full_page=True)


def close_popups(page):
    for s in ['[aria-label="Kapat"]', '[aria-label="Close"]']:
        try:
            if page.locator(s).count():
                page.locator(s).first.click(timeout=1500)
        except Exception:
            pass


def main():
    LOG.write_text("")
    os.system("pkill -9 -f 'google-chrome.*fb-jifkln9c' 2>/dev/null")
    time.sleep(2)

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(PROFILE),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1500, "height": 950},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()

        # POST via Profesyonel pano composer
        page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
        time.sleep(6)
        close_popups(page)

        existing = page.locator('div[role="article"]').filter(has_text="opening-a-company-in-turkey")
        if existing.count():
            log("p0 post exists")
            snap(page, "post-exists")
        else:
            page.get_by_text("Profesyonel pano", exact=True).first.click(timeout=8000)
            time.sleep(5)
            snap(page, "pro-dash")
            close_popups(page)

            for sel in ['span:has-text("Gönderi oluştur")', 'div[role="button"]:has-text("Oluştur")']:
                if page.locator(sel).count():
                    page.locator(sel).first.click(timeout=6000)
                    break
            time.sleep(3)

            page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
            time.sleep(5)
            # Admin feed composer
            comp = page.locator('div[role="textbox"][contenteditable="true"]').first
            if comp.count():
                comp.click(timeout=8000)
                page.keyboard.type(PINNED, delay=3)
                time.sleep(2)
                snap(page, "post-draft")
                page.keyboard.press("Control+Enter")
                time.sleep(10)
                snap(page, "post-live")
                log("post attempt ctrl+enter")

        # PIN
        page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
        time.sleep(6)
        post = page.locator('div[role="article"]').filter(has_text="Company formation").first
        if not post.count():
            post = page.locator('div[role="article"]').filter(has_text="opening-a-company").first
        if post.count():
            post.scroll_into_view_if_needed()
            post.locator('[aria-label="Bu gönderi için eylemler"]').click(timeout=6000)
            time.sleep(2)
            page.get_by_text("Gönderiyi sabitle", exact=False).first.click(timeout=5000)
            time.sleep(2)
            page.get_by_role("button", name="Sabitle").click(timeout=5000)
            log("pinned")
            snap(page, "pinned")

        # CTA via page settings
        page.goto(PAGE_SETTINGS, wait_until="domcontentloaded", timeout=120000)
        time.sleep(6)
        close_popups(page)
        snap(page, "settings")

        for t in ["Eylem düğmesi", "Action button", "Sayfa düğmesi", "Düğme"]:
            loc = page.get_by_text(t, exact=False)
            if loc.count():
                loc.first.click(timeout=5000)
                time.sleep(3)
                break

        snap(page, "cta-menu")
        try:
            page.get_by_text("İletişime geç", exact=False).first.click(timeout=5000)
            time.sleep(2)
            page.locator('input:visible').first.fill("https://cindemirlaw.com/contacts/")
            page.get_by_role("button", name="Kaydet").click(timeout=5000)
            log("cta set")
        except Exception as e:
            log(f"cta err: {e}")

        snap(page, "final")
        ctx.close()
    log("DONE")


if __name__ == "__main__":
    main()
