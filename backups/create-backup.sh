#!/usr/bin/env bash
# Static frontend mirror of cindemirlaw.com (wget --mirror).
# Not a full WordPress backup (no DB, no wp-admin).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BACKUPS="$ROOT/backups"
STAMP="$(date -u +%Y%m%d-%H%M%S)"
NAME="cindemirlaw.com-${STAMP}"
DEST="$BACKUPS/$NAME"
LOG="$BACKUPS/wget-${NAME}.log"
UA='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'

mkdir -p "$DEST"

echo "Starting mirror → $DEST"
wget \
  --mirror \
  --convert-links \
  --adjust-extension \
  --page-requisites \
  --no-parent \
  --execute robots=off \
  --user-agent="$UA" \
  --wait=0.2 \
  --random-wait \
  --directory-prefix="$DEST" \
  --restrict-file-names=windows \
  --no-check-certificate \
  "https://cindemirlaw.com/" \
  2>&1 | tee "$LOG"

TARBALL="$BACKUPS/${NAME}.tar.gz"
echo "Creating archive $TARBALL"
tar -czf "$TARBALL" -C "$BACKUPS" "$NAME"

# Split for git (100 MB parts)
split -b 100M -d "$TARBALL" "${TARBALL}.part"
rm -f "$TARBALL"

PARTS=( "${TARBALL}.part"* )
FILE_COUNT=$(find "$DEST" -type f | wc -l)
HTML_COUNT=$(find "$DEST" -name '*.html' | wc -l)
UNCOMPRESSED=$(du -sh "$DEST" | cut -f1)

cat > "$BACKUPS/README.md" <<EOF
# cindemirlaw.com backups

Static **public frontend mirrors** via \`wget --mirror\`. Not full WordPress backups (no database, no \`wp-admin\`).

## Latest

| Field | Value |
|-------|-------|
| **Created (UTC)** | $(date -u '+%Y-%m-%d %H:%M:%S') |
| **Source** | https://cindemirlaw.com |
| **Archive** | \`${NAME}.tar.gz.part*\` |
| **Log** | \`wget-${NAME}.log\` |
| **Files** | ${FILE_COUNT} |
| **HTML pages** | ${HTML_COUNT} |
| **Uncompressed** | ${UNCOMPRESSED} (gitignored) |

## Restore latest archive

\`\`\`bash
cd backups
cat ${NAME}.tar.gz.part* > ${NAME}.tar.gz
tar -xzf ${NAME}.tar.gz
# Open offline: backups/${NAME}/cindemirlaw.com/index.html
\`\`\`

## Previous backup (2026-07-09)

| Item | Description |
|------|-------------|
| \`cindemirlaw.com-20260709-113003.tar.gz.part00\` … | Earlier mirror (808 files, ~149 MB archive) |
| \`wget-cindemirlaw.com.log\` | Download log |

\`\`\`bash
cd backups
cat cindemirlaw.com-20260709-113003.tar.gz.part* > cindemirlaw.com-20260709-113003.tar.gz
tar -xzf cindemirlaw.com-20260709-113003.tar.gz
\`\`\`
EOF

echo "Done. Parts: ${PARTS[*]}"
