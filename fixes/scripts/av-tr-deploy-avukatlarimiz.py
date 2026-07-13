#!/usr/bin/env python3
"""Deploy, replace, activate, purge avukatlarimiz plugin on cindemir.av.tr."""
import os
import shutil
import tempfile
import time
from pathlib import Path

import requests
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
ZIP = ROOT / "fixes/deploy-package/cindemir-avukatlarimiz-styles.zip"
BASE = "https://cindemir.av.tr"


def wp_ok(url: str) -> bool:
    return "wp-admin" in url and "login" not in url.lower()


def verify_page() -> None:
    url = f"{BASE}/avukatlarimiz/?verify={int(time.time())}"
    r = requests.get(url, timeout=60, headers={"Cache-Control": "no-cache"})
    print(f"verify status={r.status_code} bytes={len(r.content)}")
    if len(r.content) < 1000:
        raise SystemExit("page empty after deploy")
    print("has script:", "cindemir-avukatlarimiz-team-fix" in r.text)
    print("has f2f2f2:", "f2f2f2" in r.text)


def main() -> int:
    os.system("pkill -9 -f google-chrome 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="avdep-"))
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
        ]:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=10000)
                time.sleep(25)
                break

        page.goto(f"{BASE}/wp-admin/plugins.php", timeout=90000)
        time.sleep(5)
        act = page.locator('tr[data-plugin*="cindemir-avukatlarimiz-styles"] a[href*="action=activate"]')
        if act.count():
            act.first.click(timeout=10000)
            time.sleep(10)
            print("activated")

        page.goto(f"{BASE}/wp-admin/admin-post.php?action=purge_cache&type=all", timeout=90000)
        time.sleep(8)
        print("cache purged")
        ctx.close()

    shutil.rmtree(tmp, ignore_errors=True)
    time.sleep(3)
    verify_page()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
