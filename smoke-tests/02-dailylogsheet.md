# 02 — Daily Log Sheet

**Route:** /DailyLogSheet?org=1 → DailyLogSheet.php
**Perms:** flights.log (Bruno has it)
**Tools:** Chrome DevTools
**Purpose:** Verify read-only daily timesheet loads and displays flight data

## Steps

1. **Browse to /DailyLogSheet?org=1**

2. **Verify page renders**
   - Visual: "Daily Timesheet" or "Flight Log" heading
   - Date input at top pre-filled with today's date (flatpickr)
   - "View" button next to date picker
   - Loading spinner briefly, then a table appears

3. **Verify flight table**
   - Visual: Table with columns: SEQ, Launch (towplane), Glider, W.Drv (tow pilot), PIC, P2, Duration, Charge, Location, Comments
   - At least some rows of data for today (or "No flights" empty state)
   - If flights exist: duration in HH:MM format, launch type text shown
   - Responsive: narrow viewport shows card-style layout (mobile responsive)

4. **Test date picker**
   - Click date input — flatpickr calendar pops up
   - Pick a known active date (e.g. yesterday or a day this week)
   - Click "View"
   - Visual: Table refreshes with new data, spinner shows briefly

5. **Verify print-friendliness**
   - Visual: "Print" button or CSS print layout (no nav sidebar in print preview)

## Expected Result
Timesheet loads with today's flights. Date picker works. Table renders with correct columns. No PHP errors.

6. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 02 — Daily Log Sheet

**Route:** /DailyLogSheet?org=1 → DailyLogSheet.php
**Perms:** flights.log (Bruno has it)
**Tools:** Chrome DevTools
**Purpose:** Verify read-only daily timesheet loads and displays flight data

## Steps

1. **Browse to /DailyLogSheet?org=1**

2. **Verify page renders**
   - Visual: "Daily Timesheet" or "Flight Log" heading
   - Date input at top pre-filled with today's date (flatpickr)
   - "View" button next to date picker
   - Loading spinner briefly, then a table appears

3. **Verify flight table**
   - Visual: Table with columns: SEQ, Launch (towplane), Glider, W.Drv (tow pilot), PIC, P2, Duration, Charge, Location, Comments
   - At least some rows of data for today (or "No flights" empty state)
   - If flights exist: duration in HH:MM format, launch type text shown
   - Responsive: narrow viewport shows card-style layout (mobile responsive)

4. **Test date picker**
   - Click date input — flatpickr calendar pops up
   - Pick a known active date (e.g. yesterday or a day this week)
   - Click "View"
   - Visual: Table refreshes with new data, spinner shows briefly

5. **Verify print-friendliness**
   - Visual: "Print" button or CSS print layout (no nav sidebar in print preview)

## Expected Result
Timesheet loads with today's flights. Date picker works. Table renders with correct columns. No PHP errors.


7. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/02-dailylogsheet.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/02-dailylogsheet.md'

---

*Last tested:* (set by step 7 above)