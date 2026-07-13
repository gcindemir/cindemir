#!/usr/bin/env python3
"""Replace existing avukatlarimiz plugin on cindemir.av.tr."""
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
ZIP = ROOT / "fixes/deploy-package/cindemir-avukatlarimiz-styles.zip"
BASE = "https://cindemir.av.tr"


def wp_ok(url: str) -> bool:
    return "wp-admin" in url and "login" not in url.lower()


def main() -> int:
    os.system("pkill -9 -f google-chrome 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="avteam2-"))
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
            print("not logged in", page.url)
            ctx.close()
            shutil.rmtree(tmp, ignore_errors=True)
            return 1

        page.goto(f"{BASE}/wp-admin/plugin-install.php?tab=upload", timeout=90000)
        time.sleep(4)
        for frame in [page, *page.frames]:
            ins = frame.locator('input[type="file"]')
            if ins.count():
                ins.first.set_input_files(str(ZIP))
                break
        time.sleep(6)
        page.locator("#install-plugin-submit").click(timeout=15000)
        time.sleep(20)

        for sel in [
            'a:has-text("Var olan yüklenen ile değiştirilsin")',
            'a:has-text("Replace current with uploaded")',
            'button:has-text("Var olan yüklenen ile değiştirilsin")',
        ]:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=10000)
                print("clicked replace")
                time.sleep(25)
                break

        page.screenshot(path=str(ROOT / "fixes/av-team-replace.png"), full_page=True)
        print("url:", page.url)
        print("content snippet:", page.content()[:500])

        for sel in [
            'a:has-text("Önbelleği temizle")',
            'button:has-text("Önbelleği temizle")',
            'a:has-text("Clear cache")',
        ]:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=8000)
                print("cache cleared")
                time.sleep(8)
                break

        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
