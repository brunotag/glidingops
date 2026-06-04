# Architecture

## High-Level Structure

```
glidingops/
├── *.php                    # Root PHP files (~100 files)
├── lrv/                     # Laravel 5.x partial installation
├── config/                  # Database configuration
├── orgs/                    # Organization-specific configs
│   ├── 1/                  # Wellington Wairarapa
│   ├── 2/
│   ├── 3/
│   ├── 4/
│   └── 5/
├── helpers/                # Shared helper functions
├── includes/               # Core PHP classes
├── js/                     # JavaScript files
├── css/                    # CSS files
└── tracks/                  # Tracks database config
```

## Two Databases

### 1. gliding (Main)
- Members, users, flights, aircraft
- Billing, charges, tows
- Roles, groups, duties
- Messages (broadcasts)
- Audit log

### 2. tracks (GPS Tracking) - SEPARATE DATABASE
- **Three databases** store tracking data (see [TRACKING.md](TRACKING.md) for full data flow):
  - `gliding.tracks` — 376 MB, 2.7M rows (live GPS points, source='Particle'/'Flarm*'/'SPOT'/'bTraced')
  - `tracks.tracksarchive` — 304 MB, 2.5M rows (archived historical tracks)
  - `particletrack.*` (8 tables) — 253 MB, primary Particle ingestion point

**Tracking Flow:**
1. Flarm device sends data to external tracking server (particletrack)
2. Data lands in `tracks` table (org = glider registration)
3. `api/daily-flights.php?tracks=1` queries flights + tracks for glider positions
4. `map-shared.js` renders on Leaflet map

Connection configured in:
- `config/database.php` (root - expects sample file)
- `load_model.php` - Eloquent setup for Laravel components
- Tracks connection also defined in lrv/.env (DB_TRACKS_*)

## Technology Stack

### Root PHP (Original System)
- Vanilla PHP with mysqli
- Session-based auth
- MD5 password hashing (insecure by modern standards)
- Bootstrap 3.3.7 + jQuery 1.12.4 (from jsLibraies.php)
- Custom CSS (minimal, table-based layouts)

### Laravel (lrv/)
- Laravel 5.x (partial install)
- Eloquent ORM (via Capsule)
- Used for: 2 API routes (allFlightsReport, membersRolesStatsReport)
- Mostly dead code - only 2 routes actually used

## Entry Points

### Authentication
- `Login.php` - Login form
- `checklogin.php` - Auth handler (MD5 password check)
- `home.php` - Main dashboard after login
- `SignOut.php` - Logout

### Member Management
- `members-list-v2b.php` - Modern member list (DataTables)
- `members-new.php` - Modern member create/edit form
- `api/member-form.php` - API for member form (classes, statuses, roles, save)
- `api/members.php` - DataTables API for member list

### Daily Operations
- `StartDay.php` - Select location to start flying day
- `DailySheet.php` - Flight entry form (core feature, 1300 lines)
- `DailyLogSheet.php` - View day's flights
- `CompletedSheet.php` - Finalize day

### Live Tracking
- `MasterDisplayRouter.php` - Device detection router at `/wgc`
- `MasterDisplayDesktop.php` - Full screen Leaflet map (desktop)
- `MasterDisplayMobile.php` - Mobile Leaflet map
- `api/daily-flights.php` - JSON feed for flights (with optional tracks)

## Key Files

| File | Purpose |
|------|---------|
| `helpers.php` | Core helper functions |
| `helpers/timehelpers.php` | Timezone conversions |
| `helpers/session_helpers.php` | Auth checks |
| `load_model.php` | Eloquent database setup |
| `includes/classGlidingDB.php` | Database wrapper class |
| `.htaccess` | URL routing |

## Configuration

### Database
- `config/database.php` - MySQL credentials (sample in database.php.sample)
- Or via environment variables in lrv/.env

### Tracks DB Connection
- Separate connection defined in `tracks/config/database.php`
- Uses different hostname/port if needed

### Timezone
- Stored per-organization in `organisations.timezone`
- Used for all date/time display via `orgTimezone()`

### Email
- Hardcoded in `helpers/mail.php` - WWGC-specific
- Configurable per org?
- Via PHP mail() - hardcoded in helpers/mail.php
- SendTxt.php cron job converts texts to emails