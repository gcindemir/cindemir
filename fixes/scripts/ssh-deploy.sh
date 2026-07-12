#!/usr/bin/env bash
# Deploy mu-plugins via SSH (uses Cursor SSH agent socket).
set -euo pipefail

export SSH_AUTH_SOCK="${SSH_AUTH_SOCK:-/run/host-services/ssh-auth.sock}"

HOST="${SSH_HOST:-ftp.cindemirlaw.com}"
USER="${SSH_USER:-cindemir}"
LOCAL_DIR="/workspace/fixes/mu-plugins"
REMOTE_DIR="${SSH_REMOTE_DIR:-public_html/wp-content/mu-plugins}"

FILES=(
  cindemir-mobile-brand.php
  cindemir-mobile-header-branding.php
  cindemir-contact-fixes.php
  cindemir-expose-yoast-meta.php
  cindemir-purge-cache.php
  cindemir-seo-fixes.php
)

echo "SSH_AUTH_SOCK=$SSH_AUTH_SOCK"
ssh-add -l || true

ssh -o StrictHostKeyChecking=accept-new -o ConnectTimeout=20 "${USER}@${HOST}" "mkdir -p ${REMOTE_DIR} && ls -la ${REMOTE_DIR} | head -15"

for f in "${FILES[@]}"; do
  echo "SCP $f ..."
  scp -o StrictHostKeyChecking=accept-new -o ConnectTimeout=60 \
    "${LOCAL_DIR}/${f}" "${USER}@${HOST}:${REMOTE_DIR}/${f}"
done

ssh "${USER}@${HOST}" "ls -la ${REMOTE_DIR}/ && cd public_html 2>/dev/null; wp cache flush 2>/dev/null || true; wp rocket clean --confirm 2>/dev/null || true"
echo "SSH DEPLOY DONE"
