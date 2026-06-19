# 07 — Members List

**Route:** /AllMembers → members-list-v2b.php
**Perms:** members.list (Bruno has it)
**Tools:** Chrome DevTools
**Purpose:** Verify member list DataTable loads with server-side data and filters work

## Steps

1. **Browse to /AllMembers**

2. **Verify page renders**
   - Visual: "Members" heading
   - DataTable with columns: Name, Class, Status, Phone, Mobile, Email, Medical Expiry, BFR Expiry
   - "New Member" link/button
   - Filter section with Class and Status multi-select dropdowns
   - "Apply" button for filters

3. **Verify DataTable loads**
   - Visual: Table populated with member rows
   - "Showing 1 to N of M entries" text at the bottom
   - Search box (DataTable built-in search)
   - Sortable columns — click column header to sort asc/desc (small arrow indicator appears)

4. **Verify default filter**
   - Visual: By default, only Active (status=1) members shown (excluding Short Term class)
   - Check the row count makes sense (Bruno has zzz_god, all members visible)

5. **Test class/status filter dropdowns**
   - Visual: Class dropdown — multi-select with Bootstrap Select styling
   - **Interaction:** Click the button first (shows selected items), then click an option in the popover list to toggle it
   - Select a different class (e.g. deselect all, select only "Youth")
   - Click "Apply"
   - Visual: DataTable reloads with filtered results
   - Verify rows match selected class

6. **Test member name links**
   - Click a member's name
   - Visual: Navigates to /MemberNew?id=N (member edit form)
   - Verify edit form loads with the member's data pre-filled
   - Browse back to /AllMembers

7. **Verify responsive mobile layout**
   - Visual: At narrow widths, each row flips to a card layout (label: value pairs vertically stacked)

8. **Verify "New Member" link**
   - Click "New Member"
   - Visual: Opens /MemberNew with empty form

## Expected Result
DataTable loads with server-side data, filters apply correctly, links navigate to edit/create forms. No PHP errors.

9. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 07 — Members List

**Route:** /AllMembers → members-list-v2b.php
**Perms:** members.list (Bruno has it)
**Tools:** Chrome DevTools
**Purpose:** Verify member list DataTable loads with server-side data and filters work

## Steps

1. **Browse to /AllMembers**

2. **Verify page renders**
   - Visual: "Members" heading
   - DataTable with columns: Name, Class, Status, Phone, Mobile, Email, Medical Expiry, BFR Expiry
   - "New Member" link/button
   - Filter section with Class and Status multi-select dropdowns
   - "Apply" button for filters

3. **Verify DataTable loads**
   - Visual: Table populated with member rows
   - "Showing 1 to N of M entries" text at the bottom
   - Search box (DataTable built-in search)
   - Sortable columns — click column header to sort asc/desc (small arrow indicator appears)

4. **Verify default filter**
   - Visual: By default, only Active (status=1) members shown (excluding Short Term class)
   - Check the row count makes sense (Bruno has zzz_god, all members visible)

5. **Test class/status filter dropdowns**
   - Visual: Class dropdown — multi-select with Bootstrap Select styling
   - **Interaction:** Click the button first (shows selected items), then click an option in the popover list to toggle it
   - Select a different class (e.g. deselect all, select only "Youth")
   - Click "Apply"
   - Visual: DataTable reloads with filtered results
   - Verify rows match selected class

6. **Test member name links**
   - Click a member's name
   - Visual: Navigates to /MemberNew?id=N (member edit form)
   - Verify edit form loads with the member's data pre-filled
   - Browse back to /AllMembers

7. **Verify responsive mobile layout**
   - Visual: At narrow widths, each row flips to a card layout (label: value pairs vertically stacked)

8. **Verify "New Member" link**
   - Click "New Member"
   - Visual: Opens /MemberNew with empty form

## Expected Result
DataTable loads with server-side data, filters apply correctly, links navigate to edit/create forms. No PHP errors.


10. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/07-allmembers.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/07-allmembers.md'

---

*Last tested:* (set by step 10 above)