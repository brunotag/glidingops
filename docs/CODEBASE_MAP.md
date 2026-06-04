# Codebase Map

## Directory Structure

```
glidingops/
│
├── ROOT LEVEL - Main Application (~100 PHP files)
│
├── config/                 # Configuration
│   ├── database.php.sample  # MySQL config template
│   └── site.php            # Global settings
│
├── helpers/               # Shared Helper Functions
│   ├── session_helpers.php # Auth checks, org helpers
│   ├── timehelpers.php     # Timezone conversions
│   ├── mail.php           # Email sending
│   ├── audit_helpers.php  # Audit logging
│   ├── error_message.php  # Error display
│   └── dev_mode_banner.php # Dev mode indicator
│
├── includes/              # Core PHP Classes
│   ├── classGlidingDB.php  # Main DB wrapper
│   ├── classSQLPlus.php    # Base DB class
│   ├── classTracksDB.php   # Tracks DB wrapper
│   ├── GlidingClass.php   # API client (unused?)
│   ├── GlidingGNZClass.php # GNZ API client
│   ├── ognClass.php       # OGN tracking
│   ├── Pest.php           # HTTP client
│   └── OAuth.php          # OAuth (unused?)
│
├── orgs/                  # Organization Customizations
│   ├── 1/                 # Wellington Wairarapa
│   │   ├── heading*.txt   # Header templates
│   │   ├── heading*.css    # Header styles
│   │   ├── menu1.txt       # Navigation menu
│   │   ├── menu1.css       # Menu styles
│   │   ├── accountrules.php # Billing calculations
│   │   └── orgHelpers.php  # Org-specific helpers
│   ├── 2/                 # Other orgs (same structure)
│   ├── 3/
│   ├── 4/
│   └── 5/
│
├── lrv/                   # Laravel Installation (mostly dead)
│   ├── app/               # Laravel app
│   │   ├── Models/        # Eloquent models
│   │   └── Http/          # Controllers
│   ├── config/           # Laravel config
│   ├── database/         # Migrations
│   ├── routes/           # Routes
│   ├── public/           # Public assets
│   └── vendor/           # Composer packages
│
├── tracks/                # Tracks DB Config
│   ├── config/
│   ├── includes/
│   └── api/
│
├── js/                    # JavaScript Files
│   ├── DailySheet.js      # Flight entry
│   ├── DailySheetEntry.js # Entry form
│   ├── DailySheetEntryType.js
│   ├── CrewSelect.js      # Member dropdowns
│   ├── ChargesSelect.js   # Billing options
│   ├── XMLSelect.js       # XML helpers
│   └── notify.js          # Notifications
│
├── css/                   # CSS Files
│   ├── dailysheet.css
│   └── notify.css
│
├── maintenance/          # Admin Tools
│   ├── duplicates_index.php
│   ├── duplicates_show.php
│   ├── duplicates_delete.php
│   └── testemail.php
│
├── private/               # Private/Internal (verify usage!)
│   ├── Reports.php
│   └── DumpTable.php
│
├── graphql/               # GraphQL (if any)
│
└── docs/                  # This documentation
```

---

## PHP File Patterns

### List Pages (*-list.php)
All follow similar pattern - table display with sorting/pagination:
- members-list.php
- users-list.php
- flights-list.php
- aircraft-list.php
- etc.

### Edit/Create Pages (*.php)
All follow similar pattern - form for single record:
- members.php
- users.php
- aircraft.php
- etc.

### Special Pages
- home.php - Dashboard
- Login.php, checklogin.php - Auth
- StartDay.php, DailySheet.php - Daily ops
- MasterDisplay.php - Map
- billing-report.php, Engineer.php - Reports

---

## Key File Purposes

### Core Helpers (in /helpers/)

| File | Purpose |
|------|---------|
| `helpers.php` | ~276 lines - Core utility functions (getFlightType, getRoleId, getClassId, etc.) |
| `timehelpers.php` | Timezone conversion using Eloquent - `orgTimezone()`, `timeLocalFormat()`, `timeLocalSQL()` |
| `session_helpers.php` | `require_security_level()`, `current_org()` - auth helpers |
| `mail.php` | Email class with hardcoded WWGC email settings |
| `audit_helpers.php` | `audit_log()` - writes to audit table |
| `error_message.php` | Error display utility |
| `dev_mode_banner.php` | Shows "DEV MODE" banner when active |

### Database Classes (in /includes/)

| File | Purpose |
|------|---------|
| `classGlidingDB.php` | Main DB wrapper - queries for flyingNow, completedToday, getFlightType |
| `classSQLPlus.php` | Base class - query(), singlequery(), insert() |
| `classTracksDB.php` | Wrapper for tracks DB - getTracksForFlight, numTracksForFlight |
| `GlidingClass.php` | API client (unused?) |
| `GlidingGNZClass.php` | Gliding NZ API client |
| `ognClass.php` | OGN tracking API client |
| `Pest.php` | HTTP client library (used by GlidingClass) |

### Entry Point (load_model.php)
- Sets up Eloquent ORM using Laravel Capsule
- Connects to TWO databases: 'default' (gliding) and 'tracks'
- Used by helpers/timehelpers.php to query via Eloquent

---

## External Dependencies

### CDN Libraries (from jsLibraies.php)
- jQuery 1.12.4
- Bootstrap 3.3.7
- Bootstrap Select 1.12.1

### External Services
- Google Maps API (MasterDisplay)
- Twitter API (MessagingPage - may be broken)
- (No actual SMS gateway)

---

## File Size Notes

Largest files (most complex):
1. dailysheet.php - 1302 lines (flight entry)
2. members.php - 1283 lines (member form)
3. billing-report.php - 879 lines (billing report)
4. helpers.php - 276 lines (utility functions)
5. members-list.php - 361 lines (list view)

These are some of the more complex files.

---

## Code Patterns

### Form Submission - XML-Based (DailySheet)

**Unique Pattern:** The application uses XML-based form submission instead of standard POST.

**How it works:**
1. Form data collected in JavaScript object
2. Converted to XML string: `<root><field1>value</field1>...</root>`
3. POST to server endpoint
4. Server parses XML, processes, returns XML response
5. JavaScript updates DOM based on response

**Files using this pattern:**
- DailySheet.js - DailySheetEntry.js → XML submission
- CrewSelect.js - AJAX member lookup
- ChargesSelect.js - Billing option AJAX

### Time Input - flatpickr

All time inputs use flatpickr (lightweight datetime picker):
```javascript
flatpickr(".time-input", {
  enableTime: true,
  noCalendar: true,
  dateFormat: "H:i",
  time_24hr: true
})
```

This is used for:
- Flight times (takeoff, towland, land)
- Duty times
- Any time-based input

### Dropdown Enhancement - Bootstrap Select

Member/glider selectors use Bootstrap Select (better UX):
```html
<select class="selectpicker" data-live-search="true">
```

Searchable dropdowns for:
- PIC, P2, Towpilot selection
- Glider registration
- Aircraft selection

### File Organization Pattern

| Pattern | Example | Count |
|---------|---------|-------|
| xxx-list.php | members-list.php | Table display |
| xxx.php | members.php | Add/Edit form |
| Check scripts | checklogin.php | Process actions |
| APIs | api/daily-flights.php | Flight + tracking data |

  - api/daily-flights.php (flight data with optional tracks)
- spots.php (device mapping)

### Messaging
- MessagingPage.php (create broadcast)
- messages-tree.php (sent messages treeview)
- texts-list-v2b.php (sent messages DataTables view)
- messages-list.php (display on home - legacy)
- SendTxt.php (process queue)

### Admin / Diagnostics
- ViewAs.php (impersonate / view home as another role)
