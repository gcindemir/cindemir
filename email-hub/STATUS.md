# Live cutover status

Updated: 2026-07-10 08:05 UTC

## Critical discovery

**`cindemir.av.tr` is NOT on Google Workspace.**

Google message:

> … login page for a domain that isn't using Google Workspace.

Current mail = **Doruk** only. Hub cannot go live until Workspace is created for this domain.

## Done

- [x] Architecture + migration pack in `email-hub/`
- [x] DNS sheet + [APPLY-DNS-ON-YOUR-PC.md](./APPLY-DNS-ON-YOUR-PC.md) (Doruk blocked from cloud VM)
- [x] Confirmed domain not on Workspace
- [x] Opened Workspace signup / pricing flow on Desktop

## Blocked / needs you (Desktop)

1. **Create Google Workspace** for `cindemir.av.tr`  
   - Desktop is on Workspace pricing/signup  
   - Choose **Business Starter** (or higher) → start trial  
   - Use domain **`cindemir.av.tr`**  
   - Admin user: **`gokhan@cindemir.av.tr`**  
   - Do **not** only “upgrade” consumer Gmail without attaching the domain

2. After Admin opens: create alias `cindemir@` → `gokhan@`

3. On **your PC**, apply Doruk DNS from APPLY-DNS-ON-YOUR-PC.md

4. Then: forward Gmail / iCloud / Yahoo → hub; configure Outlook/phones

## Do not do yet

- Do not switch MX before Workspace users exist  
- Do not enable personal “Send as” for yahoo/me/gmail on the hub
