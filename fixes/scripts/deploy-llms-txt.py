#!/usr/bin/env python3
"""Deploy curated llms.txt to cindemirlaw.com via wp-admin File Manager or plugin upload."""
import os
import shutil
import tempfile
import time
import zipfile
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
BASE = "https://cindemirlaw.com"
LOG = ROOT / "fixes/llms-deploy.log"
MU = ROOT / "fixes/mu-plugins"
ZIP = ROOT / "fixes/deploy-package/cindemir-llms-txt.zip"  # rebuilt in build_zip()


def log(msg: str) -> None:
    print(msg, flush=True)
    with LOG.open("a") as fh:
        fh.write(msg + "\n")


def build_zip() -> None:
    ZIP.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(ZIP, "w", zipfile.ZIP_DEFLATED) as zf:
        for name in ["cindemir-llms-txt.php", "llms.txt", "llms-full.txt"]:
            # Put php as top-level plugin entry; txt alongside
            zf.write(MU / name, f"cindemir-llms-txt/{name}")
    log(f"built {ZIP} ({ZIP.stat().st_size} bytes)")


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
                log(f"google: {exc}")
    for i in range(45):
        time.sleep(2)
        if wp_ok(page.url):
            return True
        if i % 10 == 0:
            log(f"wait {i*2}s {page.url[:90]}")
    return wp_ok(page.url)


def upload_via_file_manager(page) -> bool:
    urls = [
        f"{BASE}/wp-admin/admin.php?page=wp_file_manager",
        f"{BASE}/wp-admin/admin.php?page=file_manager_advanced",
        f"{BASE}/wp-admin/admin.php?page=filemanager",
    ]
    for url in urls:
        page.goto(url, timeout=60000)
        time.sleep(5)
        page.screenshot(path=str(ROOT / "fixes/llms-fm.png"), full_page=True)
        log(f"fm url={page.url}")
        for frame in page.frames:
            ins = frame.locator('input[type="file"]')
            if ins.count():
                files = [
                    str(MU / "cindemir-llms-txt.php"),
                    str(MU / "llms.txt"),
                    str(MU / "llms-full.txt"),
                ]
                try:
                    ins.first.set_input_files(files)
                    log("fm set_input_files ok")
                    time.sleep(12)
                    return True
                except Exception as exc:
                    log(f"fm upload err: {exc}")
    return False


def upload_plugin_zip(page) -> bool:
    page.goto(f"{BASE}/wp-admin/plugin-install.php?tab=upload", timeout=90000)
    time.sleep(4)
    for frame in [page, *page.frames]:
        ins = frame.locator('input[type="file"]')
        if ins.count():
            ins.first.set_input_files(str(ZIP))
            log("zip selected")
            break
    else:
        return False
    time.sleep(5)
    page.locator("#install-plugin-submit").click(timeout=15000)
    time.sleep(25)
    page.screenshot(path=str(ROOT / "fixes/llms-plugin-install.png"), full_page=True)
    content = page.content()
    if "Destination folder already exists" in content or "already installed" in content.lower():
        for sel in [
            'a:has-text("Replace current with uploaded")',
            'a:has-text("Var olan yüklenen ile değiştirilsin")',
        ]:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=10000)
                time.sleep(20)
                break
    for sel in ['a:has-text("Activate Plugin")', "a.activate-now", 'a:has-text("Activate")']:
        loc = page.locator(sel)
        if loc.count():
            loc.first.click(timeout=10000)
            time.sleep(12)
            log("activated")
            break
    return True


def main() -> int:
    LOG.write_text("")
    build_zip()
    os.system("pkill -9 -f google-chrome 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="llmsdep-"))
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
            ok = upload_via_file_manager(page) or upload_plugin_zip(page)
            # Also try yoast settings to disable auto llms
            page.goto(f"{BASE}/wp-admin/admin.php?page=wpseo_page_settings", timeout=60000)
            time.sleep(4)
            page.screenshot(path=str(ROOT / "fixes/llms-yoast.png"), full_page=True)
        else:
            log("LOGIN FAILED")
            page.screenshot(path=str(ROOT / "fixes/llms-login-fail.png"), full_page=True)
        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)
    log(f"RESULT {ok}")
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
