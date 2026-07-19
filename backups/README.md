# cindemirlaw.com backups

## Full daily backups (recommended)

Database + `wp-content` + `wp-config`, automated every day.

→ See **[docs/DAILY-BACKUP.md](../docs/DAILY-BACKUP.md)**

```bash
export CINDEMIR_SSH_KEY=~/.ssh/cindemirlaw_deploy
./scripts/backup/run-daily-backup.sh --install-cron   # once
./scripts/backup/run-daily-backup.sh                  # manual run
```

GitHub Actions workflow: `.github/workflows/daily-backup.yml` (02:00 UTC daily).

---

## Static public mirrors (legacy)

`wget --mirror` of the public frontend only — **not** a recoverable WordPress backup (no database / `wp-admin`).

### Latest mirror (2026-07-11)

| Field | Value |
|-------|-------|
| **Created (UTC)** | 2026-07-11 21:36:40 |
| **Source** | https://cindemirlaw.com |
| **Archive** | `cindemirlaw.com-20260711-213640.tar.gz.part00` … `part01` |

```bash
./backups/create-backup.sh
```
