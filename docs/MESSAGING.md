# Messaging System

## Overview

The messaging system has evolved over time and now consists of:
1. **Broadcast messaging** (active) - Email to members
2. **SMS layer** (dead code) - Intended for SMS, never implemented
3. **Fake Twitter** (active) - Website message board

---

## What Actually Works

### Broadcast Email (Active)

**Flow:**
1. Admin visits `MessagingPage.php`
2. Types message (160 char limit - legacy)
3. Selects recipients by role (Instructors, Tow Pilots, etc.)
4. Or selects "Fake Twitter" (broadcast to all active members)
5. Submit → Creates `messages` record
6. For individual members → Also creates `texts` record with phone number

**Actually Sends:**
- Emails to members via `SendTxt.php` cron job
- No actual SMS - just converts "texts" to emails

### Fake Twitter (Active)

**Flow:**
1. Broadcast message with "Fake Twitter" option
2. Message stored in `messages` table with `is_broadcast = true`
3. `messages-list.php` displays on homepage
4. Styled like Twitter feed

**Usage:** Displayed in iframe on home.php:
```php
<iframe src="/messages-list.php?org=1" ...>
```

---

## Dead Code - SMS System

### What Was Intended

- Store phone numbers in `texts.txt_to`
- Route via SMS gateway (Twilio, Nexmo, etc.)
- Track delivery status in `txt_status`

### What Actually Happened

- No SMS gateway ever integrated
- `txt_status` values: 0 (pending), 1 (sent), 2 (error), 3 (sent via email)
- Code uses status 3 to mean "converted to email"
- `txt_timestamp_sent` and `txt_timestamp_recv` never populated

### How It Works Now

1. MessagingPage calls `CreateTextRecord()`:
   - Creates `texts` record with member's phone number
   - Status = 0 (pending)

2. SendTxt.php cron job:
   - Queries texts where txt_status = 0
   - Looks up member's email from `members` and `users` tables
   - Sends email instead of SMS
   - Updates status to 3 (sent via email)

---

## Database Tables

### messages (Active)
```sql
id, org, create_time, msg (160 char)
txt_sender_member_id, is_broadcast
```

Used for:
- Storing broadcast messages
- Display on homepage

### texts (Mostly Dead)
```sql
txt_id, txt_unique, txt_msg_id, txt_member_id
txt_to (phone number), txt_status
txt_timestamp_create, txt_timestamp_sent, txt_timestamp_recv
```

**Issues:**
- txt_to stores phone but not used for SMS
- txt_timestamp_sent/recv never populated
- txt_status misused (3 means email, not "sent")

### members.enable_text (Dead Field)
- Stored in database but never checked/used
- Checkbox on member form suggests SMS opt-in

---

## Files Involved

### Active Files
| File | Purpose |
|------|---------|
| MessagingPage.php | Create broadcast message |
| messages-list.php | Display on homepage |
| SendTxt.php | Process queue → send emails |

### Dead Files (Can Delete)
| File | Purpose |
|------|---------|
| texts.php | Manual text management (confusing) |
| texts-list.php | List of text records (misleading) |
| texts-list-last-200.php | Redundant list, misleading label |

---

## Recommended Cleanup

### Delete These Files
- texts.php
- texts-list.php  
- texts-list-last-200.php

### Optional Cleanup
1. **Remove SMS columns** from texts table:
   - txt_timestamp_sent
   - txt_timestamp_recv
   
2. **Simplify MessagingPage**:
   - Remove CreateTextRecord() calls
   - Only use messages table
   - Direct email send instead of queue

3. **Remove dead fields** from members:
   - enable_text (never checked)

4. **Keep for compatibility**:
   - texts table (as historical)
   - SendTxt.php (works as email converter)

---

## Alternative: If Real SMS Needed

If you want actual SMS in future:
1. Integrate Twilio or similar
2. Store in texts table properly
3. Populate txt_timestamp_sent/recv
4. Check enable_text before sending