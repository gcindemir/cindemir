#!/usr/bin/env bash
# Verify live mu-plugin integrity (detect corrupted uploads).
set -euo pipefail
URL='https://cindemirlaw.com/wp-content/mu-plugins/cindemir-seo-fixes.php'
CODE=$(curl -sI "$URL" | awk '/^HTTP/{print $2; exit}')
BODY=$(curl -s "$URL")
BYTES=$(printf '%s' "$BODY" | wc -c)
echo "http_code=$CODE live_bytes=$BYTES (expect 200 + ~44009, or 404=missing)"
if [ "$CODE" = "404" ]; then
  echo "STATUS=MISSING (cindemir-seo-fixes.php not on server — upload required)"
  exit 1
fi
if printf '%s' "$BODY" | rg -q 'collapsed'; then
  echo "STATUS=CORRUPT (contains 'collapsed' markers — re-upload required)"
  exit 1
fi
if [ "$BYTES" -lt 30000 ]; then
  echo "STATUS=TOO_SMALL (partial upload — re-upload required)"
  exit 1
fi
echo "STATUS=OK"
