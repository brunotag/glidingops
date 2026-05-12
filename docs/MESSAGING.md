# Messaging System

## Overview

The messaging system has evolved over time and now consists of:
1. **Broadcast messaging** (active) - Email to members via MessagingPage
2. **SMS layer** (dead code) - Intended for SMS, never implemented
3. **Fake Twitter** (active) - Website message board

---

## What Actually Works

### Broadcast Email (Active)

**Flow:**
1. Admin visits `MessagingPage.php`
2. Types message (160 char limit - legacy)
3. Selects recipients by member search (autocomplete via `api/members-email.php`)
4. Or selects "Fake Twitter" (broadcast to all active members)
5. Submit via AJAX:
   - Creates `messages` record
   - Sends emails synchronously with real-time progress
   - For each successful send, creates `texts` record with `txt_msg_id`, `txt_member_id`, status=3
6. Results shown in modal (success/failure count)

**Note:** `txt_to` is always set to NULL — the `texts` table was originally designed for SMS phone numbers, not emails. Member email is accessible via `txt_member_id → members.id` join.

### Viewing Sent Messages

Two views available:
- **Messages Tree** (`/MessagesTree`) — Collapsible treeview grouped by message. Shows message text, time, Twitter badge, and per-recipient sendings with member name, email, status. Color-coded: green (all OK), amber (some pending/error). Per-sending: green/amber/red status pills.
- **Sent Messages** (`/SentMessages`) — DataTables flat view of all texts records. Searchable, sortable, paginated. Admin-only access.

### Fake Twitter (Active)

**Flow:**
1. Broadcast message with "Fake Twitter" option
2. Message stored in `messages` table with `is_broadcast = true`
3. Messages shown in treeview with a "Twitter" badge

---

## Known Issues

- **`txt_timestamp_create` is unreliable** due to `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` — it resets on any row update. Use `messages.create_time` for message timing.

---

## Files Involved

### Active Files
| File | Purpose |
|------|---------|
| `MessagingPage.php` | Create broadcast message (AJAX send with progress) |
| `messages-tree.php` | Treeview of all messages with per-recipient sendings |
| `texts-list-v2b.php` | DataTables flat view (admin only) |
| `api/texts.php` | DataTables API for texts-list-v2b |
| `api/members-email.php` | Member search autocomplete |
| `SendTxt.php` | Process queue -> send emails |

### Dead Files (Can Delete)
| File | Purpose |
|------|---------|
| `texts.php` | Manual text management (confusing) |
| `texts-list.php` | List of text records (misleading) |
| `texts-list-last-200.php` | Redundant list, misleading label |
| `messaging-page-old.php` | Old messaging page |
| `messages-list.php` | Homepage Twitter feed (replaced by treeview) |
