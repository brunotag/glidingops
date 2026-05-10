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

---

## Solution

### Layout

```
+----------------------------------+------------------------+
| Message                          |  Recipients            |
| [___________________________]    |  - member@email.com [x]|
| 0/500                            |  - member@email.com [x]|
|                                  |  - safety@club.org [x] |
+----------------------------------+  - committee@club.org [x]
| Search: [name...]           [Add] |                        |
|                                  |                        |
| Mailing Lists:                   |                        |
| [Safety] [Committee] [Instructors] [All Pilots] [Social]   |
| [Tow Pilots] [Flight Committee] [Web Committee]           |
|                                  |                        |
| [ ] Also post to Fake Twitter    |                        |
|                                  |                        |
|        [Preview & Send]          |                        |
+----------------------------------+------------------------+
```

### Components

**Left Panel:**
- Textarea: 500 char limit, live counter "0/500"
- Fake Twitter checkbox

**Right Panel (Recipients):**
- List of added recipients (email addresses)
- [x] button to remove
- Empty state: "No recipients yet"

**Bottom Section:**
- Member search: type name → autocomplete → [Add] → adds email to list
- Mailing list buttons (Google Group email addresses)

**Action:**
- [Preview & Send] - disabled until: message.length > 0 AND recipients > 0

### Flow

```
1. Compose:
   - Type message in textarea
   - Search for member → Add → appears in Recipients
   - Click mailing list button → adds group email to Recipients

2. Preview Modal:
   - "Send to X recipients?"
   - Message preview
   - From email shown
   - [Cancel] [Confirm & Send]

3. Send:
   - Direct email send (no queue)
   - Per-recipient try/catch
   - Save to messages table (is_broadcast = 1 if Fake Twitter)

4. Result (inline alert):
   ✓ Sent to 13 recipients
   ✗ Failed for 2:
     - john@email.com (invalid email)
     - jane@email.com (mailbox full)
```

---

## Hardcoded Mailing Lists

Google Groups email addresses:

```php
$mailing_lists = [
    'Safety'           => 'safety@club.org',
    'Committee'        => 'committee@club.org',
    'Instructors'      => 'instructors@club.org',
    'All Pilots'       => 'allpilots@club.org',
    'Social'           => 'social@club.org',
    'Tow Pilots'       => 'towpilots@club.org',
    'Flight Committee' => 'flight-committee@club.org',
    'Web Committee'    => 'web-committee@club.org',
];
```

(Actual email addresses to be configured)

---

## Files to Modify

| File | Changes |
|------|---------|
| `MessagingPage.php` | Complete UI rewrite with autocomplete, preview, inline result |
| `helpers/mail.php` | Add per-recipient try/catch, return failures |

---

## Files to Create

| File | Purpose |
|------|---------|
| `api/members-email.php` | Autocomplete endpoint - returns member emails by name search |

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
- Not needed for direct send
- Can remain for backward compatibility (existing records)

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
- Search members by name
- Return top 10 matching with email addresses
- Exclude members without valid email

---

## Mail Function Changes

### Current (helpers/mail.php)

```php
Mail::SendMailPlainTextReplyTo($email_to, $subject, $msg, $email_from)
```

### New

```php
Mail::SendMailToRecipients($recipients, $subject, $msg, $email_from)
// Returns: { success: ['a@x.com', 'b@x.com'], failed: [{ email: 'c@x.com', reason: 'invalid' }] }
```

**Behavior:**
- Takes array of email addresses
- Tries to send to each
- Returns success/failure per recipient
- Uses try/catch per recipient

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

## Rejected Ideas

- **Role bulk selection** - Not needed, selecting individuals is the main goal
- **Dynamic groups from DB** - Keep simple, hardcoded Google Groups
- **Async queue** - Direct send is simpler and gives immediate feedback

---

## Testing Checklist

- [ ] Search member "John" → returns matching members with emails
- [ ] Add member to recipients → appears in list with [x]
- [ ] Remove member → disappears from list
- [ ] Click mailing list button → adds group email to list
- [ ] Preview modal shows correct count
- [ ] Send to individual with valid email → success
- [ ] Send to invalid email → failure shown inline
- [ ] Fake Twitter checkbox → message saved with is_broadcast=1
- [ ] Message > 500 chars → prevented
- [ ] No message or no recipients → Preview button disabled