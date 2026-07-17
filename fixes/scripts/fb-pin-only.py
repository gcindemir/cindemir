#!/usr/bin/env python3
import os, time, sys
from playwright.sync_api import sync_playwright

PAGE_HOME = "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"

os.system("pkill -9 -f 'google-chrome.*fb-jifkln9c' 2>/dev/null")
time.sleep(2)

try:
    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            "/tmp/fb-jifkln9c", headless=False, channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"}, viewport={"width": 1500, "height": 950},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
        time.sleep(8)
        post = page.locator('div[role="article"]').filter(has_text="Foreign clients")
        if not post.count():
            post = page.locator('div[role="article"]').filter(has_text="opening-a-company")
        print("posts", post.count(), flush=True)
        if not post.count():
            sys.exit(1)
        post.first.locator('[aria-label="Bu gönderi için eylemler"]').click(timeout=8000)
        time.sleep(2)
        page.get_by_text("Gönderiyi sabitle").first.click(timeout=8000)
        time.sleep(2)
        page.get_by_role("button", name="Sabitle").click(timeout=8000)
        time.sleep(4)
        page.screenshot(path="/workspace/fixes/fb-page-pinned.png", full_page=True)
        print("pinned", flush=True)
        ctx.close()
except Exception as e:
    print("err", e, flush=True)
    sys.exit(1)
