# Team photo update (2026-07-18)

The far-left person in `5295681199059` left the firm. The image was cropped and redeployed.

## Files
- Source: `fixes/media/team-2026/5295681199059.{jpg,webp}` (+ size variants)
- Sync: `Cindemir_SEO_Fixes::sync_updated_team_photo()` (SEO Fixes 1.9.57)
- Live path: `wp-content/uploads/2020/06/5295681199059*`

## Deploy
```bash
# After pushing to cursor/cindemirlaw-seo-tasks-d204:
curl -sL 'https://purge.jsdelivr.net/gh/gcindemir/cindemir@cursor/cindemirlaw-seo-tasks-d204/fixes/mu-plugins/cindemir-seo-fixes.php'
curl -sL 'https://cindemirlaw.com/wp-json/cindemir/v1/pull-plugins?key=seo-pack-2026'
curl -sL 'https://cindemirlaw.com/wp-json/cindemir/v1/sync-team-photo?key=seo-pack-2026'
```

## Verify
```bash
curl -sI 'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059.jpg'
# content-length should be ~74KB (was ~46KB)
```
