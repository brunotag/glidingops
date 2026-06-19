# 00 — Setup: Login & Session

**Route:** /Login.php → /checklogin.php
**Tools:** Chrome DevTools (browser), plink (SSH)
**Purpose:** Login as Bruno, capture session cookie, verify session vars

## Prerequisites
- Bruno's credentials stored in `docs/_secrets.md`
- SSH access to production (credentials in `docs/_secrets.md`)
- **chrome-devtools-mcp** must be available (this is checked by the /smoke-test command before running)

## Steps

1. **Verify Bruno's user record in production DB**
   - SSH: `echo "SELECT id, usercode, member FROM users WHERE id = 237;" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: `237  bruno.tagliapietra@gmail.com  5708`
   - If missing, find Bruno by: `SELECT id, usercode, member FROM users WHERE usercode LIKE '%bruno%';`

2. **Open login page in browser**
   - Browse to http://glidingops.test (local dev) OR https://gops.wwgc.co.nz (production)
   - ⚠️ Ask user which target before proceeding

3. **Verify login page renders**
   - Visual: Two tabs — "Password" and "Email or Register"
   - Password tab selected by default
   - Username and Password fields visible
   - "Log In" submit button

4. **Enter credentials**
   - Fill username: `bruno.tagliapietra@gmail.com`
   - Fill password: from `docs/_secrets.md` (add if missing)
   - Click "Log In"

5. **Verify successful login**
   - Visual: Redirect to home page (dashboard)
   - URL changes to `/home` or `/home.php`
   - No error messages like "Wrong username or password"

6. **Verify session cookie captured**
   - In Chrome DevTools Network tab, find any request
   - Check Request Headers for `Cookie` containing `PHPSESSID`
   - If using PowerShell WebRequestSession: `$ws.Cookies` shows session cookie
   - Note: session must remain alive for all subsequent tests

## Expected Result
Successfully logged in, session cookie active, ready for smoke tests.

---

*Last tested: 2026-06-20 on commit 7a1a046*

