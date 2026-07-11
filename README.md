# cindemir

Workspace for Cindemir Law Office web projects.

## Latest task (2026-07-11)

Cursor görev talimatı: [`docs/cindemirlaw-cursor-gorev.md`](docs/cindemirlaw-cursor-gorev.md)

Deploy rehberi: [`fixes/INSTALL.md`](fixes/INSTALL.md)

| Görev | Durum | Dosya |
|-------|-------|-------|
| Yedekleme | Repo'da script + önceki mirror (2026-07-09) | [`backups/`](backups/) |
| 14 sayfa meta description | Script hazır; canlıda uygulanmadı | [`fixes/meta-descriptions/pages-14.json`](fixes/meta-descriptions/pages-14.json) |
| Yoast REST mu-plugin | Hazır | [`fixes/mu-plugins/cindemir-expose-yoast-meta.php`](fixes/mu-plugins/cindemir-expose-yoast-meta.php) |
| `?lang=` redirect | wp-admin ayarı gerekli | [`fixes/LANG-REDIRECT.md`](fixes/LANG-REDIRECT.md) |
| Bozuk görseller | mu-plugin v1.5.6 rewrite | [`fixes/BROKEN-IMAGES.md`](fixes/BROKEN-IMAGES.md) |

## Backups

```bash
./backups/create-backup.sh   # yeni wget mirror
```

See [`backups/README.md`](backups/README.md).

## Audits

[`audits/cindemirlaw.com-2026-07-09/SUMMARY.md`](audits/cindemirlaw.com-2026-07-09/SUMMARY.md)

## Fixes

**Live on cindemirlaw.com:** mu-plugin v1.5.5 (bu PR: v1.5.6 + expose-yoast-meta + meta script).
