#!/usr/bin/env python3
"""Add P0 deep links from cindemir.av.tr posts/pages to cindemirlaw.com."""
import json
import os
import re
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

OUT = Path("/workspace/fixes")
LOG = OUT / "avtr-link-inject.log"
BASE = "https://cindemir.av.tr"
PROFILE_SRC = Path.home() / ".config/google-chrome"

# Marker so re-runs are idempotent
MARKER = "<!-- cindemirlaw-p0-link -->"

EDITS = [
    {
        "path": "/anonim-sirket-kurmak-turk-ticaret-kanunu-kapsaminda-sirket-kurma-ve-esas-sozlesme-duzenleme-rehberi-2/",
        "html": (
            f'{MARKER}<p>Yabancı yatırımcılar için süreç özeti (İngilizce): '
            f'<a href="https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/">'
            f'yabancılar için Türkiye’de şirket kurulumu</a>.</p>'
        ),
    },
    {
        "path": "/sinirdisi-kararinin-iptali/",
        "html": (
            f'{MARKER}<p>English overview for international clients: '
            f'<a href="https://cindemirlaw.com/deportation-law-in-turkey/">'
            f'deportation law in Turkey</a>.</p>'
        ),
    },
    {
        "path": "/en/deportation-law-in-turkey/",
        "html": (
            f'{MARKER}<p>For the full English guide on our international site, see '
            f'<a href="https://cindemirlaw.com/deportation-law-in-turkey/">'
            f'deportation law in Turkey</a>.</p>'
        ),
    },
    {
        "path": "/en/consensual-divorce-in-turkey-uncontested-divorce/",
        "html": (
            f'{MARKER}<p>Related guide: '
            f'<a href="https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/">'
            f'uncontested / consensual divorce in Turkey</a>.</p>'
        ),
    },
    {
        "path": "/en/debt-recovery-in-turkey/",
        "html": (
            f'{MARKER}<p>International clients: '
            f'<a href="https://cindemirlaw.com/debt-recovery-in-turkey/">'
            f'debt recovery in Turkey</a>.</p>'
        ),
    },
    {
        "path": "/en/to-obtain-criminal-record-certificate-in-turkey/",
        "html": (
            f'{MARKER}<p>Step-by-step English guide: '
            f'<a href="https://cindemirlaw.com/getting-criminal-record-in-turkey/">'
            f'getting a criminal record certificate in Turkey</a>.</p>'
        ),
    },
    {
        "path": "/hizmetlerimiz/",
        "html": (
            f'{MARKER}<p><strong>Uluslararası / İngilizce bilgilendirme:</strong></p>'
            f'<ul>'
            f'<li><a href="https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/">Company formation for foreigners</a></li>'
            f'<li><a href="https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/">Uncontested divorce in Turkey</a></li>'
            f'<li><a href="https://cindemirlaw.com/deportation-law-in-turkey/">Deportation law in Turkey</a></li>'
            f'<li><a href="https://cindemirlaw.com/debt-recovery-in-turkey/">Debt recovery in Turkey</a></li>'
            f'<li><a href="https://cindemirlaw.com/getting-criminal-record-in-turkey/">Criminal record certificate in Turkey</a></li>'
            f'</ul>'
        ),
    },
    {
        "path": "/en/services/",
        "html": (
            f'{MARKER}<p><strong>International English guides:</strong></p>'
            f'<ul>'
            f'<li><a href="https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/">Company formation for foreigners</a></li>'
            f'<li><a href="https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/">Uncontested divorce in Turkey</a></li>'
            f'<li><a href="https://cindemirlaw.com/deportation-law-in-turkey/">Deportation law in Turkey</a></li>'
            f'<li><a href="https://cindemirlaw.com/debt-recovery-in-turkey/">Debt recovery in Turkey</a></li>'
            f'<li><a href="https://cindemirlaw.com/getting-criminal-record-in-turkey/">Criminal record certificate in Turkey</a></li>'
            f'</ul>'
        ),
    },
    {
        "path": "/hakkimizda/",
        "html": (
            f'{MARKER}<p>Uluslararası danışanlar için İngilizce sitemiz: '
            f'<a href="https://cindemirlaw.com/about-us/">Cindemir Law Office — About us</a>.</p>'
        ),
    },
]


def log(m):
    print(m, flush=True)
    with LOG.open("a") as f:
        f.write(m + "\n")


def copy_profile():
    tmp = Path(tempfile.mkdtemp(prefix="avtr-edit-"))
    for item in ["Default", "Local State"]:
        s = PROFILE_SRC / item
        if not s.exists():
            continue
        d = tmp / item
        if s.is_dir():
            shutil.copytree(
                s,
                d,
                dirs_exist_ok=True,
                ignore=shutil.ignore_patterns(
                    "Singleton*", "GPUCache", "Code Cache", "Cache", "GrShaderCache"
                ),
            )
        else:
            shutil.copy2(s, d)
    return tmp


def login(page):
    page.goto(f"{BASE}/wp-login.php", wait_until="domcontentloaded", timeout=90000)
    time.sleep(3)
    user = page.locator("#user_login")
    if user.count() and not user.input_value().strip():
        user.fill("cindemirav")
    page.locator("#wp-submit").click(timeout=15000, no_wait_after=True)
    for _ in range(25):
        time.sleep(1.5)
        if "wp-admin" in page.url and "wp-login" not in page.url.lower():
            return True
    return "wp-admin" in page.url and "wp-login" not in page.url.lower()


def get_json(page, url):
    return page.evaluate(
        """async (url) => {
          const r = await fetch(url, { credentials: 'same-origin' });
          const t = await r.text();
          return { status: r.status, text: t.slice(0, 200000) };
        }""",
        url,
    )


def rest_update(page, post_type, post_id, content, title=None):
    endpoint = "pages" if post_type == "page" else "posts"
    payload = {"content": content}
    if title is not None:
        payload["title"] = title
    return page.evaluate(
        """async ({endpoint, postId, payload}) => {
          // Get nonce from wp-api settings if present
          let nonce = (window.wpApiSettings && window.wpApiSettings.nonce) || null;
          if (!nonce) {
            const r0 = await fetch('/wp-admin/admin-ajax.php?action=rest-nonce', {credentials:'same-origin'});
            // fallback: scrape from any admin page script
          }
          const headers = {'Content-Type': 'application/json'};
          if (nonce) headers['X-WP-Nonce'] = nonce;
          // Also try X-WP-Nonce from cookie-authenticated admin bootstrap
          const r = await fetch(`/wp-json/wp/v2/${endpoint}/${postId}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers,
            body: JSON.stringify(payload),
          });
          const text = await r.text();
          return { status: r.status, text: text.slice(0, 4000) };
        }""",
        {"endpoint": endpoint, "postId": post_id, "payload": payload},
    )


def ensure_nonce(page):
    page.goto(f"{BASE}/wp-admin/", wait_until="domcontentloaded", timeout=90000)
    time.sleep(2)
    nonce = page.evaluate(
        """() => {
          if (window.wpApiSettings && window.wpApiSettings.nonce) return window.wpApiSettings.nonce;
          const m = document.documentElement.innerHTML.match(/wpApiSettings\\s*=\\s*({.*?});/s);
          if (m) { try { return JSON.parse(m[1]).nonce; } catch(e) {} }
          const m2 = document.documentElement.innerHTML.match(/"nonce"\\s*:\\s*"([^"]+)"/);
          return m2 ? m2[1] : null;
        }"""
    )
    log(f"nonce={nonce}")
    return nonce


def update_with_nonce(page, nonce, post_type, post_id, content):
    endpoint = "pages" if post_type == "page" else "posts"
    return page.evaluate(
        """async ({endpoint, postId, content, nonce}) => {
          const r = await fetch(`/wp-json/wp/v2/${endpoint}/${postId}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': nonce,
            },
            body: JSON.stringify({ content }),
          });
          const text = await r.text();
          return { status: r.status, text: text.slice(0, 2500) };
        }""",
        {"endpoint": endpoint, "postId": post_id, "content": content, "nonce": nonce},
    )


def resolve_post(page, path):
    """Resolve path -> {id, type, content.raw, link} via REST search."""
    # Try pages then posts by slug
    slug = path.strip("/").split("/")[-1]
    # URL-encoded slug may differ; prefer link match via search
    for ptype in ("pages", "posts"):
        url = f"{BASE}/wp-json/wp/v2/{ptype}?search={slug[:40]}&per_page=20&context=edit&status=publish,draft,private,future"
        res = get_json(page, url)
        if res["status"] != 200:
            continue
        try:
            items = json.loads(res["text"])
        except Exception:
            continue
        if not isinstance(items, list):
            continue
        for it in items:
            link = (it.get("link") or "").rstrip("/") + "/"
            target = BASE + path if path.startswith("/") else path
            target = target.rstrip("/") + "/"
            if link.rstrip("/") == target.rstrip("/") or path.rstrip("/") in link:
                return {
                    "id": it["id"],
                    "type": "page" if ptype == "pages" else "post",
                    "content": (it.get("content") or {}).get("raw")
                    or (it.get("content") or {}).get("rendered")
                    or "",
                    "link": it.get("link"),
                    "title": (it.get("title") or {}).get("raw")
                    or (it.get("title") or {}).get("rendered"),
                }
    # Fallback: resolve via ?p= from frontend edit link / oembed
    # Use wp/v2 by exact slug
    for ptype in ("pages", "posts"):
        url = f"{BASE}/wp-json/wp/v2/{ptype}?slug={slug}&context=edit"
        res = get_json(page, url)
        if res["status"] != 200:
            continue
        try:
            items = json.loads(res["text"])
        except Exception:
            continue
        if items:
            it = items[0]
            return {
                "id": it["id"],
                "type": "page" if ptype == "pages" else "post",
                "content": (it.get("content") or {}).get("raw")
                or (it.get("content") or {}).get("rendered")
                or "",
                "link": it.get("link"),
                "title": (it.get("title") or {}).get("raw")
                or (it.get("title") or {}).get("rendered"),
            }
    return None


def append_html(existing, block):
    if MARKER in existing:
        # already injected — replace old block between marker and next marker-end or just skip
        return existing, "skip"
    # Prefer append before closing leftovers
    return existing.rstrip() + "\n\n" + block + "\n", "appended"


def main():
    LOG.write_text("")
    profile = copy_profile()
    log(f"profile={profile}")

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(profile),
            headless=False,
            channel="chrome",
            ignore_default_args=["--enable-automation"],
            args=[
                "--no-sandbox",
                "--disable-dev-shm-usage",
                "--disable-blink-features=AutomationControlled",
            ],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1400, "height": 900},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        if not login(page):
            log("LOGIN_FAIL")
            page.screenshot(path=str(OUT / "avtr-edit-login-fail.png"), full_page=True)
            ctx.close()
            return 1
        log("LOGIN_OK")
        nonce = ensure_nonce(page)
        if not nonce:
            log("NO_NONCE")
            ctx.close()
            return 2

        results = []
        for edit in EDITS:
            path = edit["path"]
            log(f"RESOLVE {path}")
            post = resolve_post(page, path)
            if not post:
                log(f"NOT_FOUND {path}")
                results.append({"path": path, "status": "not_found"})
                continue
            log(f"FOUND id={post['id']} type={post['type']} link={post['link']}")
            new_content, action = append_html(post["content"], edit["html"])
            if action == "skip":
                log(f"SKIP already has marker {path}")
                results.append({"path": path, "status": "already", "id": post["id"]})
                continue
            # Elementor warning: if content is empty but Elementor-built, REST content update may not show
            if len(post["content"].strip()) < 40:
                log(f"WARN short/empty content — may be Elementor id={post['id']}")
            res = update_with_nonce(
                page, nonce, post["type"], post["id"], new_content
            )
            log(f"UPDATE {path} -> {res['status']} {res['text'][:200].replace(chr(10),' ')}")
            results.append(
                {
                    "path": path,
                    "status": res["status"],
                    "id": post["id"],
                    "type": post["type"],
                }
            )
            time.sleep(1)

        (OUT / "avtr-link-inject-results.json").write_text(
            json.dumps(results, indent=2)
        )
        ctx.close()
    log("DONE")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
