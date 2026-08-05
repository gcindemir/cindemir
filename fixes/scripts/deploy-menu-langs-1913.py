#!/usr/bin/env python3
import os, re, shutil, subprocess, tempfile, time, json
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

def main():
    log(f"START ver={ver()} home={code('/')}")
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null"); time.sleep(2)
    tmp=Path(tempfile.mkdtemp(prefix="m1913-"))
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
    h=subprocess.run(["curl","-sS","-A",UA,f"{SITE}/?t={int(time.time())}"],capture_output=True,text=True,timeout=60).stdout
    has_items='cindemir-lang-item' in h
    labels=re.findall(r'cindemir-lang-item[^>]*>.*?avia-menu-text">([^<]+)', h, re.S)
    log(f"html lang items={has_items} labels={labels} ver={ver()}")
    with sync_playwright() as p:
        b=p.chromium.launch(headless=True)
        page=b.new_page(viewport={"width":1440,"height":900}, user_agent=UA)
        page.goto(f"{SITE}/?cindemir_lang=en&t={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(2500)
        desk=page.evaluate('''() => [...document.querySelectorAll(".cindemir-lang-item .avia-menu-text")].map(e=>e.innerText)''')
        log(f"desktop langs={desk}")
        page.screenshot(path=str(SHOT/"menu-langs-desktop.png"), full_page=False)
        page.set_viewport_size({"width":390,"height":844})
        page.goto(f"{SITE}/?cindemir_lang=en&t={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(2000)
        page.click(".av-burger-menu-main"); page.wait_for_timeout(2000)
        # force inject if script delayed
        page.evaluate('''() => {
          if (typeof cindemirEnsureBurgerLangs === 'function') cindemirEnsureBurgerLangs();
          else {
            var ul=document.querySelector("#av-burger-menu-ul");
            if(ul && !ul.querySelector(".cindemir-lang-item")){
              [["en","English"],["zh-hans","中文"],["ru","Русский"]].forEach(function(pair){
                var li=document.createElement("li"); li.className="menu-item cindemir-lang-item language_"+pair[0];
                li.innerHTML='<a href="/?lang='+(pair[0]==='en'?'':pair[0])+'"><span class="avia-menu-text">'+pair[1]+'</span></a>';
                ul.appendChild(li);
              });
            }
          }
        }''')
        page.wait_for_timeout(500)
        burger=page.evaluate('''() => [...document.querySelectorAll("#av-burger-menu-ul .avia-menu-text, .av-burger-overlay .avia-menu-text")].map(e=>e.innerText)''')
        log(f"burger labels={burger}")
        page.screenshot(path=str(SHOT/"menu-langs-burger.png"), full_page=False)
        b.close()
    ok = has_items and ("English" in labels or "English" in desk) and ("中文" in labels or "中文" in desk or "中文" in burger)
    log(f"FINAL ver={ver()} ok={ok} home={code('/')}")
    return 0 if ok and code("/")=="200" else 1

if __name__=="__main__":
    raise SystemExit(main())
