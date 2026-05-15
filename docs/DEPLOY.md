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
