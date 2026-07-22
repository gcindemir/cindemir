# Deploy: Telegram button (Russian pages)

Floating Telegram button on **Russian (`?lang=ru`)** pages only.

- Link: **https://t.me/Cindemir_Law_Office** (`@Cindemir_Law_Office`)
- Position: bottom-right (WhatsApp stays bottom-left — no overlap)

## Upload

| File | Target |
|------|--------|
| `fixes/mu-plugins/cindemir-telegram-button.php` | `wp-content/mu-plugins/cindemir-telegram-button.php` |

Or install `fixes/deploy-package/cindemir-telegram-button.zip` via Plugins → Upload.

## Verify

```bash
UA='Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'

# Russian: button present + correct username
curl -s -A "$UA" 'https://cindemirlaw.com/?lang=ru' | grep -o 't.me/Cindemir_Law_Office' | head -1
curl -s -A "$UA" 'https://cindemirlaw.com/?lang=ru' | grep -c 'cindemir-tg-button'

# English: button must NOT appear
curl -s -A "$UA" 'https://cindemirlaw.com/' | grep -c 'cindemir-tg-button'   # expect 0
```

Purge WP Rocket / host cache after upload.
