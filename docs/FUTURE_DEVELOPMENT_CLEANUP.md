# Code Cleanup

**STATUS: NOT IMPLEMENTED** — All items below are future work. See `DEAD_CODE.md` for the authoritative list of deletable files.

## Overview

The codebase has accumulated significant technical debt from years of feature work without cleanup. Multiple systems have been replaced but old files remain. This document consolidates the remaining cleanup work.

## E1: Files to Delete

### High Confidence (no remaining references)

| File | Replaced By | Notes |
|------|-------------|-------|
| `texts-list.php` | `texts-list-v2b.php` | Old SMS-era list |
| `texts-list-last-200.php` | — | Redundant with v2b |
| `message-delete.php` | — | Dead SMS layer |
| `members.php` | `members-new.php` | Old member form |
| `members-list.php` | `members-list-v2b.php` | Old member list |
| `edit-my-details.php` | `members-new.php` via /EditMyDetails | Replaced |
| `users.php` | `users-new.php` | Old user form |
| `users-list.php` | `users-list-v2b.php` | Old user list |
| `MessagingPageOld.php` | `MessagingPage.php` | Old messaging page |
| `api/js-debug.php` | — | No callers |

### Medium Confidence (verify first)

| File | Notes |
|------|-------|
| `Heights.php` | Only referenced by `apiglidjsonv1.php` via require |
| `GlidingFlightMap.php` | No references |
| `googlemapsgenerate.php` | No references |
| `MyFlightMap.php` | No references |
| `webcams.php` | No references |
| `tracks-list.php` | No references |
| `audit.php`, `audit-list.php` | Route `/Audits` missing from `.htaccess` |

### Low Confidence (check production server)

| File | Likely Purpose |
|------|---------------|
| `getFlarmTask.php` | Cron — do NOT delete (needs rewrite, see FUTURE_DEVELOPMENT_TRACKING_OPTIMIZATION.md) |
| `GetSpotTask.php` | Cron — verify before touching |
| `DayTimes.php` | Cron — daily ops summary email |
| `ArchiveTracks.php` | Cron — track archival |
| `TracksRemoveRedundant.php` | Maintenance |
| `CookSpot.php` | Cron — processes Spot data |
| `cleanup-bookings.php` | Cron — booking cleanup |
| `apiParticlejsonv1.php` | External callback — Particle data ingestion |
| `btraced.php` | External callback — bTraced tracking |
| `SendTxt.php` | Cron — email queue processing |

## E2: Routes to Clean Up

### Remove from .htaccess

| Route | Target | Reason |
|-------|--------|--------|
| `/Member` | `members.php` | Replaced by `/MemberNew` |
| `/MembersListOld` | `members-list.php` | Replaced by `/AllMembers` |
| `/UsersOld` | `users.php` | Replaced by `/Users` |
| `/UsersListOld` | `users-list.php` | Replaced by `/UsersList` |
| `/MessagingPageOld` | `MessagingPageOld.php` | Replaced by `/MessagingPage` |
| `/texts-list-old` | `texts-list.php` | Replaced by `/texts-list` |

### Remove "Old Version" Links

| Page | Link to Remove |
|------|----------------|
| `members-new.php` | "Old Version" link to `members.php` |
| `members-list-v2b.php` | "Old Version" link to `members-list.php` |
| `users-new.php` | "Old Version" link to `users.php` |
| `users-list-v2b.php` | "Old Version" link to `users-list.php` |
| `MessagingPage.php` | `<li><a href='members-list.php'>Members</a></li>` |

## E3: Database Tables to Drop

### Safe to Drop (via Laravel migration)

| Table | Reason |
|-------|--------|
| `incentive_schemes` | Legacy — 16 schemes, not used in current billing |
| `scheme_subs` | Legacy — 1 subscription, billing ignores it |
| `address` | Already dropped by migration — verify |
| `address_type` | Already dropped by migration — verify |
| `airspace` | Already dropped by migration — verify |
| `airspacecoords` | Already dropped by migration — verify |
| `testy` | Already dropped by migration — verify |

### Keep (still referenced)

| Table | Reason Still Active |
|-------|-------------------|
| `groups` | `maintenance/duplicates_*.php` update FK refs |
| `group_member` | `maintenance/duplicates_*.php` reassign on member merge |

## E4: Incentive Scheme Code Cleanup

### What to remove

Once billing is confirmed working without schemes, remove:

| File | Reason |
|------|--------|
| `incentive_schemes.php` | Edit form |
| `incentive_schemes-list.php` | List page |
| `scheme_subs.php` | Edit form |
| `scheme_subs-list.php` | List page |
| Routes in `.htaccess` | `/IncentiveScheme`, `/IncentiveSchemes`, `/SubsToScheme`, `/SubsToSchemes` |

### Billing code references

| File | Functions to Remove |
|------|---------------------|
| `orgs/*/accountrules.php` | Scheme-related logic in `CalcGliderCharge()` |

## Order of Operations

1. Delete high-confidence files
2. Remove routes from `.htaccess`
3. Remove "Old Version" links
4. Verify nothing breaks
5. Drop dead database tables (via Laravel migration)
6. Remove incentive scheme code
7. Verify with production cron jobs before touching low-confidence files