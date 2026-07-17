#!/usr/bin/env python3
"""Set WhatsApp action button on Cindemir Law Office page only."""
import os, re, time
from pathlib import Path
from playwright.sync_api import sync_playwright

PROFILE = Path("/tmp/fb-jifkln9c")
PAGE_ID = "100066585793269"
PAGE_HOME = "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
CTA_URL = f"https://www.facebook.com/{PAGE_ID}/settings/?tab=settings"
WHATSAPP = "+905325680647"

def snap(page, n):
    page.screenshot(path=f"/workspace/fixes/fb-page-{n}.png", full_page=True)
    print(n, flush=True)

with sync_playwright() as p:
    os.system("pkill -9 -f 'google-chrome.*fb-jifkln9c' 2>/dev/null")
    time.sleep(2)
    ctx = p.chromium.launch_persistent_context(
        str(PROFILE), headless=False, channel="chrome",
        args=["--no-sandbox", "--disable-dev-shm-usage"],
        env={**os.environ, "DISPLAY": ":1"}, viewport={"width": 1500, "height": 950},
    )
    page = ctx.pages[0] if ctx.pages else ctx.new_page()

    # Page settings (NOT personal)
    page.goto(CTA_URL, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    snap(page, "settings-1")

    for t in ["Sayfa detayları", "Page details", "İnsanlar seni nasıl bulabilir"]:
        loc = page.get_by_text(t, exact=False)
        if loc.count():
            loc.first.click(timeout=5000)
            time.sleep(3)
            print("section", t, flush=True)
            break
    snap(page, "settings-2")

    for t in ["Eylem düğmesi", "Action button", "Düğme", "Button", "CTA"]:
        loc = page.get_by_text(t, exact=False)
        if loc.count():
            loc.first.click(timeout=5000)
            time.sleep(3)
            print("cta", t, flush=True)
            break
    snap(page, "settings-cta")

    for t in ["WhatsApp", "WhatsApp mesajı gönder", "Send WhatsApp"]:
        loc = page.get_by_text(t, exact=False)
        if loc.count():
            loc.first.click(timeout=5000)
            print("picked", t, flush=True)
            break
    time.sleep(2)
    snap(page, "settings-wa")

    inp = page.locator('input:visible')
    if inp.count():
        inp.first.fill(WHATSAPP)
    for t in ["Kaydet", "Save"]:
        b = page.get_by_role("button", name=t)
        if b.count():
            b.last.click(timeout=5000)
            break
    time.sleep(4)
    snap(page, "settings-saved")

    # Public view check
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    snap(page, "public-check")
    body = page.locator("body").inner_text()
    print("has WhatsApp btn:", "WhatsApp" in body, flush=True)
    ctx.close()
