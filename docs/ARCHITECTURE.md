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
3. todayxml.php queries tracks for glider positions
4. MasterDisplay.js renders on Google Maps

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
- `FlyingNow.php` - Simple status page
- `MasterDisplay.php` - Full map with tracks
- `todayxml.php` - JSON feed for map

### Reports
- `Treasurer.php` - Monthly billing report (fees broken)
- `Engineer.php` - Aircraft usage report
- `last-flights-list.php` - Currency/instructor recency

---

## Organization Customization (orgs/)

Each club (org) has its own customization folder in `/orgs/{id}/`:

### Per-Org Files

| File | Purpose |
|------|---------|
| heading1-6.txt | HTML header templates |
| heading1-6.css | Header-specific styles |
| menu1.txt | Navigation menu HTML |
| menu1.css | Menu styles |
| accountrules.php | **Billing calculation logic** |
| orgHelpers.php | Org-specific helper functions |

### Why This Matters

- **accountrules.php** - Each org defines how to calculate tow/glider fees. This is why billing is broken - CalcTowCharge() vs CalcTowCharge2() differ!
- **orgHelpers.php** - May contain `trialClass()` returning different values per org
- **Route mapping** - /wgc, /ssb, /cgc, /agc map to orgs 1-4 via .htaccess

## Data Flow

### Flight Entry
1. User visits `StartDay.php` → selects location
2. Redirects to `DailySheet.php?org=X&location=Y`
3. Form loads existing flights for date, pilots, aircraft
4. On submit → AJAX to save flights to `flights` table
5. Times stored as BIGINT (Unix timestamp in milliseconds)

### Live Tracking
1. Flarm devices send GPS to external system
2. Data stored in `tracks` table
3. `todayxml.php` queries tracks for current flights
4. `MasterDisplay.js` renders on Google Maps

### Billing Flow
1. `Treasurer.php` queries flights for month
2. Calls `CalcTowCharge()`, `CalcGliderCharge()`, `CalcOtherCharges()` from `/orgs/{id}/accountrules.php`
3. Many bugs in the calculation logic

## Tracking Architecture

### Tracking Sources (3 different systems feed into gliding.tracks)

1. **Particle Devices** - Primary tracking
   - Hardware: Particle-based GPS trackers in gliders
   - API: `tracks/apiParticlejsonv1.php` - receives UDP/TCP data
   - Stores in: `particletrack` database
   - Forwards to: gliding ops (apiglidjsonv1.php?createtrack)
   - Also sends to: gliding.net.nz (GNZ tracking)

2. **Spot Devices** - Legacy, rarely used
   - Cron: `GetSpotTask.php -o <org>`
   - Polls Spot API (findmespot.com) for gliders flying today
   - Source label: 'SPOT'

3. **Flarm/OGN** - Secondary tracking
   - Cron: `getFlarmTask.php`
   - Queries OGN (Open Glider Network) for current positions
   - Queries Gliding NZ API for historical data
   - Source labels: 'FlarmOGN', 'FlarmGNZ'

### Cron Jobs (Production - Verified)

| Schedule | Command | Purpose |
|----------|---------|---------|
| `*/2 20-23 * * *` | `php GetSpotTask.php -o 1` | Poll Spot API (evening) |
| `*/2 00-07 * * *` | `php GetSpotTask.php -o 1` | Poll Spot API (overnight) |
| `* * * * *` | `php getFlarmTask.php` | Fetch OGN/GNZ data (every minute!) |
| `0 6 * * *` | `php DayTimes.php` | Send daily operations summary email |
| `0 12 1 * *` | `gops-reporting/main.php` | Monthly billing report |

**Notes:**
- GetSpotTask runs every 2 minutes, only during 8pm-11pm and midnight-7am
- getFlarmTask runs EVERY MINUTE - could be optimized (excessive!)
- DayTimes.php analyzes GPS tracks to reconstruct flight times, emails to [ops-email]
- gops-reporting is a separate PHP reporting application (not in main repo)

### Backup Cron Jobs (Daily at noon)

| Schedule | Command | Purpose |
|----------|---------|---------|
| `0 12 * * *` | `mysqldump gliding` | Backup gliding DB to /media/mysqldump/ |
| `0 12 * * *` | `mysqldump tracks` | Backup tracks DB to /media/mysqldump/ |
| `0 12 * * *` | `mysqldump particletrack` | Backup particletrack DB to /media/mysqldump/ |
| `0 12 * * *` | `find /media/mysqldump -mtime +30 -delete` | Delete backups older than 30 days |

All three databases backed up daily at noon, stored in /media/mysqldump/ with date stamps. Old backups automatically cleaned up after 30 days.

### Tracking Display

- `todayxml.php` - JSON feed for live map
- `MasterDisplay.php` - Full screen map with Google Maps
- `FlyingNow.php` - Simple current status page

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