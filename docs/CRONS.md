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

| Schedule | Command | Purpose |
|----------|---------|---------|
| `0 12 * * *` | `mysqldump gliding > /media/mysqldump/gliding_$(date +\%Y\%m\%d).sql` | Backup gliding DB |
| `0 12 * * *` | `mysqldump tracks > /media/mysqldump/tracks_$(date +\%Y\%m\%d).sql` | Backup tracks DB |
| `0 12 * * *` | `mysqldump particletrack > /media/mysqldump/particletrack_$(date +\%Y\%m\%d).sql` | Backup particletrack DB |
| `0 12 * * *` | `find /media/mysqldump -mtime +30 -delete` | Delete backups older than 30 days |

## Booking System

| Schedule | Command | Purpose |
|----------|---------|---------|
| `0 3 * * *` | `php /path/to/cleanup-bookings.php` | Hard-delete soft-deleted bookings older than 30 days |

## Notes

- All paths are relative to the web root (`/var/www/html` or equivalent)
- GetSpotTask runs every 2 minutes, only during 8pm-11pm and midnight-7am
- getFlarmTask runs every minute (may be excessive — consider reducing)
- Backups stored in `/media/mysqldump/` with date stamps
