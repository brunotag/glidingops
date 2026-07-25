# Rebuild Production Server

## Overview

This directory contains everything needed to rebuild the glidingops production server from scratch on a fresh Ubuntu 24.04 LTS instance.

## Files

| File | Purpose |
|------|---------|
| `setup.sh` | Automated setup script (run as root) |
| `README.md` | This file (manual steps + checklist) |

## Prerequisites

- A fresh Ubuntu 24.04 LTS VPS (tested on Vultr, should work on any provider)
- Root SSH access
- DNS record pointing to the new server (or update after setup)
- Database backups from the current production server

## Quick Start

```bash
# 1. Provision the VPS and SSH in
ssh root@<new-server-ip>

# 2. Copy the setup script
# From your local machine:
scp tmp/rebuild_production/setup.sh root@<new-server-ip>:/root/

# 3. Edit the config values at the top of setup.sh
# Replace {{PLACEHOLDERS}} with values from docs/_secrets.md
nano /root/setup.sh

# 4. Run it
chmod +x /root/setup.sh && ./root/setup.sh
```

## What the Script Does

1. **System packages** — Apache 2.4, MySQL 8.0, PHP 8.3 with extensions, Certbot, git, curl
2. **Apache** — enables `mod_rewrite`, `mod_headers`, `AllowOverride All`, configures UFW
3. **MySQL** — secures root, creates databases (`gliding`, `tracks`, `particletrack`) and application users
4. **Application** — clones the repo, runs Composer install, writes `.env` and `config/database.php`
5. **SSL** — obtains Let's Encrypt certificate via Certbot
6. **Cron** — sets up SPOT/Flarm tracking, DayTimes emails, gops-reporting, database backups, disk alert
7. **Journald** — limits log storage to 100M

## Manual Steps (After Script Completes)

### 1. Restore Databases

Copy the latest backup files from the old server, then:

```bash
gunzip < gliding-YYYYMMDD.sql.gz | mysql -u root -p'<root-pw>' gliding
gunzip < tracks-YYYYMMDD.sql.gz | mysql -u root -p'<root-pw>' tracks
gunzip < particletrack-YYYYMMDD.sql.gz | mysql -u root -p'<root-pw>' particletrack
```

### 2. Run Laravel Migrations

```bash
cd /var/www/html/lrv && php artisan migrate --force
```

### 3. Clear Sessions

```bash
rm -f /var/lib/php/sessions/*
```

### 4. Config Files (gitignored)

These files exist on the old server but are not in the repo:

- **`config/mail.php`** — PHPMailer SMTP settings (see `config/mail.php.sample` for template)
- **`config/site.php`** — if the old server has one

### 5. Google Service Accounts

**Member photos** (GCP project: `membersphotosupdate`):
1. Go to https://console.cloud.google.com/iam-admin/serviceaccounts
2. Create/download a new key
3. Place at `/usr/local/.gdrive/<key-file>.json`
4. Add to crontab:
   ```
   0 0 * * * gdrive -c /usr/local/.gdrive --service-account <key-file> download query "'<folder-id>' in parents" --force --path /var/www/html/img/members >> /var/log/downloadGopsMembersPhotos.log 2>&1
   ```

**gops-reporting** (GCP project: `wwgcdashboard`):
1. Create/download a new key
2. Place at `/var/local/gops-reporting/google_sheet_cred.json`
3. Set up `/var/local/gops-reporting/config.json` (see `config.live` on old server)

### 6. DNS

Update the A record for `gops.wwgc.co.nz` to point to the new server's IP.

### 7. Verify

| Check | How |
|-------|-----|
| HTTPS works | Visit https://gops.wwgc.co.nz/ |
| Login works | Test with a known user |
| Flight entry | Create a test flight on DailySheet |
| Tracking map | Visit /wgc, verify Flarm/SPOT points appear |
| Error log | Check /Logs page or `tail -f /var/www/html/log/error.log` |
| Cron jobs | `crontab -l` to verify all entries |
| Database backups | Trigger manually: `mysqldump -u root -p gliding | gzip > /tmp/test.sql.gz` |
| Google Photos | Check `/var/www/html/img/members/` has photos |

### 8. Old Server

- Keep the old server running for at least 48 hours
- Monitor both for issues
- Deprovision only after confirming everything works

## What's NOT Included (vs The Old Script)

| Feature | Reason |
|---------|--------|
| WordPress | Abandoned — not running on production |
| Postfix | Replaced by PHPMailer/SMTP in `helpers/mail.php` |
| gops-reporting clone | Manual setup — key rotation means manual steps anyway |
| SFTP webcam upload | No longer used — verify before re-adding |
| Twitter API keys | Handled in the database, not in server config |

## Changes from Ubuntu 18.04 to 24.04

| Component | Old (18.04) | New (24.04) |
|-----------|-------------|-------------|
| Apache | 2.4.29 | 2.4.62+ |
| PHP | 7.4 (from PPA) | 8.3 (from Ubuntu repos) |
| MySQL | 5.7 | 8.0 |
| Certbot | python3-certbot-apache | python3-certbot-apache |
| PHP extensions | PPA-based | Built-in packages |

## Troubleshooting

### PHP Error: "Call to undefined function mysqli_connect()"
PHP 8.3 uses `php8.3-mysql` which includes the `mysqli` extension. Verify:
```bash
php -m | grep mysqli
```

### Laravel: "No application encryption key"
The `.env` has an `APP_KEY` — but if you need to regenerate:
```bash
cd /var/www/html/lrv && php artisan key:generate
```

### Apache: .htaccess not working
Verify `AllowOverride All` is set in `/etc/apache2/apache2.conf`:
```bash
grep -A5 '<Directory /var/www/>' /etc/apache2/apache2.conf
```

### MySQL: "Access denied for user 'admin'@'localhost'"
The application users use `mysql_native_password`. Verify:
```bash
mysql -u admin -p -e "SELECT 1"
```