# 05 — Messages Tree (Sent Messages)

**Route:** /MessagesTree → messages-tree.php
**Perms:** messages.view (Bruno has it via daily-ops and zzz_god)
**Tools:** Chrome DevTools, plink (SSH)
**Purpose:** Verify the sent-messages tree view loads and displays messages with recipient status

## Steps

1. **Browse to /MessagesTree**

2. **Verify page renders**
   - Visual: "Sent Messages" heading
   - "Table View" link pointing to /SentMessages
   - If no messages exist: "No messages found." empty state

3. **Verify message tree structure**
   - Visual: Each message shown as a collapsible header row
   - Header shows: message text (truncated), timestamp, optional broadcast badge, recipient count
   - Click a header — expands to show individual recipient rows below
   - Recipient rows show: member name, email, status (Pending/Sent/Error/Sent via Email)

4. **Verify status color coding**
   - Visual: Status labels colour-coded:
     - Green: "Sent" or "Sent via Email"
     - Amber: "Pending"
     - Red: "Error"
   - If any messages exist, at least one green status should appear

5. **Verify DB matches UI (spot-check)**
   - SSH: `echo "SELECT m.id, m.msg, m.create_time, m.is_broadcast, COUNT(t.txt_id) FROM messages m LEFT JOIN texts t ON t.txt_msg_id = m.id GROUP BY m.id ORDER BY m.id DESC LIMIT 3;" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: The most recent 3 messages with their recipient counts
   - Cross-check the latest message's text and timestamp with what appears in the tree view

6. **Verify clickable rows**
   - Visual: Click a message header — it smoothly expands/collapses
   - Multiple messages can be expanded simultaneously

## Expected Result
Messages tree renders with expandable rows, colour-coded statuses, correct recipient counts. No PHP errors.

7. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 05 — Messages Tree (Sent Messages)

**Route:** /MessagesTree → messages-tree.php
**Perms:** messages.view (Bruno has it via daily-ops and zzz_god)
**Tools:** Chrome DevTools, plink (SSH)
**Purpose:** Verify the sent-messages tree view loads and displays messages with recipient status

## Steps

1. **Browse to /MessagesTree**

2. **Verify page renders**
   - Visual: "Sent Messages" heading
   - "Table View" link pointing to /SentMessages
   - If no messages exist: "No messages found." empty state

3. **Verify message tree structure**
   - Visual: Each message shown as a collapsible header row
   - Header shows: message text (truncated), timestamp, optional broadcast badge, recipient count
   - Click a header — expands to show individual recipient rows below
   - Recipient rows show: member name, email, status (Pending/Sent/Error/Sent via Email)

4. **Verify status color coding**
   - Visual: Status labels colour-coded:
     - Green: "Sent" or "Sent via Email"
     - Amber: "Pending"
     - Red: "Error"
   - If any messages exist, at least one green status should appear

5. **Verify DB matches UI (spot-check)**
   - SSH: `echo "SELECT m.id, m.msg, m.create_time, m.is_broadcast, COUNT(t.txt_id) FROM messages m LEFT JOIN texts t ON t.txt_msg_id = m.id GROUP BY m.id ORDER BY m.id DESC LIMIT 3;" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: The most recent 3 messages with their recipient counts
   - Cross-check the latest message's text and timestamp with what appears in the tree view

6. **Verify clickable rows**
   - Visual: Click a message header — it smoothly expands/collapses
   - Multiple messages can be expanded simultaneously

## Expected Result
Messages tree renders with expandable rows, colour-coded statuses, correct recipient counts. No PHP errors.


8. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/05-messagetree.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/05-messagetree.md'

---

*Last tested:* (set by step 8 above)