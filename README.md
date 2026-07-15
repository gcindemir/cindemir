# cindemir

Workspace for Cindemir Law Office web projects.

## Latest task (2026-07-11)

Cursor görev talimatı: [`docs/cindemirlaw-cursor-gorev.md`](docs/cindemirlaw-cursor-gorev.md)

Deploy rehberi: [`fixes/INSTALL.md`](fixes/INSTALL.md)

| Görev | Durum | Dosya |
|-------|-------|-------|
| Yedekleme | Tamam (2026-07-11 mirror) | [`backups/`](backups/) |
| 14 sayfa meta description | Script hazır; **canlıda henüz uygulanmadı** | [`fixes/meta-descriptions/pages-14.json`](fixes/meta-descriptions/pages-14.json) |
| Yoast REST mu-plugin | Hazır | [`fixes/mu-plugins/cindemir-expose-yoast-meta.php`](fixes/mu-plugins/cindemir-expose-yoast-meta.php) |
| `?lang=` redirect | wp-admin ayarı gerekli | [`fixes/LANG-REDIRECT.md`](fixes/LANG-REDIRECT.md) |
| Bozuk görseller | mu-plugin v1.5.6 rewrite | [`fixes/BROKEN-IMAGES.md`](fixes/BROKEN-IMAGES.md) |
| Services sayfa tasarımı | mu-plugin v1.0.0 | [`fixes/SERVICES-PAGE.md`](fixes/SERVICES-PAGE.md) |

## Backups

```bash
./backups/create-backup.sh   # yeni wget mirror
```

See [`backups/README.md`](backups/README.md). Latest: `cindemirlaw.com-20260711-214047`.

## Audits

[`audits/cindemirlaw.com-2026-07-09/SUMMARY.md`](audits/cindemirlaw.com-2026-07-09/SUMMARY.md)

## Fixes

Mu-plugin **v1.5.6** in repo (H1, redirects, alts, title trim, broken image rewrite). Deploy: [`fixes/deploy-ftp.sh`](fixes/deploy-ftp.sh) veya [`fixes/bluehost-deploy.html`](fixes/bluehost-deploy.html).
