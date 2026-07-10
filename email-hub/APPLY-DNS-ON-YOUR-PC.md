# Apply DNS on your PC (Doruk)

This cloud VM **cannot reach** `dns.doruk.net.tr` (`ERR_CONNECTION_RESET`).  
Apply these on your own computer while logged into Doruk.

## Before MX change

1. Confirm Google Workspace has domain `cindemir.av.tr` verified.
2. Users exist: `gokhan@cindemir.av.tr` (+ `cindemir@` alias).
3. Copy DKIM TXT from Admin → Gmail → Authenticate email.

## In Doruk DNS for `cindemir.av.tr`

### A. MX — delete Doruk, add Google

Delete:

- `smail04.doruk.net.tr` (10)
- `mailbackup.doruk.net.tr` (20)

Add:

| Priority | Host | Value |
|---------:|------|-------|
| 1 | `@` | `aspmx.l.google.com.` |
| 5 | `@` | `alt1.aspmx.l.google.com.` |
| 5 | `@` | `alt2.aspmx.l.google.com.` |
| 10 | `@` | `alt3.aspmx.l.google.com.` |
| 10 | `@` | `alt4.aspmx.l.google.com.` |

### B. SPF TXT on `@`

Replace Doruk SPF with:

```text
v=spf1 include:_spf.google.com -all
```

### C. DKIM TXT on `google._domainkey`

Paste the exact value from Google Admin (starts with `v=DKIM1;`).

Then in Admin click **Start authentication**.

### D. DMARC TXT on `_dmarc`

```text
v=DMARC1; p=none; rua=mailto:gokhan@cindemir.av.tr; pct=100
```

## Verify from any machine

```bash
dig +short MX cindemir.av.tr
dig +short TXT cindemir.av.tr
dig +short TXT google._domainkey.cindemir.av.tr
dig +short TXT _dmarc.cindemir.av.tr
```

When MX shows `aspmx.l.google.com`, tell the agent — forwarding + client steps continue next.
