# 03 — Self-Launch Entry

**Route:** /SelfLaunchEntry → self-launch-entry.php
**Perms:** self-launch.access (Bruno has it via daily-ops and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify self-launch flight entry form loads with autocomplete fields

## Steps

1. **Browse to /SelfLaunchEntry**

2. **Verify page renders**
   - Visual: "Self-Launch Flight" or "Self-Launch Entry" heading
   - Date display at top showing today's date
   - Existing self-launch flights listed for today (or "Self-Launch Flights (0)" empty state)
   - Form with fields: Glider, PIC, P2, Takeoff time, Land time, Billing option, Vector, Comments

3. **Test glider autocomplete**
   - Type 2+ characters in the Glider field
   - Visual: Dropdown/autocomplete list appears with matching aircraft
   - Select a glider from the list — field populates

4. **Test PIC autocomplete**
   - Type 2+ characters in PIC field
   - Visual: Member search dropdown appears
   - Select a member

5. **Verify time inputs**
   - Visual: Takeoff and Land fields use flatpickr time picker (HH:MM, 24hr)
   - Click a time field — flatpickr pops up with time selector

6. **Verify billing option dropdown**
   - Visual: Dropdown with billing options loaded from DB
   - Default should be "Charge PIC" (id=2)
   - Click dropdown — multiple options visible

7. **Verify Save button state**
   - Visual: Save button disabled (greyed out) until glider, PIC, takeoff and land are filled
   - Once all filled — button becomes active/enabled

8. **Verify delete (if flights exist)**
   - If any self-launch flights exist for today: an "Edit" and "Delete" button on each row
   - Clicking "Delete" removes the flight
   - ⚠️ ASK USER before actually deleting any flight data

## Expected Result
Form loads with autocomplete fields, time pickers, and billing options all working. No PHP errors.

9. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 03 — Self-Launch Entry

**Route:** /SelfLaunchEntry → self-launch-entry.php
**Perms:** self-launch.access (Bruno has it via daily-ops and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify self-launch flight entry form loads with autocomplete fields

## Steps

1. **Browse to /SelfLaunchEntry**

2. **Verify page renders**
   - Visual: "Self-Launch Flight" or "Self-Launch Entry" heading
   - Date display at top showing today's date
   - Existing self-launch flights listed for today (or "Self-Launch Flights (0)" empty state)
   - Form with fields: Glider, PIC, P2, Takeoff time, Land time, Billing option, Vector, Comments

3. **Test glider autocomplete**
   - Type 2+ characters in the Glider field
   - Visual: Dropdown/autocomplete list appears with matching aircraft
   - Select a glider from the list — field populates

4. **Test PIC autocomplete**
   - Type 2+ characters in PIC field
   - Visual: Member search dropdown appears
   - Select a member

5. **Verify time inputs**
   - Visual: Takeoff and Land fields use flatpickr time picker (HH:MM, 24hr)
   - Click a time field — flatpickr pops up with time selector

6. **Verify billing option dropdown**
   - Visual: Dropdown with billing options loaded from DB
   - Default should be "Charge PIC" (id=2)
   - Click dropdown — multiple options visible

7. **Verify Save button state**
   - Visual: Save button disabled (greyed out) until glider, PIC, takeoff and land are filled
   - Once all filled — button becomes active/enabled

8. **Verify delete (if flights exist)**
   - If any self-launch flights exist for today: an "Edit" and "Delete" button on each row
   - Clicking "Delete" removes the flight
   - ⚠️ ASK USER before actually deleting any flight data

## Expected Result
Form loads with autocomplete fields, time pickers, and billing options all working. No PHP errors.


10. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/03-selflaunch.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/03-selflaunch.md'

---

*Last tested:* (set by step 10 above)