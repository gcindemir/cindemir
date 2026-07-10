# Email hub: `gokhan@cindemir.av.tr`

Single cloud inbox on **Google Workspace**. Personal accounts only forward in. All replies send as `gokhan@cindemir.av.tr`.

## Current state (2026-07-10)

| Item | Status |
|------|--------|
| `cindemir.av.tr` MX | **Doruk** (`smail04.doruk.net.tr`) — not Google yet |
| `cindemir.av.tr` SPF | `include:spf.doruk.net.tr` |
| `cindemir.av.tr` NS | Doruk DNS |
| `cindemirlaw.com` MX | Already Google (separate site domain; not this hub) |
| Workspace login URL | `https://mail.google.com/a/cindemir.av.tr` responds (domain may already be in Workspace or claimable) |

## Target

```
Gmail / iCloud / Yahoo  --forward-->  gokhan@cindemir.av.tr  (Workspace)
                                         ^
                    Gmail Web / Outlook / Thunderbird / iOS / Android
                                         |
                              Send/Reply From: gokhan@cindemir.av.tr only
```

No POP3. No multi-account Outlook. Cloud = source of truth.

## Docs in this folder

| File | Purpose |
|------|---------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Design decisions |
| [DNS-RECORDS.md](./DNS-RECORDS.md) | Exact MX / SPF / DKIM / DMARC for Doruk DNS |
| [MIGRATION-CHECKLIST.md](./MIGRATION-CHECKLIST.md) | Ordered cutover steps |
| [CLIENT-SETUP.md](./CLIENT-SETUP.md) | Outlook, Thunderbird, phone, Gmail Web |
| [FORWARDING.md](./FORWARDING.md) | Gmail / iCloud / Yahoo forward settings |

## Blockers for live cutover

1. Google Admin login (password / 2FA) for Workspace on `cindemir.av.tr`
2. Doruk DNS panel login to publish MX/SPF/DKIM/DMARC
3. Confirm Workspace license exists for `cindemir.av.tr` (or create new Workspace / add domain)
