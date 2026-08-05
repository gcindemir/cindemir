#!/usr/bin/env bash
# Run ON THE SERVER in cPanel Terminal (user: cindemir).
# Pulls mu-plugins from GitHub and purges caches.
set -euo pipefail

MU="${HOME}/public_html/wp-content/mu-plugins"
BRANCH='cursor/cindemirlaw-seo-tasks-d204'
BASE="https://raw.githubusercontent.com/gcindemir/cindemir/${BRANCH}/fixes/mu-plugins"
PHP_BIN="/usr/local/bin/php"
WP_BIN="/usr/local/bin/wp"
PUBLIC_HTML="${HOME}/public_html"

command -v "$PHP_BIN" >/dev/null 2>&1 || PHP_BIN="php"
command -v "$WP_BIN" >/dev/null 2>&1 || WP_BIN=""

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
  if ! curl -fsSL "${BASE}/${f}" -o "${f}.new"; then
    echo "FAIL curl ${f}" >&2
    rm -f "${f}.new"
    exit 1
  fi
  bytes=$(wc -c < "${f}.new" | tr -d ' ')
  if [ "$bytes" -lt "$min" ]; then
    echo "FAIL $f only ${bytes} bytes (min $min)" >&2
    rm -f "${f}.new"
    exit 1
  fi
  if ! "$PHP_BIN" -l "${f}.new" >/dev/null 2>&1; then
    echo "WARN php -l failed for $f (installing anyway)" >&2
  fi
  mv "${f}.new" "$f"
  echo "  OK ${bytes} bytes"
}

echo "=== remove empty corrupt cindemir-*.php ==="
for f in cindemir-*.php; do
  [ -f "$f" ] || continue
  [ -s "$f" ] && continue
  echo "rm empty $f"
  rm -f "$f"
done

echo "=== fetch from GitHub (${BRANCH}) ==="
fetch cindemir-seo-fixes.php 40000
fetch cindemir-expose-yoast-meta.php 2000
fetch cindemir-contact-fixes.php 20000
fetch cindemir-purge-cache.php 500
fetch cindemir-force-upgrade.php 1500 || true

echo ""
echo "=== sizes ==="
ls -la cindemir-*.php

echo ""
echo "=== version marker in file ==="
grep -E "VERSION = |Version:" cindemir-seo-fixes.php | head -3 || true

echo ""
echo "=== cache purge ==="
cd "$PUBLIC_HTML"
if [ -n "$WP_BIN" ]; then
  "$WP_BIN" cache flush 2>/dev/null || true
  "$WP_BIN" rewrite flush 2>/dev/null || true
  "$WP_BIN" yoast index --reindex 2>/dev/null || true
  if "$WP_BIN" plugin is-active wp-rocket 2>/dev/null; then
    "$WP_BIN" rocket clean --confirm 2>/dev/null || true
  fi
fi

echo ""
echo "=== trigger fix-ahrefs ==="
curl -sS "https://cindemirlaw.com/wp-json/cindemir/v1/fix-ahrefs?key=seo-pack-2026" || \
  curl -sS "https://cindemirlaw.com/wp-json/cindemir/v1/apply-seo-meta?key=seo-pack-2026&pull=1" || true

echo ""
echo "DONE. Expect HTML marker: cindemir-seo-fixes 1.8.2"
