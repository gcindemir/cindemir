#!/usr/bin/env python3
"""Add meta-description summaries to facebook-en-articles-clean.json."""
from __future__ import annotations

import html
import json
import re
import sys
import time
import urllib.request
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path

ROOT = Path("/workspace")
SRC = ROOT / "fixes/facebook-en-articles-clean.json"
OUT = ROOT / "fixes/facebook-en-articles-enriched.json"
UA = "Mozilla/5.0 (compatible; CindemirFB/1.0)"


def fetch_summary(url: str) -> str:
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=25) as resp:
        body = resp.read().decode("utf-8", "replace")
    for pat in [
        r'<meta\s+name="description"\s+content="([^"]*)"',
        r'<meta\s+property="og:description"\s+content="([^"]*)"',
    ]:
        m = re.search(pat, body, re.I)
        if m:
            text = html.unescape(m.group(1).strip())
            text = re.sub(r"\s+", " ", text)
            if text:
                return text[:480]
    # fallback: first paragraph from og or content
    m = re.search(r'<meta\s+property="og:title"\s+content="([^"]*)"', body, re.I)
    if m:
        return html.unescape(m.group(1).strip())[:200]
    return ""


def build_post_text(art: dict) -> str:
    title = html.unescape(re.sub(r"<[^>]+>", "", art.get("title", ""))).strip()
    link = art["link"].strip()
    summary = (art.get("summary") or "").strip()
    parts = [title]
    if summary and summary.lower() not in title.lower():
        parts.append("")
        parts.append(summary)
    parts.extend(["", link])
    return "\n".join(parts)


def main():
    arts = json.loads(SRC.read_text())
    existing = {}
    if OUT.exists():
        existing = {a["link"].rstrip("/"): a for a in json.loads(OUT.read_text())}

    def work(art):
        link = art["link"].rstrip("/")
        if link in existing and existing[link].get("summary"):
            art = {**art, **existing[link]}
            return art
        try:
            summary = fetch_summary(art["link"])
            time.sleep(0.15)
        except Exception as e:
            summary = ""
            art["fetch_error"] = str(e)[:120]
        art["summary"] = summary
        art["post_text"] = build_post_text(art)
        return art

    results = [None] * len(arts)
    with ThreadPoolExecutor(max_workers=8) as ex:
        futs = {ex.submit(work, dict(a)): i for i, a in enumerate(arts)}
        done = 0
        for fut in as_completed(futs):
            i = futs[fut]
            results[i] = fut.result()
            done += 1
            if done % 25 == 0:
                print(f"enriched {done}/{len(arts)}", flush=True)

    missing = [a for a in results if not a.get("summary")]
    OUT.write_text(json.dumps(results, ensure_ascii=False, indent=2))
    print(f"wrote {OUT} total={len(results)} missing_summary={len(missing)}", flush=True)
    if missing[:3]:
        for a in missing[:3]:
            print(" no summary:", a["link"], flush=True)
    return 0 if len(missing) < len(results) // 2 else 1


if __name__ == "__main__":
    raise SystemExit(main())
