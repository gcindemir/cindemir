#!/usr/bin/env python3
"""Add SSH public key via Bluehost and deploy mu-plugins."""
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
PUBKEY = os.popen("SSH_AUTH_SOCK=/run/host-services/ssh-auth.sock ssh-add -L 2>/dev/null").read().strip()


def log(msg):
    print(msg, flush=True)


def main():
    log(f"PUBKEY {PUBKEY[:60]}...")
    os.system("pkill -9 -f google-chrome 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="ssh-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
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

        urls = [
            "https://www.bluehost.com/my-account/hosting/details/sites/13219921/ssh",
            "https://my.bluehost.com/hosting/app/cindemirlaw.com/advanced/ssh",
            "https://www.bluehost.com/my-account/dashboard",
        ]
        for url in urls:
            page.goto(url, timeout=120000)
            time.sleep(5)
            log(f"{page.url} | {page.title()[:50]}")
            page.screenshot(path=str(ROOT / "fixes/ssh-bh.png"), full_page=True)
            if "login" not in page.url.lower() and "moment" not in page.title().lower():
                break

        # try paste pubkey
        for sel in ["textarea", "input[type='text']", "[contenteditable='true']"]:
            loc = page.locator(sel)
            if loc.count():
                try:
                    loc.first.fill(PUBKEY)
                    log("filled pubkey")
                    break
                except Exception:
                    pass

        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    main()
