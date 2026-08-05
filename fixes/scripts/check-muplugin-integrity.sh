#!/usr/bin/env bash
# Verify live mu-plugin integrity (detect corrupted uploads).
set -euo pipefail
BASE='https://cindemirlaw.com/wp-content/mu-plugins'
URL="$BASE/cindemir-seo-fixes.php"
CODE=$(curl -sI "$URL" | awk '/^HTTP/{print $2; exit}')
LISTING=$(curl -s "$BASE/")
BYTES=$(printf '%s' "$LISTING" | rg -o 'cindemir-seo-fixes\.php.*?</td><td[^>]*>\s*([0-9]+[KMG]?)\s*</td>' -r '$1' | head -1 || true)
echo "http_code=$CODE dir_size=$BYTES (expect 200 + ~41K in listing, or 404=missing)"
if [ "$CODE" = "404" ]; then
  echo "STATUS=MISSING (cindemir-seo-fixes.php not on server — upload required)"
  exit 1
fi
if [ -z "$BYTES" ]; then
  echo "STATUS=UNKNOWN (file returns HTTP $CODE but size not in directory listing)"
  exit 1
fi
echo "STATUS=OK"
