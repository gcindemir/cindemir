# DNS records for `cindemir.av.tr` (Doruk DNS)

Publish in **Doruk DNS Manager** (`dns.doruk.net.tr` / panel for the domain).  
Lower TTL to **300** on MX/TXT a day before cutover; raise later.

## 1. MX (replace Doruk mail MX)

**Delete** existing:

- `smail04.doruk.net.tr` (priority 10)
- `mailbackup.doruk.net.tr` (priority 20)

**Add** Google Workspace MX (exact set from Admin → Domains → Set up MX; typically):

| Priority | Host / Name | Value |
|---------:|-------------|-------|
| 1 | `@` / blank | `aspmx.l.google.com.` |
| 5 | `@` | `alt1.aspmx.l.google.com.` |
| 5 | `@` | `alt2.aspmx.l.google.com.` |
| 10 | `@` | `alt3.aspmx.l.google.com.` |
| 10 | `@` | `alt4.aspmx.l.google.com.` |

Trailing dots if Doruk requires FQDN.

## 2. SPF (replace Doruk SPF)

**Replace** current:

```text
v=spf1 include:spf.doruk.net.tr ~all
```

**With** (after Google is the only sender for the domain):

```text
v=spf1 include:_spf.google.com -all
```

If Doruk must still send something temporarily (rare):

```text
v=spf1 include:_spf.google.com include:spf.doruk.net.tr ~all
```

Remove Doruk from SPF once cutover is stable.

## 3. DKIM

1. Google Admin → Apps → Google Workspace → Gmail → **Authenticate email**
2. Generate DKIM for `cindemir.av.tr` (selector usually `google`)
3. Add TXT at Doruk:

| Host / Name | Type | Value |
|-------------|------|-------|
| `google._domainkey` | TXT | *(paste full value from Admin — starts with `v=DKIM1; k=rsa; p=...`)* |

4. In Admin, click **Start authentication**

## 4. DMARC

| Host / Name | Type | Value |
|-------------|------|-------|
| `_dmarc` | TXT | `v=DMARC1; p=none; rua=mailto:gokhan@cindemir.av.tr; pct=100` |

After 1–2 weeks of clean reports:

```text
v=DMARC1; p=quarantine; rua=mailto:gokhan@cindemir.av.tr; pct=100
```

Then:

```text
v=DMARC1; p=reject; rua=mailto:gokhan@cindemir.av.tr; pct=100
```

## 5. Verify

```bash
dig +short MX cindemir.av.tr
dig +short TXT cindemir.av.tr
dig +short TXT google._domainkey.cindemir.av.tr
dig +short TXT _dmarc.cindemir.av.tr
```

Expect Google MX, SPF with `_spf.google.com`, DKIM `p=` key, DMARC present.

## 6. Do not touch (unless intentional)

- `cindemirlaw.com` DNS/MX (already Google for the law-site domain)
- Unrelated Doruk web A/CNAME records for the website
