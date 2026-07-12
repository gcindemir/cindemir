#!/usr/bin/env python3
"""Try Google OAuth and Bluehost login for deploy."""
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
DISPLAY = ":1"
LOG = ROOT / "fixes/oauth-login.log"


def log(msg: str) -> None:
    print(msg, flush=True)
    with LOG.open("a") as fh:
        fh.write(msg + "\n")


def run_flow(name: str, start_url: str, click_selectors: list[str], success_check) -> bool:
    log(f"=== {name} ===")
    tmp = Path(tempfile.mkdtemp(prefix=f"auth-{name}-"))
    for item in ["Default", "Local State"]:
        src = PROFILE / item
        if not src.exists():
            continue
        dst = tmp / item
        if src.is_dir():
            shutil.copytree(src, dst, dirs_exist_ok=True)
        else:
            shutil.copy2(src, dst)

    ok = False
    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": DISPLAY},
            viewport={"width": 1500, "height": 950},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        page.goto(start_url, wait_until="domcontentloaded", timeout=120000)
        time.sleep(3)

        for sel in click_selectors:
            loc = page.locator(sel)
            if loc.count():
                try:
                    loc.first.click(timeout=8000)
                    log(f"{name}: clicked {sel}")
                    time.sleep(4)
                    break
                except Exception as exc:
                    log(f"{name}: click fail {sel}: {exc}")

        for i in range(30):
            time.sleep(2)
            log(f"{name} [{i*2}s] {page.url[:100]} | {page.title()[:60]}")
            if success_check(page):
                ok = True
                break

        page.screenshot(path=str(ROOT / f"fixes/oauth-{name}.png"), full_page=True)
        ctx.close()

    shutil.rmtree(tmp, ignore_errors=True)
    log(f"{name} RESULT ok={ok}")
    return ok


def main() -> int:
    LOG.write_text("")
    os.system("pkill -f 'google-chrome-stable' 2>/dev/null")
    time.sleep(2)

    wp_ok = run_flow(
        "wp-google",
        "https://cindemirlaw.com/wp-login.php",
        [
            "text=Sign in with Google",
            "button:has-text('Google')",
            "a:has-text('Google')",
        ],
        lambda p: "wp-admin" in p.url and "login" not in p.url,
    )

    bh_ok = run_flow(
        "bluehost",
        "https://www.bluehost.com/my-account/login",
        [
            "button:has-text('Login')",
            "button[type='submit']",
            "text=Login",
        ],
        lambda p: "dashboard" in p.url or "hosting" in p.url,
    )

    log(f"FINAL wp={wp_ok} bh={bh_ok}")
    return 0 if wp_ok or bh_ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
