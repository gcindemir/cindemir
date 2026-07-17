#!/usr/bin/env python3
"""Pin P0 post + WhatsApp CTA from page sidebar. No personal settings."""
import os, time
from playwright.sync_api import sync_playwright

PROFILE = "/tmp/fb-jifkln9c"
PAGE_ID = "100066585793269"
PAGE_HOME = "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"

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

    # scroll feed for P0 post
    for _ in range(3):
        page.mouse.wheel(0, 800)
        time.sleep(1)
    page.screenshot(path="/workspace/fixes/fb-page-scroll.png", full_page=True)

    post = page.locator('div[role="article"]').filter(has_text="opening-a-company")
    print("p0 posts:", post.count(), flush=True)
    if post.count():
        post.first.locator('[aria-label="Bu gönderi için eylemler"]').click(timeout=6000)
        time.sleep(2)
        page.get_by_text("Gönderiyi sabitle").first.click(timeout=5000)
        time.sleep(2)
        page.get_by_role("button", name="Sabitle").click(timeout=5000)
        print("pinned", flush=True)

    # Page settings via sidebar link
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(4)
    link = page.locator(f'a[href*="{PAGE_ID}/settings"], a[href*="/settings/?page_id={PAGE_ID}"]')
    if not link.count():
        # click Ayarlar under manage page - second occurrence often page settings
        links = page.locator('a').filter(has_text="Ayarlar")
        print("ayarlar links:", links.count(), flush=True)
        for i in range(links.count()):
            href = links.nth(i).get_attribute("href") or ""
            print(i, href[:80], flush=True)
        links.filter(has=page.locator(f'[href*="{PAGE_ID}"]')).first.click(timeout=8000)
    else:
        link.first.click(timeout=8000)
    time.sleep(6)
    page.screenshot(path="/workspace/fixes/fb-page-settings2.png", full_page=True)
    print("settings url:", page.url, flush=True)

    if PAGE_ID in page.url:
        page.get_by_placeholder("Ayarlarda ara").fill("Eylem düğmesi")
        time.sleep(2)
        page.keyboard.press("Enter")
        time.sleep(3)
        page.locator('span').filter(has_text="Eylem düğmesi").first.click(timeout=6000)
        time.sleep(2)
        page.locator('span').filter(has_text="WhatsApp").first.click(timeout=6000)
        time.sleep(2)
        page.locator('input:visible').first.fill("+905325680647")
        page.get_by_role("button", name="Kaydet").last.click(timeout=5000)
        print("cta saved", flush=True)

    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(4)
    page.screenshot(path="/workspace/fixes/fb-page-final3.png", full_page=True)
    ctx.close()
