#!/usr/bin/env python3
import os, shutil, tempfile, time, subprocess, json
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
SHOT = ROOT / "fixes"
FILE = ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
ACC = "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"


def log(m):
    print(m, flush=True)


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="wa134-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            shutil.copytree(s, d) if s.is_dir() else shutil.copy2(s, d)
    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp),
                headless=False,
                channel="chrome",
                args=["--no-sandbox", "--disable-dev-shm-usage"],
                env={**os.environ, "DISPLAY": ":1"},
                viewport={"width": 1440, "height": 900},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            page.goto(f"{SITE}/wp-admin/", timeout=120000)
            page.wait_for_timeout(2000)
            if "login" in page.url.lower():
                page.goto(f"{SITE}/wp-login.php", timeout=120000)
                page.wait_for_timeout(1000)
                page.click("#wp-submit")
                page.wait_for_timeout(7000)

            page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
            page.wait_for_timeout(5000)
            for name in ["wp-content", "mu-plugins"]:
                for frame in [page, *page.frames]:
                    loc = frame.locator(f"text={name}")
                    if loc.count():
                        try:
                            loc.first.dblclick(timeout=5000)
                            page.wait_for_timeout(1400)
                            log(f"nav {name}")
                            break
                        except Exception:
                            pass
            for frame in [page, *page.frames]:
                for sel in ["[title='Upload files']", ".elfinder-button-icon-upload"]:
                    if frame.locator(sel).count():
                        try:
                            frame.locator(sel).first.click(force=True, timeout=4000)
                            page.wait_for_timeout(800)
                        except Exception:
                            pass
            uploaded = False
            for frame in [page, *page.frames]:
                ins = frame.locator('input[type=file]')
                if not ins.count():
                    continue
                ins.first.set_input_files(str(FILE))
                page.wait_for_timeout(2000)
                for fr in [page, *page.frames]:
                    for sel in ["button:has-text('YES')", "text=YES"]:
                        if fr.locator(sel).count():
                            try:
                                fr.locator(sel).first.click(timeout=3000)
                                page.wait_for_timeout(1000)
                                log("YES")
                            except Exception:
                                pass
                page.wait_for_timeout(9000)
                uploaded = True
                log("uploaded")
                break
            if not uploaded:
                return 3

            page.goto(f"{SITE}/wp-admin/", timeout=60000)
            page.wait_for_timeout(1500)
            if page.locator("#wp-admin-bar-wp-rocket").count():
                page.hover("#wp-admin-bar-wp-rocket")
                page.wait_for_timeout(500)
                for label in [
                    "Clear cache",
                    "Clear and preload cache",
                    "Clear used CSS",
                ]:
                    if page.locator(f"text={label}").count():
                        try:
                            page.locator(f"text={label}").first.click(force=True, timeout=3000)
                            page.wait_for_timeout(3500)
                            log(f"purged {label}")
                        except Exception as e:
                            log(f"purge skip {label} {e}")
            ctx.close()
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    time.sleep(2)
    h = subprocess.run(
        ["curl", "-sS", "-A", UA, "-H", f"Accept: {ACC}", f"{SITE}/?wa134={int(time.time())}"],
        capture_output=True,
        text=True,
        timeout=60,
    ).stdout
    Path("/tmp/wa134.html").write_text(h)
    log(
        "len=%s hide=%s joinchat_div=%s fallback=%s"
        % (
            len(h),
            "cindemir-hide-joinchat" in h,
            h.count('class="joinchat'),
            "cindemir-wa-fallback" in h,
        )
    )

    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        page = b.new_page(viewport={"width": 1440, "height": 900}, user_agent=UA)
        page.goto(f"{SITE}/?v={int(time.time())}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(5000)
        data = page.evaluate(
            """() => {
            const fb=document.querySelector('#cindemir-wa-fallback');
            const jc=document.querySelectorAll('.joinchat');
            const fixed=[...document.querySelectorAll('*')].filter(el=>{
              const cs=getComputedStyle(el); if(cs.position!=='fixed') return false;
              const r=el.getBoundingClientRect();
              return r.width>=40&&r.width<=90&&r.height>=40&&r.height<=90 && cs.display!=='none' && cs.visibility!=='hidden';
            }).map(el=>({id:el.id, class:(el.className||'').toString().slice(0,80), bg:getComputedStyle(el).backgroundColor, href:el.getAttribute&&el.getAttribute('href')}));
            return {
              fb: !!fb && !!(fb.offsetWidth||fb.offsetHeight),
              href: fb&&fb.href,
              jc: jc.length,
              hide_css: !!document.getElementById('cindemir-hide-joinchat'),
              fixed
            };
        }"""
        )
        log(json.dumps(data, indent=2))
        page.screenshot(path=str(SHOT / "wa134-single.png"))
        page.set_viewport_size({"width": 390, "height": 844})
        page.wait_for_timeout(1000)
        page.screenshot(path=str(SHOT / "wa134-single-m.png"))
        b.close()

    wa_fixed = [
        f
        for f in data["fixed"]
        if "wa.me" in (f.get("href") or "")
        or f.get("id") == "cindemir-wa-fallback"
        or "25, 211, 102" in (f.get("bg") or "")
    ]
    ok = data["fb"] and data["jc"] == 0 and len(wa_fixed) == 1 and "cindemir-hide-joinchat" in h
    log("PASS" if ok else "FAIL")
    return 0 if ok else 4


if __name__ == "__main__":
    raise SystemExit(main())
