#!/usr/bin/env python3
"""List English article URLs from /articles/ and sitemap via Chrome."""
import json
import os
import re
import time
from pathlib import Path
from urllib.parse import urlparse

from playwright.sync_api import sync_playwright

OUT = Path("/workspace/fixes/facebook-all-urls.json")
# Already published day 1
SKIP = {
    "https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/",
    "https://cindemirlaw.com/deportation-law-in-turkey/",
    "https://cindemirlaw.com/debt-recovery-in-turkey/",
    "https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/",
    "https://cindemirlaw.com/getting-criminal-record-in-turkey/",
}


def norm(u):
    u = u.split("?")[0].split("#")[0]
    if not u.endswith("/"):
        u += "/"
    return u


def main():
    os.system("pkill -9 -f 'google-chrome' 2>/dev/null")
    time.sleep(2)
    urls = []
    with sync_playwright() as p:
        browser = p.chromium.launch(
            headless=True, channel="chrome", args=["--no-sandbox", "--disable-dev-shm-usage"]
        )
        page = browser.new_page(
            user_agent=(
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
                "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
            )
        )
        # sitemap as text
        page.goto("https://cindemirlaw.com/post-sitemap.xml", wait_until="domcontentloaded", timeout=90000)
        time.sleep(3)
        content = page.content()
        found = re.findall(r"https://cindemirlaw\.com/[a-z0-9\-/]+", content)
        print("sitemap raw hits", len(found), flush=True)
        for u in found:
            u = norm(u)
            if u.count("/") < 4:
                continue
            if any(x in u for x in ["/wp-", "/feed", "/tag/", "/category/", "/page/"]):
                continue
            urls.append(u)

        # articles hub pages
        for hub in [
            "https://cindemirlaw.com/articles/",
            "https://cindemirlaw.com/",
        ]:
            page.goto(hub, wait_until="domcontentloaded", timeout=90000)
            time.sleep(3)
            hrefs = page.eval_on_selector_all(
                "a[href]",
                "els => els.map(e => e.href)",
            )
            for h in hrefs:
                if not h.startswith("https://cindemirlaw.com/"):
                    continue
                path = urlparse(h).path
                if path in ("/", "/articles/", "/about-us/", "/services/", "/contacts/", "/onas/", "/nashiyurist/"):
                    continue
                if path.count("/") < 2:
                    continue
                if any(x in path for x in ["/wp-", "/tag/", "/category/", "/author/", "/page/"]):
                    continue
                # skip language query pages
                if "lang=" in h:
                    continue
                urls.append(norm(h))

        browser.close()

    # unique preserve order
    seen = set()
    uniq = []
    for u in urls:
        if u in seen or u in SKIP:
            continue
        seen.add(u)
        uniq.append(u)

    OUT.write_text(json.dumps(uniq, indent=2))
    print(f"unique remaining: {len(uniq)}", flush=True)
    for u in uniq[:40]:
        print(u, flush=True)


if __name__ == "__main__":
    main()
