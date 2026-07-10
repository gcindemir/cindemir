# Architecture

## Decision: Google Workspace as hub

| Option | Verdict |
|--------|---------|
| Google Workspace + forwarding | **Chosen** — matches Gmail Web, mobile Google sync, Outlook/Thunderbird OAuth, no POP |
| Microsoft 365 hub | Viable but weaker Gmail Web story |
| POP fetch into Gmail | Rejected — user forbids POP; delayed; duplicate risk |
| Multi-account Outlook | Rejected — Reply uses wrong From |

## Mailboxes

| Address | Role |
|---------|------|
| `gokhan@cindemir.av.tr` | Primary hub mailbox (read/send everything here) |
| `cindemir@cindemir.av.tr` | Alias of `gokhan@` **or** separate user that forwards to `gokhan@` |

Recommendation: make `cindemir@` an **alias** of `gokhan@` unless two people need separate logins.

## Personal accounts (inbound only)

| Account | Action |
|---------|--------|
| `gokhancindemir44@gmail.com` | Auto-forward → `gokhan@cindemir.av.tr` |
| `gokhancindemir@me.com` | Auto-forward → `gokhan@cindemir.av.tr` |
| `gcindemir@yahoo.com` | Auto-forward → `gokhan@cindemir.av.tr` |

Do **not** add these as send-as identities on the hub (keeps Reply From = professional address).

Optional Gmail filters on the hub: label `fwd/gmail`, `fwd/icloud`, `fwd/yahoo` using `deliveredto:` / `X-Forwarded-For` / subject patterns if available.

## Outbound identity

- SMTP path: **only** Google Workspace for `gokhan@cindemir.av.tr`
- Default “Send mail as” / account identity everywhere = `gokhan@cindemir.av.tr`
- Never configure Yahoo/iCloud/Gmail SMTP in Outlook/Thunderbird for daily use

## Sync model

One Google account → all clients via IMAP/OAuth. New computer = sign in. No PST as source of truth. No Outlook profile migration.
