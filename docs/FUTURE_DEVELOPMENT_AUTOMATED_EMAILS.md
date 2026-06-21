# Automated Daily Recap Emails

**STATUS: IMPLEMENTED** — Recap emails are sent on finalising a day via `CompletedSheet.php`. SMTP via PHPMailer replaces `mail()`.

## Overview

Replace the current ad-hoc email system with a proper daily recap sent automatically to every member who flew that day. Currently, `CompletedSheet.php` can send flight summaries when a day is manually finalised, and `DayTimes.php` sends a GPS-based summary to a single ops manager at 6AM. Neither sends a proper recap to all members.

**Goal:** Each member who flies on a given day receives an email at the end of that day with their flight details.

---

## Current State

### Existing email senders

| File | What it sends | Recipient | Trigger |
|------|--------------|-----------|---------|
| `DayTimes.php` | GPS-track-based flight times table (not from flights DB) | Single ops manager | Cron 6AM daily |
| `CompletedSheet.php` | Individual flight summary per member who flew | Each member with `enable_email=1` | Manual (visit CompletedSheet page) |
| `Mail` class | `mail()`-based, hardcoded From/Reply-To | Per caller | Various |

### Pain points

1. **No automated member recap** — must manually finalise the day to trigger emails
2. **DayTimes.php uses GPS tracks**, not the `flights` table — can be inaccurate and duplicates effort
3. **CompletedSheet emails only send per-member** (one email per flight per member) instead of a consolidated recap
4. **`mail()` delivery** — no SMTP, no tracking, no templates
5. **Hardcoded addresses** — `machinery.gops@wwgc.co.nz`, `servicedelivery@wwgc.co.nz` in `helpers/mail.php`
6. **MessagingPage.php mail call is commented out** — the broadcast messaging UI doesn't actually send

---

## Proposed System

### Daily Recap Email

A single email per member at end of day showing all their flights:

```
Subject: Your WWGC Flying Recap — 15 May 2026

Hi John,

Here's your flying for today at Greytown:

  #1  DG-1000  PIC   12:34  (45 min)  To 3,000ft  comments...
  #2  GNB      P2    14:20  (30 min)

Total flight time today: 1h 15m

See your full history at: https://gops.wwgc.co.nz/MyFlights
```

### Two trigger modes

1. **Automatic (cron):** Runs at e.g. 8PM daily, sends recap to all members who flew that day and have `enable_email=1`
2. **On finalise (optional):** Keep the current CompletedSheet behaviour but upgrade the email format

---

## Implementation Plan

### Phase 1: Database

Add a `recaps` table to track which recaps have been sent (avoid duplicates):

```sql
CREATE TABLE recaps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  localdate INT NOT NULL,      -- YYYYMMDD
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id),
  UNIQUE KEY (member_id, localdate)
);
```

**Q:** Or reuse the existing `members.localdate_lastemail` column? It's already used by CompletedSheet for the same purpose.

### Phase 2: Cron Script

Create `send-recap.php`:

```
0 20 * * * php /path/to/send-recap.php -o 1
```

Logic:
1. Query `flights` for today's date: `localdate = YYYYMMDD`
2. Collect unique member IDs from `pic` and `p2`
3. For each member with `enable_email=1` and no existing recap for today:
   - Gather their flights (glider, duration, PIC/P2, height, comments)
   - Build HTML email
   - Send via `Mail::SendMailHtml()`
   - Insert into `recaps` table

### Phase 3: Improved Email Templates

Build a proper HTML email template in `helpers/email-templates.php`:

- `recapEmail($member, $flights, $date)` — returns HTML string
- `flightSummaryRow($flight)` — single flight row
- Replaces the current inline-HTML-in-PHP-string pattern

**Q:** Use a simple PHP template (included HTML with `<?=` tags) or keep string building?

### Phase 4: SMTP Upgrade (Optional)

Replace `mail()` with SMTP via PHPMailer or Symfony Mailer for:
- Better deliverability (SPF, DKIM)
- HTML email support
- Attachment support
- BCC support

**Q:** Worth the dependency, or keep `mail()` for now?

---

## Data Sources

### Flights query pattern (from `api/myflights-data.php`):

```sql
SELECT f.localdate, f.glider, f.height, f.pic, f.p2, f.comments, 
       f.launchtype, f.location, f.start, f.land, f.seq,
       a.make_model, m.displayname as pic_name
FROM flights f 
LEFT JOIN aircraft a ON a.rego_short = f.glider AND a.org = f.org
LEFT JOIN members m ON f.pic = m.id
WHERE f.type = 1 
  AND f.localdate = $today
  AND (f.pic = $memberId OR f.p2 = $memberId)
  AND f.deleted = 0
ORDER BY f.seq ASC
```

### Duration calculation:
```php
$durationMinutes = floor(($land - $start) / 60000);
```

### Member filtering:
```php
// Only active members with email opt-in who haven't been emailed today
WHERE enable_email > 0 AND localdate_lastemail <> $today
```

---

## Files to Create

| File | Purpose |
|------|---------|
| `lrv/database/migrations/YYYY_MM_DD_HHMMSS_create_recaps_table.php` | Recap tracking table |
| `send-recap.php` | Cron script for daily recap |
| `helpers/email-templates.php` | HTML email template functions |

## Files to Modify

| File | Change |
|------|--------|
| `helpers/mail.php` | Update hardcoded addresses, optionally add SMTP |
| `CompletedSheet.php` | Optionally delegate to new recap system |
| `docs/CRONS.md` | Add new cron entry |

---

## Questions and Decisions

**Q:** Use `members.localdate_lastemail` or new `recaps` table for duplicate prevention?
**Q:** Keep `mail()` or add PHPMailer/Symfony Mailer for SMTP?
**Q:** Simple PHP string templates or separated template files?
**Q:** Should the recap only include finalised flights, or all flights for the day?
**Q:** Timezone for "end of day" — use org timezone (Pacific/Auckland)?
