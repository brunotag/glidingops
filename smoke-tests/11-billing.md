# 11 — Billing Report

**Route:** /BillingReport → billing-report.php
**Perms:** treasurer-report.view (Bruno has it via cfo and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify billing report loads with calculated charges

## Steps

1. **Browse to /BillingReport**

2. **Verify page renders**
   - Visual: "Treasurer Monthly Report" heading (not "Billing Report")
   - Month and Year selectors at top (defaults to current month)
   - "View Report" button
   - "Export CSV" button

3. **Select a month and run report**
   - Pick a month with flight data (May 2026 has 90 flights; June 2026 shows 0 early in month)
   - Click "View Report"
   - Visual: Page loads with flight listing per member
   - Takes slightly longer than the treasurer report (871 lines of PHP)

4. **Verify charges calculated**
   - Visual: Per-member section with flights listed
   - Each flight shows: glider charge, launch charge, total
   - A summary row per member with total charges
   - Trial flights section at top if any

5. **Verify CSV export**
   - Click "Export CSV"
   - Visual: CSV file downloads via /BillingReport.csv
   - Headers: SURNAME, FIRSTNAME, DATE, LOCATION, GLIDER, PIC, P2, LAUNCH, DURATION, BILING_OPTION, GLIDER_CHARGE, LAUNCH_CHARGE, TOTAL, NOTES, SECTION

## Expected Result
Billing report loads with calculated charges per member. CSV exports correctly. No PHP errors.

6. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 11 — Billing Report

**Route:** /BillingReport → billing-report.php
**Perms:** treasurer-report.view (Bruno has it via cfo and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify billing report loads with calculated charges

## Steps

1. **Browse to /BillingReport**

2. **Verify page renders**
   - Visual: "Treasurer Monthly Report" heading (not "Billing Report")
   - Month and Year selectors at top (defaults to current month)
   - "View Report" button
   - "Export CSV" button

3. **Select a month and run report**
   - Pick a month with flight data (May 2026 has 90 flights; June 2026 shows 0 early in month)
   - Click "View Report"
   - Visual: Page loads with flight listing per member
   - Takes slightly longer than the treasurer report (871 lines of PHP)

4. **Verify charges calculated**
   - Visual: Per-member section with flights listed
   - Each flight shows: glider charge, launch charge, total
   - A summary row per member with total charges
   - Trial flights section at top if any

5. **Verify CSV export**
   - Click "Export CSV"
   - Visual: CSV file downloads via /BillingReport.csv
   - Headers: SURNAME, FIRSTNAME, DATE, LOCATION, GLIDER, PIC, P2, LAUNCH, DURATION, BILING_OPTION, GLIDER_CHARGE, LAUNCH_CHARGE, TOTAL, NOTES, SECTION

## Expected Result
Billing report loads with calculated charges per member. CSV exports correctly. No PHP errors.


7. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/11-billing.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/11-billing.md'

---

*Last tested:* (set by step 7 above)