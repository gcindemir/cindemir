#!/usr/bin/env bash
# Verify live mu-plugin integrity (detect corrupted uploads).
set -euo pipefail
URL='https://cindemirlaw.com/wp-content/mu-plugins/cindemir-seo-fixes.php'
BODY=$(curl -s "$URL")
BYTES=$(printf '%s' "$BODY" | wc -c)
echo "live_bytes=$BYTES (expect ~44009)"
if printf '%s' "$BODY" | rg -q 'collapsed'; then
  echo "STATUS=CORRUPT (contains 'collapsed' markers — re-upload required)"
  exit 1
fi
if [ "$BYTES" -lt 30000 ]; then
  echo "STATUS=TOO_SMALL (partial upload — re-upload required)"
  exit 1
fi
echo "STATUS=OK"
