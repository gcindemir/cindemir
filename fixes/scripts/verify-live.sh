#!/usr/bin/env bash
# Live verification for cindemirlaw.com SEO deploy.
set -euo pipefail
UA='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
BASE='https://cindemirlaw.com'

echo "=== mu-plugins HTTP ==="
for f in cindemir-seo-fixes.php cindemir-expose-yoast-meta.php; do
  code=$(curl -sI -A "$UA" "$BASE/wp-content/mu-plugins/$f" | awk '/^HTTP/{print $2; exit}')
  echo "$f: HTTP $code"
done

echo
echo "=== plugin version marker (expect 1.8.1 after deploy) ==="
curl -s -A "$UA" "$BASE/about-us/" | rg -o 'cindemir-seo-fixes [0-9.]+' || echo "marker not found (cache or old version)"

echo
echo "=== 14 page meta sample ==="
python3 <<'PY'
import json, re, html, subprocess
pages = json.load(open('/workspace/fixes/meta-descriptions/pages-14.json'))
ok = fail = 0
for p in pages:
    raw = subprocess.check_output(['curl','-s',p['url']], text=True)
    m = re.search(r'meta name="description" content="([^"]*)"', raw)
    got = html.unescape(m.group(1)) if m else ''
    if got == p['metadesc']:
        ok += 1
    else:
        fail += 1
        print('FAIL', p['url'])
print(f'meta: {ok} ok, {fail} fail')
PY

echo
echo "=== broken image rewrite (hakan ZH) ==="
bad=$(curl -s -A "$UA" "$BASE/hakan/?lang=zh-hans" | rg -c 'chinese/wp-content|russian/wp-content' || true)
good=$(curl -s -A "$UA" "$BASE/hakan/?lang=zh-hans" | rg -c 'wp-content/uploads/2020/10/white' || true)
echo "legacy paths: $bad (want 0), fixed paths: $good (want >0)"

echo
echo "=== sitemap ?lang=en count (want 0) ==="
curl -s -A "$UA" "$BASE/page-sitemap.xml" | rg -c 'lang=en' || echo 0

echo
echo "=== sitemap ?lang= total ==="
curl -s -A "$UA" "$BASE/page-sitemap.xml" | rg -c '\?lang=' || echo 0

echo
echo "=== default lang redirect sample ==="
curl -sI -A "$UA" --max-redirs 0 "$BASE/about-us/?lang=en" | rg 'HTTP/|location:' || true

echo
echo "done"
