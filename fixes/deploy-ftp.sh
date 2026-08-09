#!/usr/bin/env bash
# Upload mu-plugins to cindemirlaw.com (Bluehost).
# Required env vars:
#   CINDEMIR_FTP_HOST  (default: 162.241.252.122)
#   CINDEMIR_FTP_USER  (Bluehost/cPanel username)
#   CINDEMIR_FTP_PASS
# Optional:
#   CINDEMIR_FTP_PATH  (default: public_html/wp-content/mu-plugins)

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
HOST="${CINDEMIR_FTP_HOST:-162.241.252.122}"
USER="${CINDEMIR_FTP_USER:?Set CINDEMIR_FTP_USER}"
PASS="${CINDEMIR_FTP_PASS:?Set CINDEMIR_FTP_PASS}"
REMOTE="${CINDEMIR_FTP_PATH:-public_html/wp-content/mu-plugins}"

if ! command -v lftp >/dev/null 2>&1; then
  echo "lftp is required. Install with: apt-get install -y lftp" >&2
  exit 1
fi

echo "Uploading mu-plugins to ${USER}@${HOST}:${REMOTE}"

lftp -u "${USER}","${PASS}" "ftp://${HOST}" <<EOF
set ssl:verify-certificate no
mkdir -p ${REMOTE}
cd ${REMOTE}
put ${ROOT}/fixes/mu-plugins/cindemir-seo-fixes.php
put ${ROOT}/fixes/mu-plugins/cindemir-expose-yoast-meta.php
put ${ROOT}/fixes/mu-plugins/cindemir-contact-fixes.php
bye
EOF

echo "Done. Purge cache, then run fixes/scripts/update-page-meta-descriptions.sh if WP_USER/WP_APP_PASSWORD are set."
