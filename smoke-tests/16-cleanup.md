# 16 — Cleanup & Verification

**Tools:** plink (SSH), Chrome DevTools
**Purpose:** Ensure no test data remains, verify final DB state

## Steps

1. **Verify no test users remain**
   - SSH: `echo "SELECT id, usercode, name FROM users WHERE name LIKE '%Smoke Test%' OR usercode LIKE 'smoke-test%' OR name LIKE '%smoke%';" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: 0 rows
   - If any remain: `DELETE FROM user_personas WHERE user_id IN (SELECT id FROM users WHERE ...);` then `DELETE FROM users WHERE name LIKE '%Smoke Test%';`
   - ⚠️ ASK USER before deleting

2. **Verify no test bookings remain**
   - SSH: `echo "SELECT id, intention, notes, deleted FROM lrv_bookings WHERE notes = 'created-by-smoke-test' OR intention LIKE '%Smoke Test%';" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: 0 rows, or all deleted = 1
   - If any active remain: ask user, then delete

3. **Verify no test messages sent to self**
   - SSH: `echo "SELECT m.id, m.msg FROM messages m JOIN texts t ON t.txt_msg_id = m.id WHERE m.msg LIKE '%Smoke Test%';" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: 0 rows, or user is OK with test messages staying

4. **Verify session can be destroyed**
   - Browse to /SignOut.php or clear session cookie
   - Visual: Redirected to login page

5. **Summary report**
   - Report: which pages passed, which failed, what was tested
   - Note any visual or functional anomalies found

## Expected Result
No test data left in DB. Session cleaned up. Ready for next smoke test run.

6. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 16 — Cleanup & Verification

**Tools:** plink (SSH), Chrome DevTools
**Purpose:** Ensure no test data remains, verify final DB state

## Steps

1. **Verify no test users remain**
   - SSH: `echo "SELECT id, usercode, name FROM users WHERE name LIKE '%Smoke Test%' OR usercode LIKE 'smoke-test%' OR name LIKE '%smoke%';" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: 0 rows
   - If any remain: `DELETE FROM user_personas WHERE user_id IN (SELECT id FROM users WHERE ...);` then `DELETE FROM users WHERE name LIKE '%Smoke Test%';`
   - ⚠️ ASK USER before deleting

2. **Verify no test bookings remain**
   - SSH: `echo "SELECT id, intention, notes, deleted FROM lrv_bookings WHERE notes = 'created-by-smoke-test' OR intention LIKE '%Smoke Test%';" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: 0 rows, or all deleted = 1
   - If any active remain: ask user, then delete

3. **Verify no test messages sent to self**
   - SSH: `echo "SELECT m.id, m.msg FROM messages m JOIN texts t ON t.txt_msg_id = m.id WHERE m.msg LIKE '%Smoke Test%';" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: 0 rows, or user is OK with test messages staying

4. **Verify session can be destroyed**
   - Browse to /SignOut.php or clear session cookie
   - Visual: Redirected to login page

5. **Summary report**
   - Report: which pages passed, which failed, what was tested
   - Note any visual or functional anomalies found

## Expected Result
No test data left in DB. Session cleaned up. Ready for next smoke test run.


7. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/16-cleanup.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/16-cleanup.md'

---

*Last tested:* (set by step 7 above)