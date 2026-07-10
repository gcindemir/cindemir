# Forwarding: personal → hub

Forward **to:** `gokhan@cindemir.av.tr`  
Keep a copy on the source only if you want a backup; otherwise forward-only is fine.

## Gmail — `gokhancindemir44@gmail.com`

1. Sign in to consumer Gmail
2. Settings → See all settings → **Forwarding and POP/IMAP**
3. **Add a forwarding address** → `gokhan@cindemir.av.tr`
4. Confirm the code emailed to the Workspace inbox
5. Select **Forward a copy** to `gokhan@…` (and keep/delete Gmail copy as you prefer)
6. **Disable POP** download (POP off)
7. Save changes

Optional: filter “to:me” still works; hub will see forwarded mail.

## iCloud — `gokhancindemir@me.com`

1. https://www.icloud.com/mail (or Apple ID → iCloud Settings on device)
2. Mail Settings (gear) → **Rules** / Forwarding (UI varies by Apple)
3. Or: Apple ID account page → iCloud → Mail → **Forward email** to `gokhan@cindemir.av.tr`
4. Prefer leaving a copy on iCloud until cutover is trusted, then optional delete-after-forward

If Rules: “If message is to me@… → Forward to gokhan@cindemir.av.tr”

## Yahoo — `gcindemir@yahoo.com`

1. Sign in Yahoo Mail
2. Settings → More settings → **Mailboxes** → your address → **Forwarding**
3. Enable forwarding to `gokhan@cindemir.av.tr`
4. Confirm verification if asked
5. Turn **POP** off if shown

## After all three

Send a unique subject from an external account to each address:

- `TEST-GMAIL-YYYYMMDD`
- `TEST-ICLOUD-YYYYMMDD`
- `TEST-YAHOO-YYYYMMDD`

Confirm all three appear under `gokhan@cindemir.av.tr`.

## Hub filters (optional)

In Workspace Gmail → Filters:

| Match | Label |
|-------|-------|
| `from:(forwarding-noreply@google.com)` or headers mentioning gmail forward | `fwd/gmail` |
| (after observing Yahoo/iCloud headers) | `fwd/yahoo` / `fwd/icloud` |

Exact header rules depend on provider; create after first real forwards arrive.
