# Services page redesign

Replaces the cluttered Enfold/Avia `/services/` layout with a multilingual editorial page.

## What changes live

Mu-plugin: `fixes/mu-plugins/cindemir-services-page.php` (v1.0.0)

| Page ID | URL |
|---------|-----|
| 18 | `/services/` (EN) |
| 2638 | `/services/?lang=ru` |
| 2637 | `/services/?lang=zh-hans` |
| 56 | `/nashiyurist/` (RU slug) |

## Design notes

- Full-bleed Istanbul hero with brand name, one headline, one short lead, Contacts link
- Practice-area index (in-page anchors) then stacked overviews — no card grid
- Neutral, informational copy (no advertising / CTA hyperbole)
- Duplicate “Startup” blocks and keyword-stuffed sentences removed from the rendered page

## Deploy

```bash
export CINDEMIR_SSH_KEY="$HOME/.ssh/cindemirlaw_deploy"
./fixes/deploy-ssh.sh
```

Then purge WP Rocket / host cache and open `/services/`.
