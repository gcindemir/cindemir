#!/usr/bin/env python3
import os, shutil, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path('/workspace')
PROFILE = Path.home() / '.config/google-chrome'
ZIP = ROOT / 'fixes/deploy-package/cindemir-header-brand-i18n.zip'
BASE = 'https://cindemirlaw.com'

os.system('pkill -9 -f google-chrome 2>/dev/null')
time.sleep(2)
tmp = Path(tempfile.mkdtemp(prefix='brandi18n-'))
for item in ['Default', 'Local State']:
    s = PROFILE / item
    if not s.exists(): continue
    d = tmp / item
    if s.is_dir(): shutil.copytree(s, d, dirs_exist_ok=True)
    else: shutil.copy2(s, d)

with sync_playwright() as p:
    ctx = p.chromium.launch_persistent_context(str(tmp), headless=False, channel='chrome',
        args=['--no-sandbox','--disable-dev-shm-usage'], env={**os.environ,'DISPLAY':':1'},
        viewport={'width':1500,'height':950})
    page = ctx.pages[0] if ctx.pages else ctx.new_page()
    page.goto(f'{BASE}/wp-login.php', wait_until='domcontentloaded', timeout=90000)
    time.sleep(3)
    page.locator('#wp-submit').click(timeout=10000)
    time.sleep(10)
    print('after', page.url, flush=True)
    if 'wp-admin' not in page.url or 'login' in page.url.lower():
        print('LOGIN FAIL'); ctx.close(); raise SystemExit(1)
    page.goto(f'{BASE}/wp-admin/plugin-install.php?tab=upload', timeout=90000)
    time.sleep(4)
    for frame in [page, *page.frames]:
        ins = frame.locator('input[type="file"]')
        if ins.count():
            ins.first.set_input_files(str(ZIP)); print('zip'); break
    time.sleep(5)
    page.locator('#install-plugin-submit').click(timeout=15000)
    time.sleep(25)
    for sel in ['a:has-text("Replace current with uploaded")','a:has-text("Activate Plugin")','a.activate-now','a:has-text("Activate")']:
        loc = page.locator(sel)
        if loc.count():
            loc.first.click(timeout=10000); print('clicked', sel); time.sleep(12); break
    # purge rocket if notice
    for sel in ['a:has-text("Clear cache")','a:has-text("Önbelleği temizle")']:
        loc = page.locator(sel)
        if loc.count():
            try:
                loc.first.click(timeout=5000, no_wait_after=True); time.sleep(5); print('purged')
            except Exception as e:
                print('purge', e)
            break
    page.goto(f'{BASE}/wp-admin/admin-post.php?action=purge_cache&type=all', timeout=60000)
    time.sleep(5)
    ctx.close()
print('DONE')
