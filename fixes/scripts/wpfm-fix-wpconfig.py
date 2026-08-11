#!/usr/bin/env python3
"""Remove WPMU_PLUGIN_DIR bypass from wp-config.php via WP File Manager."""
import os, shutil, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
DISPLAY = os.environ.get("DISPLAY", ":1")
LINES = [
    "define( 'WPMU_PLUGIN_DIR', __DIR__ . '/wp-content/mu-plugins-empty' );",
    'define( "WPMU_PLUGIN_DIR", __DIR__ . "/wp-content/mu-plugins-empty" );',
    "define( 'WPMU_PLUGIN_URL', 'https://cindemirlaw.com/wp-content/mu-plugins-empty' );",
    'define( "WPMU_PLUGIN_URL", "https://cindemirlaw.com/wp-content/mu-plugins-empty" );',
]


def main():
    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="wpcfg-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if not s.exists():
            continue
        d = tmp / item
        if s.is_dir():
            shutil.copytree(s, d, dirs_exist_ok=True)
        else:
            shutil.copy2(s, d)

    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp), headless=False, channel="chrome",
                args=["--no-sandbox", "--disable-dev-shm-usage"],
                env={**os.environ, "DISPLAY": DISPLAY},
                viewport={"width": 1500, "height": 950},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            page.goto("https://cindemirlaw.com/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
            page.wait_for_timeout(5000)
            if "login" in page.url.lower():
                print("NOT LOGGED IN", flush=True)
                return 1

            # go to public_html root
            for _ in range(3):
                for frame in [page, *page.frames]:
                    for sel in ["text=public_html", "[title='Up']", "text=.."]:
                        loc = frame.locator(sel)
                        if loc.count():
                            try:
                                loc.first.click(timeout=3000)
                                page.wait_for_timeout(1500)
                            except Exception:
                                pass

            for frame in [page, *page.frames]:
                loc = frame.locator("text=wp-config.php")
                if loc.count():
                    loc.first.dblclick(timeout=5000)
                    page.wait_for_timeout(3000)
                    break

            edited = False
            for frame in [page, *page.frames]:
                for sel in ["textarea", ".CodeMirror", "textarea#newcontent", "div[contenteditable='true']"]:
                    loc = frame.locator(sel)
                    if not loc.count():
                        continue
                    try:
                        content = loc.first.input_value() if sel == "textarea" else loc.first.inner_text()
                    except Exception:
                        try:
                            content = loc.first.inner_text()
                        except Exception:
                            continue
                    if "mu-plugins-empty" not in content:
                        print("bypass already removed", flush=True)
                        edited = True
                        break
                    new = content
                    for line in LINES:
                        new = new.replace(line + "\n", "")
                        new = new.replace(line, "")
                    loc.first.fill(new) if sel == "textarea" else loc.first.evaluate(
                        "(el, t) => { el.innerText = t; }", new
                    )
                    edited = True
                    print("edited wp-config", flush=True)
                    break
                if edited:
                    break

            for frame in [page, *page.frames]:
                for sel in ["text=Save", "button:has-text('Save')", "text=Save File"]:
                    loc = frame.locator(sel)
                    if loc.count():
                        try:
                            loc.first.click(timeout=5000)
                            page.wait_for_timeout(3000)
                            print("saved", flush=True)
                            break
                        except Exception:
                            pass

            page.screenshot(path=str(ROOT / "fixes/wpconfig-edit.png"), full_page=True)

            # verify REST
            page.goto("https://cindemirlaw.com/wp-json/cindemir/v1/fix-ahrefs?key=seo-pack-2026", timeout=90000)
            page.wait_for_timeout(3000)
            body = page.locator("body").inner_text()[:400]
            print("fix-ahrefs:", body, flush=True)
            ctx.close()
            return 0 if "rest_no_route" not in body else 2
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    raise SystemExit(main())
