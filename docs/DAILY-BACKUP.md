# Daily backup — cindemirlaw.com

Primary path: **WordPress WP-Cron** (no SSH). Optional SSH/GitHub Actions remain as a secondary offsite copy.

## What you get (WordPress)

Each run stores under the site:

```
wp-content/cindemir-backups/YYYY-MM-DD/
  database.sql.gz
  wp-config.php.gz
  code.zip              # mu-plugins + plugins + themes
  uploads.zip           # if uploads tree < ~450MB
  manifest.json
  STATUS.json
wp-content/cindemir-backup-latest.json
```

Retention: **14 days**. Directory is denied via `.htaccess`.

Schedule: ~**02:00** site timezone (WP-Cron). If cron is lazy on shared hosting, an overdue run may start on front traffic (throttled).

## Deploy (mu-plugin)

`cindemir-backup.php` is pulled with the usual pack:

```bash
# After pushing to cursor/cindemirlaw-seo-tasks-d204:
curl -sS -X POST \
  "https://cindemirlaw.com/wp-json/cindemir/v1/pull-plugins?key=seo-pack-2026&commit=<sha>"
```

SEO `ensure_backup_mu_plugin()` also installs/refreshes the backup plugin on `init` when missing or outdated.

## REST API (`key=seo-pack-2026`)

| Route | Purpose |
|-------|---------|
| `GET /wp-json/cindemir/v1/backup-status` | Last run + next cron |
| `GET /wp-json/cindemir/v1/backup-list` | Recent backup folders |
| `POST /wp-json/cindemir/v1/backup-run` | Run backup now |

Examples:

```bash
curl -sS 'https://cindemirlaw.com/wp-json/cindemir/v1/backup-status?key=seo-pack-2026'
curl -sS -X POST 'https://cindemirlaw.com/wp-json/cindemir/v1/backup-run?key=seo-pack-2026'
curl -sS 'https://cindemirlaw.com/wp-json/cindemir/v1/backup-list?key=seo-pack-2026'
```

## Restore (overview)

```bash
# DB (on server / staging)
gunzip -c database.sql.gz | wp db import - --path=~/public_html

# Code
cd ~/public_html/wp-content && unzip /path/to/code.zip

# Uploads (if present)
cd ~/public_html/wp-content && unzip /path/to/uploads.zip

# wp-config only if needed
gunzip -c wp-config.php.gz > ~/public_html/wp-config.php
```

Prefer restoring into a staging copy first.

## Optional: SSH / GitHub Actions (secondary)

If `CINDEMIR_SSH_KEY` is set, the older Bluehost script + Actions workflow can still copy archives offsite:

| Path | Role |
|------|------|
| `fixes/scripts/server-daily-backup.sh` | Full `wp-content` tar under `~/backups/cindemirlaw/` |
| `scripts/backup/run-daily-backup.sh` | SSH runner + download |
| `.github/workflows/daily-backup.yml` | Cron `0 2 * * *` UTC |

Not required for the WordPress-native daily backup to work.

## Security

- Archives contain **database credentials and customer data** — never commit them to git.
- `backups/live/` is gitignored.
- Status JSON under `wp-content/` is denied via `.htaccess`; use the REST key to read it.

## Files

| Path | Role |
|------|------|
| `fixes/mu-plugins/cindemir-backup.php` | WP-Cron + REST (primary) |
| `docs/DAILY-BACKUP.md` | This doc |
| `fixes/scripts/server-daily-backup.sh` | Optional SSH full backup |
| `scripts/backup/run-daily-backup.sh` | Optional SSH runner |
| `.github/workflows/daily-backup.yml` | Optional Actions job |
