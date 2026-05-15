# Cron Jobs

All cron jobs run on the production server. The Vagrant dev box does not run these.

## Tracking-Related Jobs

See [TRACKING.md](TRACKING.md) for full tracking system documentation.

| Schedule | Command | Purpose |
|----------|---------|---------|
| `*/2 20-23 * * *` | `php /path/to/GetSpotTask.php -o 1` | Poll Spot API (evening) |
| `*/2 00-07 * * *` | `php /path/to/GetSpotTask.php -o 1` | Poll Spot API (overnight) |
| `* * * * *` | `php /path/to/getFlarmTask.php` | Fetch OGN/GNZ tracking data |
| `0 6 * * *` | `php /path/to/DayTimes.php` | Send daily ops summary email from tracks |

## Other Jobs

| Schedule | Command | Purpose |
|----------|---------|---------|
| `0 12 1 * *` | `php /path/to/gops-reporting/main.php` | Monthly billing report |

## Backup Jobs

See [DATABASE_BACKUP.md](DATABASE_BACKUP.md) for full backup documentation.

| Schedule | Command | Purpose |
|----------|---------|---------|
| `0 12 * * *` | `mysqldump -uroot -p... gliding \| gzip > /media/mysqldump/gliding-$(date +\%Y\%m\%d).sql.gz` | Backup gliding DB |
| `0 12 * * *` | `mysqldump -uroot -p... tracks \| gzip > /media/mysqldump/tracks-$(date +\%Y\%m\%d).sql.gz` | Backup tracks DB |
| `0 12 * * *` | `mysqldump -uroot -p... particletrack \| gzip > /media/mysqldump/particletrack-$(date +\%Y\%m\%d).sql.gz` | Backup particletrack DB |
| `30 12 * * *` | `rclone sync /media/mysqldump/ gdrive:` | Sync backups to Google Shared Drive |
| `0 12 * * *` | `find /media/mysqldump -type f -mtime +30 -delete` | Delete backups older than 30 days |

## Booking System

| Schedule | Command | Purpose |
|----------|---------|---------|
| `0 3 * * *` | `php /path/to/cleanup-bookings.php` | Hard-delete soft-deleted bookings older than 30 days |

## Notes

- All paths are relative to the web root (`/var/www/html` or equivalent)
- GetSpotTask runs every 2 minutes, only during 8pm-11pm and midnight-7am
- getFlarmTask runs every minute (may be excessive — consider reducing)
- Backups stored in `/media/mysqldump/` with date stamps
