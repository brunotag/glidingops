# Features

## Daily Operations (Core - Heavily Used)

### DailySheet - Offline/Disconnected Capability

**Key Feature:** Works offline using localStorage and XML-based data.

### Architecture

```
[PHP generates initial XML]
       ↓
[JavaScript parses into xmlDoc]
       ↓
[Store in localStorage]
       ↓
[Changes happen in local XML DOM]
       ↓
[Sync back via POST to updflights.php]
```

### How Offline Works

**1. Initial Load (dailysheet.php lines 1140-1181):**
```javascript
// Check localStorage for saved data
lxml = localStorage.getItem(datestring);

// Compare update sequence numbers
local_updseq > server_updseq → use local, show "Not Synchronised"
local_updseq <= server_updseq → use server data

// Always save current state
localStorage.setItem(datestring, fxml);
```

**2. Update Sequence (updseq):**
- Every change increments a local counter
- Stored in XML: `<updseq>123</updseq>`
- Compared on reload to detect unsynced changes

**3. Sync Status Display:**
- Green "Sync" = in sync with server
- Red "Not Synchronised" = local changes pending

**4. Sending to Server:**
```javascript
function sendXMLtoServer() {
  var params = "org=" + org + "&upd=" + xml2Str(xmlDoc);
  xmlhttp.open("POST", "updflights.php", true);
  xmlhttp.send(params);
}
```

**Server (updflights.php):**
- Parses XML payload
- Processes insert/update/delete
- Returns new updseq

### Data Flow

1. **Load:** PHP → XML → JavaScript xmlDoc → localStorage
2. **Edit:** Changes in memory (xmlDoc), save to localStorage
3. **Sync:** POST xmlDoc to updflights.php → server saves to DB
4. **Reconnect:** Compare updseq, merge if needed

### Key JavaScript Files

| File | Purpose |
|------|---------|
| DailySheet.js | Main UI logic, row management |
| DailySheetEntry.js | Entry type (tow/winch/self) dropdowns |
| DailySheetEntryType.js | Launch type selection |
| XMLSelect.js | Generic XML-based select dropdowns |
| CrewSelect.js | Member selection (PIC, P2, Towpilot) |
| ChargesSelect.js | Billing option selection |

### Offline Indicators

- Element `#sync` shows status (green/red)
- Data persists across browser refreshes
- Works without network (if previously loaded)

---

### StartDay.php

**Purpose:** Begin a new daily timesheet. Simplified — only asks for location (always today's date).

**Flow:**
1. Enter location (pre-filled from org default)
2. Submit → redirects to DailySheet.php?org=X&location=Y

**Security:** Requires security level 4 (Daily Ops)

---

### EditDailySheet.php

**Purpose:** Edit an existing timesheet for a specific date (no location needed).

**Flow:**
1. Select a date from date picker
2. Looks up org's default_location from database
3. Submit → redirects to DailySheet.php with date parameter

**Security:** Requires security level 4 (Daily Ops)

**Route:** `/EditDailySheet`

---

### DailySheet.php
**Purpose:** Entry form for recording flights

**Details:** ~1300 lines, most complex page in app

**Features:**
- Add/edit/delete flights for the day
- Flight sequence numbering (auto-increment)
- Launch type selection (Tow, Self Launch, Winch)
- Glider registration entry
- Towplane selection (dropdown from aircraft table)
- PIC (pilot in command), P2 (second pilot), Towpilot selection
- Time entry: takeoff, tow landing, landing (flatpickr timepicker)
- Height (for aero tows)
- Billing option selection
- Comments field

**Time Storage:**
- Times stored as BIGINT (Unix timestamp in milliseconds)
- JavaScript handles time calculations
- XML-based data exchange with server

**Security:** Requires security level 4 (Daily Ops)

---

### DailyLogSheet.php
**Purpose:** View flights for a specific date

**Features:**
- Date picker to view any day's flights
- Card-layout form, striped hoverable table with dark header
- Displays all flight details in table format
- Print-friendly (landscape, hides menu/form/print button)
- HOME/BACK navigation menu

**Security:** Requires security level 1 (Member)

---

### CompletedSheet.php
**Purpose:** Finalize a flying day

**Features:**
- Summary of all flights
- Calculates totals
- Can mark day as complete
- Email summary option

**Security:** Requires security level 4 (Daily Ops)

---

## Flight Tracking (Live Map - Used)

### Overview
GPS tracking system using Flarm devices. Separate database (`tracks` or `particletrack`) from main `gliding` database.

### How It Works
1. **Flarm devices** in aircraft send GPS coordinates to external tracking server
2. Data stored in `tracks` table with glider registration as identifier
3. **spots** table maps gliderrego_short → spotkey (device identifier)
4. **todayxml.php** queries tracks for flights currently in air
5. **MasterDisplay.php** renders positions on Google Maps

### Database Connection
- Main DB: `gliding` (members, flights, etc.)
- Tracks DB: `tracks` or `particletrack` (GPS data only)
- Connection details in `tracks/config/database.php`

### MasterDisplay.php
**Purpose:** Full-screen live tracking map for public display

**Details:**
- Google Maps API (hybrid satellite view)
- Auto-refresh via JavaScript polling
- Shows:
  - Active flights with colored paths
  - Current position markers
  - Altitude, distance, vector to launch
  - Completed flights list

**Access:** Public - no authentication required
**Routes:** /wgc (org 1), /ssb (org 2), /cgc (org 3), /agc (org 4)

### todayxml.php
**Purpose:** JSON/XML data feed for map

**Parameters:**
- org (required)
- date (optional, defaults to today)

**Returns:**
- Duties for the day
- Flying flights with positions from tracks table
- Completed flights

### Spots Management
**spots.php / spots-list.php**

Purpose: Configure tracking device links

Fields:
- rego_short (glider registration)
- spotkey (device identifier from Flarm/OGN)
- polltimelast, polltimeall (polling intervals)
- lastreq, lastlistreq (last request timestamps)

---

## Flight Tracking (Live Map - Used)
**Purpose:** Simple status page showing who's flying

**Features:**
- Auto-refresh every 30 seconds
- List of currently airborne flights
- Flight duration counter

---

### MasterDisplay.php
**Purpose:** Full-screen live tracking map

**Features:**
- Google Maps integration (hybrid/satellite)
- Shows all airborne gliders with tracks
- Color-coded flight paths
- Real-time position updates via polling
- Completed flights list on side

**Access:** Public (no auth required) - URL patterns: /wgc, /ssb, /cgc, /agc

---

### todayxml.php
**Purpose:** JSON/XML data feed for map

**Returns:**
- Today's duties
- Flying flights with positions from tracks table
- Completed flights

---

## Members Management

### members.php
**Purpose:** Add/edit member records (legacy)

**Fields:** 40+ fields including:
- Personal: name, DOB
- Address: postal and emergency contact
- Contact: phone (home/mobile/work), email
- Aviation: GNZ number, QGP number, class, status
- Medical: medical expire, BFR expire
- Permissions: solo, enable_text, enable_email

**Security:** Requires security level 6 (Member + Booking Admin)

**Issue:** NOT mobile-friendly - huge table-based form

---

### members-list-v2b.php (Modern Member List)
**Purpose:** List all members with DataTables filtering

**Features:**
- Server-side pagination via DataTables
- Filter by class, status
- Sort by any column
- Pagination with page size selector (10/25/50/100)
- Export to CSV
- Photo display (60px, clickable modal)
- Actions column with Edit button
- "Create New" button links to members-new.php
- Vertical alignment for all table cells

**Security:** Requires security level 1 (Member)

**Route:** `/AllMembers`

---

### members-new.php (Modern Member Form)
**Purpose:** Add/edit member records - new modernized form

**Features:**
- Single page form with 4 sections: Member Details, Address, Emergency Contact, Roles
- Uses direct mysqli queries (not Laravel Eloquent)
- Auto-suggest displayname from Firstname + Surname
- Default class = Flying, default status = Active
- enable_email always true (no checkbox needed)
- Photo upload to /media/members/<org>/
- Address fields: mem_addr1-4, mem_city, mem_country, mem_postcode
- Emergency contact: emerg_addr1-3, emerg_phone
- Role assignment via checkboxes
- Form validation with server-side checks

**API Endpoint:** `api/member-form.php` - handles POST (save) and GET (load classes/statuses/roles)

**Security:** Requires security level 6 (Member + Booking Admin)

**Route:** `/MemberNew`

---

### edit-my-details.php
**Purpose:** Members can edit their own details

**Features:**
- Subset of fields (non-admin fields)
- Members can only edit themselves

**Security:** Requires security level 1 (Member)

---

## Users Management

### users.php
**Purpose:** Create/edit system users

**Fields:**
- Name, username (email), password
- Organization (org)
- Security level (bitmask)
- Member linkage (optional)

**Security:** Requires security level 64 (Admin)

---

### users-list.php
**Purpose:** List all users

**Security:** Requires security level 64 (Admin)

---

## Reports

### Treasurer.php
**Purpose:** Monthly billing report

**Features:**
- Select month/year
- Lists all flights with calculated charges
- Separate sections: Check flights, No charge, Trials, Charged
- Totals by member
- Export to CSV

**Issue:** Billing calculations are BROKEN - "fees are wrong but times can be trusted"

**Security:** Requires security level 8 (CFO/Treasurer)

---

### Engineer.php
**Purpose:** Aircraft usage report

**Features:**
- Filter by glider and date range
- Shows flight hours per aircraft
- Used for maintenance tracking

**Security:** Requires security level 32 (Engineer)

---

### last-flights-list.php (Currency Report)
**Purpose:** Show when each member last flew

**Features:**
- Shows: last flight, last solo, last as P2, last as P1
- 90-day currency flag
- Only shows Active members

**Security:** Requires security level 32 (Engineer)

---

### MyFlights.php
**Purpose:** Member's own flight history

**Features:**
- Member sees their own flights
- Shows flight details and charges
- Billing calculations (via accountrules.php)

**Security:** Requires security level 1 (Member)

---

## Messaging (Broadcast System)

### MessagingPage.php
**Purpose:** Broadcast messages to members

**Features:**
- Text input (160 char limit - Twitter legacy)
- Select recipients by role (Instructors, Tow Pilots, etc.)
- "Fake Twitter" option - stores message for display on homepage
- Creates entries in messages table

**Security:** Requires security level 1 (Member)

---

### messages-list.php
**Purpose:** Display broadcast messages on homepage

**Features:**
- Styled like Twitter feed
- Shows last 20 messages for org
- Embedded in home.php via iframe

---

### SendTxt.php
**Purpose:** Process queued messages (cron job)

**Details:**
- Looks for texts with txt_status = 0
- Converts to emails (NOT actual SMS)
- Updates txt_status to 3 (sent via email)
- Redirects back to MessagingPage

**Note:** This is a misnamed "text" → email converter, no actual SMS

---

## Aircraft Management

### aircraft.php
**Purpose:** Add/edit aircraft (gliders and towplanes)

**Fields:**
- Registration, short rego
- Type (glider/towplane)
- Make/model, seats, serial number
- Club glider flag, bookable flag
- Charge rates (per minute, max per flight)
- Maintenance dates (annual, supplementary)
- Flarm ID, Spot ID (tracking links)

**Security:** Requires security level 120 (Admin + Engineer)

---

### aircraft-list.php
**Purpose:** List all aircraft

---

### aircrafttype.php
**Purpose:** Manage aircraft types

---

## Data Maintenance Pages

All follow similar pattern: *-list.php (list) + *.php (edit)

| Page | Purpose |
|------|---------|
| launchtypes-list.php / launchtypes.php | Launch methods (Tow, Self, Winch) |
| flighttypes-list.php / flighttypes.php | Flight types (Glider, Check, Retrieve) |
| billingoptions-list.php / billingoptions.php | Billing schemes |
| towcharges-list.php / towcharges.php | Tow pricing |
| charges-list.php / charges.php | Other fees (airways, landing) |
| dutytypes-list.php / dutytypes.php | Duty types |
| duty-list.php / duty.php | Roster entries |
| roles-list.php / roles.php | Role definitions |
| role_member-list.php / role_member.php | Role assignments |
| groups-list.php / groups.php | Member groups |
| group_member-list.php / group_member.php | Group membership |
| incentive_schemes-list.php / incentive_schemes.php | Pricing schemes |
| scheme_subs-list.php / scheme_subs.php | Member subscriptions |
| membership_class-list.php / membership_class.php | Member classes |
| membership_status-list.php | Member statuses |
| spots-list.php / spots.php | Tracking devices |
| audit-list.php | Audit log |
| organisations-list.php / organisations.php | Organizations |

---

## Maintenance Tools

### maintenance/duplicates_index.php
Find duplicate members (same firstname + surname in org)

### maintenance/duplicates_show.php
Show details of duplicates

### maintenance/duplicates_delete.php
Delete duplicate members

### maintenance/testemail.php
Test email sending

---

## Authentication & Registration

### Login.php
Login form - checks against users table

### checklogin.php
Validates credentials (MD5 password hash), sets session

### Register.php
Self-service registration for new members
- Enter email
- If email matches existing member, creates user account
- Sends password via email

### changepw.php
Password change form

### Forgotten.php
Password reset request

---

## Aircraft & Configuration

### aircraft.php / aircraft-list.php
**Purpose:** Manage gliders and towplanes

**Fields:**
- registration (unique identifier)
- type (aircrafttype FK)
- callsign (radio call sign)
- competition_id (for gliders - used in tracking)
- club_owned (boolean - who owns)
- member (owner if not club - FK to members)
- towplane (boolean - is it a towplane?)
- glider (boolean - is it a glider?)

**Important:** A plane can be both towplane AND glider (self-launching). The boolean fields determine behavior.

### aircrafttype.php / aircrafttype-list.php
**Purpose:** Aircraft types (ASK-21, Pawnee, etc.)
- name, manufacturer, category

### dutytypes.php / dutytypes-list.php
**Purpose:** Types of duties (Launch, Landing, Instructor, etc.)
- name, default_pay_rate

### launchtypes.php / launchtypes-list.php
**Purpose:** Launch methods (Aerotow, Winch, Self Launch)
- name, cost per launch

### groups.php / groups-list.php
**Purpose:** Organize pilots for daily ops (Group A, Group B, etc.)

### group_member.php / group_member-list.php
**Purpose:** Assign members to groups (time-based)

---

## Tracking System Architecture

See the dedicated **[TRACKING.md](TRACKING.md)** for full documentation. This section provides a summary.

### Overview
Multiple tracking sources feed GPS data into three databases, displayed on the live map.

### Data Flow Summary

```
Particle/Flarm/SPOT/bTraced  -->  gliding.tracks  -->  todayxml.php  -->  MasterDisplay
                                    |                     
                                    +--> ArchiveTracks.php (after 3 days) --> tracks.tracksarchive
                                    |                     
                                    +--> particletrack.* (Particle primary ingestion)
```

### Sources

1. **Particle Devices** (primary) - Hardware in gliders, received by `tracks/apiParticlejsonv1.php`, stored in `particletrack.*`, forwarded to `gliding.tracks`
2. **Flarm/OGN** - `getFlarmTask.php` cron (every minute), writes to `gliding.tracks`
3. **SPOT** - `GetSpotTask.php` cron (every 2 mins, 8pm-7am), writes to `gliding.tracks`
4. **bTraced** - `btraced.php`, writes to `gliding.tracks`

### Map Display

- **Current:** `MasterDisplayNew.php` (Leaflet + OpenTopoMap) via `/wgc-new`
- **Legacy:** `MasterDisplay.php` (Google Maps) via `/wgc`, `/ssb`, `/cgc`, `/agc`

### todayxml.php
**Purpose:** JSON feed for live tracking map
**Access:** Public (no auth)
**Parameters:** org, date (optional)
**Returns:** Flights, duties, positions from tracks DB

### apiParticlejsonv1.php
**Purpose:** Alternative tracking data
**Used by:** Probably legacy tracking system

### apiglidjsonv1.php
**Purpose:** Gliding data API
**Used by:** Unknown - check for external consumers

### getFlarmTask.php
**Purpose:** Task/waypoint data for Flarm devices
**Used by:** Gliders with Flarm units

### GetSpotTask.php
**Purpose:** Task/waypoint data for Spot devices
**Used by:** Gliders with Spot trackers

### GroupXML.php
**Purpose:** Group assignment data in XML format
**Used by:** DailySheet for group-based flight logging

### btraced.php
**Purpose:** B-Trace device data receiver
**Used by:** Specific tracking hardware

---

## API Clients (includes/)

### GlidingGNZClass.php
**Purpose:** Gliding New Zealand (GNZ) API client
**Used by:** Member sync, competition results
**Status:** Likely active - GNZ integration important for member records

### ognClass.php
**Purpose:** Open Glider Network (OGN) API client
**Used by:** Tracking - receives OGN beacon data
**Status:** Likely active - OGN provides additional tracking data

### GlidingClass.php
**Purpose:** Generic gliding API (unused?)
**Status:** Not verified - check if used anywhere

### Pest.php
**Purpose:** HTTP client library
**Used by:** API classes above for HTTP requests
**Status:** Active dependency

### graphql/graphql.php
**Purpose:** Experimental GraphQL endpoint
**Status:** Likely unused - verify before relying on it

---

## Cron Jobs

### SendTxt.php
**Purpose:** Process message queue
**Schedule:** Likely runs every 5 minutes
**Action:** Converts pending "texts" to emails