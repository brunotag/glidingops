# Upgrade Plan: Ubuntu 18.04 → 24.04 (Fresh Server)

## Strategy

Build a new server in parallel. The old production server stays live until the new one is fully verified. Rollback = keep using the old server.

Current: Apache 2.4.29 + PHP 7.4.33 + MySQL 5.7.40 + OpenSSL 1.1.1 (Ubuntu 18.04)
Target:  Apache 2.4.62+ + PHP 8.3 + MySQL 8.0 + OpenSSL 3.x (Ubuntu 24.04)

The existing `tools/rebuild_production/setup.sh` provisions the target. This document covers what's missing: code fixes, config changes, and migration gotchas.

---

## 1. PHP 7.4 → 8.3

### Verdict: Very Low Risk

The codebase is remarkably clean. **No removed functions** (`each()`, `create_function()`, `mysql_*`, `strftime()`, `utf8_encode()`, etc.) were found anywhere in 340 analyzed files.

### Required Code Changes (6 lines in 2 files)

PHP 8.2 deprecated `${var}` string interpolation syntax inside double-quoted strings. Replace with `{$var}`:

**`dailysheet.php`** (lines 203–204):
```php
// Before
$flights .= "<glider>${row['glider']}</glider>";
$flights .= "<vector>${row['vector']}</vector>";
// After
$flights .= "<glider>{$row['glider']}</glider>";
$flights .= "<vector>{$row['vector']}</vector>";
```

**`updflights.php`** (lines 265, 323):
```php
// Before
$q3 .= ",glider='${glid}',vector='${vector}',towpilot=";
$q3 .= ",'${glid}', '${vector}',";
// After
$q3 .= ",glider='{$glid}',vector='{$vector}',towpilot=";
$q3 .= ",'{$glid}', '{$vector}',";
```

### Composer / Laravel 8.0

`lrv/composer.json` requires `"php": ">=7.4"` and `"laravel/framework": "^8.0"`. Laravel 8.x supports PHP 8.3 (the latest Laravel 8.x release does). However:

| Package | Version | PHP 8.3 Compat |
|---------|---------|----------------|
| `laravel/framework` | ^8.0 | Yes (8.83.27+) |
| `phpmailer/phpmailer` | ^7.1 | Yes |
| `noweh/twitter-api-v2-php` | ^3.1 | Check during `composer install` |
| `google/apiclient` | ^1.1 | Likely — may need v2 |
| `laravelcollective/html` | ^6.0 | Yes |
| `phpunit/phpunit` | ^9.0 | Yes |

**Action:** Run `composer update --no-dev` on the new server and resolve any version constraint issues. May need to bump some `^` constraints.

### PHP Extensions (Current vs Target)

All currently loaded extensions are available on Ubuntu 24.04 as `php8.3-*` packages. The setup script already installs the common ones. Add these if missing:
- `php8.3-imagick` (already in setup.sh)
- `php8.3-xsl` (currently loaded, not in setup.sh)
- `php8.3-sockets` (currently loaded, not in setup.sh)
- `php8.3-ftp` (currently loaded, not in setup.sh)

Update setup.sh line 38–40:
```bash
php8.3 php8.3-cli php8.3-curl php8.3-xml php8.3-mysql \
php8.3-mbstring php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl \
php8.3-imagick php8.3-xsl php8.3-sockets php8.3-ftp
```

---

## 2. MySQL 5.7 → 8.0

### Verdict: Moderate Risk — Needs Prep

Three concrete issues must be addressed before restore:

### 2a. Zero-Date Defaults (`0000-00-00 00:00:00`)

MySQL 8.0 rejects `'0000-00-00 00:00:00'` by default (sql_mode includes `NO_ZERO_DATE`). The production DB has 12+ columns with this default, including `bookings.lastmodify`, `charges.validfrom`, `texts.txt_timestamp_sent`, and `spots.lastreq`.

**Fix at restore time:** Temporarily disable `NO_ZERO_DATE` during the DB restore:

```bash
mysql -u root -p <<EOF
SET GLOBAL sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
EOF

gunzip < gliding-YYYYMMDD.sql.gz | mysql -u root -p gliding
gunzip < tracks-YYYYMMDD.sql.gz | mysql -u root -p tracks
gunzip < particletrack-YYYYMMDD.sql.gz | mysql -u root -p particletrack
```

Or permanently configure `sql_mode` in `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```
[mysqld]
sql_mode = "ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION"
```

### 2b. `VALUES()` Function Deprecated in `ON DUPLICATE KEY UPDATE`

Two files use MySQL 8.0.20-deprecated syntax:

**`oauth-callback.php:266`** and **`oauth-link-action.php:66`**:
```php
// Before
ON DUPLICATE KEY UPDATE last_login = VALUES(last_login)
// After (MySQL 8.0.19+ allows alias reference)
ON DUPLICATE KEY UPDATE last_login = last_login
```

### 2c. Stricter `GROUP BY` (`ONLY_FULL_GROUP_BY`)

Two queries select non-aggregated columns not in `GROUP BY`. Both should be safe due to functional dependency (they GROUP BY a PRIMARY KEY that the extra column is JOINed from), but verify on the new server:

- `api/pic-p2-analytics.php:72` — `GROUP BY f.pic` with `m.displayname` in SELECT
- `last-flights-list.php:114` — `GROUP BY m.id` with `m.displayname` in SELECT

**Fix if needed:** Add the extra column to the GROUP BY, or use `ANY_VALUE()`.

### 2d. Auth Plugin

MySQL 8.0 defaults to `caching_sha2_password`. The setup.sh already uses `mysql_native_password` for application users, which is correct. No change needed.

### 2e. Charset Mismatch

The application connects with `utf8` but the tables are `latin1`. MySQL 8.0 treats `utf8` as `utf8mb3` (deprecated). Not a blocker — will work as-is. Consider migrating to `utf8mb4` as a future task.

---

## 3. Apache 2.4.29 → 2.4.62+

### Verdict: No Risk

Apache 2.4 is a stable series. The same MPM is used (prefork) with `mod_php`. All currently enabled modules (`mod_rewrite`, `mod_headers`, `mod_ssl`, `mod_deflate`, etc.) exist in Ubuntu 24.04's Apache 2.4.62+.

**No .htaccess changes needed.** `RewriteRule`, `ErrorDocument`, and `AllowOverride All` behave identically.

### Slight Improvement Opportunity

Consider switching from `mpm_prefork` to `mpm_event` with `php-fpm` (PHP 8.3 supports this well). This is optional — prefork works fine for the current traffic levels.

---

## 4. Configuration Files to Migrate

These are gitignored and must be copied from the old server or recreated from templates:

| File | Action |
|------|--------|
| `config/database.php` | Written by setup.sh (passwords in `_secrets.md`) |
| `lrv/.env` | Written by setup.sh |
| `config/mail.php` | Copy from old server (SMTP creds) |
| `config/oauth.php` | Copy from old server (OAuth client IDs/secrets) |
| `config/site.php` | Copy from old server (MAP_API_KEY, calendar IDs) |
| `config/google-calendar.php` | Copy from old server |
| `lrv/storage/google-calendar-key.json` | Copy from old server (PEM private key) |
| `/var/local/gops-reporting/config.json` | Recreate from template |
| `/var/local/gops-reporting/google_sheet_cred.json` | Copy from old server |
| `img/members/` | Copy from old server (member photos) |

---

## 5. Migration Steps Checklist

### Phase 1: Code Changes (Do Now, Before New Server)

- [ ] Fix `${var}` → `{$var}` in `dailysheet.php` (2 lines)
- [ ] Fix `${var}` → `{$var}` in `updflights.php` (2 lines)
- [ ] Fix `VALUES(last_login)` → `last_login` in `oauth-callback.php` and `oauth-link-action.php`
- [ ] Update `tools/rebuild_production/setup.sh` — add missing PHP extensions (xsl, sockets, ftp)
- [ ] Commit and push

### Phase 2: Provision New Server

- [ ] Provision Ubuntu 24.04 VPS
- [ ] Copy `tools/rebuild_production/setup.sh` to new server
- [ ] Fill in `{{PLACEHOLDERS}}` from `docs/_secrets.md`
- [ ] Run `setup.sh`
- [ ] Copy config files (mail.php, oauth.php, site.php, google-calendar.php, google-calendar-key.json)
- [ ] Copy `img/members/` directory
- [ ] Set sql_mode to allow zero dates
- [ ] Restore databases from latest backup
- [ ] Run `composer update --no-dev` to resolve any PHP 8.3 dependency issues
- [ ] Run `php artisan migrate --force`

### Phase 3: Verify (Side-by-Side)

- [ ] Edit local `hosts` file to point `gops.wwgc.co.nz` to new server IP
- [ ] Test: HTTPS works
- [ ] Test: Login
- [ ] Test: Flight entry (DailySheet)
- [ ] Test: Tracking map (/wgc)
- [ ] Test: Messaging
- [ ] Test: Billing report
- [ ] Test: Log viewer (/Logs)
- [ ] Test: Member add/edit
- [ ] Test: Error pages (404, 403)
- [ ] Check `log/error.log` and `log/app.log` for warnings
- [ ] Run integration tests (if possible)
- [ ] Remove hosts entry

### Phase 4: Cut Over

- [ ] Update DNS A record for `gops.wwgc.co.nz` → new server IP
- [ ] Wait for propagation
- [ ] Keep old server running for 48+ hours
- [ ] Monitor both servers
- [ ] Deprovision old server after confirming everything works

---

## 6. Rollback

Keep the old server running. If issues are found after DNS switch:

1. Point DNS back to old server IP
2. Diagnose and fix on new server
3. Repeat cutover

No data loss risk — both servers share the same DB backup source (old server's production DB). The new server only has a restored backup; it never writes to the live DB until DNS switch.