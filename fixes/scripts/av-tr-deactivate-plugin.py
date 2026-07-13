#!/usr/bin/env python3
"""Deactivate broken plugin on cindemir.av.tr."""
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

PROFILE = Path.home() / ".config/google-chrome"
BASE = "https://cindemir.av.tr"


def wp_ok(url: str) -> bool:
    return "wp-admin" in url and "login" not in url.lower()


def main() -> int:
    os.system("pkill -9 -f google-chrome 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="avoff-"))
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
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1500, "height": 950},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        page.goto(f"{BASE}/wp-admin/", timeout=90000)
        time.sleep(4)
        if not wp_ok(page.url):
            print("login failed")
            ctx.close()
            return 1

        page.goto(f"{BASE}/wp-admin/plugins.php", timeout=90000)
        time.sleep(5)
        deact = page.locator('tr[data-plugin*="cindemir-avukatlarimiz-styles"] a[href*="action=deactivate"]')
        if deact.count():
            deact.first.click(timeout=10000)
            time.sleep(8)
            print("deactivated")

        page.goto(f"{BASE}/wp-admin/admin-post.php?action=purge_cache&type=all", timeout=90000)
        time.sleep(6)
        print("purged")
        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
