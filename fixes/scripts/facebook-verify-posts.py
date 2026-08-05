#!/usr/bin/env python3
"""Verify recent English article posts appear on the Page / Business Suite."""
from __future__ import annotations

import json
import os
import re
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
ASSET = "336218871992"
BIZ = "1161971660662915"
SHOT = ROOT / "fixes"
OUT = SHOT / "fb-verify-findings.json"

NEEDLES = [
    "CISG",
    "Ottoman Title",
    "Tourism Law",
    "cindemirlaw.com/cisg",
    "cindemirlaw.com/ottoman",
    "cindemirlaw.com/tourism",
]

URLS = [
    f"https://business.facebook.com/latest/posts/published_posts?asset_id={ASSET}&business_id={BIZ}",
    f"https://www.facebook.com/profile.php?id={ASSET}",
]


def copy_profile(tmp: Path):
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if not s.exists():
            continue
        d = tmp / item
        if s.is_dir():
            shutil.copytree(
                s,
                d,
                ignore=shutil.ignore_patterns(
                    "Cache", "Code Cache", "GPUCache", "Service Worker", "Blobstore", "DawnCache"
                ),
            )
        else:
            shutil.copy2(s, d)


def dismiss(page):
    for label in [
        "Şimdi değil",
        "Not now",
        "Belki daha sonra",
        "Close",
        "Kapat",
        "Tamam",
        "OK",
        "Yok say",
    ]:
        try:
            loc = page.get_by_role("button", name=re.compile(re.escape(label), re.I))
            if loc.count():
                loc.first.click(timeout=1200)
                page.wait_for_timeout(300)
        except Exception:
            pass
    try:
        x = page.locator("[aria-label='Close'], [aria-label='Kapat']")
        if x.count():
            x.first.click(timeout=1000)
    except Exception:
        pass


def main():
    os.system("pkill -f 'google-chrome|chromium' 2>/dev/null")
    time.sleep(2)

    tmp = Path(tempfile.mkdtemp(prefix="fb-verify-"))
    copy_profile(tmp)
    print("profile", tmp, flush=True)

    findings = {}
    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1400, "height": 900},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()

        for i, url in enumerate(URLS, 1):
            print(f"goto {i}: {url}", flush=True)
            try:
                page.goto(url, timeout=120000)
                page.wait_for_timeout(6500)
                dismiss(page)
                page.wait_for_timeout(1500)
                # scroll a bit to load feed
                for _ in range(4):
                    page.mouse.wheel(0, 1200)
                    page.wait_for_timeout(800)
                path = SHOT / f"fb-verify-{i}.png"
                page.screenshot(path=str(path), full_page=False)
                body = page.inner_text("body")
                hits = [n for n in NEEDLES if n.lower() in body.lower()]
                findings[url] = {"final_url": page.url, "hits": hits, "body_len": len(body)}
                print("hits", hits, "url", page.url[:120], flush=True)
            except Exception as e:
                findings[url] = {"error": str(e)}
                print("ERR", e, flush=True)

        OUT.write_text(json.dumps(findings, indent=2, ensure_ascii=False))
        print("wrote", OUT, flush=True)
        ctx.close()

    shutil.rmtree(tmp, ignore_errors=True)
    print(json.dumps(findings, indent=2, ensure_ascii=False), flush=True)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
