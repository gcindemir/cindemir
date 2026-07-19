# Daily backup — cindemirlaw.com

Full WordPress backup (database + `wp-content` + `wp-config`), every day.

## What you get

Each run creates on the server:

```
~/backups/cindemirlaw/YYYY-MM-DD/
  database.sql.gz
  wp-config.php.gz
  wp-content.tar.gz      # caches excluded
  manifest.txt
  STATUS.json
~/backups/cindemirlaw/LATEST.json
wp-content/cindemir-backup-latest.json   # for REST status
```

Retention on server: **14 days** (configurable via `CINDEMIR_BACKUP_KEEP_DAYS`).

Offsite copy: GitHub Actions uploads artifacts (DB + config + manifest by default) with **14-day** retention.

## 1) One-time setup

### A. GitHub secrets (repo → Settings → Secrets)

| Secret | Required | Value |
|--------|----------|--------|
| `CINDEMIR_SSH_KEY` | yes | Private key for `cindemir@162.241.252.122` |
| `CINDEMIR_SSH_HOST` | no | default `162.241.252.122` |
| `CINDEMIR_SSH_USER` | no | default `cindemir` |
| `CINDEMIR_BACKUP_PULL_CONTENT` | no | `1` to also download `wp-content.tar.gz` |

### B. Install server script + cron

Locally (or via Actions → Run workflow → `install_cron=1`):

```bash
export CINDEMIR_SSH_KEY=~/.ssh/cindemirlaw_deploy
chmod +x scripts/backup/run-daily-backup.sh fixes/scripts/server-daily-backup.sh
./scripts/backup/run-daily-backup.sh --install-cron
```

Cron line (02:00 server time):

```
0 2 * * * $HOME/bin/cindemir-daily-backup.sh >> $HOME/backups/cindemirlaw/cron.log 2>&1
```

### C. Deploy status mu-plugin (optional)

Upload `fixes/mu-plugins/cindemir-backup.php` with the usual mu-plugin pull/SSH deploy, then:

```bash
curl -sS 'https://cindemirlaw.com/wp-json/cindemir/v1/backup-status?key=seo-pack-2026'
```

## 2) Daily automation

Workflow: `.github/workflows/daily-backup.yml`

- **Schedule:** `0 2 * * *` UTC
- **Manual:** Actions → “Daily cindemirlaw.com backup” → Run workflow

It will:

1. SSH to Bluehost  
2. Run `~/bin/cindemir-daily-backup.sh`  
3. Download `database.sql.gz`, `wp-config.php.gz`, `manifest.txt`, `STATUS.json`  
4. Upload them as a GitHub Actions artifact (14 days)

## 3) Manual run

```bash
export CINDEMIR_SSH_KEY=~/.ssh/cindemirlaw_deploy
./scripts/backup/run-daily-backup.sh

# Also pull full wp-content archive:
CINDEMIR_BACKUP_PULL_CONTENT=1 ./scripts/backup/run-daily-backup.sh
```

## 4) Restore (overview)

```bash
# DB
gunzip -c database.sql.gz | wp db import - --path=~/public_html

# wp-content
cd ~/public_html && tar -xzf /path/to/wp-content.tar.gz

# wp-config (only if needed)
gunzip -c wp-config.php.gz > ~/public_html/wp-config.php
```

Prefer restoring into a staging copy first.

## 5) Security notes

- Archives contain **database credentials and customer data** — never commit them to git.
- `backups/live/` is gitignored.
- Status JSON under `wp-content/` is denied via `.htaccess`; use the REST key to read it.

## Files

| Path | Role |
|------|------|
| `fixes/scripts/server-daily-backup.sh` | Runs on Bluehost |
| `scripts/backup/run-daily-backup.sh` | SSH runner + download |
| `.github/workflows/daily-backup.yml` | Daily GitHub Actions job |
| `fixes/mu-plugins/cindemir-backup.php` | `/backup-status` REST |
| `docs/DAILY-BACKUP.md` | This doc |
