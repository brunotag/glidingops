# Booking System

## Overview

Build a booking system in Gliding Ops where club members can book flying days. The system syncs to the existing Google Calendar (write-only) — the calendar is the single source of truth for display. Google Form bookings (trial flights, etc.) appear in the list because we read from the calendar. Members can edit/delete their own bookings.

**Key principle:** Google Form + Google Calendar remain for unauthenticated users. Gliding Ops adds self-service for logged-in club members.

---

## Requirements

1. Logged-in club members can book a flying day from Gliding Ops
2. Bookings sync to the same Google Calendar as the Google Form **(write-only — calendar is truth for display)**
3. Members can edit/delete their own bookings (admin can edit/delete any)
4. Only a date is required — no time selection (sequence determined by Google Calendar)
5. Each booking is a 1-minute Google Calendar event starting from 9am in sequence
6. No voucher/trial flight fields (Google Form only)
7. Page shows ALL future bookings (from both Google Form and Gliding Ops), grouped by date, in schedule/agenda view. No past bookings shown.

---

## Data Fields

Matches the Google Form minus voucher/trial:

| Field | Type | Notes |
|-------|------|-------|
| Date | date | The flying day |
| Intentions | textarea | Simple free text — no radio buttons, not used for analytics |
| Glider | radio + free text | Options: "DG-1000", "GMB", "GNB", "Other" (reveals text input) |
| Notes | textarea | Optional free text |

For logged-in members, `name` is derived from their member record.

---

## Database

### Migration: Drop legacy tables, create new `bookings` table

The existing `bookings` and `bookingtypes` tables have 27 and 9 stale rows respectively. No UI references them. `maintenance/duplicates_delete.php` references `bookings` — we'll update it.

**USER:** Are we sure no other process reads these tables?
**OP:** Only `maintenance/duplicates_delete.php` (member merge tool) references `bookings` in the codebase. No cron jobs, no other PHP files. Safe to drop after updating that one file.

New schema via Laravel migration:

```sql
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `intention` text DEFAULT NULL,
  `aircraft_rego` varchar(6) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `google_event_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_org_date` (`org`, `booking_date`),
  KEY `idx_member` (`member_id`),
  FOREIGN KEY (`org`) REFERENCES `organisations` (`id`),
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

**Notes:**
- `member_id` is NOT NULL — every booking in our DB is linked to a member
- `aircraft_rego` is free text (varchar(6) matches aircraft.registration length), not a FK
- No `sequence` — the Google Calendar event start time determines order
- `google_event_id` links our record to the Calendar event for edit/delete sync
- `deleted` is a boolean flag (soft delete) — lets us recreate the event if it was deleted from the calendar but not our DB
- No `source` column — we only store Gliding Ops bookings; Google Form bookings are read from the calendar at display time

### Model

`lrv/app/Models/Booking.php`:
- `$table = 'bookings'`
- `$timestamps = true`
- `$casts = ['deleted' => 'boolean']`
- Relationships: `member()`, `organisation()`

---

## Google Calendar Integration

### Credentials

**USER:** Done.

**USER:** How do I get a service account key?
**OP:** Here are the steps:

1. Go to https://console.cloud.google.com/ → Create a project (or select existing)
2. Enable **Google Calendar API** from the library
3. Go to **IAM & Admin** → **Service Accounts** → **Create Service Account**
   - Name: `gliding-ops-booking`
   - Click **Done**
4. Select the new service account → **Keys** tab → **Add Key** → **Create New Key** → **JSON**
   - A `.json` file downloads automatically
5. Copy this file to `lrv/storage/google-calendar-key.json` on the Vagrant box
6. Share the WGC Bookings calendar with the service account email (found in the JSON as `client_email`)

The calendar ID is `[see _secrets.md]`.

Storage:
- Key file: `lrv/storage/google-calendar-key.json` (gitignored — add to `.gitignore`)
- Calendar ID: in `lrv/.env` as `GOOGLE_CALENDAR_ID=[see _secrets.md]`
- Both exposed via `config/site.php` for access from vanilla PHP

### Service Class

`lrv/app/Services/GoogleCalendarService.php`

**OP:** `google/apiclient` is needed via Composer.  
**USER:** Composer works on Vagrant. Run: `cd lrv && composer require google/apiclient`.

| Method | Purpose |
|--------|---------|
| `getEventsForDateRange(string $startDate, string $endDate): array` | List all events across a date range (for the "all future bookings" view) |
| `createEvent(string $date, int $seq, string $summary, string $desc): string` | Create 1-min event, return event ID |
| `updateEvent(string $eventId, string $summary, string $desc): void` | Update event text |
| `deleteEvent(string $eventId): void` | Remove event from calendar |
| `getNextSequence(string $date): int` | Count events for date, return next sequence number |

### Event Format

```
Summary:  [[DisplayNameOfMember]] - [[GliderRegistration]] - [[Intentions]] ([[Notes]])
Example:  Fred Gordon - DG-1000 - To Solo (bring the good glider)

Description:
glider: DG-1000
intentions: To Solo
details: bring the good glider

Start:    2026-05-15T09:03:00+12:00   (09:00 + seq * 1 min)
End:      2026-05-15T09:04:00+12:00   (1 minute later)
```

### Sequence Algorithm

1. Query Google Calendar for all events on `booking_date`
2. Count them: `nextSeq = events.length + 1`
3. New event start time = `09:00 + (nextSeq - 1) * 1 min`

**Q:** Should we also store sequence in Calendar metadata for re-sync?
**USER:** No, the hour gives us the sequence.

### Syncing Approach

- **Create:** Write to DB + create Calendar event, store `google_event_id`
- **Update:** Update DB + update Calendar event by ID
- **Delete:** Soft-delete in DB (`deleted = 1`) + delete Calendar event by ID
- **Display:** Read ALL events from Calendar for future dates. Match our DB records by `google_event_id` to determine which are editable/deletable.

**Edge case — deleted in Calendar but not in DB:** Google Calendar wins. Mark our booking as `deleted = 1` so we don't try to manage it.

---

## Frontend Page

### Page: `bookings.php`

Vanilla PHP + jQuery/Bootstrap, following `MessagingPage.php` pattern. Auth: `$_SESSION['security'] & 1`.

### Layout

Show ALL future bookings, grouped by date, in chronological order. No past bookings.

```
+---------------------------------------------------+
|  [+ Add Booking]                                  |
|                                                    |
|  15 May 2026                                       |
|    #1    John Smith     DG-1000    To Solo        [✏️][🗑️]
|    #2    (trial)        GMB        Trial Flight        ← Google Form, read-only
|    #3    Jane Doe       GNB        To Soaring     [✏️][🗑️]
|                                                    |
|  16 May 2026                                       |
|    #1    (trial)        DG-1000    Trial Flight        ← Google Form, read-only
|                                                    |
|  18 May 2026                                       |
|    #1    Bob Jones      DG-1000    Currency       [✏️][🗑️]
+---------------------------------------------------+
```

Key behaviours:
- No date picker — just show everything upcoming
- Dates with no bookings don't appear
- Google Form bookings are read from Calendar, shown but NOT editable
- Edit/Delete buttons only on own bookings (matched by `google_event_id` in our DB with `member_id = $_SESSION['memberid']`)
- Admin sees Edit/Delete on ALL bookings

### CRUD Actions

All via POST to `bookings.php?action=X`, return JSON.

| Action | Auth | Logic |
|--------|------|-------|
| `create` | Any member | Validate, get next seq via `GoogleCalendarService::getNextSequence()`, insert DB, create Calendar event, save `google_event_id` |
| `update` | Owner or Admin | Update DB + update Calendar event |
| `delete` | Owner or Admin | Soft-delete DB (`deleted = 1`) + delete Calendar event |

### Display Logic (Page Load)

1. `GoogleCalendarService::getEventsForDateRange(today, +90 days)` → all events
2. Query DB for all `google_event_id`s that match
3. Build combined list: each event has `isOurs` flag (true if `google_event_id` is in our DB and `deleted = 0`)
4. Group by date, sort by start time

---

## Route

```
RewriteRule ^Bookings$ bookings.php [L,QSA]
```

---

## Homepage Link

In `home.php`, add "Bookings (new - WIP)" link alongside the existing external booking link (until the new system is verified in production).

---

## Files Created

| File | Purpose |
|------|---------|
| `lrv/database/migrations/2026_05_15_000001_create_bookings_table.php` | Drop legacy + create new bookings table |
| `lrv/app/Models/Booking.php` | Eloquent model |
| `lrv/app/Services/GoogleCalendarService.php` | Google Calendar API wrapper |
| `bookings.php` | Frontend page |
| `cleanup-bookings.php` | Cron script — hard-delete bookings older than 30 days |
| `config/google-calendar.php` | Calendar ID + key path (gitignored via `config/*.php`) |
| `config/google-calendar.php.sample` | Template for the above |

## Files Modified

| File | Change |
|------|--------|
| `.htaccess` | Added `RewriteRule ^Bookings$ bookings.php` |
| `home.php` | Added "BOOKINGS (NEW - WIP)" link |
| `lrv/composer.json` | Added `google/apiclient` dependency |
| `maintenance/duplicates_delete.php` | Updated from old `bookings.member` to `bookings.member_id` |
| `load_model.php` | Reverted to original (no changes needed) |

---

## Edge Cases

| Scenario | Handling |
|----------|----------|
| Google Calendar API unreachable | Save to DB, skip Calendar sync, log error. UI shows success with a warning. The event will be missing from the calendar — admin can retry. |
| Two people book simultaneously | `getNextSequence()` could return same seq. Fallback: if Calendar create returns 409 (already exists at that time), retry with `seq + 1`. |
| Edit/delete after booking date | Bookings aren't shown past their date, so edit/delete not possible (no UI for past bookings). |
| Google Form creates events at the same time as us | Form creates events independently at 9:00, 9:01... Our `getNextSequence()` reads the calendar so we always slot after existing events. |
| Deleted in Calendar but not in DB | On next display load, if we can't find our `google_event_id` in the Calendar results, mark booking as `deleted = 1`. Won't try to manage it. |
| Deleted in DB but event still in Calendar | On next user action for that booking (if somehow visible), delete the orphaned Calendar event. |
| Member deletes account | Member merge tool (`maintenance/duplicates_delete.php`) handles `bookings.member_id` FK cleanup. |

---

## Auto Delete

Old bookings (30 days) will be automatically deleted via cron job. Hard deletion, not soft deletion.

## Technical Notes

- **Google API PHP Client v1.1.5** — old library, works with service account JSON keys via `Google_Auth_AssertionCredentials` + PEM private key with password `[see _secrets.md]`. Generates deprecation warnings on PHP 8.x (harmless — interface mismatches for `ArrayAccess`, `Iterator`, `Countable`). Suppress in `php.ini` or ignore.
- **`config/*.php` is gitignored** — `config/google-calendar.php` must be created manually on each deployment from `config/google-calendar.php.sample`
- **Service account key** goes in `lrv/storage/google-calendar-key.json` (also gitignored via `lrv/storage` in root `.gitignore`)

## Open Questions

- [x] ~~How should "Other" intention free text be stored?~~ → Intention is always free text (simple textarea), no radio buttons needed.
- [x] ~~Can `composer` run on the Vagrant box?~~ → Yes.

## Decisions Log

| Date | Decision |
|------|----------|
| 2026-05-15 | Intention = free text textarea, not radio buttons |
| 2026-05-15 | Composer available on Vagrant |
| 2026-05-15 | Old bookings hard-deleted after 30 days via cron |
