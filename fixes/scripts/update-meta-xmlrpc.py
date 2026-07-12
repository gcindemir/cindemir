#!/usr/bin/env python3
"""Update Yoast _yoast_wpseo_metadesc for pages in pages-14.json via WordPress XML-RPC.

Requires WP admin credentials (not Application Password):
  export WP_USER=admin
  export WP_PASS='...'

Or reads Chrome saved login for cindemirlaw.com/wp-login.php when run in Cursor VM.

Updates the oldest existing meta row per page (avoids duplicate postmeta).
"""
from __future__ import annotations

import json
import os
import sys
import time
import xmlrpc.client
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
JSON_PATH = ROOT / "fixes/meta-descriptions/pages-14.json"
ENDPOINT = "https://cindemirlaw.com/xmlrpc.php"


class UATransport(xmlrpc.client.SafeTransport):
    user_agent = (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
        "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    )


def main() -> int:
    user = os.environ.get("WP_USER", "admin")
    password = os.environ.get("WP_PASS") or os.environ.get("WP_APP_PASSWORD")
    if not password:
        print("Set WP_USER and WP_PASS", file=sys.stderr)
        return 1

    pages = json.loads(JSON_PATH.read_text(encoding="utf-8"))
    server = xmlrpc.client.ServerProxy(ENDPOINT, transport=UATransport())
    ok = 0
    for i, row in enumerate(pages):
        if i:
            time.sleep(1)
        pid = row["id"]
        meta = row["metadesc"]
        post = server.wp.getPost(0, user, password, pid)
        metas = [
            cf
            for cf in post.get("custom_fields", [])
            if cf.get("key") == "_yoast_wpseo_metadesc"
        ]
        if not metas:
            print(f"SKIP {pid}: no _yoast_wpseo_metadesc", file=sys.stderr)
            continue
        target = min(metas, key=lambda x: int(x["id"]))
        server.wp.editPost(
            0,
            user,
            password,
            pid,
            {
                "custom_fields": [
                    {
                        "id": target["id"],
                        "key": "_yoast_wpseo_metadesc",
                        "value": meta,
                    }
                ]
            },
        )
        print(f"OK page {pid} (meta_id {target['id']}, {len(meta)} chars)")
        ok += 1
    print(f"Updated {ok}/{len(pages)} pages.")
    return 0 if ok == len(pages) else 1


if __name__ == "__main__":
    raise SystemExit(main())
