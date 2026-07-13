#!/usr/bin/env python3
"""Bluehost login, add SSH key, deploy via SSH."""
import os
import shutil
import subprocess
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
PUBKEY = subprocess.check_output(
    "SSH_AUTH_SOCK=/run/host-services/ssh-auth.sock ssh-add -L 2>/dev/null | head -1",
    shell=True,
    text=True,
).strip()
LOG = ROOT / "fixes/ssh-full.log"


def log(msg: str) -> None:
    print(msg, flush=True)
    with LOG.open("a") as fh:
        fh.write(msg + "\n")


def click_login(page) -> None:
    for sel in [
        "button:has-text('Login')",
        "button[type='submit']",
        "input[type='submit'][value*='Login']",
        "#btn_login",
    ]:
        loc = page.locator(sel)
        if loc.count():
            try:
                loc.first.click(timeout=8000)
                log(f"clicked {sel}")
                return
            except Exception as exc:
                log(f"click fail {sel}: {exc}")


def main() -> int:
    LOG.write_text("")
    log(f"pubkey={PUBKEY[:50]}...")
    os.system("pkill -9 -f google-chrome 2>/dev/null")
    time.sleep(2)

    tmp = Path(tempfile.mkdtemp(prefix="sshfull-"))
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

        page.goto("https://www.bluehost.com/my-account/login", timeout=120000)
        for i in range(25):
            time.sleep(3)
            log(f"BH [{i*3}s] {page.url[:90]} | {page.title()[:40]}")
            if "moment" not in page.title().lower() and "login" in page.url.lower():
                click_login(page)
            if "dashboard" in page.url or "hosting" in page.url:
                break

        page.screenshot(path=str(ROOT / "fixes/sshfull-1.png"), full_page=True)

        ssh_urls = [
            "https://www.bluehost.com/my-account/hosting/details/sites/13219921/ssh",
            "https://my.bluehost.com/hosting/app/cindemirlaw.com/advanced/ssh",
            "https://my.bluehost.com/hosting/app/cindemirlaw.com/cpanel/filemanager",
        ]
        for url in ssh_urls:
            page.goto(url, timeout=120000)
            time.sleep(6)
            log(f"goto {page.url}")
            if "login" not in page.url.lower():
                break

        page.screenshot(path=str(ROOT / "fixes/sshfull-2.png"), full_page=True)

        # SSH key import
        for sel in [
            "text=Import Key",
            "text=Manage SSH Keys",
            "text=Add Key",
            "button:has-text('Import')",
        ]:
            loc = page.locator(sel)
            if loc.count():
                try:
                    loc.first.click(timeout=5000)
                    time.sleep(2)
                    log(f"clicked {sel}")
                except Exception:
                    pass

        for sel in ["textarea", "input[name*='key']", "input[type='text']"]:
            loc = page.locator(sel)
            if loc.count():
                try:
                    loc.first.fill(PUBKEY)
                    log("pubkey pasted")
                    break
                except Exception:
                    pass

        for sel in ["text=Import", "button:has-text('Import')", "text=Authorize", "button:has-text('Save')"]:
            loc = page.locator(sel)
            if loc.count():
                try:
                    loc.first.click(timeout=5000)
                    time.sleep(3)
                    log(f"saved {sel}")
                except Exception:
                    pass

        page.screenshot(path=str(ROOT / "fixes/sshfull-3.png"), full_page=True)

        # File manager fallback - upload zip
        if "filemanager" in page.url.lower():
            for folder in ["public_html", "wp-content", "mu-plugins"]:
                for sel in [f"text={folder}", f"td:has-text('{folder}')"]:
                    loc = page.locator(sel)
                    if loc.count():
                        try:
                            loc.first.dblclick(timeout=5000)
                            time.sleep(2)
                            break
                        except Exception:
                            pass
            zip_path = ROOT / "fixes/deploy-package/mu-plugins.zip"
            for frame in [page, *page.frames]:
                ins = frame.locator('input[type="file"]')
                if ins.count():
                    ins.first.set_input_files(str(zip_path))
                    log("uploaded mu-plugins.zip")
                    time.sleep(20)
                    break

        page.screenshot(path=str(ROOT / "fixes/sshfull-final.png"), full_page=True)
        ctx.close()

    shutil.rmtree(tmp, ignore_errors=True)

    # try ssh deploy
    env = {**os.environ, "SSH_AUTH_SOCK": "/run/host-services/ssh-auth.sock"}
    r = subprocess.run(["bash", str(ROOT / "fixes/scripts/ssh-deploy.sh")], env=env, capture_output=True, text=True)
    log(r.stdout)
    log(r.stderr)
    log(f"ssh exit={r.returncode}")
    return r.returncode


if __name__ == "__main__":
    raise SystemExit(main())
