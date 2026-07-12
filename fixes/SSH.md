# SSH — cindemirlaw.com (Bluehost)

Bluehost paneline (e-posta kodu / Google onayı) girmeden sunucuya bağlanmak için SSH kullanın.

## Hızlı bağlantı

```bash
ssh cindemir@162.241.252.122
```

Şifre: cPanel ana şifreniz (`cindemir` kullanıcısı).

## Kolay alias (önerilen)

```bash
# 1) Örnek config'i kopyalayın
cat fixes/ssh-config.example >> ~/.ssh/config
chmod 600 ~/.ssh/config

# 2) Artık kısa komut:
ssh cindemirlaw
cd ~/public_html
```

## Şifresiz giriş (SSH anahtarı)

Mac'inizde bir kez:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/cindemirlaw_deploy -C "gokhan@cindemir"
ssh-copy-id -i ~/.ssh/cindemirlaw_deploy.pub cindemir@162.241.252.122
```

Sonra `ssh cindemirlaw` şifre sormaz.

## Önemli yollar

| Yol | Açıklama |
|-----|----------|
| `~/public_html/` | WordPress kökü |
| `~/public_html/wp-content/mu-plugins/` | SEO mu-plugins |
| `~/public_html/wp-content/themes/enfold/` | Enfold teması |

## Sık komutlar

```bash
ssh cindemirlaw

# Mu-plugins listele
ls -la ~/public_html/wp-content/mu-plugins/

# Cache temizle
cd ~/public_html && wp cache flush

# Canlı kontrol (kendi bilgisayarınızdan)
curl -sI https://cindemirlaw.com/ | head -3
```

## Otomatik deploy (mu-plugins)

```bash
# Anahtar ile (önerilen)
export CINDEMIR_SSH_KEY="$HOME/.ssh/cindemirlaw_deploy"
./fixes/deploy-ssh.sh

# veya şifre ile
export CINDEMIR_SSH_PASS='your-cpanel-password'
./fixes/deploy-ssh.sh
```

Tam paket (deploy + meta + doğrulama):

```bash
./fixes/scripts/auto-deploy.sh
```

## Notlar

- **FTP kullanmayın** — Bluehost FTP upload timeout veriyor; SSH/SCP güvenilir.
- Boş (0 byte) mu-plugin dosyaları siteyi bozabilir; `deploy-ssh.sh` bunları temizler.
- WP-CLI sunucuda hazır: `/usr/local/bin/wp`
