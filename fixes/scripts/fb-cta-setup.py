#!/usr/bin/env python3
"""Set FB CTA via Sayfa kurulumu settings."""
import os, time
from pathlib import Path
from playwright.sync_api import sync_playwright

PROFILE = Path("/tmp/fb-jifkln9c")
PAGE_SETTINGS = "https://www.facebook.com/100066585793269/settings"

with sync_playwright() as p:
    os.system("pkill -9 -f 'google-chrome.*fb-jifkln9c' 2>/dev/null")
    time.sleep(2)
    ctx = p.chromium.launch_persistent_context(
        str(PROFILE), headless=False, channel="chrome",
        args=["--no-sandbox", "--disable-dev-shm-usage"],
        env={**os.environ, "DISPLAY": ":1"}, viewport={"width": 1500, "height": 950},
    )
    page = ctx.pages[0] if ctx.pages else ctx.new_page()
    page.goto(PAGE_SETTINGS, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)

    for t in ["Sayfa kurulumu", "Sayfa detayları", "Page setup", "Page details"]:
        loc = page.get_by_text(t, exact=False)
        if loc.count():
            loc.first.click(timeout=5000)
            time.sleep(3)
            print("clicked", t, flush=True)
            break

    page.screenshot(path="/workspace/fixes/fb-setup.png", full_page=True)

    for t in ["Eylem düğmesi", "Action button", "Düğme", "Button"]:
        loc = page.get_by_text(t, exact=False)
        if loc.count():
            loc.first.click(timeout=5000)
            time.sleep(3)
            print("cta section", t, flush=True)
            break

    page.screenshot(path="/workspace/fixes/fb-cta2.png", full_page=True)
    ctx.close()
