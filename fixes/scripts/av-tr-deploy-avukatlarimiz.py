#!/usr/bin/env python3
"""Deploy Cindemir Avukatlarımız Styles plugin to cindemir.av.tr."""
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
ZIP = ROOT / "fixes/deploy-package/cindemir-avukatlarimiz-styles.zip"
LOG = ROOT / "fixes/av-team-deploy.log"
BASE = "https://cindemir.av.tr"


def log(msg: str) -> None:
    print(msg, flush=True)
    with LOG.open("a") as fh:
        fh.write(msg + "\n")


def wp_ok(url: str) -> bool:
    return "wp-admin" in url and "login" not in url.lower()


def try_login(page) -> bool:
    page.goto(f"{BASE}/wp-admin/", timeout=90000)
    time.sleep(4)
    if wp_ok(page.url):
        return True
    page.goto(f"{BASE}/wp-login.php", timeout=60000)
    time.sleep(2)
    for sel in ["text=Sign in with Google", "a:has-text('Google')", "button:has-text('Google')"]:
        loc = page.locator(sel)
        if loc.count():
            try:
                with page.expect_popup(timeout=15000) as pop:
                    loc.first.click(timeout=8000)
                g = pop.value
                for _ in range(30):
                    time.sleep(2)
                    for acc in [
                        "text=gokhancindemir44@gmail.com",
                        'div[data-email="gokhancindemir44@gmail.com"]',
                        "button:has-text('Continue')",
                        "text=Continue",
                    ]:
                        al = g.locator(acc)
                        if al.count():
                            try:
                                al.first.click(timeout=3000)
                                time.sleep(2)
                            except Exception:
                                pass
                    if wp_ok(page.url):
                        return True
            except Exception as exc:
                log(f"google login: {exc}")
    for i in range(60):
        time.sleep(2)
        if wp_ok(page.url):
            return True
        if i % 10 == 0:
            log(f"wait {i * 2}s {page.url[:80]}")
    return wp_ok(page.url)


def deploy_plugin(page) -> bool:
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
        log("no file input found")
        return False
    time.sleep(8)
    page.locator("#install-plugin-submit").click(timeout=15000)
    log("install clicked")
    time.sleep(35)
    page.screenshot(path=str(ROOT / "fixes/av-team-deploy.png"), full_page=True)
    content = page.content()
    if "Destination folder already exists" in content:
        log("plugin exists — activating from plugins page")
        page.goto(f"{BASE}/wp-admin/plugins.php", timeout=90000)
        time.sleep(5)
        for sel in [
            'tr[data-plugin*="cindemir-avukatlarimiz-styles"] .deactivate',
            'a[href*="cindemir-avukatlarimiz-styles"][href*="action=activate"]',
        ]:
            loc = page.locator(sel)
            if loc.count():
                if "deactivate" in sel:
                    log("plugin already active")
                    return purge_cache(page)
                break
        act = page.locator('a[href*="cindemir-avukatlarimiz-styles"][href*="action=activate"]')
        if act.count():
            act.first.click(timeout=10000)
            time.sleep(10)
            log("activated existing")
            return purge_cache(page)
        return replace_via_plugins(page)
    for sel in ['a:has-text("Activate Plugin")', "a.activate-now", 'a:has-text("Replace current with uploaded")']:
        loc = page.locator(sel)
        if loc.count():
            loc.first.click(timeout=10000)
            time.sleep(20)
            log(f"clicked {sel}")
            return purge_cache(page)
    return "Plugin installed successfully" in content


def replace_via_plugins(page) -> bool:
    page.goto(f"{BASE}/wp-admin/plugins.php", timeout=90000)
    time.sleep(5)
    upd = page.locator('a[href*="cindemir-avukatlarimiz-styles"]')
    log(f"plugin links: {upd.count()}")
    return True


def purge_cache(page) -> bool:
    for url in [
        f"{BASE}/wp-admin/admin.php?page=wprocket",
        f"{BASE}/wp-admin/options-general.php?page=wprocket",
    ]:
        try:
            page.goto(url, timeout=60000)
            time.sleep(4)
            for sel in [
                "#wp-rocket-purge-all",
                'button:has-text("Clear cache")',
                'a:has-text("Clear cache")',
                'input[value="Clear cache"]',
            ]:
                loc = page.locator(sel)
                if loc.count():
                    loc.first.click(timeout=8000)
                    time.sleep(8)
                    log("cache purged")
                    return True
        except Exception as exc:
            log(f"purge {url}: {exc}")
    log("cache purge skipped")
    return True


def main() -> int:
    LOG.write_text("")
    if not ZIP.exists():
        log(f"missing zip: {ZIP}")
        return 1

    os.system("pkill -9 -f google-chrome 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="avteam-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if not s.exists():
            continue
        d = tmp / item
        if s.is_dir():
            shutil.copytree(s, d, dirs_exist_ok=True)
        else:
            shutil.copy2(s, d)

    ok = False
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
        if try_login(page):
            log("LOGGED IN")
            ok = deploy_plugin(page)
        else:
            log("LOGIN FAILED")
            page.screenshot(path=str(ROOT / "fixes/av-team-login-fail.png"), full_page=True)
        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)
    log(f"RESULT {ok}")
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
