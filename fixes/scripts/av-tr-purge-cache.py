#!/usr/bin/env python3
"""Activate plugin and purge WP Rocket cache on cindemir.av.tr."""
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
BASE = "https://cindemir.av.tr"


def wp_ok(url: str) -> bool:
    return "wp-admin" in url and "login" not in url.lower()


def main() -> int:
    os.system("pkill -9 -f google-chrome 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="avpurge-"))
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
            print("login failed", page.url)
            ctx.close()
            return 1

        page.goto(f"{BASE}/wp-admin/plugins.php", timeout=90000)
        time.sleep(5)
        row = page.locator('tr[data-plugin*="cindemir-avukatlarimiz-styles"]')
        if row.count():
            text = row.first.inner_text()
            print("plugin row:", text[:200])
            if "Etkinleştir" in text or "Activate" in text:
                act = row.first.locator('a[href*="action=activate"]')
                if act.count():
                    act.first.click(timeout=10000)
                    time.sleep(10)
                    print("activated")

        page.goto(f"{BASE}/wp-admin/admin-post.php?action=purge_cache&type=all", timeout=90000)
        time.sleep(8)
        print("purge url:", page.url)

        page.goto(f"{BASE}/wp-admin/admin.php?page=wprocket", timeout=90000)
        time.sleep(5)
        for sel in [
            "#wp-rocket-purge-all",
            'button:has-text("Clear and preload cache")',
            'button:has-text("Önbelleği temizle")',
            'a:has-text("Clear cache")',
        ]:
            loc = page.locator(sel)
            if loc.count():
                try:
                    loc.first.click(timeout=8000, no_wait_after=True)
                    print("clicked", sel)
                    time.sleep(8)
                except Exception as exc:
                    print("click err", sel, exc)

        page.screenshot(path=str(ROOT / "fixes/av-team-purge.png"), full_page=True)
        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
