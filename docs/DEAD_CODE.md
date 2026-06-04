# Dead Code — Can Be Deleted

## Overview

This application has accumulated technical debt. This document identifies code that can be safely removed, with confidence levels based on automated scanning (2026-05-18).

---

## Confidence Scale

| Level | Meaning |
|-------|---------|
| **HIGH** | No internal references, no route, or confirmed replaced |
| **MEDIUM** | Few/no references but might have external callers |
| **LOW** | Likely a cron job or external callback — verify on server first |

---

## High Confidence — Safe to Delete Now

### Priority 1 — Unreferenced Files

| Priority | File | Reason |
|----------|------|--------|
| 1 | `api/js-debug.php` | Route exists but nothing calls it |
| 2 | `MessagingPageOld.php` | Listed as to-delete in AGENTS.md, replaced by MessagingPage.php |
| 3 | `texts-list.php` | Dead SMS layer, replaced by `texts-list-v2b.php` |
| 4 | `users-list.php` | Replaced by `users-list-v2b.php`, "Old Version" button in modern page |
| 5 | `users.php` | Replaced by `users-new.php`, "Old Version" button in modern page |
| 6 | `members-list.php` | Replaced by `members-list-v2b.php`, "Old Version" button in modern page |
| 7 | `members.php` | Replaced by `members-new.php`, "Old Version" button in modern page |
| 8 | `edit-my-details.php` | Replaced by `members-new.php` via `/EditMyDetails` route |
| 9 | `js/DailySheetEntry.js` | Not loaded by any page. Superseded by `DailySheetEntryType.js` |
| 10 | `texts-list-last-200.php` | Dead SMS layer (referenced only by MessagingPageOld.php) |
| 11 | `message-delete.php` | Dead SMS layer (referenced only by texts-list-last-200.php) |

### Legacy Routes to Remove

| Route | File | Replaced By |
|-------|------|-------------|
| `/Member` | `members.php` | `/MemberNew` → `members-new.php` |
| `/MembersListOld` | `members-list.php` | `/AllMembers` → `members-list-v2b.php` |
| `/MessagingPageOld` | `MessagingPageOld.php` | `/MessagingPage` → `MessagingPage.php` |
| `/texts-list-old` | `texts-list.php` | `/texts-list` → `texts-list-v2b.php` |
| `/UsersOld` | `users.php` | `/Users` → `users-new.php` |
| `/UsersListOld` | `users-list.php` | `/UsersList` → `users-list-v2b.php` |
| `/wgc/desktop` | `map/MasterDisplayDesktop.php` | **Do NOT add** — only the router at `/wgc`, no sub-routes |
| `/wgc/mobile` | `map/MasterDisplayMobile.php` | **Do NOT add** — only the router at `/wgc`, no sub-routes |

### Links to Delete Before Removing Routes

- `members-new.php` — "Old Version" link to `members.php`
- `members-list-v2b.php` — "Old Version" link to `members-list.php`
- `users-new.php` — "Old Version" link to `users.php`
- `users-list-v2b.php` — "Old Version" link to `users-list.php`
- `MessagingPage.php:336` — `<li><a href='members-list.php'>Members</a></li>`

---

## Medium Confidence — Verify Then Delete

| File | Notes |
|------|-------|
| `SendMailAttach.php` | No references found, unclear if used |
| `Heights.php` | Only referenced by `apiglidjsonv1.php` via require |
| `GlidingFlightMap.php` | No references |
| `googlemapsgenerate.php` | No references |
| `MyFlightMap.php` | No references |
| `webcams.php` | No references |
| `tracks-list.php` | No references |
| `group_member.php` | No links found |
| `group_member-list.php` | No links found |
| `groups-list.php` | No links found |
| `audit.php` | Documented route `/Audits` missing from `.htaccess` |
| `audit-list.php` | Documented route `/Audits` missing from `.htaccess` |

---

## Low Confidence — Cron Jobs / External Callbacks

Verify these are still configured on the production server before touching.

| File | Likely Purpose |
|------|---------------|
| `getFlarmTask.php` | Cron: fetches Flarm/OGN tracking (every minute!) |
| `GetSpotTask.php` | Cron: polls Spot API |
| `DayTimes.php` | Cron: daily ops summary email |
| `ArchiveTracks.php` | Cron: archives old tracks |
| `TracksRemoveRedundant.php` | Maintenance: removes redundant track data |
| `CookSpot.php` | Cron: processes Spot data |
| `cleanup-bookings.php` | Cron: cleans old bookings |
| `apiParticlejsonv1.php` | External callback: Particle device data ingestion |
| `btraced.php` | External callback: bTraced tracking data |

---

## SMS/Text System

**Files to Delete:**
- `texts-list.php` — Already listed in Priority 1
- `texts-list-last-200.php` — Already listed in Priority 1
- `message-delete.php` — Already listed in Priority 1
- `SendTxt.php` — Cron job, may still process email queue (verify)

**What Happens:**
- Removes confusing "texts" terminology
- Keeps email broadcast working
- Simplifies messaging flow

---

## Unused Database Tables

### Confirmed Dead (Safe to Drop)

These tables have zero SQL references in PHP code. Some have already been dropped by Laravel migrations.

| Table | Status |
|-------|--------|
| `address` | Already dropped by migration |
| `address_type` | Already dropped by migration |
| `airspace` | Already dropped by migration |
| `airspacecoords` | Already dropped by migration |
| `testy` | Already dropped by migration |
| `controllers` | No files, no code references |
| `switches` | No files, no code references |
| `msg` | No files, no code references |
| `msguser` | No files, no code references |
| `vouchers` | No files, no code references |
| `vouchertype` | No files, no code references |

### Still Active (Do Not Delete Yet)

These tables have active queries in billing and maintenance code. Need refactoring first.

| Table | Reason Still Active |
|-------|-------------------|
| `incentive_schemes` | Queried in `Treasurer2.php` → `MemberScheme()` and `orgs/*/accountrules.php` → `CalcGliderCharge()` |
| `scheme_subs` | Same as above, plus `maintenance/duplicates_delete.php` references it |

### Former Dead Code Files (Already Deleted)

| File(s) | Status |
|---------|--------|
| `vouchers.php`, `vouchers-list.php`, `vouchertype.php`, `vouchertype-list.php` | Already deleted (voucher system never implemented) |
| `airspace.php`, `airspace-list.php`, `airspacecoords.php`, `airspacecoords-list.php` | Already deleted (files not found in scan — tables dropped by migration) |
| `controllers.php`, `controllers-list.php`, `switches.php`, `switches-list.php` | Already deleted (files not found in scan) |
| `msg.php`, `msguser.php` | Already deleted (files not found in scan) |

---

## Unreferenced API

| File | Confidence | Notes |
|------|-----------|-------|
| `api/js-debug.php` | **HIGH** — Dead | Has `.htaccess` route but zero callers in any PHP/JS file |

All other 11 API files are actively referenced by front-end code.

### Missing Route

`api/member-form.php` is called via direct path `/api/member-form.php` from `members-new.php` but has no clean URL RewriteRule in `.htaccess`. Works currently because Apache serves existing files directly.

---

## Routes Missing From .htaccess

Routes documented in `ROUTES.md` but missing RewriteRules:

| Route | Target |
|-------|--------|
| `/Audits` | `audit-list.php` |
| `/api/member-form` | `api/member-form.php` |

---

## Laravel Installation (lrv/)

**Status:** ~95% dead code

**What's Used:**
- `lrv/routes/web.php` — 2 routes (allFlightsReport, membersRolesStatsReport)
- `lrv/app/Models/*` — Eloquent models used via `load_model.php`
- Some Laravel migrations

**What Can Be Deleted:**
- All controllers (except maybe API ones)
- All views
- Most routes
- Unused models
- Homestead/Vagrant configs
- Most of vendor/

**Decision:** Either delete entire `/lrv/` and rebuild properly later, or keep minimal (models + 2 routes) and clean up rest.

---

## Private Folder

**Path:** `/private/`

**Contents:** `Reports.php`, `DumpTable.php`

**Status:** No internal references found in scans. Likely dead. Verify `private/Reports.php` is not used before deleting.

---

## Files Already Deleted (2026-05-18 Session)

Cleaned up during current session:
- `Treasurer.php` — Deleted, replaced by TreasurerReportNew3/4
- `Treasurer-save.php` — Deleted, dead code (no routes, no references)
- `TreasurerReportNew.php` — Deleted (was Option 1)
- `TreasurerReportNew2.php` — Deleted (was Option 2)

## Files Already Deleted (2026-06-04 Session)

Cleaned up during current session:
- `role_member-list.php` — Deleted (at `/AssignRoles`), unused UI for role assignments
- `role_member.php` — Deleted (at `/AssignRole`), unused add/edit form for role assignments
- Routes `/AssignRoles` and `/AssignRole` removed from `.htaccess`
- Link "Role Assignment" removed from `home.php`
- `role_member` schema block removed from `GlidingSchema.txt`
- `duty.php`, `duty-list.php`, `dutytypes.php`, `dutytypes-list.php` — Deleted along with routes `/Roster`, `/Rosters`, `/DutyType`, `/DutyTypes`
- Duty queries removed from `home.php`, `FlyingNow.php`, `todayxml.php`
- "Duty Types" link removed from `home.php`
- `duty` and `dutytypes` database tables dropped via migration
- `duty` UPDATE/FK references removed from 3 maintenance files
- `FlyingNow.php` — Deleted (at `/FlyingNow`), unreferenced auto-refresh status page
- `map/MasterDisplay.php` — Deleted (at `/wgc-old`, `/ssb`, `/cgc`, `/agc`), old Google Maps map
- `map/MasterDisplayNew.php` — Deleted (at `/wgc-mixed`), old single-file Leaflet map
- `map/map.css`, `map/map.js`, `map/mapiconmaker.js` — Deleted, support files for old maps
- `map/PAP_LONG_24P.cup` — Deleted, waypoint file for old map
- Routes `/wgc-old`, `/wgc-mixed`, `/ssb`, `/cgc`, `/agc` removed from `.htaccess`
- Old map links for orgs 2-4 removed from `home.php`
- All unused heading variants (heading1/3/4/6 .txt & .css) deleted from all 5 org dirs and root
- `FlyingNow.php`, `organisations-list.php`, `organisations.php` converted to heading2 header
- `dailysheet.php`, `StartDay.php`, `EditDailySheet.php` also converted to heading2 earlier
- `todayxml.php` — Deleted, replaced by `api/daily-flights.php?tracks=1`
---

## Caution

**Before Deleting Anything:**
1. Check for references in code
2. Check for data in tables
3. Backup database
4. Test on non-production first

**When in Doubt:** Leave it for now, document as "verify later"

---

## What NOT to Delete

- Core operational files (DailySheet, flights, members, etc.)
- Configuration files
- Helpers that are actually used
- Org-specific customizations (orgs/*)
