# 09 — Users CRUD

**Route:** /Users → users-new.php (create), /Users/N (edit)
**Perms:** users.manage (Bruno has it via admin and zzz_god)
**Tools:** Chrome DevTools, plink (SSH)
**Purpose:** Verify user creation, verification, and deletion

## Steps

1. **Browse to /Users** (create form)

2. **Verify create form renders**
   - Visual: "Users" heading with create form
   - Fields: Name (required), Usercode (required), Password (required), Organisation, Expires (required), Member (dropdown), Force Password Reset (checkbox), Personas (checkbox list)

3. **⚠️ ASK USER before creating a user**
   - Creating a user = real login account

4. **Create a test user**
   - Fill fields:
     - Name: "Smoke Test User"
     - Usercode: "smoke-test-{YYYYMMDDHHMM}" (unique timestamp)
     - Password: "smoke-test-pw-123"
      - Expires: some future date (e.g. +30 days via flatpickr)
        - **Note:** flatpickr date widget is hard to click-programmatically. Use JS as workaround:
          `() => { const inp = document.querySelector('input.flatpickr-input'); inp.value = '2026-07-20'; inp.dispatchEvent(new Event('change', {bubbles: true})); }`
      - Member: search and select a member (any active member)
     - Force Password Reset: ticked
     - Personas: select "member" persona (basic access)
   - Submit the form
   - Visual: Redirected to /UsersList on success
   - Check for success message/flash

5. **Verify user in DB**
   - SSH: `echo "SELECT id, name, usercode, org, expire, force_pw_reset, member FROM users WHERE usercode = 'smoke-test-{TIMESTAMP}';" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: 1 row, fields match what was entered
   - Take note of the user `id` for cleanup

6. **Verify user in UI list**
   - Visual: On /UsersList, the new user appears in the DataTable
   - Search for "smoke-test" to find it
   - Verify Name, Usercode, Member, Expiry match

7. **Delete the test user**
   - ⚠️ ASK USER before deleting user (will affect any test data)
   - Option A (if delete action available): Click delete/remove on the user
   - Option B (direct DB): `echo "DELETE FROM users WHERE id = N;" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Also clean up user_personas: `echo "DELETE FROM user_personas WHERE user_id = N;" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`

8. **Verify deletion**
   - SSH: Same query as step 5
   - Expected: 0 rows
   - Visual: Refresh /UsersList — user no longer in DataTable

## Expected Result
User created, visible in list/DB, then deleted. UI matches DB at every step.

9. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 09 — Users CRUD

**Route:** /Users → users-new.php (create), /Users/N (edit)
**Perms:** users.manage (Bruno has it via admin and zzz_god)
**Tools:** Chrome DevTools, plink (SSH)
**Purpose:** Verify user creation, verification, and deletion

## Steps

1. **Browse to /Users** (create form)

2. **Verify create form renders**
   - Visual: "Users" heading with create form
   - Fields: Name (required), Usercode (required), Password (required), Organisation, Expires (required), Member (dropdown), Force Password Reset (checkbox), Personas (checkbox list)

3. **⚠️ ASK USER before creating a user**
   - Creating a user = real login account

4. **Create a test user**
   - Fill fields:
     - Name: "Smoke Test User"
     - Usercode: "smoke-test-{YYYYMMDDHHMM}" (unique timestamp)
     - Password: "smoke-test-pw-123"
      - Expires: some future date (e.g. +30 days via flatpickr)
        - **Note:** flatpickr date widget is hard to click-programmatically. Use JS as workaround:
          `() => { const inp = document.querySelector('input.flatpickr-input'); inp.value = '2026-07-20'; inp.dispatchEvent(new Event('change', {bubbles: true})); }`
      - Member: search and select a member (any active member)
     - Force Password Reset: ticked
     - Personas: select "member" persona (basic access)
   - Submit the form
   - Visual: Redirected to /UsersList on success
   - Check for success message/flash

5. **Verify user in DB**
   - SSH: `echo "SELECT id, name, usercode, org, expire, force_pw_reset, member FROM users WHERE usercode = 'smoke-test-{TIMESTAMP}';" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: 1 row, fields match what was entered
   - Take note of the user `id` for cleanup

6. **Verify user in UI list**
   - Visual: On /UsersList, the new user appears in the DataTable
   - Search for "smoke-test" to find it
   - Verify Name, Usercode, Member, Expiry match

7. **Delete the test user**
   - ⚠️ ASK USER before deleting user (will affect any test data)
   - Option A (if delete action available): Click delete/remove on the user
   - Option B (direct DB): `echo "DELETE FROM users WHERE id = N;" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Also clean up user_personas: `echo "DELETE FROM user_personas WHERE user_id = N;" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`

8. **Verify deletion**
   - SSH: Same query as step 5
   - Expected: 0 rows
   - Visual: Refresh /UsersList — user no longer in DataTable

## Expected Result
User created, visible in list/DB, then deleted. UI matches DB at every step.


10. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/09-users-crud.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/09-users-crud.md'

---

*Last tested:* (set by step 10 above)