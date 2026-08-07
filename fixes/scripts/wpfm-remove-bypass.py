#!/usr/bin/env python3
"""Edit wp-config.php in public_html via WP File Manager — remove mu-plugins-empty bypass."""
import os, re, shutil, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"


def nav_public_html(page):
    page.goto("https://cindemirlaw.com/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
    page.wait_for_timeout(6000)
    for _ in range(6):
        for frame in [page, *page.frames]:
            for sel in ["text=public_html", "a:has-text('public_html')"]:
                loc = frame.locator(sel)
                if loc.count():
                    try:
                        loc.first.dblclick(timeout=4000)
                        page.wait_for_timeout(2000)
                        print("in public_html", flush=True)
                        return True
                    except Exception:
                        pass
        for frame in [page, *page.frames]:
            for sel in ["[title='Up']", "button:has-text('Up')", "text=.."]:
                loc = frame.locator(sel)
                if loc.count():
                    try:
                        loc.first.click(timeout=2000)
                        page.wait_for_timeout(1500)
                    except Exception:
                        pass
    return False


def main():
    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="wpcfg2-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            if s.is_dir():
                shutil.copytree(s, d, dirs_exist_ok=True)
            else:
                shutil.copy2(s, d)

    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp), headless=False, channel="chrome",
                args=["--no-sandbox"], env={**os.environ, "DISPLAY": ":1"},
                viewport={"width": 1500, "height": 950},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            if not nav_public_html(page):
                print("FAIL nav", flush=True)
                return 1

            for frame in [page, *page.frames]:
                loc = frame.locator("text=wp-config.php")
                if loc.count():
                    loc.first.click(timeout=3000)
                    page.wait_for_timeout(800)
                    break

            for frame in [page, *page.frames]:
                for sel in ["text=Edit", "button:has-text('Edit')", "[title='Edit']"]:
                    loc = frame.locator(sel)
                    if loc.count():
                        loc.first.click(timeout=4000)
                        page.wait_for_timeout(3000)
                        print("opened editor", flush=True)
                        break

            content = ""
            editor = None
            for frame in [page, *page.frames]:
                for sel in ["textarea#filecontent", "textarea", ".CodeMirror textarea"]:
                    loc = frame.locator(sel)
                    if loc.count():
                        try:
                            content = loc.first.input_value()
                            editor = loc.first
                            break
                        except Exception:
                            pass
                if editor:
                    break

            if not content:
                print("FAIL read content", flush=True)
                page.screenshot(path=str(ROOT / "fixes/wpcfg-fail.png"), full_page=True)
                return 2

            print("has bypass:", "mu-plugins-empty" in content, flush=True)
            new = re.sub(
                r"\n?define\s*\(\s*['\"]WPMU_PLUGIN_(DIR|URL)['\"].*?mu-plugins-empty.*?\);\s*\n?",
                "\n",
                content,
            )
            if new == content:
                print("no change needed", flush=True)
            else:
                editor.fill(new)
                for frame in [page, *page.frames]:
                    for sel in ["text=Save", "button:has-text('Save')", "#elfinder-save"]:
                        loc = frame.locator(sel)
                        if loc.count():
                            loc.first.click(timeout=4000)
                            page.wait_for_timeout(4000)
                            print("saved wp-config", flush=True)
                            break

            page.goto("https://cindemirlaw.com/wp-json/cindemir/v1/fix-ahrefs?key=seo-pack-2026", timeout=90000)
            page.wait_for_timeout(4000)
            body = page.locator("body").inner_text()[:500]
            print("fix-ahrefs:", body, flush=True)

            page.goto("https://cindemirlaw.com/?nocache=" + str(int(time.time())), timeout=90000)
            page.wait_for_timeout(3000)
            html = page.content()
            m = re.search(r"cindemir-seo-fixes ([0-9.]+)", html)
            print("version:", m.group(0) if m else "not found", flush=True)
            print("critical:", "critical error" in html.lower(), flush=True)

            ctx.close()
            return 0 if m and m.group(1).startswith("1.8") else 3
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    raise SystemExit(main())
