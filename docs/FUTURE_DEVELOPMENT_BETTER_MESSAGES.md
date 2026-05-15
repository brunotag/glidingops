# Better Messaging System

## Problem

Current MessagingPage.php issues:
- Long checkbox grids for member selection (unwieldy)
- No search/filter for finding members
- Sender unaware of email failures (state stays "pending")
- Silent failures for some recipients
- No confirmation before send

Current multi-stage flow:
1. MessagingPage → creates `messages` + `texts` records → POSTs to SendTxt.php
2. SendTxt.php → processes immediately → sends emails → redirects back
3. User has no feedback on failures

---

## Goals

1. **Direct send** - No intermediate queue, send immediately
2. **Failure visibility** - Sender must know which recipients failed
3. **Confirmation before send** - Preview recipients before sending
4. **Easy recipient selection** - Search members, not checkbox grids
5. **Mailing lists** - Pre-configured Google Groups (5-10 hardcoded)
6. **Progress feedback** - User sees real-time progress of email sending
7. **Build ex novo** - New MessagingPage.php, don't adapt old code

---

## New Flow

### Step 1: Compose
- User types message in textarea (500 char limit, live counter)
- Searches for members by name → autocomplete → [Add] → adds to Recipients
- Clicks mailing list button → adds Google Group email to Recipients
- [ ] Also post to Fake Twitter checkbox
- [Preview & Send] button (disabled until: message.length > 0 AND recipients > 0)

### Step 2: Preview Modal
```
+------------------------------------------+
| Confirm Send                             |
|                                          |
| Message will be sent to 15 recipients:  |
|                                          |
| - john@email.com                        |
| - jane@email.com                        |
| - safety@club.org                        |
| ...                                      |
|                                          |
| [Cancel]              [Send Now]         |
+------------------------------------------+
```

### Step 3: Send with Progress UI
**PHP Side:**
- `MessagingPage.php` receives `send=1` POST
- Creates `messages` record with `is_broadcast` if Fake Twitter
- Streams progress back via chunked output
- For each recipient, calls `Mail::SendMail()` with try/catch
- Collects per-recipient success/failure

**JavaScript Side (Progress):**
```
Sending to 15 recipients...
[████████████░░░░░░░░] 45% (7/15)

Sending to: john@email.com ✓
Sending to: jane@email.com ✓
Sending to: invalid-email ✗ (invalid email)
Sending to: safety@club.org ✓
...
```

- Modal with animated progress bar
- Real-time list of emails being processed below bar
- Green checkmark for success, red X for failure with reason

### Step 4: Results Display (Inline Alert)
```
+------------------------------------------+
| ✓ Message sent successfully!            |
|                                          |
| Sent: 13 recipients                     |
| Failed: 2                               |
|   - invalid-email: Invalid email address |
|   - full-mailbox: Mailbox full           |
|                                          |
| [Done]                                   |
+------------------------------------------+
```

---

## Layout

```
+----------------------------------+------------------------+
| Message                          |  Recipients (X)        |
| [___________________________]    |  - Name <email> [x]   |
| 0/500                            |  - Name <email> [x]    |
|                                  |  - list@email [x]      |
+----------------------------------+------------------------+
| Search: [name...]           [Add] |                        |
|                                  |                        |
| Mailing Lists:                   |                        |
| [WGC Committee] [WGC Instructors Team] [WGC LPC Group]   |
| [WGC Members] [WGC Service Delivery] [WGC Winch Group]  |
| [WWGC Cable Car] [Official Observers]                   |
|                                  |                        |
| [ ] Also post to Fake Twitter    |                        |
|                                  |                        |
|        [Preview & Send]          |                        |
+----------------------------------+------------------------+
```

**Email display format:**
- Members: `Name <email@domain.com>` (Gmail style)
- Groups/lists: `list@email.com` only

---

## Hardcoded Mailing Lists

Google Groups email addresses:

```php
$mailing_lists = [
    'WGC Committee'          => '[see _secrets.md]',
    'WGC Instructors Team'  => '[see _secrets.md]',
    'WGC LPC Group'         => '[see _secrets.md]',
    'WGC Members'            => '[see _secrets.md]',
    'WGC Service Delivery'   => '[see _secrets.md]',
    'WGC Winch Group'        => '[see _secrets.md]',
    'WWGC Cable Car'         => '[see _secrets.md]',
    'Official Observers'     => '[see _secrets.md]',
];
```

(Actual email addresses to be configured)

---

## Files to Create/Modify

| File | Action | Purpose |
|------|--------|---------|
| `MessagingPage.php` | Rewrite ex novo | New UI, JS for progress, AJAX send with streaming |
| `helpers/mail.php` | Modify | Add `SendMailToRecipients()` returning success/failed |
| `api/members-email.php` | Create | Autocomplete member search by name |

---

## Database Changes

**messages table (existing):**
```
id, org, create_time, msg, txt_sender_member_id, is_broadcast
```
- Already exists ✓
- Save all messages for history and Fake Twitter
- is_broadcast = 1 when posted to Fake Twitter

**texts table:**
- Not needed for direct send (but keep for backward compatibility with existing records)

---

## Mail Function Changes

### Current (helpers/mail.php)

```php
Mail::SendMailPlainTextReplyTo($email_to, $subject, $msg, $email_from)
```

### New

```php
Mail::SendMailToRecipients($recipients, $subject, $msg, $email_from)
// $recipients: array of {email: string}
// Returns: {
    success: int,           // count sent
    failed: array of {email: string, reason: string},  // count failed
    total: int              // total attempted
}
```

**Behavior:**
- Takes array of email addresses
- Tries to send to each
- Returns success/failure per recipient
- Uses try/catch per recipient

---

## API Endpoints

### `api/members-email.php`

**Request:** `GET /api/members-email.php?search=john`

**Response:**
```json
[
  { "name": "John Smith", "email": "john.smith@email.com" },
  { "name": "John Doe", "email": "john.doe@email.com" }
]
```

**Behavior:**
- Search members by name (surname OR firstname LIKE %search%)
- Return top 10 matching with email addresses
- Exclude members without valid email

---

## Recipient Types

1. **Individual member** - added via search
   - Look up email from members table via API
   - Send directly to member's email

2. **Google Group** - added via mailing list button
   - Email address is the Google Group address
   - Google handles distribution to group members
   - No member lookup needed

---

## Progress UI Details

### Implementation Approach: Streaming Response

**Why streaming?**
- Real-time feedback feels more responsive
- User sees that something is happening during the send
- Individual email statuses show exactly what went wrong

**How it works:**
1. JS opens XMLHttpRequest to MessagingPage.php with `fetch()` or `XMLHttpRequest`
2. PHP starts output buffering, sends headers, then flushes
3. For each email processed, PHP outputs a JSON chunk: `{"email":"x","status":"success|failed","reason":"y"}\n`
4. JS reads streaming response, parses each line, updates UI
5. On completion, PHP sends final chunk: `{"done":true,"success":13,"failed":2}`

### Progress Bar
- Animated CSS progress bar
- Shows percentage and count: "45% (7/15)"
- Updates in real-time as each email completes

### Email Status List
```
john@email.com ✓ Sent
jane@email.com ✓ Sent
bad-email ✗ Failed - Invalid email address
safety@club.org ✓ Sent
```

---

## Testing Checklist

- [ ] Search member "John" → returns matching members with emails
- [ ] Add member to recipients → appears in list with [x]
- [ ] Remove member → disappears from list
- [ ] Click mailing list button → adds group email to list
- [ ] Preview modal shows correct count
- [ ] Progress bar animates during send
- [ ] Each email shows success/failure as processed
- [ ] Send to individual with valid email → success shown
- [ ] Send to invalid email → failure shown with reason
- [ ] Fake Twitter checkbox → message saved with is_broadcast=1
- [ ] Message > 500 chars → prevented
- [ ] No message or no recipients → Preview button disabled
- [ ] Final results show correct success/failure counts

---

## Rejected Ideas

- **Role bulk selection** - Not needed, selecting individuals is the main goal
- **Dynamic groups from DB** - Keep simple, hardcoded Google Groups
- **Async queue with cron** - Direct send is simpler and gives immediate feedback
- **Adapting old MessagingPage.php** - Build ex novo for cleaner code