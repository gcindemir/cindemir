#!/usr/bin/env bash
# Repair WP menus on live (RU/ZH targets + ZH labels) via SSH + WP-CLI.
# Usage: ./fixes/scripts/repair-menus-ssh.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
HOST="${CINDEMIR_SSH_HOST:-162.241.252.122}"
USER="${CINDEMIR_SSH_USER:-cindemir}"
KEY="${CINDEMIR_SSH_KEY:-}"
PASS="${CINDEMIR_SSH_PASS:-}"

SSH_OPTS=(-o StrictHostKeyChecking=accept-new -o ConnectTimeout=20)
if [[ -n "$KEY" ]]; then
  SSH_OPTS+=(-i "$KEY")
elif [[ -f "${ROOT}/fixes/.ssh/cindemirlaw_deploy" ]]; then
  SSH_OPTS+=(-i "${ROOT}/fixes/.ssh/cindemirlaw_deploy")
fi

ssh_cmd() {
  if [[ -n "$PASS" && ! " ${SSH_OPTS[*]} " =~ " -i " ]]; then
    sshpass -p "$PASS" ssh "${SSH_OPTS[@]}" "${USER}@${HOST}" "$@"
  else
    ssh "${SSH_OPTS[@]}" "${USER}@${HOST}" "$@"
  fi
}

scp_cmd() {
  if [[ -n "$PASS" && ! " ${SSH_OPTS[*]} " =~ " -i " ]]; then
    sshpass -p "$PASS" scp "${SSH_OPTS[@]}" "$@"
  else
    scp "${SSH_OPTS[@]}" "$@"
  fi
}

echo "Uploading cindemir-menu-fix.php …"
scp_cmd "${ROOT}/fixes/mu-plugins/cindemir-menu-fix.php" \
  "${USER}@${HOST}:public_html/wp-content/mu-plugins/cindemir-menu-fix.php"

echo "Running menu repair …"
ssh_cmd 'cd public_html && wp eval '"'"'if (class_exists("Cindemir_Menu_Fix")) { Cindemir_Menu_Fix::force_repair(); echo "REPAIR_OK\n"; } else { echo "MISSING_CLASS\n"; }'"'"'
ssh_cmd 'cd public_html && wp cache flush 2>/dev/null || true'
echo "Done."
