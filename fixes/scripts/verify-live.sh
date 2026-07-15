#!/usr/bin/env bash
# Verify cindemirlaw.com SEO pack on production.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
JSON="$ROOT/fixes/meta-descriptions/pages-14.json"
UA='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'

echo "=== Meta descriptions (14 pages) ==="
ok=0
total=0
while IFS= read -r row; do
  total=$((total + 1))
  id=$(echo "$row" | jq -r '.id')
  url=$(echo "$row" | jq -r '.url')
  expected=$(echo "$row" | jq -r '.metadesc')
  snippet=$(echo "$row" | jq -r '.metadesc[:30]')
  live=$(curl -sS -A "$UA" "$url" | rg -o '<meta name="description" content="[^"]*"' | head -1 | sed 's/.*content="//;s/"$//' | sed 's/&#039;/'"'"'/g')
  if [[ "$live" == *"${snippet}"* ]] || [[ "$expected" == *"${live:0:30}"* ]]; then
    echo "OK  page $id"
    ok=$((ok + 1))
  else
    echo "FAIL page $id"
    echo "     expected: ${snippet}..."
    echo "     live:     ${live:0:80}..."
  fi
done < <(jq -c '.[]' "$JSON")
echo "Meta: $ok / $total"

echo ""
echo "=== REST endpoints ==="
http_meta=$(curl -sS -o /tmp/apply-seo-meta.json -w '%{http_code}' -A "$UA" \
  'https://cindemirlaw.com/wp-json/cindemir/v1/apply-seo-meta?key=seo-pack-2026')
echo "apply-seo-meta: HTTP $http_meta"
if [[ "$http_meta" == "200" ]]; then
  cat /tmp/apply-seo-meta.json | python3 -m json.tool 2>/dev/null || cat /tmp/apply-seo-meta.json
fi

http_zh=$(curl -sS -o /dev/null -w '%{http_code}' -A "$UA" \
  'https://cindemirlaw.com/wp-json/cindemir/v1/setup-zh-contacts?key=wpml-setup-zh-2026')
echo "setup-zh-contacts: HTTP $http_zh"

routes=$(curl -sS -A "$UA" 'https://cindemirlaw.com/wp-json/' | jq -r '.routes | keys[] | select(test("cindemir"))' 2>/dev/null || true)
echo "cindemir routes: ${routes:-none}"

if [[ "$ok" -eq "$total" && "$http_meta" == "200" ]]; then
  echo "VERIFY: all checks passed."
  exit 0
fi
echo "VERIFY: incomplete (meta $ok/$total, apply-seo-meta HTTP $http_meta)."
exit 1
