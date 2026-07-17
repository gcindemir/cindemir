#!/usr/bin/env python3
"""Final: publish P0 post, pin, WhatsApp CTA. Page 100066585793269 only."""
import os, time
from playwright.sync_api import sync_playwright

PROFILE = "/tmp/fb-jifkln9c"
PAGE_ID = "100066585793269"
PAGE_HOME = "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
PAGE_SETTINGS = f"https://www.facebook.com/{PAGE_ID}/settings/?tab=settings"

POST = """Foreign clients in Türkiye — practical English guides from Cindemir Law Office:

Company formation: https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/
Deportation: https://cindemirlaw.com/deportation-law-in-turkey/
Debt recovery: https://cindemirlaw.com/debt-recovery-in-turkey/
Criminal record: https://cindemirlaw.com/getting-criminal-record-in-turkey/
https://cindemirlaw.com/about-us/"""

def snap(p, n):
    p.screenshot(path=f"/workspace/fixes/fb-page-{n}.png", full_page=True)
    print(n, flush=True)

with sync_playwright() as pw:
    os.system("pkill -9 -f 'google-chrome.*fb-jifkln9c' 2>/dev/null")
    time.sleep(2)
    ctx = pw.chromium.launch_persistent_context(
        PROFILE, headless=False, channel="chrome",
        args=["--no-sandbox", "--disable-dev-shm-usage"],
        env={**os.environ, "DISPLAY": ":1"}, viewport={"width": 1500, "height": 950},
    )
    page = ctx.pages[0] if ctx.pages else ctx.new_page()

    # --- POST ---
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)

    if page.get_by_text("Gönderi ayarları", exact=False).count():
        page.get_by_role("button", name="Paylaş", exact=True).click(timeout=8000)
        time.sleep(12)
        print("published draft", flush=True)
    elif not page.locator('div[role="article"]').filter(has_text="opening-a-company").count():
        page.locator('span').filter(has_text="düşünüyor").first.click(timeout=8000)
        time.sleep(2)
        page.locator('div[contenteditable="true"]').last.click(timeout=8000)
        page.keyboard.type(POST, delay=2)
        time.sleep(2)
        page.get_by_role("button", name="İleri").click(timeout=8000)
        time.sleep(3)
        snap(page, "before-share")
        page.get_by_role("button", name="Paylaş", exact=True).click(timeout=10000)
        time.sleep(12)
        print("published new", flush=True)
    snap(page, "after-post")

    # --- PIN ---
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    post = page.locator('div[role="article"]').filter(has_text="Company formation")
    if not post.count():
        post = page.locator('div[role="article"]').filter(has_text="opening-a-company")
    if post.count():
        post.first.locator('[aria-label="Bu gönderi için eylemler"]').click(timeout=6000)
        time.sleep(2)
        page.get_by_text("Gönderiyi sabitle").first.click(timeout=5000)
        time.sleep(2)
        page.get_by_role("button", name="Sabitle").click(timeout=5000)
        print("pinned", flush=True)
        time.sleep(3)

    # --- WHATSAPP CTA via page-scoped settings URL ---
    page.goto(PAGE_SETTINGS, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    snap(page, "settings")
    print("url", page.url, flush=True)

    if PAGE_ID not in page.url:
        page.locator(f'a[href*="/{PAGE_ID}/settings"]').first.click(timeout=8000)
        time.sleep(5)

    # Sidebar: how people contact you
    page.locator('span').filter(has_text="İnsanlar seni nasıl bulabilir").first.click(timeout=8000)
    time.sleep(4)
    snap(page, "contact-settings")

    page.locator('span').filter(has_text="Eylem düğmesi").first.click(timeout=8000)
    time.sleep(3)
    snap(page, "cta-options")

    page.locator('span').filter(has_text="WhatsApp").first.click(timeout=8000)
    time.sleep(2)
    page.locator('input:visible').first.fill("+905325680647")
    page.get_by_role("button", name="Kaydet").last.click(timeout=5000)
    time.sleep(3)
    snap(page, "cta-done")

    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(5)
    snap(page, "final")
    body = page.locator("body").inner_text()
    print("checks:", "WhatsApp" in body, "opening-a-company" in body, "532 568" in body, flush=True)
    ctx.close()
