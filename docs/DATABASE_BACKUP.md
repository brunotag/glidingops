# Database Backup to Google Drive

## Current Setup

Backups run daily at noon via cron. All three databases are dumped, gzipped, and stored locally at `/media/mysqldump/` with 30-day retention. A Shared Drive in Google Drive mirrors the local folder via rclone sync.

```
0 12 * * * mysqldump -uroot -p[see _secrets.md] gliding | gzip > /media/mysqldump/gliding-$(date +\%Y\%m\%d).sql.gz
0 12 * * * mysqldump -uroot -p[see _secrets.md] tracks | gzip > /media/mysqldump/tracks-$(date +\%Y\%m\%d).sql.gz
0 12 * * * mysqldump -uroot -p[see _secrets.md] particletrack | gzip > /media/mysqldump/particletrack-$(date +\%Y\%m\%d).sql.gz
30 12 * * * rclone sync /media/mysqldump/ gdrive:
0 12 * * * find /media/mysqldump -type f -mtime +30 -delete
```

### Why

Backups on the same machine are useless if the server dies or the disk fails. The Shared Drive provides an off-site copy.

## Storage Stats

| Metric | Value |
|--------|-------|
| Backup dir | `/media/mysqldump/` |
| Total stored | ~3.5 GB (30 days) |
| Per day compressed | ~120 MB (all 3 DBs) |
| Free disk | 17 GB (of 29 GB total) |

## How It Works

- **12:00** — all 3 dumps run, compressed files written to `/media/mysqldump/`
- **12:30** — rclone syncs the local folder to the Shared Drive (new files uploaded, deleted files pruned)
- **13:00** — `find -mtime +30 -delete` removes files older than 30 days locally. Next sync mirrors the deletion on Drive

Because `rclone sync` mirrors the source exactly, Drive always has the same 30-day window as local storage. If you want indefinite archives, change to `rclone copy`.

## Implementation Details

### Service Account

Created exclusively for backups. Separate project from the member photos service account.

| Field | Value |
|-------|-------|
| **Project** | `[see _secrets.md]` |
| **Service account** | `[see _secrets.md]` |
| **Key file (server)** | `[see _secrets.md]` |

### Shared Drive

A Google Shared Drive was required because service accounts can't write to "My Drive" folders (quota restriction). The drive is owned by an internal IT account (see `_secrets.md`).

| Field | Value |
|-------|-------|
| **Owner** | `[see _secrets.md]` |
| **Name** | `glidingops-backups` |
| **Drive ID** | `[see _secrets.md]` |
| **Service account role** | Content Manager |

### Tools

**rclone** v1.74.1 installed on the production server. Configured as:

```
rclone config create gdrive drive \
  service_account_file=[see _secrets.md] \
  team_drive=[see _secrets.md]
```

Note `team_drive` (not `root_folder_id`) — this is required for Shared Drives. The sync target is the root of the Shared Drive (`gdrive:`) since there's only one purpose for this drive.

## Restore Procedure

To restore a backup file:

```bash
# List available backups
rclone ls gdrive:

# Download a specific backup
rclone copy gdrive:gliding-20260501.sql.gz /tmp/

# Restore to MySQL
zcat /tmp/gliding-20260501.sql.gz | mysql -uadmin -p gliding
```

## Initial Setup (2026-05-15)

1. Created Google Cloud project `[see _secrets.md]`
2. Enabled Drive API
3. Created service account `[see _secrets.md]` with JSON key
4. Created Shared Drive `glidingops-backups`, added service account as Content Manager (owner: `[see _secrets.md]`)
5. Installed rclone on the server
6. Configured rclone with `team_drive` option
7. Initial sync: 96 files, 3.5 GB, completed in ~3 minutes at ~25 MB/s
8. Added `rclone sync` line to root's crontab
