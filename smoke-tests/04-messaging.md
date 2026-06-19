# 04 — Messaging (Broadcast)

**Route:** /MessagingPage → MessagingPage.php
**Perms:** messages.send (Bruno has it via daily-ops and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify message compose form loads and you can construct a message

## Steps

1. **Browse to /MessagingPage**

2. **Verify page renders**
   - Visual: Two-panel layout
   - Left panel: compose form (message text area, subject, mailing list buttons, member search)
   - Right panel: recipient list (empty initially)
   - "Send to Recipients" button

3. **Verify character counter**
   - Type a few characters in the message text area
   - Visual: Character counter below the text area updates (e.g. "N/500")
   - Keep typing past ~400 — counter turns amber then red as it approaches 500

4. **Verify subject auto-generation**
   - Visual: Subject field pre-filled with "WWGC Msg | DDD dd Mon HH:MM AM/PM" format
   - A "Custom Subject" checkbox — tick it to edit subject manually

5. **Test member search autocomplete**
   - Type 2+ characters in the member search field
   - Visual: Autocomplete dropdown appears with matching member names/emails
   - Click a member — name appears as a recipient tag in the right panel

6. **Test mailing list buttons**
   - Click a mailing list button (e.g. "WGC Committee")
   - Visual: All members of that list appear as recipient tags in the right panel (or a confirmation)
   - Click a different list — recipients accumulate

7. **Verify "Fake Twitter" checkbox**
   - Visual: Checkbox labelled "Fake Twitter" or "Post as Broadcast"
   - Toggle it — no visual change but changes `is_broadcast` flag

8. **Verify "Remove All" / individual remove**
   - Visual: Each recipient tag has an "x" to remove individually
   - A "Remove All" link/button clears entire recipient list
   - Test removing one recipient — it disappears from the list

9. **⚠️ ASK USER before sending**
   - Do NOT click "Send to Recipients" without asking
   - Sending dispatches real emails

## Expected Result
Compose form renders with all widgets functional. Autocomplete, subject line, mailing lists, recipient list management all work.

10. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 04 — Messaging (Broadcast)

**Route:** /MessagingPage → MessagingPage.php
**Perms:** messages.send (Bruno has it via daily-ops and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify message compose form loads and you can construct a message

## Steps

1. **Browse to /MessagingPage**

2. **Verify page renders**
   - Visual: Two-panel layout
   - Left panel: compose form (message text area, subject, mailing list buttons, member search)
   - Right panel: recipient list (empty initially)
   - "Send to Recipients" button

3. **Verify character counter**
   - Type a few characters in the message text area
   - Visual: Character counter below the text area updates (e.g. "N/500")
   - Keep typing past ~400 — counter turns amber then red as it approaches 500

4. **Verify subject auto-generation**
   - Visual: Subject field pre-filled with "WWGC Msg | DDD dd Mon HH:MM AM/PM" format
   - A "Custom Subject" checkbox — tick it to edit subject manually

5. **Test member search autocomplete**
   - Type 2+ characters in the member search field
   - Visual: Autocomplete dropdown appears with matching member names/emails
   - Click a member — name appears as a recipient tag in the right panel

6. **Test mailing list buttons**
   - Click a mailing list button (e.g. "WGC Committee")
   - Visual: All members of that list appear as recipient tags in the right panel (or a confirmation)
   - Click a different list — recipients accumulate

7. **Verify "Fake Twitter" checkbox**
   - Visual: Checkbox labelled "Fake Twitter" or "Post as Broadcast"
   - Toggle it — no visual change but changes `is_broadcast` flag

8. **Verify "Remove All" / individual remove**
   - Visual: Each recipient tag has an "x" to remove individually
   - A "Remove All" link/button clears entire recipient list
   - Test removing one recipient — it disappears from the list

9. **⚠️ ASK USER before sending**
   - Do NOT click "Send to Recipients" without asking
   - Sending dispatches real emails

## Expected Result
Compose form renders with all widgets functional. Autocomplete, subject line, mailing lists, recipient list management all work.


11. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/04-messaging.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/04-messaging.md'

---

*Last tested:* (set by step 11 above)