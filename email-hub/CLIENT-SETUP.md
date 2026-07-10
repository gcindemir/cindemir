# Client setup (one account only)

Account everywhere: **`gokhan@cindemir.av.tr`**  
Password / 2FA: Workspace credentials (not consumer Gmail).

## Gmail Web

1. Open https://mail.google.com
2. Sign in as `gokhan@cindemir.av.tr` (use “Use another account” if consumer Gmail is default)
3. Settings → See all settings → **Accounts and Import**
4. “Send mail as”: only `gokhan@cindemir.av.tr` → **make default**
5. Settings → **General** → Signature if desired

## Outlook (Windows / Mac desktop)

**Preferred:** Add Google account via Microsoft’s Google connector (OAuth).

1. File → Add Account
2. Enter `gokhan@cindemir.av.tr`
3. Choose Google / sign in with Google when prompted
4. Finish; remove any Gmail/Yahoo/iCloud accounts from this Outlook profile

**Manual IMAP (if needed):**

| Setting | Value |
|---------|-------|
| Incoming | `imap.gmail.com` · SSL · 993 |
| Outgoing | `smtp.gmail.com` · SSL/TLS · 465 or STARTTLS 587 |
| Username | `gokhan@cindemir.av.tr` |
| Auth | OAuth2 / App password if org requires |

New PC: install Outlook → add same Google account. **No profile copy.**

## Thunderbird

1. Add account → Email → `gokhan@cindemir.av.tr`
2. Use OAuth2 with Google
3. Account Settings → default identity From = `gokhan@cindemir.av.tr`
4. Remove other accounts

## iPhone

1. Settings → Mail → Accounts → Add Account → **Google**
2. Sign in `gokhan@cindemir.av.tr`
3. Enable Mail
4. Delete Yahoo / iCloud Mail / consumer Gmail from Mail app (keep iCloud account for contacts/photos if needed, but disable **Mail**)

## Android

1. Settings → Accounts → Add account → Google
2. `gokhan@cindemir.av.tr`
3. Gmail app → switch to that account as default for sending

## Verify Reply behavior

1. From personal Gmail, email yourself at `gokhancindemir44@gmail.com`
2. Message arrives in Workspace (via forward)
3. Reply from phone/Outlook/web
4. Recipient must see **From: gokhan@cindemir.av.tr**
