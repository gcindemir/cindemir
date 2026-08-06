#!/usr/bin/env python3
import os, shutil, tempfile, time, re, json
from pathlib import Path
from playwright.sync_api import sync_playwright

PROFILE = Path.home() / ".config/google-chrome"
ASSET = "336218871992"
BIZ = "1161971660662915"
BASE = f"https://business.facebook.com/latest/home?asset_id={ASSET}&business_id={BIZ}"
URLS = [
    BASE,
    f"https://business.facebook.com/latest/composer/?asset_id={ASSET}&business_id={BIZ}",
    f"https://business.facebook.com/latest/posts/published/?asset_id={ASSET}&business_id={BIZ}",
    f"https://www.facebook.com/{ASSET}",
    f"https://www.facebook.com/profile.php?id={ASSET}",
]


def dismiss(page):
    for label in ["Şimdi değil", "Not now", "Not Now", "Tamam", "OK", "Close", "Kapat"]:
        try:
            loc = page.get_by_role("button", name=re.compile(re.escape(label), re.I))
            if loc.count():
                loc.first.click(timeout=1200)
                page.wait_for_timeout(400)
        except Exception:
            pass


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(3)
    tmp = Path(tempfile.mkdtemp(prefix="fb-biz-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if not s.exists():
            continue
        d = tmp / item
        if s.is_dir():
            shutil.copytree(
                s,
                d,
                ignore=shutil.ignore_patterns("Cache", "Code Cache", "GPUCache", "Service Worker", "Blobstore"),
            )
        else:
            shutil.copy2(s, d)
    print("profile", tmp, flush=True)

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1400, "height": 900},
            slow_mo=40,
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        for i, u in enumerate(URLS):
            print("TRY", u, flush=True)
            page.goto(u, timeout=120000)
            page.wait_for_timeout(4500)
            dismiss(page)
            safe = re.sub(r"[^a-zA-Z0-9]+", "-", u.split("?")[0].split("/")[-1] or f"u{i}")[:40]
            page.screenshot(path=f"/workspace/fixes/fb-explore-{i}-{safe}.png")
            print(" url", page.url, flush=True)
            print(" title", page.title(), flush=True)
            try:
                body = page.inner_text("body")[:900]
            except Exception as e:
                body = f"<err {e}>"
            print(" text", body, flush=True)
            n = page.locator("div[role=textbox], textarea, [contenteditable=true]").count()
            print(" textboxes", n, flush=True)
            labels = page.evaluate(
                """() => [...document.querySelectorAll('a,button,div[role=button]')].map(e=>(e.innerText||e.getAttribute('aria-label')||'').trim()).filter(t=>t && t.length<60).slice(0,60)"""
            )
            print(" labels", labels[:40], flush=True)

        # From business home, click Create post variants
        page.goto(BASE, timeout=120000)
        page.wait_for_timeout(4000)
        dismiss(page)
        for sel in [
            "Create post",
            "Bir gönderi oluştur",
            "Create",
            "Oluştur",
            "Posts",
            "Gönderiler",
            "Write a post",
            "Gönderi yaz",
        ]:
            loc = page.locator(f"text={sel}")
            print("count", sel, loc.count(), flush=True)
            if loc.count():
                try:
                    loc.first.click(timeout=2500)
                    page.wait_for_timeout(3000)
                    page.screenshot(path=f"/workspace/fixes/fb-explore-click-{sel.replace(' ','_')}.png")
                    print("clicked", sel, page.url, flush=True)
                    print(page.inner_text("body")[:700], flush=True)
                    print("textboxes", page.locator("div[role=textbox], [contenteditable=true]").count(), flush=True)
                except Exception as e:
                    print("click fail", sel, e, flush=True)
        ctx.close()
    print("DONE", flush=True)


if __name__ == "__main__":
    main()
