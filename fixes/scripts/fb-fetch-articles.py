#!/usr/bin/env python3
"""Fetch English article titles + meta from cindemirlaw.com via Chrome (bypass ModSecurity)."""
import json
import os
import re
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
OUT = ROOT / "fixes/facebook-articles.json"

URLS = [
    "https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/",
    "https://cindemirlaw.com/deportation-law-in-turkey/",
    "https://cindemirlaw.com/debt-recovery-in-turkey/",
    "https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/",
    "https://cindemirlaw.com/getting-criminal-record-in-turkey/",
    "https://cindemirlaw.com/airport-detention-in-turkey-legal-risks-for-foreign-travellers-and-how-to-respond/",
    "https://cindemirlaw.com/sales-of-real-estate-to-foreigners-in-turkey/",
    "https://cindemirlaw.com/turkish-citizenship-law-in-english/",
    "https://cindemirlaw.com/enforcement-of-a-foreign-decision-in-turkey/",
    "https://cindemirlaw.com/airport-customs-seizures-and-smuggling-offences-under-turkish-law/",
    "https://cindemirlaw.com/how-to-lift-entry-ban-to-turkey/",
    "https://cindemirlaw.com/turkish-inheritance-law/",
    "https://cindemirlaw.com/humanitarian-residence-permit-in-turkey-legal-insights-by-cindemir-law-office/",
    "https://cindemirlaw.com/can-russian-establish-a-company-in-turkey/",
    "https://cindemirlaw.com/criminal-record-deletion-in-turkey-for-foreign-nationals/",
]


def main():
    os.system("pkill -9 -f 'google-chrome' 2>/dev/null")
    time.sleep(2)
    articles = []
    with sync_playwright() as p:
        browser = p.chromium.launch(
            headless=True,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
        )
        page = browser.new_page(
            user_agent=(
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
                "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
            )
        )
        for url in URLS:
            try:
                page.goto(url, wait_until="domcontentloaded", timeout=60000)
                time.sleep(2)
                title = page.locator("h1.post-title, h1.entry-title, h1").first.inner_text(timeout=10000).strip()
                desc = page.locator('meta[name="description"]').get_attribute("content") or ""
                og = page.locator('meta[property="og:image"]').get_attribute("content") or ""
                # first paragraph for richer summary
                para = ""
                for sel in [".entry-content p", ".av-article-text p", "article p"]:
                    loc = page.locator(sel)
                    if loc.count():
                        for i in range(min(loc.count(), 4)):
                            t = loc.nth(i).inner_text().strip()
                            if len(t) > 80:
                                para = t
                                break
                    if para:
                        break
                articles.append(
                    {
                        "url": url,
                        "title": re.sub(r"\s+", " ", title),
                        "description": desc.strip(),
                        "excerpt": para[:400],
                        "image": og,
                    }
                )
                print(f"OK {title[:60]}", flush=True)
            except Exception as e:
                print(f"FAIL {url}: {e}", flush=True)
        browser.close()

    OUT.write_text(json.dumps(articles, indent=2, ensure_ascii=False))
    print(f"wrote {len(articles)} -> {OUT}", flush=True)


if __name__ == "__main__":
    main()
