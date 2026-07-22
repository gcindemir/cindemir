# Deploy: Floating Telegram button

Adds a fixed Telegram contact button linking to **https://t.me/gcindemir** on every front-end page.

## Upload

| File | Target |
|------|--------|
| `fixes/mu-plugins/cindemir-telegram-button.php` | `wp-content/mu-plugins/cindemir-telegram-button.php` |

**Or** install as a normal plugin from `fixes/deploy-package/cindemir-telegram-button.zip` (Plugins → Add New → Upload).

## Behavior

- Blue circular button (Telegram brand color)
- Default: bottom-right
- If Joinchat (WhatsApp) is visible on the right, button moves to bottom-left
- Does not replace footer/header Telegram icons

## After upload

Purge WP Rocket / host cache, then verify:

```bash
UA='Mozilla/5.0'
curl -s -A "$UA" https://cindemirlaw.com/ | grep -c 'cindemir-tg-button'   # >= 1
curl -s -A "$UA" https://cindemir.av.tr/ | grep -c 'cindemir-tg-button'     # >= 1
```
