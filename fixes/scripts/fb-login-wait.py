#!/usr/bin/env python3
"""Open Facebook login and wait until session is ready (or timeout)."""
import os
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

PROFILE = Path("/home/ubuntu/.chrome-agent")
PAGE_HOME = (
    "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
)
OUT = Path("/workspace/fixes/fb-login-status.txt")


def main():
    PROFILE.mkdir(parents=True, exist_ok=True)
    timeout_s = int(os.environ.get("FB_LOGIN_WAIT", "300"))
    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(PROFILE),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1400, "height": 900},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        page.goto("https://www.facebook.com/login", wait_until="domcontentloaded", timeout=120000)
        time.sleep(3)
        page.screenshot(path="/workspace/fixes/fb-login-open.png", full_page=True)
        OUT.write_text("waiting_for_login\n")
        print("opened login; waiting up to", timeout_s, "s", flush=True)

        start = time.time()
        while time.time() - start < timeout_s:
            url = page.url
            try:
                body = page.locator("body").inner_text(timeout=5000)
            except Exception:
                body = ""
            logged = (
                "login" not in url.lower()
                and "checkpoint" not in url.lower()
                and ("Ana Sayfa" in body or "Home" in body or "Ne düşünüyorsun" in body or "Cindemir" in body)
            )
            # also try navigating to page home to verify
            if "login" not in url.lower() and "email" not in body.lower()[:500]:
                page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=60000)
                time.sleep(4)
                body2 = page.locator("body").inner_text(timeout=10000)
                if "Cindemir" in body2 and "Giriş Yap" not in body2 and "Log in" not in body2[:800]:
                    page.screenshot(path="/workspace/fixes/fb-login-ok.png", full_page=True)
                    OUT.write_text("logged_in\n")
                    print("LOGIN_OK", flush=True)
                    ctx.close()
                    return 0
            time.sleep(5)
            print("still waiting...", int(time.time() - start), "s", flush=True)

        page.screenshot(path="/workspace/fixes/fb-login-timeout.png", full_page=True)
        OUT.write_text("timeout\n")
        print("LOGIN_TIMEOUT", flush=True)
        ctx.close()
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
