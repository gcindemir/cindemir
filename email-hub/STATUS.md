# Live cutover status

Updated: 2026-07-10

## Done in repo

- [x] Architecture chosen: Workspace hub + forwarding (no POP)
- [x] DNS record sheet for Doruk → Google
- [x] Migration checklist, client setup, forwarding guides

## Waiting on you (credentials / 2FA)

| Step | Status |
|------|--------|
| Google Admin password / 2FA | **Blocked** — Chrome at password prompt for `gokhancindemir44@gmail.com` → admin.google.com |
| Confirm Workspace owns `cindemir.av.tr` | Pending Admin access |
| Create/verify `gokhan@` + `cindemir@` alias | Pending |
| Doruk DNS login | **Blocked from this VM** — `dns.doruk.net.tr` → `ERR_CONNECTION_RESET` |
| Publish MX/SPF/DKIM/DMARC | Pending reachable Doruk panel (or you paste records in Doruk UI using DNS-RECORDS.md) |
| Enable Gmail/iCloud/Yahoo forwarding | Pending each mailbox login after hub receives mail |

## After you unlock Admin + Doruk

Agent can continue: create users/aliases, copy DKIM, apply DNS, turn on forwarding, verify with test messages.
