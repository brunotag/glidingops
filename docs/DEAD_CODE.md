# Dead Code - Can Be Deleted

## Overview

This application has accumulated technical debt. This document identifies code that can be safely removed.

---

## 1. Laravel Installation (lrv/)

**Path:** `/lrv/`

**Status:** 95% dead code

**What's Used:**
- `lrv/routes/web.php` - 2 routes (allFlightsReport, membersRolesStatsReport)
- `lrv/app/Models/*` - Eloquent models used via load_model.php
- Some Laravel migrations

**What Can Be Deleted:**
- All controllers (except maybe API ones)
- All views
- Most routes
- Unused models
- Homestead/Vagrant configs
- Most of vendor/

**Decision:** Either:
1. Delete entire /lrv/ and rebuild properly later
2. Or keep minimal (models + 2 routes) and clean up rest

---

## 2. SMS/Text System

**Files to Delete:**
- texts.php
- texts-list.php
- texts-list-last-200.php
- message-delete.php (if only used by texts-list-last-200)

**Database Cleanup:**
- texts table can be kept (historical) or dropped
- members.enable_text field - unused

**What Happens:**
- Removes confusing "texts" terminology
- Keeps email broadcast working
- Simplifies messaging flow

---

## 3. Unused Tables (Database)

These tables have no active use - can be dropped:

```sql
-- Check first!
SELECT COUNT(*) FROM address;
SELECT COUNT(*) FROM address_type;
SELECT COUNT(*) FROM controllers;
SELECT COUNT(*) FROM switches;
SELECT COUNT(*) FROM msguser;
SELECT COUNT(*) FROM msg;
SELECT COUNT(*) FROM vouchers;
SELECT COUNT(*) FROM vouchertype;
SELECT COUNT(*) FROM airspace;
SELECT COUNT(*) FROM airspacecoords;
SELECT COUNT(*) FROM testy;
```

If counts return 0, safe to drop.

---

## 4. Unused PHP Files

### Voucher System (Never Implemented)

**Files:**
- vouchers.php
- vouchers-list.php
- vouchertype.php
- vouchertype-list.php

**Database Tables:**
- vouchers (id, org, voucher_code, member, flight, value, expiry)
- vouchertype (id, org, name, value, days_valid)

**Intended Purpose:** Pre-paid flight credits
- Members buy vouchers
- Redeem during flight entry
- System never completed

**Status:** Safe to delete all 4 files + 2 tables (if they exist)

---

### Incentive Schemes & Subscriptions (LEGACY - NOT USED)

**Files:**
- incentive_schemes.php
- incentive_schemes-list.php
- scheme_subs.php
- scheme_subs-list.php

**Database Tables:**
- incentive_schemes (16 defined, all legacy)
- scheme_subs (only 1 active subscription - effectively unused)

**Current State:**
- 0 active subscriptions in production
- 16 incentive schemes defined but no members subscribed
- Logic exists in all 5 orgs/accountrules.php to check subscriptions
- But output is not considered - billing uses standard rates

**Logic in accountrules.php:**
Each org has code like:
```php
// Check specific glider schemes
$q = "SELECT ... FROM scheme_subs LEFT JOIN incentive_schemes ... 
     WHERE member = $memberid AND start <= '$date' AND end >= '$date' 
     AND specific_glider_list LIKE '%$glider%'";

// Check general schemes  
$q = "SELECT ... FROM scheme_subs LEFT JOIN incentive_schemes ...
     WHERE member = $memberid AND start <= '$date' AND end >= '$date'";
```

**What to Delete:**
1. Files: incentive_schemes.php, incentive_schemes-list.php, scheme_subs.php, scheme_subs-list.php
2. Tables: incentive_schemes, scheme_subs
3. Logic in all 5 orgs/accountrules.php that queries scheme_subs

**Verification:** Run Treasurer.php with and without the scheme_subs logic - results should be identical.

---

### Airspace System (Never Completed)

**Files:**
- airspace.php
- airspace-list.php
- airspacecoords.php
- airspacecoords-list.php

**Database Tables:**
- airspace (id, org, name, type, base, top, call-sign)
- airspacecoords (id, airspace_id, lat, lon, order)

**Intended Purpose:** Show airspace on map
- Define controlled airspace regions
- Display warnings on MasterDisplay
- System never fully implemented

**Status:** Safe to delete all 4 files + 2 tables

---

### Hardware Control System (Defunct)

**Files:**
- controllers.php
- controllers-list.php
- switches.php
- switches-list.php

**Database Tables:**
- controllers (id, org, name, type, ip, port, status)
- switches (id, controller_id, name, pin, state)

**Intended Purpose:** Control airfield hardware
- Wind direction indicators
- Lighting control
- Retrieval gate controls
- Never implemented - likely proof-of-concept

**Status:** Safe to delete all 4 files + 2 tables

---

### Internal Messaging (Defunct)

**Files:**
- msg.php (if exists)
- msguser.php

**Database Tables:**
- msg (id, from_user, to_user, subject, body, timestamp)
- msguser (user_id, msg_id, is_read)

**Intended Purpose:** Internal user-to-user messaging
- Replaced by email/MessagingPage
- Never fully implemented

**Status:** Likely safe to delete

---

### Test/Temp Tables

```sql
testy  -- Likely debugging artifact
```

**Status:** Safe to drop if empty

---

## 5. Private Folder

**Path:** `/private/`

**Contents:**
- Reports.php - Appears unused
- DumpTable.php - Diagnostic

**Check:** grep for references:
```bash
grep -r "private/Reports" *.php
```

If no references, can delete.

---

## 6. Duplicate/Orphaned Files

### Root Level
- Some files may be duplicates of others
- Check carefully before deleting

### Track System Duplication
- `/tracks/` folder has duplicate classes
- `/includes/classTracksDB.php` is the active one
- Can verify which is used

---

## 7. Obsolete Code

### Old JS Libraries
- Some in root may be unused
- Check which are actually loaded (jsLibraies.php)

### CSS Files
- Many old stylesheets in root
- orgs/* have duplicate copies
- Check what's actually used

---

## Quick Cleanup Priority

### Priority 1 (Safe - Just Delete Files)
1. Delete texts.php, texts-list.php, texts-list-last-200.php
2. Delete vouchers.php, vouchers-list.php, vouchertype.php, vouchertype-list.php

### Priority 2 (Verify Then Delete)
3. Check private/Reports.php usage
4. Check airspace/controllers tables have no data

### Priority 3 (Assumed - Verify Then Delete)
5. **All billing logic** - See TODO.md section "Billing Calculation - ASSUME BROKEN/UNUSED"
   - incentive_schemes.php, scheme_subs.php and their list files
   - Tables: incentive_schemes, scheme_subs
   - All CalcTowCharge*, CalcGliderCharge, CalcOtherCharges functions in all 5 orgs/accountrules.php
   - Simplify Treasurer.php to just show times, not calculate fees

### Priority 4 (Major Cleanup)
6. Decide on Laravel - delete or properly implement
7. Database table cleanup

---

## Caution

**Before Deleting Anything:**
1. Check for references in code
2. Check for data in tables
3. Backup database
4. Test on non-production first

**Some Files May Be:**
- Occasionally used
- Referenced by cron jobs
- Only accessible to certain users
- Legacy but intentionally kept

**When in Doubt:** Leave it for now, document as "verify later"

---

## 8. Recent Replacements (2026)

### Old MyFlights Page
**File:** `MyFlights.php`
**Replaced by:** `MyFlights.php` (modernized with AJAX loading), `MyFlightsCSV.php`
**Route:** `/MyFlights`
**Links to delete:** Find with `grep -r "MyFlights.php" --include="*.php"`
**Notes:** Still has old tracks DB dependency for non-essential data

### Old Members List
**File:** `members-list.php`
**Replaced by:** `members-list-v2b.php`
**Route:** `/AllMembers` (currently both point to v2b), `/MembersListOld` points to old
**Links to delete:**
- `MessagingPage.php:336` - `<li><a href='members-list.php'>Members</a></li>`

### Old Member Form
**File:** `members.php`
**Replaced by:** `members-new.php`
**Route:** `/Member`
**Links to delete:**
- `members-new.php:140` - link to "Old Version"
- `members-list-v2b.php:145` - link to "Old Version"
- `members.php:654` - form action (self-referential)

### edit-my-details.php
**File:** `edit-my-details.php`
**Replaced by:** `members-new.php` via `/EditMyDetails` route
**Route:** None (internal page)
**Links to delete:** None - no external links found, only self-references
**Notes:** No other files link to it, can be deleted once `/EditMyDetails` is verified working

---

## Old Users System (2026)

### Old Users List
**File:** `users-list.php`
**Replaced by:** `users-list-v2b.php`
**Route:** `/UsersListOld`
**Links to delete:**
- `users-list-v2b.php` - "Old Version" button

### Old Users Form
**File:** `users.php`
**Replaced by:** `users-new.php`
**Route:** `/UsersOld`
**Links to delete:**
- `users-new.php` - "Old Version" button

---

## What NOT to Delete

- Core operational files (DailySheet, flights, members, etc.)
- Configuration files
- Helpers that are actually used
- Org-specific customizations (orgs/*)