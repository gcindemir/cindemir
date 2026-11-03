#!/usr/bin/env python3
import os
import time
import json
from datetime import datetime
from pathlib import Path
from playwright.sync_api import sync_playwright

PAGE_HOME = "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
cal = json.loads(Path("/workspace/fixes/facebook-content-calendar.json").read_text())
post = next(p for d in cal["days"] if d["day"] == 2 for p in d["posts"])
print("post", post["id"], post["scheduled_at"], flush=True)

os.system("pkill -9 -f 'google-chrome.*fb-jifkln9c' 2>/dev/null")
time.sleep(3)

with sync_playwright() as p:
    ctx = p.chromium.launch_persistent_context(
        "/tmp/fb-jifkln9c",
        headless=False,
        channel="chrome",
        args=["--no-sandbox", "--disable-dev-shm-usage"],
        env={**os.environ, "DISPLAY": ":1"},
        viewport={"width": 1500, "height": 950},
    )
    page = ctx.pages[0] if ctx.pages else ctx.new_page()
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    print("url", page.url, flush=True)
    page.locator('span:has-text("Ne düşünüyorsun")').first.click(timeout=8000)
    time.sleep(2)
    page.locator('div[contenteditable="true"][role="textbox"]').last.click()
    page.keyboard.type(post["summary"][:180], delay=1)
    page.get_by_role("button", name="İleri").first.click(timeout=8000)
    time.sleep(3)
    page.screenshot(path="/workspace/fixes/fb-dbg-settings.png", full_page=True)
    page.get_by_text("Şimdi yayınla", exact=False).first.click(timeout=6000)
    time.sleep(2)
    page.screenshot(path="/workspace/fixes/fb-dbg-plan.png", full_page=True)
    print("sonrasi", page.get_by_role("button", name="Sonrası için planla").count(), flush=True)
    when = datetime.fromisoformat(post["scheduled_at"])
    date_val = f"{when.day} Tem {when.year}"
    time_val = when.strftime("%H:%M")
    inputs = page.locator("div[role=dialog]").last.locator("input")
    print("inputs", inputs.count(), flush=True)
    for i in range(inputs.count()):
        try:
            print(i, inputs.nth(i).get_attribute("aria-label"), inputs.nth(i).input_value(), flush=True)
        except Exception as e:
            print(i, e, flush=True)
    if inputs.count() >= 1:
        inputs.nth(0).click()
        page.keyboard.press("Control+a")
        page.keyboard.type(date_val, delay=20)
    if inputs.count() >= 2:
        inputs.nth(1).click()
        page.keyboard.press("Control+a")
        page.keyboard.type(time_val, delay=20)
    page.screenshot(path="/workspace/fixes/fb-dbg-filled.png", full_page=True)
    page.get_by_role("button", name="Sonrası için planla").first.click(timeout=10000)
    time.sleep(3)
    page.screenshot(path="/workspace/fixes/fb-dbg-after.png", full_page=True)
    print("planla btn", page.get_by_role("button", name="Planla", exact=True).count(), flush=True)
    if page.get_by_role("button", name="Planla", exact=True).count():
        page.get_by_role("button", name="Planla", exact=True).click(timeout=10000)
        time.sleep(7)
        print("SCHEDULED OK", flush=True)
    page.screenshot(path="/workspace/fixes/fb-dbg-done.png", full_page=True)
    ctx.close()
print("done", flush=True)
