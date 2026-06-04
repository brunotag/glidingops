# Access Matrix — Current Bitmask → Future Persona Mapping

Reference document for the permission system overhaul. Every page, API endpoint, and navigation feature is mapped to its current security requirement and the new persona requirement.

## Legend

| Column | Meaning |
|--------|---------|
| **Route** | URL path (clean or direct) |
| **File** | PHP file handling the request |
| **Current** | Current bitmask check (`$_SESSION['security'] & N` or `< N`) |
| **New** | New persona/permission check |
| **Type** | `auth` = any logged in, `persona(X)` = requires persona X, `X\|Y` = OR (any of) |
| **Notes** | Special cases |

---

## Auth-Only Pages (currently `& 1` or `< 1`)

No "Member" persona needed — any authenticated user can access.

| Route | File | Current | New | Notes |
|-------|------|---------|-----|-------|
| `/home` | home.php | `& 1` | `require_auth()` | Dashboard |
| `/AllMembers` | members-list-v2b.php | `& 1` | `require_auth()` | |
| `/MemberNew` | members-new.php | `< 1` | `require_auth()` | Some features inside check admin |
| `/EditMyDetails` | members-new.php | `< 1` | `require_auth()` | |
| `/MyFlights` | MyFlights.php | `< 1` | `require_auth()` | |
| `/MyFlightsCSV` | MyFlightsCSV.php | `< 1` | `require_auth()` | |
| `/PasswordChange` | PasswordChange.php | `& 1` | `require_auth()` | |
| `changepw.php` | changepw.php | `& 1` | `require_auth()` | AJAX |
| `/AllFlightsReportNew` | AllFlightsReportNew.php | `& 1` | `require_auth()` | |
| `/AllFlightsMobile` | AllFlightsReportMobile.php | `< 1` | `require_auth()` | |
| `/DailyLogSheet` | DailyLogSheet.php | `< 1` | `require_auth()` | |
| `/Bookings` | bookings.php | `& 1` | `require_auth()` | |
| `/SeasonTrends` | analytics-season-trends.php | `< 1` | `require_auth()` | |
| `/Spots` | spots-list.php | `& 1` | `require_auth()` | |
| `spots.php` | spots.php | `& 1` | `require_auth()` | |
| `tracks.php` | tracks.php | `& 1` | `require_auth()` | |
| `tracks-list.php` | tracks-list.php | `& 1` | `require_auth()` | |
| `/DevEmailPreview` | dev-email-preview.php | `& 1` + `isLocal()` | `require_auth()` + `isLocal()` | Dev only |
| `private/Reports.php` | private/Reports.php | `& 1` | `require_auth()` | |

---

## Daily Ops Pages (currently `& 4`)

| Route | File | Current | New | Notes |
|-------|------|---------|-----|-------|
| `/DailySheet` | dailysheet.php | `< 4` OR secret code | `require_persona('daily-ops')` | Also allows secret code |
| `/StartDay` | StartDay.php | `& 4` | `require_persona('daily-ops')` | |
| `/EditDailySheet` | EditDailySheet.php | `& 4` | `require_persona('daily-ops')` | |
| `/SelfLaunchEntry` | self-launch-entry.php | `& 4` | `require_persona('daily-ops')` | |
| `updflights.php` | updflights.php | `& 4` | `require_persona('daily-ops')` | AJAX |
| `memberlistfortimesheet.php` | memberlistfortimesheet.php | `& 4` | `require_persona('daily-ops')` | AJAX |
| `DaycheckAndFinal.php` | DaycheckAndFinal.php | `& 4` | `require_persona('daily-ops')` | AJAX |
| `/SentMessages` | texts-list-v2b.php | `& 4` | `require_persona('daily-ops')` | |
| `/MessagesTree` | messages-tree.php | `& 4` | `require_persona('daily-ops')` | |
| `texts.php` | texts.php | `& 4` | `require_persona('daily-ops')` | |
| `/Groups` | groups-list.php | `& 4` | `require_persona('daily-ops')` | |
| `groups.php` | groups.php | `& 4` | `require_persona('daily-ops')` | |
| `group_member-list.php` | group_member-list.php | `& 4` | `require_persona('daily-ops')` | |
| `group_member.php` | group_member.php | `& 4` | `require_persona('daily-ops')` | |
| `maintenance/testemail.php` | maintenance/testemail.php | `& 4` | `require_persona('daily-ops')` | |

## Combined Daily Ops + Member — now just Daily Ops

Previously `& 5` (needed both bits). Member requirement removed.

| Route | File | Current | New | Notes |
|-------|------|---------|-----|-------|
| `/MessagingPage` | MessagingPage.php | `& 5` | `require_persona('daily-ops')` | Member requirement dropped |

## Combined Daily Ops + Booking

| Route | File | Current | New | Notes |
|-------|------|---------|-----|-------|
| `GroupAllocate.php` | GroupAllocate.php | `& 6` | `require_persona('daily-ops','booking')` | Only used by groups |

---

## CFO Pages (currently `& 8`)

| Route | File | Current | New | Notes |
|-------|------|---------|-----|-------|
| `/BillingReport` | billing-report.php | `& 8` | `require_persona('cfo')` | |
| `/TreasurerReportNew3` | TreasurerReportNew3.php | `& 8` | `require_persona('cfo')` | |
| `/TreasurerReportNew4` | TreasurerReportNew4.php | `& 8` | `require_persona('cfo')` | |
| `/OtherCharges` | charges-list.php | `& 8` | `require_persona('cfo')` | |
| `charges.php` | charges.php | `& 8` | `require_persona('cfo')` | |
| `/TowCharges` | towcharges-list.php | `& 8` | `require_persona('cfo')` | |
| `towcharges.php` | towcharges.php | `& 8` | `require_persona('cfo')` | |
| `/IncentiveSchemes` | incentive_schemes-list.php | `& 8` | `require_persona('cfo')` | |
| `incentive_schemes.php` | incentive_schemes.php | `& 8` | `require_persona('cfo')` | |
| `/SubsToSchemes` | scheme_subs-list.php | `& 8` | `require_persona('cfo')` | |
| `scheme_subs.php` | scheme_subs.php | `& 8` | `require_persona('cfo')` | |

---

## Engineer Pages (currently `& 32`)

| Route | File | Current | New | Notes |
|-------|------|---------|-----|-------|
| `/Engineer` | Engineer.php | `& 32` | `require_persona('engineer')` | |
| `/last-flights-list` | last-flights-list.php | `& 32` | `require_persona('engineer')` | |

---

## Admin Pages (currently `& 64`)

| Route | File | Current | New | Notes |
|-------|------|---------|-----|-------|
| `/UsersList` | users-list-v2b.php | `& 64` | `require_persona('admin')` | |
| `/Users` | users-new.php | `& 64` | `require_persona('admin')` | |
| `/Audits` | audit-list.php | `& 64` | `require_persona('admin')` | Route missing from .htaccess |
| `audit.php` | audit.php | `& 64` | `require_persona('admin')` | |
| `/AircraftTypes` | aircrafttype-list.php | `& 64` | `require_persona('admin')` | |
| `aircrafttype.php` | aircrafttype.php | `& 64` | `require_persona('admin')` | |
| `/FlightTypes` | flighttypes-list.php | `& 64` | `require_persona('admin')` | |
| `flighttypes.php` | flighttypes.php | `& 64` | `require_persona('admin')` | |
| `/LaunchTypes` | launchtypes-list.php | `& 64` | `require_persona('admin')` | |
| `launchtypes.php` | launchtypes.php | `& 64` | `require_persona('admin')` | |
| `/BillingOptions` | billingoptions-list.php | `& 64` | `require_persona('admin')` | |
| `billingoptions.php` | billingoptions.php | `& 64` | `require_persona('admin')` | |
| `/Roles` | roles-list.php | `& 64` | `require_persona('admin')` | |
| `roles.php` | roles.php | `& 64` | `require_persona('admin')` | |
| `/membership_class` | membership_class-list.php | `& 64` | `require_persona('admin')` | |
| `membership_class.php` | membership_class.php | `& 64` | `require_persona('admin')` | |
| `/membership_status` | membership_status-list.php | `& 64` | `require_persona('admin')` | |
| `membership_status.php` | membership_status.php | `& 64` | `require_persona('admin')` | |
| `/Analytics` | analytics-dashboard.php | `& 64` | `require_persona('admin')` | |
| `manage-secret-code.php` | manage-secret-code.php | `& 64` | `require_persona('admin')` | |
| `maintenance/duplicates_index.php` | duplicates_index.php | `& 64` | `require_persona('admin')` | |
| `maintenance/duplicates_show.php` | duplicates_show.php | `& 64` | `require_persona('admin')` | |
| `maintenance/duplicates_delete.php` | duplicates_delete.php | `& 64` | `require_persona('admin')` | |

---

## God Pages (currently `& 128`)

| Route | File | Current | New | Notes |
|-------|------|---------|-----|-------|
| `/Organisations` | organisations-list.php | `& 128` | `require_persona('god')` | |
| `/Organisation` | organisations.php | `& 128` | `require_persona('god')` | |
| `/ViewAs` | ViewAs.php | `& 128` | `require_persona('god')` | |
| `/InviteUsers` | invite-users.php | `& 128` | `require_persona('god')` | |
| `maintenance/duplicates_suggestions.php` | duplicates_suggestions.php | `& 128` | `require_persona('god')` | |

---

## Combined Access (OR logic — any of the listed personas)

### Admin OR Engineer OR CFI OR CFO (current `& 120 = 64+32+16+8`)

| Route | File | Current | New |
|-------|------|---------|-----|
| `/AllAircraft` | aircraft-list.php | `& 120` | `require_persona('admin','engineer','cfi','cfo')` |
| `/Aircraft` | aircraft.php | `& 120` | `require_persona('admin','engineer','cfi','cfo')` |

### Admin OR CFO (current `& 72 = 64+8`)

| Route | File | Current | New |
|-------|------|---------|-----|
| `/flights-list` | flights-list.php | `& 72` | `require_persona('admin','cfo')` |
| `flights.php` | flights.php | `& 72` | `require_persona('admin','cfo')` |

---

## API Endpoints

| Route | File | Current | New | Notes |
|-------|------|---------|-----|-------|
| `GET /api/daily-flights` | api/daily-flights.php | `memberid` or `org` param | `require_auth()` OR public with `org` | Unchanged |
| `GET /api/members` | api/members.php | `& 1` | `require_auth()` | |
| `GET/POST /api/member-form` | api/member-form.php | `& 1` | `require_auth()` | |
| `GET /api/member-search` | api/member-search.php | `& 1` | `require_auth()` | |
| `GET /api/members-email` | api/members-email.php | `memberid` | `require_auth()` | |
| `GET /api/aircraft` | api/aircraft.php | `& 1` | `require_auth()` | |
| `GET /api/track-flights` | api/track-flights.php | `& 1` | `require_auth()` | |
| `GET /api/favourites` | api/favourites.php | `& 1` | `require_auth()` | |
| `GET /api/myflights` | api/myflights-data.php | `< 1` + `memberid` | `require_auth()` | |
| `GET /api/flights-report` | api/flights-report.php | `& 1` | `require_auth()` | |
| `GET /api/analytics-data` | api/analytics-data.php | `< 1` | `require_auth()` | |
| `GET /api/analytics-trends` | api/analytics-trends.php | `< 1` | `require_auth()` | |
| `GET /api/date-members` | api/date-members.php | `isLocal()` + `memberid` | `require_auth()` + `isLocal()` | Dev only |
| `GET/POST /api/flights` | api/flights.php | `& 4` | `require_persona('daily-ops')` | |
| `GET /api/texts` | api/texts.php | `& 4` | `require_persona('daily-ops')` | |
| `GET /api/users` | api/users.php | `& 64` | `require_persona('admin')` | |
| `GET/POST /api/user-form` | api/user-form.php | `& 64` | `require_persona('admin')` | |
| `POST /api/magic-link-request` | api/magic-link-request.php | public | public | Unchanged |
| `GET /api/magic-link-verify` | api/magic-link-verify.php | public | public | Unchanged |

---

## Navigation Features in home.php (conditional rendering)

These are in-page UI elements shown/hidden based on `$effectiveSecurity`. They become conditional checks against `$_SESSION['permissions']` in the new system.

### Shown to all authenticated users (`>= 1`)

| Feature | Current check | New check | Notes |
|---------|--------------|-----------|-------|
| Rosters & Bookings card | `>= 1` | `require_auth()` | Contains external Google links + /Bookings |
| Members & Users card | `>= 1` | `require_auth()` | Contains /AllMembers + conditional sub-links |
| Reports card | `>= 1` | `require_auth()` | Contains conditional sub-links |
| Analytics card | `>= 1` | `require_auth()` | Contains /SeasonTrends + conditional /Analytics |
| All Flights Report link (in Reports) | `& 1` | `require_auth()` | Actually redundant with `>= 1` outer check |
| All Flights Report (New) link (in Reports) | `& 1` | `require_auth()` | Same |

### Shown to Daily Ops (`>= 4` or `& 4`)

| Feature | Current check | New check | Notes |
|---------|--------------|-----------|-------|
| Daily Ops card | `>= 4` | `require_persona('daily-ops')` | /DailySheet, /StartDay, etc. |
| Messaging card | `>= 5` | `require_persona('daily-ops')` | Changed: was 5, now just daily-ops |

### Shown to CFO (`& 8`)

| Feature | Current check | New check |
|---------|--------------|-----------|
| Billing Report link | `& 8` | `require_persona('cfo')` |
| Treasurer Report links | `& 8` | `require_persona('cfo')` |

### Shown to Engineer (`& 32`)

| Feature | Current check | New check |
|---------|--------------|-----------|
| Engineer Report link | `& 32` | `require_persona('engineer')` |
| Currency Report link | `& 32` | `require_persona('engineer')` |

### Shown to Admin (`& 64`)

| Feature | Current check | New check |
|---------|--------------|-----------|
| View Users link | `& 64` | `require_persona('admin')` |
| Create User link | `& 64` | `require_persona('admin')` |
| Manage Duplicates link | `& 64` | `require_persona('admin')` |
| Compare Seasons link (Analytics) | `& 64` | `require_persona('admin')` |
| Diagnostics & Recovery card | `& 64` | `require_persona('admin')` |
| Aircraft Types link (Data Maintenance) | `& 64` | `require_persona('admin')` |
| Flights Raw link (Data Maintenance) | `& 64` | `require_persona('admin')` |
| Membership Classes link | `& 64` | `require_persona('admin')` |
| Membership Statuses link | `& 64` | `require_persona('admin')` |
| Roles link | `& 64` | `require_persona('admin')` |
| Spots link | `& 64` | `require_persona('admin')` |
| Manage Secret Code link | `& 64` | `require_persona('admin')` |

### Shown to Admin + CFO (`& 72 = 64+8`)

| Feature | Current check | New check |
|---------|--------------|-----------|
| Incentive Schemes link | `& 72` | `require_persona('admin','cfo')` |
| Other Charges link | `& 72` | `require_persona('admin','cfo')` |
| Subs to Incentives link | `& 72` | `require_persona('admin','cfo')` |
| Tow Charging link | `& 72` | `require_persona('admin','cfo')` |

### Shown to Admin + Engineer + CFI + CFO (`& 104 = 64+32+8`)

| Feature | Current check | New check |
|---------|--------------|-----------|
| Aircraft link (Data Maintenance) | `& 104` | `require_persona('admin','engineer','cfi','cfo')` |

### Shown to full Data Maintenance group (`& 120 = 64+32+16+8`)

| Feature | Current check | New check |
|---------|--------------|-----------|
| Data Maintenance card wrapper | `& 120` | `require_persona('admin','engineer','cfi','cfo')` |

### Shown to God (`& 128`)

| Feature | Current check | New check |
|---------|--------------|-----------|
| Super Admin card | `& 128` | `require_persona('god')` |
| Invite Users link | `& 128` | `require_persona('god')` |
| Organisations link | `& 128` | `require_persona('god')` |
| Suggested Duplicates link | `& 128` | `require_persona('god')` |

### Members Roles Report (`& 24 = 16+8`)

| Feature | Current check | New check |
|---------|--------------|-----------|
| Members Roles Report link | `& 24` | `require_persona('cfi','cfo')` |

---

## Secret Code (Service User)

| Route | File | Current | New | Notes |
|-------|------|---------|-----|-------|
| `/DailySheet?org=1&key=...` | dailysheet.php | creates session with level 5 | hardcodes `['daily-ops']` persona | No other pages accessible |

---

## ViewAs

| Route | File | Current | New |
|-------|------|---------|-----|
| `/ViewAs` | ViewAs.php | `& 128` | `require_persona('god')` |
| `/home?as=N` | home.php | `& 128` + override `$effectiveSecurity` | `require_persona('god')` + filter by persona name |

---

## Summary: Persona-to-Pages Mapping

### Any Authenticated User (replaces `& 1`)
- /home, /AllMembers, /MemberNew, /EditMyDetails, /MyFlights, /MyFlightsCSV
- /PasswordChange, /AllFlightsReportNew, /AllFlightsMobile, /DailyLogSheet
- /Bookings, /SeasonTrends, /Spots, /Spots (edit), tracks pages
- /DevEmailPreview (dev only)
- API: members, member-form, member-search, members-email, aircraft, track-flights
- API: favourites, myflights, flights-report, analytics-data, analytics-trends

### daily-ops persona (replaces `& 4`)
- /DailySheet, /StartDay, /EditDailySheet, /SelfLaunchEntry
- /MessagingPage, /SentMessages, /MessagesTree
- /Groups, group CRUD pages
- updflights.php, memberlistfortimesheet.php, DaycheckAndFinal.php
- API: flights (POST), texts
- + all authenticated pages

### booking persona (replaces `& 2`)
- No standalone pages, only in combos: GroupAllocate.php (with daily-ops)

### cfo persona (replaces `& 8`)
- /BillingReport, /TreasurerReportNew3/4
- /OtherCharges, /TowCharges, /IncentiveSchemes, /SubsToSchemes
- + all authenticated pages

### engineer persona (replaces `& 32`)
- /Engineer, /last-flights-list
- /AllAircraft, /Aircraft (shared with admin/cfi/cfo)
- + all authenticated pages

### admin persona (replaces `& 64`)
- /UsersList, /Users, /Audits, /Analytics
- /AircraftTypes, /FlightTypes, /LaunchTypes, /BillingOptions, /Roles
- /membership_class, /membership_status, /Spots (admin)
- manage-secret-code.php, maintenance/duplicates_*
- /AllAircraft, /Aircraft (shared with engineer/cfi/cfo)
- /IncentiveSchemes, /OtherCharges, /SubsToSchemes, /TowCharges (shared with cfo)
- /flights-list, flights.php (shared with cfo)
- API: users, user-form
- + all authenticated pages

### god persona (replaces `& 128`)
- /Organisations, /Organisation
- /ViewAs, /InviteUsers
- maintenance/duplicates_suggestions.php
- + all authenticated pages

### service-user (secret code)
- /DailySheet only
- No other pages

---

## User Count by Persona (from local DB, verify on production)

| Persona | Bit | Users | Derived from levels |
|---------|-----|-------|-------------------|
| *(none)* | 1 | 266 | Level 1 only |
| booking | 2 | ~55 | Levels 7,39,63,127 |
| daily-ops | 4 | ~38 | Levels 5,7,21,37,39,53,63,127 |
| cfo | 8 | ~46 | Levels 9,63,127 |
| cfi | 16 | ~48 | Levels 21,53,63,127 |
| engineer | 32 | ~57 | Levels 33,37,39,53,63,127 |
| admin | 64 | ~43 | Levels 63,127 |
| god | 128 | 10 | Level 255 |

---

## Testing Matrix

To manually verify before migration: create one test user per persona, log in, and confirm:

| Test # | Persona(s) | Should access | Should NOT access |
|--------|-----------|---------------|-------------------|
| 1 | *(none — auth only)* | /MyFlights, /AllMembers, /PasswordChange | /DailySheet, /BillingReport, /Users, /Organisations |
| 2 | daily-ops | /DailySheet, /MessagingPage, /Groups | /BillingReport, /Users, /Organisations |
| 3 | cfo | /BillingReport, /TowCharges | /DailySheet (if no daily-ops), /Users |
| 4 | engineer | /Engineer, /AllAircraft | /BillingReport, /Users |
| 5 | admin | /Users, /AircraftTypes, /Analytics, /flights-list | /Organisations, /ViewAs |
| 6 | god | /Organisations, /ViewAs, /InviteUsers | *(everything)* |
| 7 | daily-ops + booking | /DailySheet, GroupAllocate.php | /BillingReport |
| 8 | admin + cfo | /Users, /BillingReport, /flights-list | /Organisations |
| 9 | admin + engineer | /Users, /Engineer, /AllAircraft | /Organisations |
| 10 | admin + engineer + cfi + cfo | /AllAircraft, /Aircraft, /Users, /Engineer, /BillingReport | /Organisations |
| 11 | service-user (secret code) | /DailySheet | /BillingReport, /Users, /MyFlights |
