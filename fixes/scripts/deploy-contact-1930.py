#!/usr/bin/env python3
"""Deploy SEO 1.9.30 + contact 1.3.7 and verify contact form submit on EN/RU."""
import os, shutil, tempfile, time, json
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
FILES = [
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php",
    ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-contact-fixes.php",
]
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"


def upload_via_fm():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="contact1930-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            shutil.copytree(s, d) if s.is_dir() else shutil.copy2(s, d)
    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp), headless=False, channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"}, viewport={"width": 1280, "height": 800},
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
        page.wait_for_timeout(4500)
        for name in ["wp-content", "mu-plugins"]:
            for frame in [page, *page.frames]:
                loc = frame.locator(f"text={name}")
                if loc.count():
                    try:
                        loc.first.dblclick(timeout=4000)
                        page.wait_for_timeout(1200)
                        break
                    except Exception:
                        pass
        for dest in FILES:
            for frame in [page, *page.frames]:
                for sel in ["[title='Upload files']", ".elfinder-button-icon-upload"]:
                    if frame.locator(sel).count():
                        try:
                            frame.locator(sel).first.click(force=True, timeout=3000)
                            page.wait_for_timeout(500)
                        except Exception:
                            pass
            uploaded = False
            for frame in [page, *page.frames]:
                ins = frame.locator('input[type="file"]')
                if not ins.count():
                    continue
                local = tmp / dest.name
                shutil.copy2(dest, local)
                ins.first.set_input_files(str(local))
                page.wait_for_timeout(1500)
                for fr in [page, *page.frames]:
                    for sel in ["button:has-text('YES')", "text=YES"]:
                        if fr.locator(sel).count():
                            try:
                                fr.locator(sel).first.click(timeout=2500)
                                page.wait_for_timeout(600)
                            except Exception:
                                pass
                page.wait_for_timeout(7000)
                print("uploaded", dest.name, flush=True)
                uploaded = True
                break
            if not uploaded:
                print("FAIL_UPLOAD", dest.name, flush=True)
        page.goto(f"{SITE}/wp-admin/", timeout=60000)
        page.wait_for_timeout(1000)
        if page.locator("#wp-admin-bar-wp-rocket").count():
            page.hover("#wp-admin-bar-wp-rocket")
            page.wait_for_timeout(400)
            if page.locator("text=Clear cache").count():
                page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                page.wait_for_timeout(4000)
                print("purged", flush=True)
        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)


def verify():
    t = int(time.time())
    results = {}
    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        for code, url in [
            ("en", f"{SITE}/contacts/?lang=en&nocache={t}"),
            ("ru", f"{SITE}/contacts/?lang=ru&nocache={t}"),
            ("zh", f"{SITE}/contacts/?lang=zh-hans&nocache={t}"),
        ]:
            page = b.new_page(viewport={"width": 1280, "height": 900}, user_agent=UA)
            page.goto(url, wait_until="domcontentloaded", timeout=120000)
            page.wait_for_timeout(3500)
            info = page.evaluate(
                """() => {
                  const scripts=[...document.querySelectorAll('script#cindemir-contact-form-fallback-js')];
                  const s=scripts[scripts.length-1];
                  const src=(s&&s.getAttribute('src'))||'';
                  const nowp=!!(s&&(s.hasAttribute('data-nowprocket')||s.hasAttribute('nowprocket')));
                  const inline=!!(s&&!src&&(s.textContent||'').indexOf('__cindemirContactBound')!==-1);
                  const ver=(document.documentElement.innerHTML.match(/cindemir-seo-fixes\\s+([0-9.]+)/)||[])[1]||'';
                  return {
                    ver, scriptCount: scripts.length, nowp, inline, deferredData: src.indexOf('data:')===0,
                    hasForm: !!document.querySelector('form.avia_ajax_form'),
                    bound: !!(document.querySelector('form.avia_ajax_form')||{}).dataset?.cindemirBound
                  };
                }"""
            )
            page.fill("#avia_1_1", f"Cursor Test {code}")
            page.fill("#avia_2_1", f"cursor-{code}@example.com")
            page.fill("#avia_3_1", "+905551112233")
            page.fill("#avia_4_1", f"Contact form verification {code} — ignore.")
            page.click('input[type="submit"]')
            page.wait_for_timeout(8000)
            after = page.evaluate(
                """() => {
                  const box=document.querySelector('.ajaxresponse');
                  const form=document.querySelector('form.avia_ajax_form');
                  return {
                    text:(box&&box.innerText||'').trim().slice(0,200),
                    formDisp: form?getComputedStyle(form).display:null,
                    success: !!(box&&/sent/i.test(box.innerText||''))
                  };
                }"""
            )
            results[code] = {**info, **after}
            page.screenshot(path=str(ROOT / f"fixes/contact-{code}-1930.png"))
            page.close()
            print(code, json.dumps(results[code]), flush=True)
        b.close()
    ok = all(
        r.get("ver") == "1.9.30"
        and r.get("hasForm")
        and (r.get("inline") or r.get("nowp"))
        and r.get("success")
        for r in results.values()
    )
    print("OK" if ok else "FAIL", flush=True)
    return 0 if ok else 1


def main():
    upload_via_fm()
    time.sleep(2)
    return verify()


if __name__ == "__main__":
    raise SystemExit(main())
