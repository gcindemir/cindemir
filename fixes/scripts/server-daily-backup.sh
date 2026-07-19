#!/usr/bin/env bash
# cindemirlaw.com — daily backup (runs ON Bluehost / the WordPress host)
#
# Creates under ~/backups/cindemirlaw/YYYY-MM-DD/:
#   database.sql.gz   — full MySQL dump via WP-CLI
#   wp-config.php.gz  — config (contains secrets; keep private)
#   wp-content.tar.gz — themes, plugins, mu-plugins, uploads (caches excluded)
#   manifest.txt      — versions + sizes
#   STATUS.json       — machine-readable summary for monitoring
#
# Retention: deletes dated folders older than KEEP_DAYS (default 14).
#
# Install (once, via SSH):
#   mkdir -p ~/bin ~/backups/cindemirlaw
#   scp fixes/scripts/server-daily-backup.sh cindemirlaw:~/bin/cindemir-daily-backup.sh
#   chmod +x ~/bin/cindemir-daily-backup.sh
#   crontab -e   →  0 2 * * * /home4/cindemir/bin/cindemir-daily-backup.sh >> /home4/cindemir/backups/cindemirlaw/cron.log 2>&1
#
# Or trigger remotely:
#   ./scripts/backup/run-daily-backup.sh

set -euo pipefail

WP_PATH="${CINDEMIR_WP_PATH:-$HOME/public_html}"
BACKUP_ROOT="${CINDEMIR_BACKUP_ROOT:-$HOME/backups/cindemirlaw}"
KEEP_DAYS="${CINDEMIR_BACKUP_KEEP_DAYS:-14}"
WP_CLI="${CINDEMIR_WP_CLI:-/usr/local/bin/wp}"
DATE="$(date +%F)"
STAMP="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
DIR="${BACKUP_ROOT}/${DATE}"
LOCK="${BACKUP_ROOT}/.lock"

mkdir -p "$BACKUP_ROOT" "$DIR"

if command -v flock >/dev/null 2>&1; then
	exec 9>"$LOCK"
	if ! flock -n 9; then
		echo "Backup already running; exiting."
		exit 0
	fi
fi

cd "$WP_PATH"

echo "==> cindemirlaw daily backup ${DATE}"
echo "    WP_PATH=${WP_PATH}"
echo "    DIR=${DIR}"

# --- Database ---
if [[ -x "$WP_CLI" ]] || command -v wp >/dev/null 2>&1; then
	WP=( "$WP_CLI" )
	command -v "$WP_CLI" >/dev/null 2>&1 || WP=( wp )
	"${WP[@]}" db export - --path="$WP_PATH" --allow-root 2>/dev/null | gzip -9 > "${DIR}/database.sql.gz" \
		|| "${WP[@]}" db export - --path="$WP_PATH" | gzip -9 > "${DIR}/database.sql.gz"
else
	echo "ERROR: WP-CLI not found at ${WP_CLI}" >&2
	exit 1
fi

# --- wp-config ---
if [[ -f wp-config.php ]]; then
	gzip -9 -c wp-config.php > "${DIR}/wp-config.php.gz"
fi

# --- wp-content (exclude caches / temp) ---
tar -czf "${DIR}/wp-content.tar.gz" \
	--exclude='wp-content/cache' \
	--exclude='wp-content/wp-rocket-config' \
	--exclude='wp-content/et-cache' \
	--exclude='wp-content/uploads/wpcf7_uploads' \
	--exclude='wp-content/upgrade' \
	--exclude='wp-content/debug.log' \
	--exclude='*.log' \
	wp-content

# --- Manifest ---
{
	echo "site=cindemirlaw.com"
	echo "date=${DATE}"
	echo "utc=${STAMP}"
	echo "host=$(hostname 2>/dev/null || echo unknown)"
	echo "wp_path=${WP_PATH}"
	echo "---"
	"${WP[@]}" core version --path="$WP_PATH" --allow-root 2>/dev/null || "${WP[@]}" core version --path="$WP_PATH" || true
	echo "--- plugins ---"
	"${WP[@]}" plugin list --path="$WP_PATH" --format=csv --allow-root 2>/dev/null \
		|| "${WP[@]}" plugin list --path="$WP_PATH" --format=csv || true
	echo "--- themes ---"
	"${WP[@]}" theme list --path="$WP_PATH" --format=csv --allow-root 2>/dev/null \
		|| "${WP[@]}" theme list --path="$WP_PATH" --format=csv || true
	echo "--- sizes ---"
	du -h "${DIR}/database.sql.gz" "${DIR}/wp-config.php.gz" "${DIR}/wp-content.tar.gz" 2>/dev/null || true
	du -sh "$DIR" 2>/dev/null || true
} > "${DIR}/manifest.txt"

DB_BYTES=$(wc -c < "${DIR}/database.sql.gz" | tr -d ' ')
CFG_BYTES=$(wc -c < "${DIR}/wp-config.php.gz" 2>/dev/null | tr -d ' ' || echo 0)
CONTENT_BYTES=$(wc -c < "${DIR}/wp-content.tar.gz" | tr -d ' ')
TOTAL_BYTES=$(( DB_BYTES + CFG_BYTES + CONTENT_BYTES ))

cat > "${DIR}/STATUS.json" <<EOF
{
  "ok": true,
  "site": "cindemirlaw.com",
  "date": "${DATE}",
  "utc": "${STAMP}",
  "dir": "${DIR}",
  "files": {
    "database.sql.gz": ${DB_BYTES},
    "wp-config.php.gz": ${CFG_BYTES},
    "wp-content.tar.gz": ${CONTENT_BYTES}
  },
  "total_bytes": ${TOTAL_BYTES},
  "keep_days": ${KEEP_DAYS}
}
EOF

# Pointer for monitoring / REST
cp -f "${DIR}/STATUS.json" "${BACKUP_ROOT}/LATEST.json"
# Web-reachable status for authenticated REST (not a public URL listing)
if [[ -d "${WP_PATH}/wp-content" ]]; then
	cp -f "${DIR}/STATUS.json" "${WP_PATH}/wp-content/cindemir-backup-latest.json"
fi

# --- Retention ---
if [[ "$KEEP_DAYS" =~ ^[0-9]+$ ]] && [[ "$KEEP_DAYS" -gt 0 ]]; then
	find "$BACKUP_ROOT" -mindepth 1 -maxdepth 1 -type d -name '20*' -mtime "+${KEEP_DAYS}" -print -exec rm -rf {} \; 2>/dev/null || true
fi

echo "==> Done: ${DIR}"
echo "    database.sql.gz  = ${DB_BYTES} bytes"
echo "    wp-content.tar.gz = ${CONTENT_BYTES} bytes"
cat "${DIR}/STATUS.json"
