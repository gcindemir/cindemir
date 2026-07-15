#!/usr/bin/env bash
# Upload mu-plugins to cindemirlaw.com via SSH/SCP (Bluehost).
#
# Env (one of):
#   CINDEMIR_SSH_KEY   path to private key (recommended)
#   CINDEMIR_SHP_PASS  password fallback (cPanel password)
#
# Optional:
#   CINDEMIR_SSH_HOST  default: 162.241.252.122
#   CINDEMIR_SSH_USER  default: cindemir
#   CINDEMIR_SSH_PATH  default: public_html/wp-content/mu-plugins
#
# Usage:
#   export CINDEMIR_SSH_KEY="$HOME/.ssh/cindemirlaw_deploy"
#   ./fixes/deploy-ssh.sh
#   ./fixes/deploy-ssh.sh --purge-only

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
HOST="${CINDEMIR_SSH_HOST:-162.241.252.122}"
USER="${CINDEMIR_SSH_USER:-cindemir}"
REMOTE="${CINDEMIR_SSH_PATH:-public_html/wp-content/mu-plugins}"
KEY="${CINDEMIR_SSH_KEY:-}"
PASS="${CINDEMIR_SSH_PASS:-}"
PURGE_ONLY=false
[[ "${1:-}" == "--purge-only" ]] && PURGE_ONLY=true

SSH_OPTS=(-o StrictHostKeyChecking=accept-new -o ConnectTimeout=20)
SCP_OPTS=(-o StrictHostKeyChecking=accept-new -o ConnectTimeout=20)

if [[ -n "$KEY" ]]; then
  SSH_OPTS+=(-i "$KEY")
  SCP_OPTS+=(-i "$KEY")
elif [[ -z "$PASS" ]]; then
  # Local dev default (gitignored); CI should set CINDEMIR_SSH_KEY secret.
  if [[ -f "${ROOT}/fixes/.ssh/cindemirlaw_deploy" ]]; then
    SSH_OPTS+=(-i "${ROOT}/fixes/.ssh/cindemirlaw_deploy")
    SCP_OPTS+=(-i "${ROOT}/fixes/.ssh/cindemirlaw_deploy")
  fi
fi

ssh_cmd() {
  if [[ -n "$PASS" && -z "$KEY" && ! " ${SSH_OPTS[*]} " =~ " -i " ]]; then
    command sshpass -p "$PASS" ssh "${SSH_OPTS[@]}" "${USER}@${HOST}" "$@"
  else
    ssh "${SSH_OPTS[@]}" "${USER}@${HOST}" "$@"
  fi
}

scp_cmd() {
  if [[ -n "$PASS" && -z "$KEY" && ! " ${SCP_OPTS[*]} " =~ " -i " ]]; then
    command sshpass -p "$PASS" scp "${SCP_OPTS[@]}" "$@"
  else
    scp "${SCP_OPTS[@]}" "$@"
  fi
}

purge_empty_plugins() {
  ssh_cmd "cd ${REMOTE} && for f in *.php; do
    [ -f \"\$f\" ] || continue
    [ ! -s \"\$f\" ] || continue
    [ \"\$f\" = 'sso.php' ] && continue
    rm -v \"\$f\"
  done"
}

upload_plugins() {
  local files=(
    cindemir-seo-fixes.php
    cindemir-contact-fixes.php
    cindemir-expose-yoast-meta.php
    cindemir-services-page.php
  )
  echo "Uploading mu-plugins → ${USER}@${HOST}:${REMOTE}"
  ssh_cmd "mkdir -p ${REMOTE}"
  for f in "${files[@]}"; do
    scp_cmd "${ROOT}/fixes/mu-plugins/${f}" "${USER}@${HOST}:${REMOTE}/${f}"
    echo "  ✓ ${f}"
  done
  purge_empty_plugins
}

flush_cache() {
  ssh_cmd "cd public_html && wp cache flush 2>/dev/null || true"
}

verify_remote() {
  ssh_cmd "php -l ${REMOTE}/cindemir-seo-fixes.php && ls -la ${REMOTE}/cindemir-*.php"
}

main() {
  if ! $PURGE_ONLY; then
    upload_plugins
    verify_remote
  fi
  flush_cache
  echo "Done. Run: ./fixes/scripts/verify-live.sh"
}

main "$@"
