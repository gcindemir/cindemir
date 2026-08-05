#!/usr/bin/env python3
"""Re-login to Facebook via saved profile 'Devam', then verify Business Suite composer."""
from __future__ import annotations

import os
import re
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

PROFILE = Path.home() / ".config/google-chrome"
ASSET = "336218871992"
BIZ = "1161971660662915"
COMPOSER = f"https://business.facebook.com/latest/composer/?asset_id={ASSET}&business_id={BIZ}"
SHOT = Path("/workspace/fixes")
STATUS = SHOT / "fb-login-status.txt"


def copy_profile(tmp: Path):
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if not s.exists():
            continue
        d = tmp / item
        if s.is_dir():
            shutil.copytree(
                s,
                d,
                ignore=shutil.ignore_patterns(
                    "Cache", "Code Cache", "GPUCache", "Service Worker", "Blobstore", "DawnCache"
                ),
            )
        else:
            shutil.copy2(s, d)


def sync_session_back(tmp: Path):
    src = tmp / "Default"
    dst = PROFILE / "Default"
    for name in [
        "Cookies",
        "Cookies-journal",
        "Login Data",
        "Login Data-journal",
        "Web Data",
        "Web Data-journal",
        "Preferences",
        "Secure Preferences",
    ]:
        s = src / name
        if s.exists():
            shutil.copy2(s, dst / name)
            print("synced", name, flush=True)


def dismiss(page):
    for label in ["Şimdi değil", "Not now", "Tamam", "OK", "Close", "Kapat", "Belki daha sonra"]:
        try:
            loc = page.get_by_role("button", name=re.compile(re.escape(label), re.I))
            if loc.count():
                loc.first.click(timeout=1200)
                page.wait_for_timeout(300)
        except Exception:
            pass


def click_devam(page) -> bool:
    """Click the saved-profile Continue button on facebook.com."""
    for sel in [
        page.get_by_role("button", name=re.compile(r"^Devam$", re.I)),
        page.get_by_role("button", name=re.compile(r"^Continue$", re.I)),
        page.locator("div[role='button']:has-text('Devam')"),
        page.locator("button:has-text('Devam')"),
        page.locator("text=Gökhan Cindemir").locator("xpath=ancestor::div[1]").locator("text=Devam"),
    ]:
        try:
            if sel.count():
                sel.first.click(timeout=3000)
                page.wait_for_timeout(4000)
                print("clicked Devam", flush=True)
                return True
        except Exception as e:
            print("devam click skip", e, flush=True)
    return False


def session_ok(ctx) -> bool:
    cookies = {c["name"]: c.get("value", "") for c in ctx.cookies("https://www.facebook.com")}
    return bool(cookies.get("c_user") and cookies.get("xs"))


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    for p in [PROFILE / "SingletonLock", PROFILE / "SingletonCookie", PROFILE / "SingletonSocket"]:
        try:
            p.unlink()
        except Exception:
            pass

    tmp = Path(tempfile.mkdtemp(prefix="fb-relogin-"))
    copy_profile(tmp)
    print("profile", tmp, flush=True)
    STATUS.write_text(f"profile {tmp}\n")

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage", "--disable-blink-features=AutomationControlled"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1400, "height": 900},
            slow_mo=50,
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        page.set_default_timeout(120000)

        page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(4000)
        page.screenshot(path=str(SHOT / "fb-relogin-home.png"))
        print("home", page.url, flush=True)

        if not session_ok(ctx):
            clicked = click_devam(page)
            page.wait_for_timeout(5000)
            page.screenshot(path=str(SHOT / "fb-relogin-after-devam.png"))
            print("after_devam", page.url, "clicked=", clicked, flush=True)
            dismiss(page)

        # Wait for session / CAPTCHA / password
        logged = False
        for i in range(150):
            has = session_ok(ctx)
            u = page.url
            print(f"[{i*5}s] has_session={has} url={u[:120]}", flush=True)
            STATUS.write_text(f"wait has_session={has} url={u}\n")
            page.screenshot(path=str(SHOT / "fb-relogin-live.png"))

            # Only re-click Devam on the saved-profile home, not on 2FA
            if (
                not has
                and "two_factor" not in u
                and "two_step" not in u
                and page.locator("text=Gökhan Cindemir").count()
                and page.locator("text=Devam").count()
            ):
                click_devam(page)

            body = ""
            try:
                body = page.inner_text("body")[:1500].lower()
            except Exception:
                pass
            if "two_factor" in u or "two_step" in u or "kimlik doğrulama" in body or "authentication app" in body:
                print("NEED_2FA — waiting for user to approve/enter code", flush=True)
                STATUS.write_text(f"NEED_2FA url={u}\n")
            elif any(x in body for x in ["captcha", "arkose", "güvenlik kontrol", "security check"]):
                print("SECURITY_CHALLENGE_VISIBLE — waiting for user", flush=True)
                STATUS.write_text(f"NEED_USER_LOGIN challenge url={u}\n")

            if has and "login" not in u.lower():
                # open composer
                page.goto(COMPOSER, wait_until="domcontentloaded", timeout=120000)
                page.wait_for_timeout(5000)
                dismiss(page)
                page.screenshot(path=str(SHOT / "fb-relogin-composer.png"))
                boxes = page.locator("div[role='textbox'], [contenteditable='true']").count()
                print("composer", page.url[:120], "boxes=", boxes, flush=True)
                if "login" not in page.url.lower() and boxes:
                    logged = True
                    STATUS.write_text(f"LOGGED_IN {page.url}\n")
                    print("LOGGED_IN", flush=True)
                    break
                # maybe need Facebook continue on biz page
                if "login" in page.url.lower():
                    try:
                        btn = page.get_by_role("button", name=re.compile(r"Facebook ile devam", re.I))
                        if btn.count():
                            btn.first.click(timeout=3000)
                            page.wait_for_timeout(5000)
                    except Exception:
                        pass
            page.wait_for_timeout(5000)

        sync_session_back(tmp)
        if logged:
            STATUS.write_text("READY\n")
            print("READY", flush=True)
            # keep profile path for poster to reuse same tmp? sync back done
            page.wait_for_timeout(1500)
            ctx.close()
            return 0

        STATUS.write_text("LOGIN_TIMEOUT\n")
        print("LOGIN_TIMEOUT", flush=True)
        ctx.close()
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
