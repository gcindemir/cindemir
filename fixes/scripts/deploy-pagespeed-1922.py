#!/usr/bin/env python3
"""Deploy PageSpeed 1.9.22: mu-plugin + WebP heroes, purge, re-Lighthouse."""
import os, shutil, tempfile, time, subprocess, json
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
FILE = ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php"
WEBP = [
    (ROOT / "fixes/540664430.webp", "540664430.webp", ["wp-content", "uploads", "2020", "10"]),
    (ROOT / "fixes/5295681199059.webp", "5295681199059.webp", ["wp-content", "uploads", "2020", "06"]),
]


def login_fm(page):
    page.goto(f"{SITE}/wp-admin/", timeout=120000)
    page.wait_for_timeout(2000)
    if "login" in page.url.lower():
        page.goto(f"{SITE}/wp-login.php", timeout=120000)
        page.wait_for_timeout(1000)
        page.click("#wp-submit")
        page.wait_for_timeout(7000)
    page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
    page.wait_for_timeout(4500)


def dblclick_path(page, names):
    for name in names:
        for frame in [page, *page.frames]:
            loc = frame.locator(f"text={name}")
            if loc.count():
                try:
                    loc.first.dblclick(timeout=4000)
                    page.wait_for_timeout(1000)
                    break
                except Exception:
                    pass


def upload_file(page, path):
    for frame in [page, *page.frames]:
        for sel in ["[title='Upload files']", ".elfinder-button-icon-upload"]:
            if frame.locator(sel).count():
                try:
                    frame.locator(sel).first.click(force=True, timeout=3000)
                    page.wait_for_timeout(500)
                except Exception:
                    pass
    for frame in [page, *page.frames]:
        ins = frame.locator('input[type="file"]')
        if not ins.count():
            continue
        ins.first.set_input_files(str(path))
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
        print("uploaded", path.name, flush=True)
        return True
    return False


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="psi22-"))
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
        login_fm(page)
        dblclick_path(page, ["wp-content", "mu-plugins"])
        upload_file(page, FILE)

        for local, name, path_parts in WEBP:
            page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
            page.wait_for_timeout(3500)
            dblclick_path(page, path_parts)
            upload_file(page, local)

        page.goto(f"{SITE}/wp-admin/", timeout=60000)
        page.wait_for_timeout(1000)
        if page.locator("#wp-admin-bar-wp-rocket").count():
            page.hover("#wp-admin-bar-wp-rocket")
            page.wait_for_timeout(400)
            if page.locator("text=Clear cache").count():
                page.locator("text=Clear cache").first.click(force=True, timeout=4000)
                page.wait_for_timeout(5000)
                print("purged", flush=True)
        ctx.close()
    shutil.rmtree(tmp, ignore_errors=True)
    time.sleep(3)

    # Quick live checks
    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        page = b.new_page(viewport={"width": 390, "height": 844})
        page.goto(f"{SITE}/?cb={int(time.time())}", wait_until="networkidle", timeout=120000)
        page.wait_for_timeout(2000)
        d = page.evaluate(
            """() => ({
              ver: (document.documentElement.innerHTML.match(/cindemir-seo-fixes ([0-9.]+)/)||[])[1],
              hasGsi: !!document.querySelector('script[src*=\"gsi/client\"]'),
              hasWebpHero: document.documentElement.innerHTML.includes('540664430.webp'),
              hasPreconnect: !!document.querySelector('link[rel=preconnect][href*=\"fonts.googleapis\"]'),
              hasA11y: !!document.getElementById('cindemir-pagespeed-a11y'),
              menuRole: (document.querySelector('#avia-menu')||{}).getAttribute?.('role'),
              readMore: [...document.querySelectorAll('a')].filter(a=>/read more/i.test(a.innerText||'')).length
            })"""
        )
        print("live", json.dumps(d), flush=True)
        b.close()

    # Lighthouse mobile + desktop
    for label, args in [
        ("mobile", ["--form-factor=mobile", "--screenEmulation.mobile=true"]),
        ("desktop", ["--preset=desktop"]),
    ]:
        out = f"/tmp/lh-after-{label}"
        cmd = [
            "npx", "--yes", "lighthouse@12.2.1", f"{SITE}/",
            "--only-categories=performance,accessibility,best-practices,seo",
            *args,
            "--output=json", f"--output-path={out}",
            "--chrome-flags=--headless --no-sandbox --disable-dev-shm-usage",
            "--quiet",
        ]
        subprocess.run(cmd, check=False, timeout=180)
        data = json.loads(Path(out).read_text())
        scores = {k: round(data["categories"][k]["score"] * 100) for k in data["categories"]}
        print(label, scores, flush=True)
        Path(f"/workspace/fixes/psi-after-{label}.json").write_text(json.dumps(scores, indent=2))

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
