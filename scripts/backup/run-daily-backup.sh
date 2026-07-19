#!/usr/bin/env bash
# Run cindemirlaw.com daily backup over SSH, then pull critical artifacts locally.
#
# Env:
#   CINDEMIR_SSH_KEY   path to private key (or GitHub secret written to a file)
#   CINDEMIR_SSH_HOST  default 162.241.252.122
#   CINDEMIR_SSH_USER  default cindemir
#   CINDEMIR_SSH_PASS  optional password fallback (sshpass)
#   CINDEMIR_BACKUP_OUT  local output dir (default ./backups/live)
#   CINDEMIR_BACKUP_PULL_CONTENT  set to 1 to also download wp-content.tar.gz
#
# Usage:
#   export CINDEMIR_SSH_KEY=~/.ssh/cindemirlaw_deploy
#   ./scripts/backup/run-daily-backup.sh
#   ./scripts/backup/run-daily-backup.sh --install-cron   # one-time crontab install

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
HOST="${CINDEMIR_SSH_HOST:-162.241.252.122}"
USER="${CINDEMIR_SSH_USER:-cindemir}"
KEY="${CINDEMIR_SSH_KEY:-}"
PASS="${CINDEMIR_SSH_PASS:-}"
OUT="${CINDEMIR_BACKUP_OUT:-$ROOT/backups/live}"
PULL_CONTENT="${CINDEMIR_BACKUP_PULL_CONTENT:-0}"
INSTALL_CRON=false
[[ "${1:-}" == "--install-cron" ]] && INSTALL_CRON=true

SSH_OPTS=(-o StrictHostKeyChecking=accept-new -o ConnectTimeout=30 -o ServerAliveInterval=30)
SCP_OPTS=(-o StrictHostKeyChecking=accept-new -o ConnectTimeout=30)

if [[ -n "$KEY" ]]; then
	SSH_OPTS+=(-i "$KEY")
	SCP_OPTS+=(-i "$KEY")
elif [[ -f "${ROOT}/fixes/.ssh/cindemirlaw_deploy" ]]; then
	SSH_OPTS+=(-i "${ROOT}/fixes/.ssh/cindemirlaw_deploy")
	SCP_OPTS+=(-i "${ROOT}/fixes/.ssh/cindemirlaw_deploy")
fi

ssh_cmd() {
	if [[ -n "${PASS}" && -z "${KEY}" && ! " ${SSH_OPTS[*]} " =~ " -i " ]]; then
		command sshpass -p "$PASS" ssh "${SSH_OPTS[@]}" "${USER}@${HOST}" "$@"
	else
		ssh "${SSH_OPTS[@]}" "${USER}@${HOST}" "$@"
	fi
}

scp_from() {
	# scp_from remote local
	if [[ -n "${PASS}" && -z "${KEY}" && ! " ${SCP_OPTS[*]} " =~ " -i " ]]; then
		command sshpass -p "$PASS" scp "${SCP_OPTS[@]}" "$@"
	else
		scp "${SCP_OPTS[@]}" "$@"
	fi
}

SERVER_SCRIPT="${ROOT}/fixes/scripts/server-daily-backup.sh"
if [[ ! -f "$SERVER_SCRIPT" ]]; then
	echo "Missing ${SERVER_SCRIPT}" >&2
	exit 1
fi

echo "==> Installing/updating remote backup script"
ssh_cmd 'mkdir -p ~/bin ~/backups/cindemirlaw'
scp_from "$SERVER_SCRIPT" "${USER}@${HOST}:~/bin/cindemir-daily-backup.sh"
ssh_cmd 'chmod +x ~/bin/cindemir-daily-backup.sh'

if [[ "$INSTALL_CRON" == true ]]; then
	echo "==> Installing daily crontab (02:00 server time)"
	ssh_cmd 'bash -s' <<'CRON'
set -euo pipefail
CRON_LINE='0 2 * * * /home4/cindemir/bin/cindemir-daily-backup.sh >> /home4/cindemir/backups/cindemirlaw/cron.log 2>&1'
# Detect home if not /home4/cindemir
HOME_DIR="${HOME}"
CRON_LINE="0 2 * * * ${HOME_DIR}/bin/cindemir-daily-backup.sh >> ${HOME_DIR}/backups/cindemirlaw/cron.log 2>&1"
tmpdir=$(mktemp)
crontab -l 2>/dev/null | grep -v 'cindemir-daily-backup' > "$tmpdir" || true
echo "$CRON_LINE" >> "$tmpdir"
crontab "$tmpdir"
rm -f "$tmpdir"
echo "Installed:"
crontab -l | grep cindemir-daily-backup || true
CRON
	echo "==> Cron installed."
	exit 0
fi

echo "==> Running remote backup"
ssh_cmd 'bash ~/bin/cindemir-daily-backup.sh'

DATE="$(ssh_cmd 'date +%F')"
REMOTE_DIR="~/backups/cindemirlaw/${DATE}"
LOCAL_DIR="${OUT}/${DATE}"
mkdir -p "$LOCAL_DIR"

echo "==> Pulling critical artifacts → ${LOCAL_DIR}"
scp_from "${USER}@${HOST}:${REMOTE_DIR}/database.sql.gz" "${LOCAL_DIR}/database.sql.gz"
scp_from "${USER}@${HOST}:${REMOTE_DIR}/wp-config.php.gz" "${LOCAL_DIR}/wp-config.php.gz" || true
scp_from "${USER}@${HOST}:${REMOTE_DIR}/manifest.txt" "${LOCAL_DIR}/manifest.txt"
scp_from "${USER}@${HOST}:${REMOTE_DIR}/STATUS.json" "${LOCAL_DIR}/STATUS.json"
scp_from "${USER}@${HOST}:~/backups/cindemirlaw/LATEST.json" "${OUT}/LATEST.json" || true

if [[ "$PULL_CONTENT" == "1" ]]; then
	echo "==> Pulling wp-content.tar.gz (large)"
	scp_from "${USER}@${HOST}:${REMOTE_DIR}/wp-content.tar.gz" "${LOCAL_DIR}/wp-content.tar.gz"
fi

echo "==> Local backup ready: ${LOCAL_DIR}"
ls -lh "$LOCAL_DIR"
cat "${LOCAL_DIR}/STATUS.json"
