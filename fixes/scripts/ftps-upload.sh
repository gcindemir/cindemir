#!/usr/bin/env bash
# Upload mu-plugins via FTPS (box5711.bluehost.com) and verify non-zero size.
set -euo pipefail
HOST='ftp.cindemirlaw.com'
USER='cursoradmin@cindemirlaw.com'
PASS='g3.8cB4owh'
REMOTE_DIR='wp-content/mu-plugins'
ROOT='/workspace/fixes/mu-plugins'

ftps_put() {
  local file="$1"
  local local_path="$ROOT/$file"
  local expect
  expect=$(wc -c < "$local_path" | tr -d ' ')
  echo "UPLOAD $file (local $expect bytes)..."
  curl -k --ssl-reqd --ftp-ssl --ftp-pasv --max-time 120 \
    -T "$local_path" -u "$USER:$PASS" \
    "ftp://$HOST/$REMOTE_DIR/$file" -s -o /dev/null
  local got
  got=$(curl -k --ssl-reqd --ftp-ssl --ftp-pasv --max-time 60 \
    -u "$USER:$PASS" "ftp://$HOST/$REMOTE_DIR/$file" -s | wc -c | tr -d ' ')
  echo "VERIFY $file remote=$got bytes (expect $expect)"
  if [ "$got" -lt 100 ]; then
    echo "FAIL $file too small"
    return 1
  fi
  echo "OK $file"
}

ftps_put cindemir-remote-deploy.php
ftps_put cindemir-expose-yoast-meta.php
ftps_put cindemir-purge-cache.php
ftps_put cindemir-seo-fixes.php
echo "ALL UPLOADS OK"
