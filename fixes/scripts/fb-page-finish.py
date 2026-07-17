#!/usr/bin/env python3
"""
Finish Cindemir Law Office page ONLY (id 100066585793269).
No personal profile. No other business pages.
"""
import os
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

PROFILE = Path("/tmp/fb-jifkln9c")
PAGE_ID = "100066585793269"
PAGE_HOME = (
    "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
)
PAGE_SETTINGS = f"https://www.facebook.com/{PAGE_ID}/settings"


def snap(page, name):
    page.screenshot(path=f"/workspace/fixes/fb-page-{name}.png", full_page=True)
    print(name, flush=True)


def assert_cindemir_page(page):
    assert PAGE_ID in page.url or "Cindemir" in page.locator("body").inner_text(timeout=10000), (
        f"wrong page context: {page.url}"
    )
    body = page.locator("body").inner_text()
    if "Karadeniz" in body or "Ereğli" in body:
        raise RuntimeError("wrong business page — abort")


def close_popups(page):
    for sel in ['[aria-label="Kapat"]', '[aria-label="Close"]']:
        try:
            if page.locator(sel).count():
                page.locator(sel).first.click(timeout=2000)
                time.sleep(0.5)
        except Exception:
            pass


def publish_open_draft(page):
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    close_popups(page)
    assert_cindemir_page(page)

    if page.get_by_text("Gönderi Oluştur", exact=False).count():
        snap(page, "draft-open")
        page.get_by_role("button", name="İleri").click(timeout=8000)
        time.sleep(3)
        snap(page, "draft-next")
        for label in ["Paylaş", "Yayınla", "Post", "Publish"]:
            btn = page.get_by_role("button", name=label)
            if btn.count():
                btn.first.click(timeout=10000)
                print(f"published via {label}", flush=True)
                break
        time.sleep(12)
    elif page.locator('div[role="article"]').filter(has_text="opening-a-company").count():
        print("post already live", flush=True)
    else:
        page.locator('span').filter(has_text="düşünüyor").first.click(timeout=6000)
        time.sleep(2)
        text = """Foreign clients in Türkiye — practical English guides from Cindemir Law Office:

Company formation: https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/
Deportation: https://cindemirlaw.com/deportation-law-in-turkey/
Debt recovery: https://cindemirlaw.com/debt-recovery-in-turkey/
Criminal record: https://cindemirlaw.com/getting-criminal-record-in-turkey/
https://cindemirlaw.com/about-us/"""
        page.locator('div[contenteditable="true"]').last.click(timeout=8000)
        page.keyboard.type(text, delay=2)
        page.get_by_role("button", name="İleri").click(timeout=8000)
        time.sleep(2)
        page.get_by_role("button", name="Paylaş").click(timeout=10000)
        time.sleep(12)
    snap(page, "post-done")


def pin_post(page):
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    assert_cindemir_page(page)
    post = page.locator('div[role="article"]').filter(has_text="Company formation")
    if not post.count():
        post = page.locator('div[role="article"]').filter(has_text="opening-a-company")
    if post.count():
        post.first.scroll_into_view_if_needed()
        post.first.locator('[aria-label="Bu gönderi için eylemler"]').click(timeout=6000)
        time.sleep(2)
        page.get_by_text("Gönderiyi sabitle", exact=False).first.click(timeout=5000)
        time.sleep(2)
        page.get_by_role("button", name="Sabitle").click(timeout=5000)
        print("pinned", flush=True)
        snap(page, "pinned")


def set_whatsapp_cta(page):
    page.goto(PAGE_SETTINGS, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    assert_cindemir_page(page)
    snap(page, "settings")

    # Search settings — page-scoped URL only
    search = page.get_by_placeholder("İhtiyacın olan ayarı bul")
    if not search.count():
        search = page.get_by_placeholder("Ayarlarda ara")
    if search.count():
        search.first.fill("Eylem düğmesi")
        time.sleep(2)
        page.keyboard.press("Enter")
        time.sleep(3)
        snap(page, "search-cta")

    for text in ["Eylem düğmesi", "Action button", "Sayfa düğmesi"]:
        loc = page.get_by_text(text, exact=False)
        if loc.count():
            loc.first.click(timeout=5000)
            time.sleep(3)
            break

    snap(page, "cta-list")
    for text in ["WhatsApp", "WhatsApp mesajı gönder", "Send WhatsApp message"]:
        loc = page.get_by_text(text, exact=False)
        if loc.count():
            loc.first.click(timeout=6000)
            print(f"cta {text}", flush=True)
            break

    time.sleep(2)
    tel = page.locator('input[type="tel"]:visible, input:visible')
    if tel.count():
        tel.first.fill("+905325680647")
    for t in ["Kaydet", "Save"]:
        b = page.get_by_role("button", name=t)
        if b.count():
            b.last.click(timeout=5000)
            time.sleep(3)
    snap(page, "cta-saved")


def main():
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

        try:
            publish_open_draft(page)
        except Exception as e:
            print("post err:", e, flush=True)
            snap(page, "post-err")

        try:
            pin_post(page)
        except Exception as e:
            print("pin err:", e, flush=True)

        try:
            set_whatsapp_cta(page)
        except Exception as e:
            print("cta err:", e, flush=True)
            snap(page, "cta-err")

        page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
        time.sleep(5)
        assert_cindemir_page(page)
        snap(page, "verify")
        body = page.locator("body").inner_text()
        print("WhatsApp visible:", "WhatsApp" in body, flush=True)
        print("P0 post visible:", "opening-a-company" in body, flush=True)
        ctx.close()


if __name__ == "__main__":
    main()
