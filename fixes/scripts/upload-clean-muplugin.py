#!/usr/bin/env python3
"""Upload clean mu-plugins via cPanel File Manager."""
import os
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path('/workspace')
ZIP = ROOT / 'fixes/deploy-package/mu-plugins.zip'
SRC = Path.home() / '.config/google-chrome'
DISPLAY = ':1'


def main():
    tmp = Path(tempfile.mkdtemp(prefix='bh-up-'))
    for item in ['Default', 'Local State']:
        s = SRC / item
        if s.exists():
            d = tmp / item
            if s.is_dir():
                shutil.copytree(s, d, dirs_exist_ok=True)
            else:
                shutil.copy2(s, d)

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            user_data_dir=str(tmp),
            headless=False,
            channel='chrome',
            args=['--no-sandbox', '--disable-dev-shm-usage'],
            env={**os.environ, 'DISPLAY': DISPLAY},
            viewport={'width': 1500, 'height': 950},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        page.goto('https://my.bluehost.com/hosting/app/cindemirlaw.com/cpanel/filemanager', timeout=120000)
        time.sleep(12)
        page.screenshot(path=str(ROOT / 'fixes/upload-0.png'), full_page=True)
        print('URL', page.url, page.title())

        # navigate wp-content/mu-plugins via tree clicks
        for label in ['wp-content', 'mu-plugins']:
            for sel in [f'text={label}', f'a:has-text("{label}")', f'td:has-text("{label}")']:
                loc = page.locator(sel)
                if loc.count():
                    try:
                        loc.first.dblclick(timeout=5000)
                        time.sleep(2)
                        print('opened', label)
                        break
                    except Exception:
                        try:
                            loc.first.click(timeout=3000)
                            time.sleep(2)
                            print('clicked', label)
                            break
                        except Exception:
                            pass

        page.screenshot(path=str(ROOT / 'fixes/upload-1-muplugins.png'), full_page=True)

        # upload zip
        for frame in [page, *page.frames]:
            inputs = frame.locator('input[type="file"]')
            if inputs.count():
                try:
                    inputs.first.set_input_files(str(ZIP))
                    print('UPLOADED ZIP')
                    time.sleep(15)
                    break
                except Exception as e:
                    print('upload err', e)

        page.screenshot(path=str(ROOT / 'fixes/upload-2-done.png'), full_page=True)

        # extract
        for sel in ['text=mu-plugins.zip', 'td:has-text("mu-plugins.zip")']:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=3000)
                break
        for sel in ['text=Extract', 'button:has-text("Extract")']:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=5000)
                time.sleep(8)
                print('EXTRACTED')
                break

        page.screenshot(path=str(ROOT / 'fixes/upload-3-final.png'), full_page=True)
        time.sleep(3)
        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)


if __name__ == '__main__':
    main()
