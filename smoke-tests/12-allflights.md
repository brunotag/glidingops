# 12 — All Flights Report

**Route:** /AllFlightsReportNew → AllFlightsReportNew.php
**Perms:** flights.list (Bruno has it via booking, daily-ops, cfo, and more)
**Tools:** Chrome DevTools
**Purpose:** Verify flights DataTable loads with date filtering and member autocomplete

## Steps

1. **Browse to /AllFlightsReportNew**

2. **Verify page renders**
   - Visual: "Flights Report" or "All Flights" heading
   - Date range inputs: From and To (default = 1st of month to today)
   - "View Report" button, "Today" button
   - Member name autocomplete search field
   - DataTable placeholder

3. **Verify default data loads**
   - Visual: DataTable populated with this month's flights
   - Columns: DATE, SEQ, LOCATION, LAUNCH, TOW, GLIDER, TOWY (tow pilot), PIC, P2, TAKE OFF, LAND, DUR, HGT, CHARGE, COMMENTS, FINAL
   - "Showing 1 to N of M entries" text

4. **Verify non-finalised row highlighting**
   - Visual: Rows where FINAL = no have a yellow/orange background (`.row-nonfinalised`)
   - Spot-check a few rows

5. **Test member autocomplete**
   - Type 2+ characters in the member search field
   - Visual: Dropdown suggestions appear (#member-suggestions)
   - Select a member — hidden #filter-member field updates
   - Click "View Report"
   - Visual: DataTable reloads filtered to that member's flights

6. **Test date range filter**
   - Change From date to an earlier date (e.g. start of last month)
   - Click "View Report"
   - Visual: DataTable reloads with more rows (flights from last month included)

7. **Test "Today" button**
   - Click "Today"
   - Visual: Date range resets to today's date
   - DataTable reloads with just today's flights

8. **Verify total duration row**
   - Visual: A footer/summary row at the bottom showing total duration across all filtered flights

## Expected Result
Flights DataTable loads with filters working. Non-finalised rows highlighted yellow. Member autocomplete and date filtering function correctly.

9. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 12 — All Flights Report

**Route:** /AllFlightsReportNew → AllFlightsReportNew.php
**Perms:** flights.list (Bruno has it via booking, daily-ops, cfo, and more)
**Tools:** Chrome DevTools
**Purpose:** Verify flights DataTable loads with date filtering and member autocomplete

## Steps

1. **Browse to /AllFlightsReportNew**

2. **Verify page renders**
   - Visual: "Flights Report" or "All Flights" heading
   - Date range inputs: From and To (default = 1st of month to today)
   - "View Report" button, "Today" button
   - Member name autocomplete search field
   - DataTable placeholder

3. **Verify default data loads**
   - Visual: DataTable populated with this month's flights
   - Columns: DATE, SEQ, LOCATION, LAUNCH, TOW, GLIDER, TOWY (tow pilot), PIC, P2, TAKE OFF, LAND, DUR, HGT, CHARGE, COMMENTS, FINAL
   - "Showing 1 to N of M entries" text

4. **Verify non-finalised row highlighting**
   - Visual: Rows where FINAL = no have a yellow/orange background (`.row-nonfinalised`)
   - Spot-check a few rows

5. **Test member autocomplete**
   - Type 2+ characters in the member search field
   - Visual: Dropdown suggestions appear (#member-suggestions)
   - Select a member — hidden #filter-member field updates
   - Click "View Report"
   - Visual: DataTable reloads filtered to that member's flights

6. **Test date range filter**
   - Change From date to an earlier date (e.g. start of last month)
   - Click "View Report"
   - Visual: DataTable reloads with more rows (flights from last month included)

7. **Test "Today" button**
   - Click "Today"
   - Visual: Date range resets to today's date
   - DataTable reloads with just today's flights

8. **Verify total duration row**
   - Visual: A footer/summary row at the bottom showing total duration across all filtered flights

## Expected Result
Flights DataTable loads with filters working. Non-finalised rows highlighted yellow. Member autocomplete and date filtering function correctly.


10. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/12-allflights.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/12-allflights.md'

---

*Last tested:* (set by step 10 above)