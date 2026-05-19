# Deployment

## SSH Access

Hostname, IP, user, and password in `docs/_secrets.md` (Production SSH section).

From **Linux/Mac/WSL**: `ssh root@<host-ip>`

From **Windows (PuTTY)**: `plink -ssh -batch -pw '<password>' root@<host-ip> "<command>"`

## Gitignored Files

These files are not in git and must be created manually on each deployment.

| File | Notes |
|------|-------|
| `config/database.php` | DB credentials — copy from existing or create from `config/database.php.sample` |
| `config/google-calendar.php` | Calendar ID + service account key path — create from `config/google-calendar.php.sample` |
| `lrv/.env` | Laravel env — copy from existing |
| `lrv/storage/google-calendar-key.json` | Google service account key JSON |
| `log/app.log` | Auto-created by PHP on first request |
| `log/error.log` | Auto-created by PHP on first request |

## Steps

1. `git pull`
2. For each missing file in the table above, create or copy from existing
3. `cd lrv && composer install --no-dev`
4. `cd lrv && php artisan migrate`

## Schema Changes

All schema changes use Laravel migrations (see `docs/DEVELOP.md`). After pulling new code:

```bash
cd /home/vagrant/code/lrv
php artisan migrate
```

This creates the `magic_link_tokens` table (for passwordless login) and any other new tables.

## Magic Link Login Deployment

The magic link flow involves these changes out of git:

| Change | Notes |
|--------|-------|
| `api/magic-link-request.php` | New file |
| `api/magic-link-verify.php` | New file |
| `login.php` | Modified (tabbed UI) |
| `.htaccess` | Routes added for `^api/magic-link-request` and `^api/magic-link-verify` |
| `Forgotten.php` | **Deleted** (replaced by magic link) |
| `Register.php` | **Deleted** (absorbed into Email or Register tab) |
| `magic_link_tokens` table | Created by `php artisan migrate` |

## Member Photos Migration

Photos were migrated from `img/members/{displayname}.jpg` to `img/members/{member_id}.jpg`:

1. Migration script (`maintenance/migrate-photos.php`) ran on production
2. Google Drive cron sync removed (was overwriting photos hourly)
3. `img/noprofile.png` used as fallback when no photo exists
4. GD extension installed on production for photo resize (max 400px, JPEG q80)
