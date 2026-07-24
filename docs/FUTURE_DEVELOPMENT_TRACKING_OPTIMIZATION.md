# Tracking System Optimization

**STATUS: NOT IMPLEMENTED** — All items below are future work.

## Overview

The tracking system generates significant and highly variable network traffic on the production server (~150 MB received, hundreds MB sent daily). The primary culprit is `getFlarmTask.php`, which runs every minute and polls multiple external APIs. Secondary sources include backups and Spot polling.

## A1: getFlarmTask Rewrite

Runs every minute (1,440 runs/day). Current flow:

1. **Self-calls (`flyingnow/1`)** — HTTTPS curl to `gops.wwgc.co.nz` to get today's glider list
2. **Per glider self-calls (`flarmcode/ZK-xx`)** — another HTTPS curl to itself for ICAO hex codes
3. **OGN worldwide feed (`live.glidernet.org/lxml.php?a=0&z=0`)** — downloads ALL beacon data globally
4. **Per glider GNZ pings (`gliding.net.nz/api/v1/tracking/{date}/{ICAO}/pings`)** — fetches ALL day's pings for each glider
5. **Raw INSERT into `gliding.tracks`** — no dedup

### Fixes

| # | Fix | Impact |
|---|-----|--------|
| 1 | Replace self-calls with direct DB query (join `flights` + `aircraft` for gliders + ICAO codes) | Saves 1 + N HTTP requests/min |
| 2 | After fetching GNZ data, track latest `point_time` per glider; next run only fetch newer data | **Largest win** — currently re-downloads every glider's full day every run |
| 3 | Only fetch GNZ data for currently-flying gliders (skip completed ones until end of day) | Reduces per-run volume by ~50% on busy days |
| 4 | Use OGN filtered endpoint or skip OGN entirely — only 3.53% of data comes from OGN | Saves ~500KB/min of worldwide XML |
| 5 | Add `INSERT IGNORE` or UNIQUE `(glider, point_time, point_time_milli)` constraint | Prevents DB bloat |
| 6 | Early exit: if no in-flight flights, skip entirely | Saves entire run on quiet days |

Source distribution (2 years): FlarmGNZ 96.44%, FlarmOGN 3.53%, SPOT 0.03%, Particle 0%, bTraced 0%.

## A2: Tracking Data Cleanup

- Deduplicate existing `gliding.tracks` (2.7M rows, no dedup constraint — likely duplicates from re-fetching the same GNZ pings every minute)
- Verify `ArchiveTracks.php` runs automatically (currently documented as "manual")
- Drop dead Particle/bTraced ingestion endpoints if confirmed unused

## A3: GetSpotTask Review

Runs every 2 minutes 8pm-7am (~600 requests/day). Only 439 SPOT records in 2 years (0.03% of tracking data). Consider reducing frequency (every 10 min instead of 2) or disabling entirely.

## A4: Backup Traffic

Daily `rclone sync` at 12:30 uploads ~120 MB compressed DB backups to Google Shared Drive. Fixed cost, not variable. No action needed.

## Files to Modify

| File | Change |
|------|--------|
| `getFlarmTask.php` | Rewrite: replace self-calls with DB query, incremental GNZ fetch, dedup, early exit |
| `includes/GlidingGNZClass.php` | Add `getFlarmDataSince()` method (if GNZ API supports `?since=` param) |
| `includes/classGlidingDB.php` | Add dedup to `createTrack()`, add `getLatestTrackTime()` method |
| `includes/ognClass.php` | Optionally: add ICAO-filtered fetch |
| `docs/TRACKING.md` | Update after changes |
| `docs/CRONS.md` | Update schedule if frequency changes |