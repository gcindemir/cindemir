#!/usr/bin/env bash
# Run ON THE SERVER after: ssh cindemir@162.241.252.122
# Restores mu-plugins from GitHub (fixes 0-byte corruption).
set -euo pipefail

MU=~/public_html/wp-content/mu-plugins
BRANCH='cursor/cindemirlaw-seo-tasks-d204'
BASE="https://raw.githubusercontent.com/gcindemir/cindemir/${BRANCH}/fixes/mu-plugins"

mkdir -p "$MU"
cd "$MU"

backup_if_nonempty() {
  local f="$1"
  if [ -f "$f" ] && [ -s "$f" ]; then
    cp -a "$f" "${f}.bak-$(date +%Y%m%d-%H%M%S)"
  fi
}

fetch() {
  local f="$1" min="$2"
  echo "→ $f"
  backup_if_nonempty "$f"
  curl -fsSL "${BASE}/${f}" -o "${f}.new"
  bytes=$(wc -c < "${f}.new" | tr -d ' ')
  if [ "$bytes" -lt "$min" ]; then
    echo "FAIL $f only ${bytes} bytes (min $min)" >&2
    rm -f "${f}.new"
    exit 1
  fi
  mv "${f}.new" "$f"
  php -l "$f" >/dev/null
  echo "  OK ${bytes} bytes"
}

# Remove empty corrupt copies (keep sso.php, endurance, etc.)
for f in cindemir-*.php; do
  [ -f "$f" ] || continue
  [ -s "$f" ] && continue
  echo "rm empty $f"
  rm -f "$f"
done

fetch cindemir-seo-fixes.php 40000
fetch cindemir-expose-yoast-meta.php 2000
fetch cindemir-contact-fixes.php 20000
fetch cindemir-purge-cache.php 500

echo ""
echo "=== optional: trigger Ahrefs fix endpoint ==="
echo "curl -s \"https://cindemirlaw.com/wp-json/cindemir/v1/fix-ahrefs?key=seo-pack-2026\""

echo ""
echo "=== sizes ==="
ls -la cindemir-*.php

echo ""
echo "=== cache purge ==="
cd ~/public_html
/usr/local/bin/wp cache flush 2>/dev/null || true
/usr/local/bin/wp yoast index --reindex 2>/dev/null || true
if /usr/local/bin/wp plugin is-active wp-rocket 2>/dev/null; then
  /usr/local/bin/wp rocket clean --confirm 2>/dev/null || true
fi

echo ""
echo "DONE. Verify from your Mac:"
echo "  curl -sI https://cindemirlaw.com/hakan/?lang=zh-hans | head -3"
echo "  curl -s https://cindemirlaw.com/page-sitemap.xml | rg -c 'lang=' || true"
