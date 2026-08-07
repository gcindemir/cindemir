#!/usr/bin/env bash
# Update Yoast meta descriptions for 14 pages via WordPress REST API.
#
# Prerequisites:
#   1. Upload fixes/mu-plugins/cindemir-expose-yoast-meta.php to wp-content/mu-plugins/
#   2. Create an Application Password in wp-admin (Users → Profile → Application Passwords)
#
# Usage:
#   export WP_USER='your-wp-username'
#   export WP_APP_PASSWORD='xxxx xxxx xxxx xxxx xxxx xxxx'
#   ./fixes/scripts/update-page-meta-descriptions.sh          # apply
#   ./fixes/scripts/update-page-meta-descriptions.sh --dry-run # preview only
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
JSON="$ROOT/fixes/meta-descriptions/pages-14.json"
BASE="https://cindemirlaw.com/wp-json/wp/v2"
DRY_RUN=false

if [[ "${1:-}" == "--dry-run" ]]; then
  DRY_RUN=true
fi

if [[ -z "${WP_USER:-}" || -z "${WP_APP_PASSWORD:-}" ]]; then
  echo "Error: set WP_USER and WP_APP_PASSWORD (WordPress Application Password)." >&2
  echo "Example:" >&2
  echo "  export WP_USER='admin'" >&2
  echo "  export WP_APP_PASSWORD='abcd efgh ijkl mnop qrst uvwx'" >&2
  exit 1
fi

AUTH="$(printf '%s:%s' "$WP_USER" "$WP_APP_PASSWORD" | base64 -w0 2>/dev/null || printf '%s:%s' "$WP_USER" "$WP_APP_PASSWORD" | base64)"

echo "Reading $JSON"
count=0
while IFS= read -r row; do
  id=$(echo "$row" | jq -r '.id')
  url=$(echo "$row" | jq -r '.url')
  metadesc=$(echo "$row" | jq -r '.metadesc')
  len=${#metadesc}

  if (( len < 110 || len > 160 )); then
    echo "WARN page $id: meta length $len (target 110–160) — $url" >&2
  fi

  if $DRY_RUN; then
    echo "[dry-run] page $id ($len chars): $metadesc"
    continue
  fi

  payload=$(jq -n --arg d "$metadesc" '{meta: {_yoast_wpseo_metadesc: $d}}')
  http_code=$(curl -s -o /tmp/wp-meta-response.json -w '%{http_code}' \
    -X POST "$BASE/pages/$id" \
    -H "Authorization: Basic $AUTH" \
    -H "Content-Type: application/json" \
    -d "$payload")

  if [[ "$http_code" != "200" ]]; then
    echo "FAIL page $id HTTP $http_code" >&2
    cat /tmp/wp-meta-response.json >&2
    exit 1
  fi

  echo "OK page $id ($len chars) → $url"
  ((count++)) || true
done < <(jq -c '.[]' "$JSON")

if $DRY_RUN; then
  echo "Dry run complete ($(jq length "$JSON") pages)."
else
  echo "Updated $count pages."
fi
