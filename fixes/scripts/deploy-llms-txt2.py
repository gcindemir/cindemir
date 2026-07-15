#!/usr/bin/env python3
"""Deploy llms plugin to cindemirlaw.com with longer OAuth wait."""
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
ZIP = ROOT / "fixes/deploy-package/cindemir-llms-txt.zip"
BASE = "https://cindemirlaw.com"
LOG = ROOT / "fixes/llms-deploy2.log"


def log(m):
    print(m, flush=True)
    with LOG.open("a") as f:
        f.write(m + "\n")


def wp_ok(url):
    return "wp-admin" in url and "login" not in url.lower()


def main():
    LOG.write_text("")
    os.system("pkill -9 -f google-chrome 2>/dev/null; pkill -9 -f chrom 2>/dev/null")
    time.sleep(3)
    tmp = Path(tempfile.mkdtemp(prefix="llms2-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if not s.exists():
            continue
        d = tmp / item
        if s.is_dir():
            shutil.copytree(s, d, dirs_exist_ok=True)
        else:
            shutil.copy2(s, d)

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage", "--start-maximized"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1500, "height": 950},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        page.goto(f"{BASE}/wp-login.php", wait_until="domcontentloaded", timeout=90000)
        time.sleep(3)
        page.screenshot(path=str(ROOT / "fixes/llms-login0.png"), full_page=True)
        log(f"start {page.url}")

        # Click Google if present
        for sel in ["text=Sign in with Google", "a:has-text('Google')", "#login-google", ".google-login"]:
            loc = page.locator(sel)
            if loc.count():
                try:
                    loc.first.click(timeout=5000)
                    log(f"clicked {sel}")
                    time.sleep(5)
                except Exception as e:
                    log(f"click fail {e}")

        # Wait up to 3 min for login (manual possible in noVNC)
        for i in range(90):
            time.sleep(2)
            if wp_ok(page.url):
                log(f"LOGGED IN at {i*2}s")
                break
            # try account picker periodically
            if i % 5 == 0:
                for acc in [
                    "text=gokhancindemir44@gmail.com",
                    'div[data-identifier="gokhancindemir44@gmail.com"]',
                    "text=Continue",
                    "button:has-text('Continue')",
                ]:
                    for fr in [page, *ctx.pages]:
                        al = fr.locator(acc)
                        if al.count():
                            try:
                                al.first.click(timeout=2000)
                                log(f"acc {acc}")
                            except Exception:
                                pass
            if i % 15 == 0:
                log(f"[{i*2}s] {page.url[:120]}")
                page.screenshot(path=str(ROOT / f"fixes/llms-wait-{i}.png"), full_page=True)

        if not wp_ok(page.url):
            log("LOGIN FAILED")
            page.screenshot(path=str(ROOT / "fixes/llms-login-fail.png"), full_page=True)
            ctx.close()
            return 1

        page.goto(f"{BASE}/wp-admin/plugin-install.php?tab=upload", timeout=90000)
        time.sleep(4)
        uploaded = False
        for frame in [page, *page.frames]:
            ins = frame.locator('input[type="file"]')
            if ins.count():
                ins.first.set_input_files(str(ZIP))
                uploaded = True
                log("zip selected")
                break
        if not uploaded:
            log("no file input")
            ctx.close()
            return 1
        time.sleep(6)
        page.locator("#install-plugin-submit").click(timeout=15000)
        log("install clicked")
        time.sleep(30)
        page.screenshot(path=str(ROOT / "fixes/llms-after-install.png"), full_page=True)
        for sel in [
            'a:has-text("Replace current with uploaded")',
            'a:has-text("Activate Plugin")',
            "a.activate-now",
            'a:has-text("Activate")',
        ]:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=10000)
                time.sleep(15)
                log(f"clicked {sel}")
                page.screenshot(path=str(ROOT / "fixes/llms-after-activate.png"), full_page=True)

        # Visit homepage to trigger sync_root_files
        page.goto(f"{BASE}/?llms_sync=1", timeout=60000)
        time.sleep(5)
        ctx.close()
    log("DONE")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
