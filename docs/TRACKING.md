# Tracking System

## Overview

GPS tracking data flows from multiple sources into three databases, feeding the live map display and flight replay features.

## Data Flow

```
PARTICLE DEVICE (hardware in glider)
    |
    v
tracks/apiParticlejsonv1.php  (also root copy: apiParticlejsonv1.php)
    |
    ├──> particletrack.track  (primary ingest - raw Particle data)
    |
    ├──> FORWARDS to [production-hostname]/api/v1/json/*/createtrack
    |       |
    |       v  apiglidjsonv1.php
    |       └──> gliding.tracks  (source='Particle')
    |
    └──> FORWARDS to gliding.net.nz (GNZ national tracking)

FLARM/OGN  (getFlarmTask.php - cron, every minute)
    |
    ├──> OGN (Open Glider Network) live beacon data
    ├──> Gliding NZ API (historical)
    └──> gliding.tracks  (source='FlarmOGN' | 'FlarmGNZ')

SPOT  (GetSpotTask.php - cron, every 2 mins, 8pm-7am)
    |
    └──> gliding.tracks  (source='SPOT')

bTraced mobile app  (btraced.php)
    |
    └──> gliding.tracks  (source='bTraced')
```

**Read path (live map):**
```
gliding.tracks  -->  api/daily-flights.php?tracks=1  -->  map-shared.js (modern Leaflet map)
```

**Read fallback (flight replay):**
```
gliding.tracks  -->  if empty  -->  tracks.tracksarchive
```
Used by: MyFlightMap.php, igcgenerate.php, googlemapsgenerate.php, apiglidjsonv1.php

**Archival:**
```
gliding.tracks (records older than 3 days)
    |
    ├──> COPY to tracks.tracksarchive  (ArchiveTracks.php)
    └──> DELETE from gliding.tracks
```

---

## Three Databases

### `gliding.tracks` (376 MB, 2.7M rows)
The live tracking table in the main gliding database. All sources write here.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| org | INT | Organisation ID |
| user | INT | User ID |
| create_time | DATETIME | When record was created |
| trip_id | VARCHAR | Trip identifier |
| glider | VARCHAR | Glider registration (rego_short) |
| point_id | INT | Point sequence |
| point_time | BIGINT | Unix timestamp (ms) |
| point_time_milli | INT | Millisecond component |
| lattitude | DOUBLE | GPS latitude |
| longitude | DOUBLE | GPS longitude |
| altitude | DOUBLE | GPS altitude (m) |
| accuracy | DOUBLE | GPS accuracy |
| tracks_source | VARCHAR(16) | Source identifier |

**Source values:** `Particle`, `FlarmOGN`, `FlarmGNZ`, `SPOT`, `bTraced`

#### Source Distribution (Last 2 Years — 1,481,211 records)

| Source | Records | % | Status |
|--------|---------|---|--------|
| `FlarmGNZ` | 1,428,458 | 96.44% | Active — polled every minute by `getFlarmTask.php` |
| `FlarmOGN` | 52,314 | 3.53% | Active — OGN live beacon data |
| `SPOT` | 439 | 0.03% | Rarely triggers (evening/overnight only) |
| `Particle` | 0 | 0% | **Dead** — no data in 2 years |
| `bTraced` | 0 | 0% | **Dead** — no data in 2 years |
| `NZSPOT` | 0 | 0% | **Dead** — no data in 2 years |

Over 96% of tracking data comes from FlarmGNZ (Gliding NZ historical API). The onboard Particle trackers and bTraced mobile app have not transmitted any data in over 2 years — the cron-based polling is the effective tracking system.

### `particletrack.*` (253 MB, 8 tables)
Primary ingestion point for Particle hardware. Data is received, stored, validated (geofence, speed), then forwarded.

| Table | Size | Rows | Purpose |
|-------|------|------|---------|
| track | 249 MB | 2.4M | Main GPS point storage |
| velocity | 2 MB | 15.7K | Velocity/ground speed |
| position | 1.8 MB | 8.4K | Raw OGN/Flarm positions |
| trip | 0.3 MB | 2.5K | Trip grouping |
| info | 0.2 MB | 1.7K | Flight identification |
| aircraft | 0.05 MB | 155 | Aircraft registry |
| vehicle | 0.03 MB | 11 | Particle device registry |
| aircraft_type | 0.02 MB | 34 | Aircraft type codes |

### `tracks.tracksarchive` (304 MB, 2.5M rows)
Archived historical tracking data. Records are moved here after 3 days.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| trip_id | VARCHAR | Trip identifier |
| glider | VARCHAR | Glider registration |
| point_id | INT | Point sequence |
| point_time | BIGINT | Unix timestamp (ms) |
| point_time_milli | INT | Millisecond component |
| lattitude | DOUBLE | GPS latitude |
| longitude | DOUBLE | GPS longitude |
| altitude | DOUBLE | GPS altitude (m) |
| accuracy | DOUBLE | GPS accuracy |
| speed | DOUBLE | Ground speed |
| source | VARCHAR | Source identifier |

---

## Tracking Sources

### Particle Devices (Primary)
- Hardware: Particle-based GPS trackers installed in gliders
- Protocol: UDP + HTTP data sent to `tracks/apiParticlejsonv1.php`
- Validation: NZ geofence, speed < 69 m/s, altitude rate < 50 m/s, deduplication
- Also forwards to: gliding.net.nz (GNZ national tracking)

### Flarm/OGN (Secondary)
- **getFlarmTask.php** (cron, every minute)
- Queries Open Glider Network (OGN) for live Flarm beacon data
- Queries Gliding NZ API for additional positions
- Source labels: `FlarmOGN` (OGN), `FlarmGNZ` (GNZ)

### SPOT (Legacy)
- **GetSpotTask.php** (cron, every 2 minutes, 8pm-11pm and midnight-7am)
- Polls `findmespot.com` API for today's flights
- Source label: `SPOT`

### bTraced (Mobile App)
- **btraced.php** - receives data from the bTraced mobile app
- Source label: `bTraced`

---

## Cron Jobs

| Schedule | Command | Purpose |
|----------|---------|---------|
| `* * * * *` | `getFlarmTask.php` | Fetch OGN/GNZ data every minute |
| `*/2 20-23 * * *` | `GetSpotTask.php -o 1` | Poll Spot API (evening) |
| `*/2 00-07 * * *` | `GetSpotTask.php -o 1` | Poll Spot API (overnight) |
| `0 6 * * *` | `DayTimes.php` | Daily ops summary email from tracks |
| (manual) | `ArchiveTracks.php` | Archive tracks older than 3 days |

**Backups (all at noon daily, kept 30 days):**
- `mysqldump gliding` - Main DB
- `mysqldump tracks` - Tracks archive DB
- `mysqldump particletrack` - Particle ingestion DB

---

## Map Display

### MasterDisplay (Current - `/wgc`, split desktop/mobile)
- **Technology:** Leaflet.js + OpenTopoMap tiles (free, no API key)
- **Files:**
  - `map/MasterDisplayDesktop.php` — desktop layout (sidebar + map)
  - `map/MasterDisplayMobile.php` — mobile layout (full map + overlay + drag divider)
  - `map/MasterDisplayRouter.php` — device detection (UA check)
  - `map/map-shared.js` — shared logic (no twin elements, mode-guarded)
  - `map/map-shared.css` — shared styles, scoped to `.desktop-mode` / `.mobile-mode`
- **Features:**
  - Separate HTML files per mode — no twin element syncing, no `refreshOverlay()`
  - 24-color unique palette per flight
  - Altitude gradient coloring (single flight mode)
  - Multi-select flight toggle, Flying Only mode
  - Brightness slider (visible when 1 flight selected)
  - Contrast slider on desktop (opacity 0–0.8)
  - Compact flying section (`flex: 0 0 auto`), completed fills remaining
  - Dark `#080a14` section headers with `#e94560` accent border
  - Glider markers: 34px circle with 2-letter rego, auto-contrasting text
  - Auto-refresh preserves flight selections; timer in mm:ss/h:mm:ss
- **Data feed:** `api/daily-flights.php?tracks=1` (JSON, public with `org` param)
- **Routes:** `/wgc` (router)

### Map Configuration

Org-specific map settings in `organisations` table:

| Column | Purpose |
|--------|---------|
| map_centre_lat | Default map centre latitude |
| map_centre_lon | Default map centre longitude |
| def_launch_lat | Launch point latitude |
| def_launch_lon | Launch point longitude |

### Future Development
See `MAP.md` for current map architecture and feature reference.

---

## Live Data Feed

### api/daily-flights.php
- **URL:** `api/daily-flights.php?org=1&tracks=1&date=YYYY-MM-DD` (public with `org` param)
- **Format:** JSON
- **Returns:** Current flights and GPS track positions
- **Consumed by:** map-shared.js (modern Leaflet map), DailyLogSheet, self-launch-entry

### apiglidjsonv1.php
- **Routes:** `/api/v1/json/*/tracks`, `/api/v1/json/*/trackheights`, `/api/v1/json/*/flightdata`, `/api/v1/json/*/createtrack`
- Internal API for tracking data access and ingestion
- Receives forwarded data from Particle ingestion pipeline
- Provides flight data for 3D flight viewer (GlidingFlightMap.php)

---

## Flight Replay & Export

### MyFlightMap.php
- Individual flight track displayed on Google Maps
- Falls back from `gliding.tracks` to `tracks.tracksarchive`
- Displays polyline with altitude/speed stats
- Linked from MyFlights.php flight list

### GlidingFlightMap.php
- 3D flight viewer using Google Charts
- Calls `apiglidjsonv1.php` flightdata API
- Requires the `g_tracks` JS variable

### igcgenerate.php
- **Route:** `/OlcFile.igc`
- Generates IGC flight log format for OLC scoring
- Reads both `gliding.tracks` and `tracks.tracksarchive`

### googlemapsgenerate.php
- Generates WKT LINESTRING CSV for Google Maps import
- Reads both `gliding.tracks` and `tracks.tracksarchive`

---

## Device Configuration

### spots table
Maps glider registrations to tracking device identifiers.

| Column | Purpose |
|--------|---------|
| rego_short | Glider registration code |
| spotkey | Spot/Flarm device identifier |
| polltimelast | Last poll timestamp |
| polltimeall | Poll all flag |

---

## DayTimes.php (Daily Ops)

- Runs at 6am daily (cron)
- Reads `gliding.tracks` via `allTracksForOrgToday()`
- Reconstructs flight times using altitude thresholds (>150ft = start, <150ft = land)
- Emails summary to club ops manager (address in _secrets.md)

---

## Key Files Reference

| File | Role |
|------|------|
| `tracks/apiParticlejsonv1.php` | Receives Particle UDP/HTTP data |
| `apiParticlejsonv1.php` | Duplicate of above (root level) |
| `apiglidjsonv1.php` | Internal API: read/write gliding.tracks |
| `api/daily-flights.php` | Flight + tracking JSON feed (map data) |
| `getFlarmTask.php` | Cron: fetch Flarm/OGN data |
| `GetSpotTask.php` | Cron: fetch SPOT data |
| `btraced.php` | bTraced mobile app ingestion |
| `ArchiveTracks.php` | Move old tracks to archive |
| `DayTimes.php` | Daily ops email from tracks |
| `MyFlightMap.php` | Single flight replay |
| `igcgenerate.php` | IGC export |
| `googlemapsgenerate.php` | CSV export |
| `includes/classGlidingDB.php` | DB class for gliding.tracks |
| `includes/classTracksDB.php` | DB class for tracks.tracksarchive |
| `tracks/includes/classtrackDB.php` | DB class for particletrack |
| `includes/ognClass.php` | OGN API client |
| `includes/GlidingGNZClass.php` | GNZ API client |
| `spots.php` / `spots-list.php` | Device configuration admin |
