# Live cutover status

Updated: 2026-07-10 (continue pass)

## Done in repo

- [x] Architecture: Workspace hub + forwarding (no POP)
- [x] DNS record sheet + **APPLY-DNS-ON-YOUR-PC.md** (Doruk unreachable from VM)
- [x] Migration checklist, client setup, forwarding guides

## Live progress this session

| Step | Status |
|------|--------|
| Consumer Gmail settings open | `gokhancindemir44@gmail.com` → Forwarding/POP/IMAP page reachable |
| Disable POP / enable forward | **Not finished** — settings UI flaky with DevTools; needs clean click + confirm code to `gokhan@cindemir.av.tr` |
| Google Admin | **Blocked** — Admin requires **Workspace admin**, not consumer Gmail. Password prompt for `gokhancindemir44@gmail.com` failed (wrong password / not admin). Need login as `gokhan@cindemir.av.tr` (or create Workspace if none). |
| Doruk DNS | **Blocked from VM** — all Doruk panels connection reset. Use [APPLY-DNS-ON-YOUR-PC.md](./APPLY-DNS-ON-YOUR-PC.md) |
| MX still Doruk | Confirmed — do not forward-only plan as final until Google MX live |

## What you must do on Desktop now

1. **Workspace admin login**  
   - Open Desktop view  
   - Sign in to https://admin.google.com as **`gokhan@cindemir.av.tr`** (not @gmail.com)  
   - If that account does not exist → start https://workspace.google.com signup for `cindemir.av.tr`

2. **Doruk DNS on your PC**  
   - Apply records from APPLY-DNS-ON-YOUR-PC.md **after** Workspace users exist

3. Tell the agent when Admin is open / MX switched — forwarding + Outlook steps resume immediately
