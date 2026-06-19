# 13 — Last Flights / Currency Report

**Route:** /last-flights-list?col=1&descsort=1 → last-flights-list.php
**Perms:** last-flights.view (Bruno has it via engineer and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify currency/engineer report showing each member's last flight

## Steps

1. **Browse to /last-flights-list?col=1&descsort=1**

2. **Verify page renders**
   - Visual: "Last Flights" or "Currency Report" heading
   - Table with columns: MEMBER, LAST FLIGHT, LAST SOLO, LAST AS P2, LAST AS P1 WITH OTHER P2, HAS FLOWN IN THE PAST 90 DAYS

3. **Verify data loads**
   - Visual: Rows for active members of Flying class
   - Each row shows dates for last flight, last solo, etc.
   - "Yes" or "No" in the "Has Flown in Past 90 Days" column

4. **Test sorting**
   - Visual: Column headers are clickable
   - Click "LAST FLIGHT" header — table re-sorts by that column
   - Click again — order toggles asc/desc
   - URL updates with `col=N&descsort=N` parameters

5. **Verify responsive mobile layout**
   - Visual: Narrow viewport shows card-style layout

## Expected Result
Currency table loads with active members' last flight dates. Sorting works. No PHP errors.

6. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 13 — Last Flights / Currency Report

**Route:** /last-flights-list?col=1&descsort=1 → last-flights-list.php
**Perms:** last-flights.view (Bruno has it via engineer and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify currency/engineer report showing each member's last flight

## Steps

1. **Browse to /last-flights-list?col=1&descsort=1**

2. **Verify page renders**
   - Visual: "Last Flights" or "Currency Report" heading
   - Table with columns: MEMBER, LAST FLIGHT, LAST SOLO, LAST AS P2, LAST AS P1 WITH OTHER P2, HAS FLOWN IN THE PAST 90 DAYS

3. **Verify data loads**
   - Visual: Rows for active members of Flying class
   - Each row shows dates for last flight, last solo, etc.
   - "Yes" or "No" in the "Has Flown in Past 90 Days" column

4. **Test sorting**
   - Visual: Column headers are clickable
   - Click "LAST FLIGHT" header — table re-sorts by that column
   - Click again — order toggles asc/desc
   - URL updates with `col=N&descsort=N` parameters

5. **Verify responsive mobile layout**
   - Visual: Narrow viewport shows card-style layout

## Expected Result
Currency table loads with active members' last flight dates. Sorting works. No PHP errors.


7. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/13-lastflights.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/13-lastflights.md'

---

*Last tested:* (set by step 7 above)