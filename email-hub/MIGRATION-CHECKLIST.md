# Migration checklist

Do **in order**. Do not change MX before users exist and domain is verified in Workspace.

## Phase 0 — Access

- [ ] Google Admin login works (`admin.google.com`) with an admin for `cindemir.av.tr`
- [ ] Doruk DNS login works for `cindemir.av.tr`
- [ ] Confirm Workspace subscription / seats for `gokhan@` and optionally `cindemir@`

## Phase 1 — Workspace prep (before MX change)

- [ ] Add or verify domain `cindemir.av.tr` in Google Admin
- [ ] Create user `gokhan@cindemir.av.tr` (or confirm exists)
- [ ] Create `cindemir@cindemir.av.tr` as **alias** of `gokhan@` (preferred) or separate user
- [ ] Sign in once to Gmail as `gokhan@cindemir.av.tr`
- [ ] Generate DKIM key in Admin (do not start auth until TXT is published)
- [ ] Lower DNS TTL on MX/TXT to 300

## Phase 2 — Optional: import old Doruk mail

- [ ] Google Admin → Data migration (or IMAP migration) from Doruk into `gokhan@`
- [ ] Spot-check folders/labels

## Phase 3 — DNS cutover

- [ ] Publish Google MX (remove Doruk MX) — see [DNS-RECORDS.md](./DNS-RECORDS.md)
- [ ] Publish SPF `include:_spf.google.com`
- [ ] Publish DKIM TXT → Start authentication in Admin
- [ ] Publish DMARC `p=none`
- [ ] Wait for propagation; `dig` verify
- [ ] Admin → Domains → Gmail setup shows MX verified (green)

## Phase 4 — Send identity

- [ ] Gmail (as `gokhan@`) → Settings → Accounts → default send address = `gokhan@cindemir.av.tr`
- [ ] Do **not** add Yahoo / iCloud / consumer Gmail as Send mail as
- [ ] Send test to external address (e.g. personal Gmail); check From + https://www.mail-tester.com score

## Phase 5 — Forwarding (personal → hub)

- [ ] `gokhancindemir44@gmail.com` → forward to `gokhan@cindemir.av.tr` ([FORWARDING.md](./FORWARDING.md))
- [ ] `gokhancindemir@me.com` → forward to hub
- [ ] `gcindemir@yahoo.com` → forward to hub
- [ ] Send test into each personal address; confirm arrival in Workspace inbox
- [ ] Optional: labels/filters for source

## Phase 6 — Clients

- [ ] Remove extra mail accounts from Outlook / Thunderbird / iPhone / Android
- [ ] Add **only** `gokhan@cindemir.av.tr` ([CLIENT-SETUP.md](./CLIENT-SETUP.md))
- [ ] Reply to a forwarded Gmail message; confirm From is `gokhan@cindemir.av.tr`

## Phase 7 — Harden & clean up

- [ ] DMARC → `quarantine` then `reject` when reports are clean
- [ ] Enforce 2FA on Workspace admin + `gokhan@`
- [ ] Stop daily use of Yahoo/iCloud/Gmail UIs (keep accounts for Apple ID / recovery / forwarding only)
- [ ] After stable period: cancel Doruk mailbox product if billed separately (keep DNS if site still on Doruk)

## Rollback

If Google MX fails: restore Doruk MX + Doruk SPF from backup notes; disable Workspace-only assumption until fixed. Keep forwarding off until hub receives mail again.
