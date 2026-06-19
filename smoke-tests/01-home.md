# 01 — Home / Dashboard

**Route:** /home → home.php
**Perms:** home.view (Bruno has it)
**Tools:** Chrome DevTools
**Purpose:** Verify dashboard loads, favourites widget shows his pages, nav links render

## Steps

1. **Browse to /home**
   - If login didn't redirect, navigate to `/home`

2. **Verify dashboard renders**
   - Visual: Page title "Dashboard" or club branding header
   - Nav sidebar or top nav visible with links
   - Widget grid of cards/link-buttons below

3. **Verify Favourites widget**
   - Visual: A "Favourites" section/card at or near the top
   - Shows link buttons for Bruno's 15 favourited pages:
     - Self-Launch Flight, View Daily Timesheet, Bookings (new), Bookings (Google), View Members, Broadcast a Message, See Past Messages, View Users, Create User, Members Roles Report, Treasurer Report (New), All Flights Report (New), Currency Report, Engineer Report, Billing Report
   - Each link has a star icon (filled = favourited)
   - Clicking a star unfills it (removes from favourites)

4. **Verify non-favourite widgets**
   - Visual: Other sections visible below favourites
   - Common sections: My Flights, Daily Operations (if permitted), Admin tools
   - No broken images or PHP warning text visible

5. **Verify "View As" link (if applicable)**
   - Visual: Small "View As" link near top or in settings area (requires god.view-as perm)
   - Bruno has zzz_god persona, so this should be visible

## Expected Result
Dashboard renders cleanly with all 15 favourite links visible and functional. No PHP errors or broken elements.

6. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 01 — Home / Dashboard

**Route:** /home → home.php
**Perms:** home.view (Bruno has it)
**Tools:** Chrome DevTools
**Purpose:** Verify dashboard loads, favourites widget shows his pages, nav links render

## Steps

1. **Browse to /home**
   - If login didn't redirect, navigate to `/home`

2. **Verify dashboard renders**
   - Visual: Page title "Dashboard" or club branding header
   - Nav sidebar or top nav visible with links
   - Widget grid of cards/link-buttons below

3. **Verify Favourites widget**
   - Visual: A "Favourites" section/card at or near the top
   - Shows link buttons for Bruno's 15 favourited pages:
     - Self-Launch Flight, View Daily Timesheet, Bookings (new), Bookings (Google), View Members, Broadcast a Message, See Past Messages, View Users, Create User, Members Roles Report, Treasurer Report (New), All Flights Report (New), Currency Report, Engineer Report, Billing Report
   - Each link has a star icon (filled = favourited)
   - Clicking a star unfills it (removes from favourites)

4. **Verify non-favourite widgets**
   - Visual: Other sections visible below favourites
   - Common sections: My Flights, Daily Operations (if permitted), Admin tools
   - No broken images or PHP warning text visible

5. **Verify "View As" link (if applicable)**
   - Visual: Small "View As" link near top or in settings area (requires god.view-as perm)
   - Bruno has zzz_god persona, so this should be visible

## Expected Result
Dashboard renders cleanly with all 15 favourite links visible and functional. No PHP errors or broken elements.


7. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/01-home.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/01-home.md'

---

*Last tested:* (set by step 7 above)