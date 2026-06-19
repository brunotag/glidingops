# 10 — Treasurer Report (New)

**Route:** /TreasurerReportNew4 → TreasurerReportNew4.php
**Perms:** treasurer-report.view (Bruno has it via cfo and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify treasurer report loads with month selector and data

## Steps

1. **Browse to /TreasurerReportNew4**

2. **Verify page renders**
   - Visual: "Treasurer Report" heading
   - Month and Year dropdowns at top (defaults to previous month)
   - "View Report" button
   - "Export CSV" button

3. **Select a month with data and run report**
   - Pick a month that definitely has flights (e.g. May 2026 had 90 flights; June 2026 may have 0 early in month)
   - Click "View Report"
   - Visual: Loading spinner briefly, then a DataTable of flights appears
   - Columns: DATE, LOCATION, GLIDER, PIC, P2, DURATION, LAUNCH, TYPE, NOTES

4. **Verify flight data displayed**
   - Visual: Each row shows a flight with correct member names and times
   - Trial flights section shown separately (if any trial flights that month)
   - Per-member grouping visible

5. **Verify CSV export**
   - Click "Export CSV"
   - Visual: CSV file downloads
   - If not downloading: check the Network tab for the CSV response
   - The CSV should have headers: SURNAME, FIRST NAME, DATE, LOCATION, GLIDER, PIC, P2, DURATION, LAUNCH, TYPE, NOTES

## Expected Result
Treasurer report loads with data for the selected month. DataTable and CSV export both functional. No PHP errors.

6. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 10 — Treasurer Report (New)

**Route:** /TreasurerReportNew4 → TreasurerReportNew4.php
**Perms:** treasurer-report.view (Bruno has it via cfo and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify treasurer report loads with month selector and data

## Steps

1. **Browse to /TreasurerReportNew4**

2. **Verify page renders**
   - Visual: "Treasurer Report" heading
   - Month and Year dropdowns at top (defaults to previous month)
   - "View Report" button
   - "Export CSV" button

3. **Select a month with data and run report**
   - Pick a month that definitely has flights (e.g. May 2026 had 90 flights; June 2026 may have 0 early in month)
   - Click "View Report"
   - Visual: Loading spinner briefly, then a DataTable of flights appears
   - Columns: DATE, LOCATION, GLIDER, PIC, P2, DURATION, LAUNCH, TYPE, NOTES

4. **Verify flight data displayed**
   - Visual: Each row shows a flight with correct member names and times
   - Trial flights section shown separately (if any trial flights that month)
   - Per-member grouping visible

5. **Verify CSV export**
   - Click "Export CSV"
   - Visual: CSV file downloads
   - If not downloading: check the Network tab for the CSV response
   - The CSV should have headers: SURNAME, FIRST NAME, DATE, LOCATION, GLIDER, PIC, P2, DURATION, LAUNCH, TYPE, NOTES

## Expected Result
Treasurer report loads with data for the selected month. DataTable and CSV export both functional. No PHP errors.


7. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/10-treasurer.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/10-treasurer.md'

---

*Last tested:* (set by step 7 above)