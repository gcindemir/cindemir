# cindemirlaw.com backup

**Created (UTC):** 2026-07-09 11:30:03  
**Source:** https://cindemirlaw.com (162.241.252.122)  
**Method:** `wget --mirror` static frontend snapshot (HTML/CSS/JS/images)

## Important

This is a **public frontend mirror**, not a full WordPress backup (no database, no `wp-admin`, no server-only files).

## Contents

| Item | Description |
|------|-------------|
| `cindemirlaw.com-20260709-113003.tar.gz.part00` … | Split archive parts (reassemble below) |
| `wget-cindemirlaw.com.log` | Download log |
| `cindemirlaw.com-20260709-113003/` | Uncompressed mirror (local only; gitignored) |

**Stats:** 808 files · 308 HTML pages · ~470 MB uncompressed · ~149 MB archive

## Restore archive

```bash
cd backups
cat cindemirlaw.com-20260709-113003.tar.gz.part* > cindemirlaw.com-20260709-113003.tar.gz
tar -xzf cindemirlaw.com-20260709-113003.tar.gz
# Open offline:
# backups/cindemirlaw.com-20260709-113003/cindemirlaw.com/index.html
```
