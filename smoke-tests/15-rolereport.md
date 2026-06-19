# 15 — Members Roles Stats Report (Laravel)

**Route:** /app/reports/membersRolesStatsReport (Laravel route via /api/v2/)
**Perms:** Requires Laravel auth — Bruno's session should carry over
**Tools:** Chrome DevTools
**Purpose:** Verify Laravel-powered members roles matrix report loads

## Steps

1. **Browse to /app/reports/membersRolesStatsReport**

2. **Verify page renders**
   - Visual: "Members Roles Statistics" or "Roles Report" heading
   - Matrix table with members as rows, role categories as columns

3. **Verify matrix structure**
   - Visual: Columns: Instructor, Winch Driver, LPC, Engineer, Tow Pilot, CMT
   - Rows: Active members of the org
   - Checkmarks or "Yes" indicators where a member has a role

4. **Verify role grouping**
   - Visual: A/B Cat Instructor and C Cat Instructor both map to a single "Instructor" column
   - Check a known instructor appears under Instructor

5. **Verify styling**
   - Visual: Clean table layout (Laravel Blade + Bootstrap)
   - No Laravel debug toolbar error bars

## Expected Result
Laravel report loads with role matrix. All role categories rendered. No Laravel errors.

6. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 15 — Members Roles Stats Report (Laravel)

**Route:** /app/reports/membersRolesStatsReport (Laravel route via /api/v2/)
**Perms:** Requires Laravel auth — Bruno's session should carry over
**Tools:** Chrome DevTools
**Purpose:** Verify Laravel-powered members roles matrix report loads

## Steps

1. **Browse to /app/reports/membersRolesStatsReport**

2. **Verify page renders**
   - Visual: "Members Roles Statistics" or "Roles Report" heading
   - Matrix table with members as rows, role categories as columns

3. **Verify matrix structure**
   - Visual: Columns: Instructor, Winch Driver, LPC, Engineer, Tow Pilot, CMT
   - Rows: Active members of the org
   - Checkmarks or "Yes" indicators where a member has a role

4. **Verify role grouping**
   - Visual: A/B Cat Instructor and C Cat Instructor both map to a single "Instructor" column
   - Check a known instructor appears under Instructor

5. **Verify styling**
   - Visual: Clean table layout (Laravel Blade + Bootstrap)
   - No Laravel debug toolbar error bars

## Expected Result
Laravel report loads with role matrix. All role categories rendered. No Laravel errors.


7. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/15-rolereport.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/15-rolereport.md'

---

*Last tested:* (set by step 7 above)