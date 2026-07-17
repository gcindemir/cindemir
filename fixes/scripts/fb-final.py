#!/usr/bin/env python3
"""WhatsApp CTA via Düzenle + repost P0. Cindemir page only."""
import os, time
from playwright.sync_api import sync_playwright

PROFILE = "/tmp/fb-jifkln9c"
PAGE_HOME = "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
POST = "Foreign clients in Türkiye — P0 guides: https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/ https://cindemirlaw.com/deportation-law-in-turkey/ https://cindemirlaw.com/debt-recovery-in-turkey/ https://cindemirlaw.com/getting-criminal-record-in-turkey/ https://cindemirlaw.com/about-us/"

with sync_playwright() as pw:
    os.system("pkill -9 -f 'google-chrome.*fb-jifkln9c' 2>/dev/null")
    time.sleep(2)
    ctx = pw.chromium.launch_persistent_context(
        PROFILE, headless=False, channel="chrome",
        args=["--no-sandbox", "--disable-dev-shm-usage"],
        env={**os.environ, "DISPLAY": ":1"}, viewport={"width": 1500, "height": 950},
    )
    page = ctx.pages[0] if ctx.pages else ctx.new_page()
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)

    # Düzenle menu
    page.locator('div[role="button"]:has-text("Düzenle")').first.click(timeout=8000)
    time.sleep(3)
    page.screenshot(path="/workspace/fixes/fb-page-edit-menu.png", full_page=True)

    for t in ["Eylem düğmesi", "Action button", "Düğme", "Button"]:
        loc = page.get_by_text(t, exact=False)
        if loc.count():
            loc.first.click(timeout=5000)
            print("found", t, flush=True)
            time.sleep(2)
            break

    page.screenshot(path="/workspace/fixes/fb-page-edit-cta.png", full_page=True)
    for t in ["WhatsApp", "WhatsApp mesajı"]:
        loc = page.get_by_text(t, exact=False)
        if loc.count():
            loc.first.click(timeout=5000)
            time.sleep(2)
            page.locator('input:visible').first.fill("+905325680647")
            page.get_by_role("button", name="Kaydet").last.click(timeout=5000)
            print("wa cta", flush=True)
            break

    page.keyboard.press("Escape")
    time.sleep(2)

    # Post
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(5)
    page.locator('span').filter(has_text="düşünüyor").first.click(timeout=8000)
    time.sleep(2)
    page.locator('div[contenteditable="true"]').last.fill(POST)
    time.sleep(2)
    page.get_by_role("button", name="İleri").click(timeout=8000)
    time.sleep(2)
    page.get_by_role("button", name="Paylaş", exact=True).click(timeout=10000)
    time.sleep(10)
    page.screenshot(path="/workspace/fixes/fb-page-repost.png", full_page=True)
    print("posted", flush=True)

    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(5)
    body = page.locator("body").inner_text()
    page.screenshot(path="/workspace/fixes/fb-page-end.png", full_page=True)
    print("wa", "WhatsApp" in body, "p0", "opening-a-company" in body, "532", "532 568" in body, flush=True)
    ctx.close()
