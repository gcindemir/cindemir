#!/usr/bin/env bash
# Full automatic deploy for cindemirlaw.com SEO pack.
#
# Required (one of):
#   CINDEMIR_FTP_USER + CINDEMIR_FTP_PASS  → upload mu-plugins via FTP
#   WP_USER + WP_APP_PASSWORD              → meta via REST (needs expose-yoast mu-plugin)
#
# Usage:
#   ./fixes/scripts/auto-deploy.sh
#   ./fixes/scripts/auto-deploy.sh --skip-ftp    # only REST/meta trigger
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
HOST="${CINDEMIR_FTP_HOST:-162.241.252.122}"
REMOTE="${CINDEMIR_FTP_PATH:-public_html/wp-content/mu-plugins}"
UA='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
SKIP_FTP=false
[[ "${1:-}" == "--skip-ftp" ]] && SKIP_FTP=true

ftp_upload() {
  local local_file="$1"
  local remote_name="$2"
  local url="ftp://${HOST}/${REMOTE}/${remote_name}"
  echo "FTP upload: ${remote_name} → ${url}"
  curl --ftp-create-dirs -sS --ftp-pasv -T "${local_file}" -u "${CINDEMIR_FTP_USER}:${CINDEMIR_FTP_PASS}" "${url}"
}

deploy_ftp() {
  if [[ -z "${CINDEMIR_FTP_USER:-}" || -z "${CINDEMIR_FTP_PASS:-}" ]]; then
    echo "SKIP FTP: CINDEMIR_FTP_USER / CINDEMIR_FTP_PASS not set." >&2
    return 1
  fi
  ftp_upload "${ROOT}/fixes/mu-plugins/cindemir-seo-fixes.php" "cindemir-seo-fixes.php"
  ftp_upload "${ROOT}/fixes/mu-plugins/cindemir-expose-yoast-meta.php" "cindemir-expose-yoast-meta.php"
  ftp_upload "${ROOT}/fixes/mu-plugins/cindemir-contact-fixes.php" "cindemir-contact-fixes.php"
  ftp_upload "${ROOT}/fixes/mu-plugins/cindemir-footer-fixes.php" "cindemir-footer-fixes.php"
  echo "FTP upload OK."
}

trigger_rest_meta() {
  echo "Triggering /cindemir/v1/apply-seo-meta …"
  local body http
  body=$(curl -sS -A "$UA" "https://cindemirlaw.com/wp-json/cindemir/v1/apply-seo-meta?key=seo-pack-2026")
  http=$(curl -sS -o /dev/null -w '%{http_code}' -A "$UA" "https://cindemirlaw.com/wp-json/cindemir/v1/apply-seo-meta?key=seo-pack-2026")
  echo "HTTP ${http}"
  echo "${body}" | python3 -m json.tool 2>/dev/null || echo "${body}"
  [[ "${http}" == "200" ]]
}

apply_meta_rest() {
  if [[ -z "${WP_USER:-}" || -z "${WP_APP_PASSWORD:-}" ]]; then
    echo "SKIP WP REST meta: WP_USER / WP_APP_PASSWORD not set." >&2
    return 1
  fi
  "${ROOT}/fixes/scripts/update-page-meta-descriptions.sh"
}

verify_live() {
  echo "--- Verification ---"
  local meta
  meta=$(curl -sS -A "$UA" 'https://cindemirlaw.com/about-us/' | rg -o '<meta name="description" content="[^"]*"' | head -1 || true)
  echo "about-us meta: ${meta}"
  local expected="Cindemir Law Office, 2004'ten bu yana İstanbul'da faaliyet gösteren"
  if [[ "${meta}" == *"${expected}"* ]]; then
    echo "VERIFY OK: about-us meta updated."
    return 0
  fi
  echo "VERIFY WARN: about-us meta not yet updated." >&2
  return 1
}

main() {
  local ok=false
  if ! $SKIP_FTP; then
    if deploy_ftp; then ok=true; fi
  fi
  if trigger_rest_meta; then ok=true; fi
  if apply_meta_rest; then ok=true; fi
  if ! $ok; then
    echo "ERROR: No deploy method succeeded. Set FTP or WP credentials in Cursor Cloud Environment secrets." >&2
    exit 1
  fi
  sleep 2
  verify_live || true
  echo "Done. Purge host cache; check Polylang/WPML lang URL setting (fixes/LANG-REDIRECT.md)."
}

main "$@"
