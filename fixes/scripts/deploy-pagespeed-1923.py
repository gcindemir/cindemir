#!/usr/bin/env python3
"""Upload WebP assets into exact uploads folders via WP File Manager, then deploy 1.9.23."""
import os, shutil, tempfile, time, subprocess, json
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SITE = "https://cindemirlaw.com"
PHP = ROOT / "fixes/plugins/cindemir-seo-pack/includes/cindemir-seo-fixes.php"
ASSETS = [
    {
        "local": ROOT / "fixes/540664430.webp",
        "url": f"{SITE}/wp-content/uploads/2020/10/540664430.webp",
        "nav": ["wp-content", "uploads", "2020", "10"],
    },
    {
        "local": ROOT / "fixes/5295681199059.webp",
        "url": f"{SITE}/wp-content/uploads/2020/06/5295681199059.webp",
        "nav": ["wp-content", "uploads", "2020", "06"],
    },
]


def login(page):
    page.goto(f"{SITE}/wp-admin/", timeout=120000)
    page.wait_for_timeout(2000)
    if "login" in page.url.lower():
        page.goto(f"{SITE}/wp-login.php", timeout=120000)
        page.wait_for_timeout(1000)
        page.click("#wp-submit")
        page.wait_for_timeout(7000)


def open_fm(page):
    page.goto(f"{SITE}/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
    page.wait_for_timeout(4500)


def go_home(page):
    for frame in [page, *page.frames]:
        for sel in ["[title='Home']", ".elfinder-button-icon-home", "button[title='Home']"]:
            if frame.locator(sel).count():
                try:
                    frame.locator(sel).first.click(force=True, timeout=2000)
                    page.wait_for_timeout(1200)
                    return
                except Exception:
                    pass


def dbl(page, name):
    for frame in [page, *page.frames]:
        loc = frame.locator(f".elfinder-cwd-filename[title='{name}'], span[title='{name}'], text={name}")
        if loc.count():
            try:
                loc.first.dblclick(timeout=4000)
                page.wait_for_timeout(1400)
                return True
            except Exception:
                pass
    # fallback exact text
    for frame in [page, *page.frames]:
        loc = frame.locator(f"text={name}")
        if loc.count():
            try:
                loc.first.dblclick(timeout=4000)
                page.wait_for_timeout(1400)
                return True
            except Exception:
                pass
    return False


def upload(page, path):
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
            for sel in ["button:has-text('YES')", "text=YES", "button:has-text('Replace')", "text=Replace"]:
                if fr.locator(sel).count():
                    try:
                        fr.locator(sel).first.click(timeout=2500)
                        page.wait_for_timeout(700)
                    except Exception:
                        pass
        page.wait_for_timeout(8000)
        return True
    return False


def http_ok(url):
    r = subprocess.run(["curl", "-sI", url], capture_output=True, text=True, timeout=30)
    return "HTTP/2 200" in r.stdout or "HTTP/1.1 200" in r.stdout


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="webp23-"))
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
        login(page)

        # PHP first
        open_fm(page)
        go_home(page)
        dbl(page, "wp-content")
        dbl(page, "mu-plugins")
        upload(page, PHP)
        print("php uploaded", flush=True)

        for asset in ASSETS:
            if http_ok(asset["url"]):
                print("already ok", asset["url"], flush=True)
                continue
            open_fm(page)
            go_home(page)
            ok = True
            for part in asset["nav"]:
                if not dbl(page, part):
                    print("nav fail", part, flush=True)
                    ok = False
                    break
            if not ok:
                continue
            upload(page, asset["local"])
            time.sleep(2)
            print("asset", asset["local"].name, "http", http_ok(asset["url"]), flush=True)

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

    for asset in ASSETS:
        print("final", asset["url"], http_ok(asset["url"]), flush=True)

    # Lighthouse
    for label, args in [
        ("mobile", ["--form-factor=mobile", "--screenEmulation.mobile=true"]),
        ("desktop", ["--preset=desktop"]),
    ]:
        out = f"/tmp/lh-final-{label}"
        subprocess.run(
            [
                "npx", "--yes", "lighthouse@12.2.1", f"{SITE}/?v={int(time.time())}",
                "--only-categories=performance,accessibility,best-practices,seo",
                *args, "--output=json", f"--output-path={out}",
                "--chrome-flags=--headless --no-sandbox --disable-dev-shm-usage", "--quiet",
            ],
            check=False, timeout=180,
        )
        data = json.loads(Path(out).read_text())
        scores = {k: round(data["categories"][k]["score"] * 100) for k in data["categories"]}
        print("SCORE", label, scores, flush=True)
        Path(f"/workspace/fixes/psi-final-{label}.json").write_text(json.dumps(scores, indent=2))
        fails = []
        for cat in data["categories"]:
            for ref in data["categories"][cat]["auditRefs"]:
                if ref.get("weight", 0) <= 0:
                    continue
                a = data["audits"][ref["id"]]
                if a.get("score") is not None and a["score"] < 1:
                    fails.append(f"{cat}:{ref['id']}={a['score']}")
        print("FAILS", label, fails, flush=True)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
