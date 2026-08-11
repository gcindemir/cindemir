# cindemirlaw.com backups

Static **public frontend mirrors** via `wget --mirror`. Not full WordPress backups (no database, no `wp-admin`).

## Latest (2026-07-11)

| Field | Value |
|-------|-------|
| **Created (UTC)** | 2026-07-11 21:36:40 |
| **Source** | https://cindemirlaw.com |
| **Archive** | `cindemirlaw.com-20260711-213640.tar.gz.part00` … `part01` |
| **Log** | `wget-cindemirlaw.com-20260711-213640.log` |
| **Files** | 490 |
| **HTML pages** | 308 |
| **Uncompressed** | 276M (gitignored) |

## Restore latest archive

```bash
cd backups
cat cindemirlaw.com-20260711-213640.tar.gz.part* > cindemirlaw.com-20260711-213640.tar.gz
tar -xzf cindemirlaw.com-20260711-213640.tar.gz
# Open offline: backups/cindemirlaw.com-20260711-213640/cindemirlaw.com/index.html
```

## Create a new backup

```bash
./backups/create-backup.sh
```

## Previous backup (2026-07-09)

| Item | Description |
|------|-------------|
| `cindemirlaw.com-20260709-113003.tar.gz.part00` … | Earlier mirror (808 files, ~149 MB archive) |
| `wget-cindemirlaw.com.log` | Download log |

```bash
cd backups
cat cindemirlaw.com-20260709-113003.tar.gz.part* > cindemirlaw.com-20260709-113003.tar.gz
tar -xzf cindemirlaw.com-20260709-113003.tar.gz
```
