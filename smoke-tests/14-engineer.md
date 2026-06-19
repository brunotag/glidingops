# 14 — Engineer Report

**Route:** /Engineer → Engineer.php
**Perms:** engineer.view (Bruno has it via engineer and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify engineer/aircraft report page loads with glider selector

## Steps

1. **Browse to /Engineer**

2. **Verify page renders**
   - Visual: "Engineer Report" or "Engineer" heading
   - Form with: Glider dropdown (all aircraft in org), From date, To date
   - "View Report" and "Export to Excel" buttons
   - "Print" button

3. **Verify glider dropdown**
   - Visual: Dropdown populated with all gliders from the org's aircraft table
   - Select a glider that has flights recorded (e.g. one with recent activity)

4. **Select date range and run report**
   - From: start of this month
   - To: today
   - Click "View Report"
   - Visual: Table appears below with columns: DATE, FLIGHT DURATION, LAUNCH TYPE
   - Total duration and flight count at the bottom

5. **Verify "Export to Excel"**
   - Click "Export to Excel"
   - Visual: CSV file downloads (form action changes to /Engineer.csv)

6. **Verify print button**
   - Visual: "Print" button visible, triggers browser print dialog

## Expected Result
Engineer report loads with glider selector and date pickers. Report table renders with flight data. CSV export works.

## Notes
- Stray `?>` appears at the top of the page (PHP output issue in Engineer.php). Not a functional blocker but worth investigating separately.

7. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 14 — Engineer Report

**Route:** /Engineer → Engineer.php
**Perms:** engineer.view (Bruno has it via engineer and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify engineer/aircraft report page loads with glider selector

## Steps

1. **Browse to /Engineer**

2. **Verify page renders**
   - Visual: "Engineer Report" or "Engineer" heading
   - Form with: Glider dropdown (all aircraft in org), From date, To date
   - "View Report" and "Export to Excel" buttons
   - "Print" button

3. **Verify glider dropdown**
   - Visual: Dropdown populated with all gliders from the org's aircraft table
   - Select a glider that has flights recorded (e.g. one with recent activity)

4. **Select date range and run report**
   - From: start of this month
   - To: today
   - Click "View Report"
   - Visual: Table appears below with columns: DATE, FLIGHT DURATION, LAUNCH TYPE
   - Total duration and flight count at the bottom

5. **Verify "Export to Excel"**
   - Click "Export to Excel"
   - Visual: CSV file downloads (form action changes to /Engineer.csv)

6. **Verify print button**
   - Visual: "Print" button visible, triggers browser print dialog

## Expected Result
Engineer report loads with glider selector and date pickers. Report table renders with flight data. CSV export works.

## Notes
- Stray `?>` appears at the top of the page (PHP output issue in Engineer.php). Not a functional blocker but worth investigating separately.


8. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/14-engineer.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/14-engineer.md'

---

*Last tested:* (set by step 8 above)