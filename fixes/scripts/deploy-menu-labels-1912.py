#!/usr/bin/env python3
import os, re, shutil, subprocess, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace"); PROFILE = Path.home()/".config/google-chrome"
SITE="https://cindemirlaw.com"; SHOT=ROOT/"fixes"
UA="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
FILE=ROOT/"fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php"

def log(m): print(m, flush=True)
def code(p="/"):
    return subprocess.run(["curl","-sS","-o","/dev/null","-w","%{http_code}","-A",UA,f"{SITE}{p}"],capture_output=True,text=True,timeout=45).stdout.strip()
def ver():
    h=subprocess.run(["curl","-sS","-A",UA,f"{SITE}/?t={int(time.time())}"],capture_output=True,text=True,timeout=60).stdout
    m=re.search(r"cindemir-seo-fixes ([0-9.]+)",h); return m.group(1) if m else None
def login(page):
    page.goto(f"{SITE}/wp-admin/", timeout=120000); page.wait_for_timeout(2500)
    if "login" in page.url.lower():
        page.goto(f"{SITE}/wp-login.php", timeout=120000); page.wait_for_timeout(1200); page.click("#wp-submit"); page.wait_for_timeout(7000)
    return "login" not in page.url.lower()
def fm_upload(page, fpath):
    page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000); page.wait_for_timeout(5000)
    for name in ["wp-content","mu-plugins"]:
        for frame in [page,*page.frames]:
            loc=frame.locator(f"text={name}")
            if loc.count():
                try: loc.first.dblclick(timeout=5000); page.wait_for_timeout(1600); log(f"nav {name}"); break
                except Exception: pass
    for frame in [page,*page.frames]:
        for sel in ["[title='Upload files']",".elfinder-button-icon-upload"]:
            if frame.locator(sel).count():
                try: frame.locator(sel).first.click(force=True, timeout=4000); page.wait_for_timeout(1000)
                except Exception: pass
    for frame in [page,*page.frames]:
        ins=frame.locator('input[type="file"]')
        if not ins.count(): continue
        ins.first.set_input_files(str(fpath)); page.wait_for_timeout(2000)
        for fr in [page,*page.frames]:
            for sel in ["button:has-text('YES')","text=YES"]:
                if fr.locator(sel).count():
                    try: fr.locator(sel).first.click(timeout=3000); page.wait_for_timeout(1200); log("YES")
                    except Exception: pass
        page.wait_for_timeout(9000); log(f"uploaded {fpath.name}"); return True
    return False
def purge(page):
    page.goto(f"{SITE}/wp-admin/", timeout=60000); page.wait_for_timeout(1500)
    if page.locator("#wp-admin-bar-wp-rocket").count():
        page.hover("#wp-admin-bar-wp-rocket"); page.wait_for_timeout(500)
        if page.locator("text=Clear cache").count():
            try: page.locator("text=Clear cache").first.click(force=True, timeout=4000); page.wait_for_timeout(4000); log("rocket purged")
            except Exception: pass

def menu_labels(path):
    h=subprocess.run(["curl","-sS","-A",UA,f"{SITE}{path}{('&' if '?' in path else '?')}t={int(time.time())}"],capture_output=True,text=True,timeout=60).stdout
    return re.findall(r'<span class="avia-menu-text">([^<]+)</span>', h)

def main():
    log(f"START ver={ver()} home={code('/')}")
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null"); time.sleep(2)
    tmp=Path(tempfile.mkdtemp(prefix="m1912-"))
    for item in ["Default","Local State"]:
        s=PROFILE/item
        if s.exists():
            d=tmp/item; shutil.copytree(s,d) if s.is_dir() else shutil.copy2(s,d)
    try:
        with sync_playwright() as p:
            ctx=p.chromium.launch_persistent_context(str(tmp), headless=False, channel="chrome",
                args=["--no-sandbox","--disable-dev-shm-usage"], env={**os.environ,"DISPLAY":":1"}, viewport={"width":1280,"height":800})
            page=ctx.pages[0] if ctx.pages else ctx.new_page()
            if not login(page): return 2
            if not fm_upload(page, FILE): return 3
            log(f"health {code('/')} ver={ver()}"); purge(page); ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)
    time.sleep(2)
    zh=menu_labels("/?lang=zh-hans"); ru=menu_labels("/?lang=ru"); en=menu_labels("/")
    log(f"EN {en[:10]}"); log(f"RU {ru[:10]}"); log(f"ZH {zh[:10]}")
    zh_ok = all(x in zh for x in ["关于我们","文章","服务","我们的团队","媒体报道","联系我们"]) and "研讨" not in zh and "招聘信息" not in zh and "支持" not in zh
    with sync_playwright() as p:
        b=p.chromium.launch(headless=True); page=b.new_page(viewport={"width":1280,"height":900}, user_agent=UA)
        page.goto(f"{SITE}/?lang=zh-hans&t={int(time.time())}", wait_until="domcontentloaded", timeout=120000); page.wait_for_timeout(2500)
        page.screenshot(path=str(SHOT/"menu-zh-fixed.png"), full_page=False); b.close()
    log(f"FINAL ver={ver()} zh_ok={zh_ok} home={code('/')}")
    return 0 if zh_ok and code("/")=="200" else 1
if __name__=="__main__":
    raise SystemExit(main())
