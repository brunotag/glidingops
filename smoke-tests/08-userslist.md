# 08 — Users List

**Route:** /UsersList → users-list-v2b.php
**Perms:** users.manage (Bruno has it via admin and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify user accounts DataTable loads correctly

## Steps

1. **Browse to /UsersList**

2. **Verify page renders**
   - Visual: "Users" heading
   - DataTable with columns: Name, Usercode, Member, Org, Expiry, Force PW Reset
   - "New User" button/links

3. **Verify DataTable loads**
   - Visual: Table populated with user rows
   - "Showing 1 to N of M entries" text
   - DataTable search box functional

4. **Verify sortable columns**
   - Click "Usercode" column header — rows reorder asc/desc
   - Visual: Arrow indicator on sorted column

5. **Verify user name links to edit**
   - Click a user's name
   - Visual: Navigates to /Users/N (user edit form)
   - Verify edit form loads with that user's data
   - Browse back

6. **Verify "New User" button**
   - Click "New User"
   - Visual: Navigates to /Users with empty create form

7. **Verify responsive layout**
   - Visual: Narrow viewport shows card-style layout

## Expected Result
DataTable loads with server-side data. Links to create/edit forms work. No PHP errors.

8. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 08 — Users List

**Route:** /UsersList → users-list-v2b.php
**Perms:** users.manage (Bruno has it via admin and zzz_god)
**Tools:** Chrome DevTools
**Purpose:** Verify user accounts DataTable loads correctly

## Steps

1. **Browse to /UsersList**

2. **Verify page renders**
   - Visual: "Users" heading
   - DataTable with columns: Name, Usercode, Member, Org, Expiry, Force PW Reset
   - "New User" button/links

3. **Verify DataTable loads**
   - Visual: Table populated with user rows
   - "Showing 1 to N of M entries" text
   - DataTable search box functional

4. **Verify sortable columns**
   - Click "Usercode" column header — rows reorder asc/desc
   - Visual: Arrow indicator on sorted column

5. **Verify user name links to edit**
   - Click a user's name
   - Visual: Navigates to /Users/N (user edit form)
   - Verify edit form loads with that user's data
   - Browse back

6. **Verify "New User" button**
   - Click "New User"
   - Visual: Navigates to /Users with empty create form

7. **Verify responsive layout**
   - Visual: Narrow viewport shows card-style layout

## Expected Result
DataTable loads with server-side data. Links to create/edit forms work. No PHP errors.


9. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/08-userslist.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/08-userslist.md'

---

*Last tested:* (set by step 9 above)