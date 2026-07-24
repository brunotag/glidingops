# Security Hardening

**STATUS: NOT IMPLEMENTED** — All items below are future work.

## Overview

The codebase has several known security vulnerabilities inherited from its age as a vanilla PHP application. These are documented high-impact issues that should be addressed systematically.

## F1: MD5 -> bcrypt Password Hashing

### Problem
`users.password` stores MD5 hashes. MD5 is trivially rainbow-table-attackable, unsalted, and considered insecure for over a decade.

### Approach
1. **Add a `password_hash` column** (VARCHAR(255), nullable) alongside the existing `password` column
2. **On successful login**: if user has a `password_hash`, verify with `password_verify()`. If they only have an MD5 hash, verify MD5 then re-hash with `password_hash( PASSWORD_BCRYPT )` and store in `password_hash`, clear `password`.
3. **After transition period** (e.g. 6 months): drop the `password` column and `force_pw_reset` logic, make `password_hash` NOT NULL.
4. **New users / password changes**: always write to `password_hash`, leave `password` NULL.

### Files to Modify
| File | Change |
|------|--------|
| `checklogin.php` | Bcrypt verification with MD5 fallback + rehash |
| `PasswordChange.php` | Write bcrypt hash instead of MD5 |
| `api/magic-link-request.php` | Generate random password as bcrypt |
| `config/database.php.sample` | No change needed |
| `login.php` | No change (form is the same) |

### Schema Change (Laravel Migration)
```sql
ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER password;
```

## F2: SQL Injection

### Problem
Pervasive string concatenation in SQL queries across ~300 call sites. e.g.:
```php
$q = "SELECT * FROM users WHERE usercode='$myusername'";
```

### Approach
Two strategies, apply based on context:

**Strategy A — Parameterized queries (hot paths):**
Convert critical auth and data-modification queries to prepared statements. Focus on:
- `checklogin.php` — username lookup
- `members-new.php`, `users-new.php` — all INSERT/UPDATE
- `DailySheet.php` — flight entry
- API endpoints — all user-supplied input

**Strategy B — Connection-level interception (see FUTURE_DEVELOPMENT_DB_LOGGING.md):**
The `LoggedMySQLi` wrapper catches failures but doesn't prevent injection. Strategy A is required for injection prevention.

## F3: CSRF Protection

### Problem
No CSRF tokens on any form. An attacker can trick an authenticated user into submitting actions (changing passwords, creating users, modifying flights).

### Approach
1. **Add a `helpers/csrf.php`** with `generate_csrf_token()` and `verify_csrf_token()` functions
2. **Every form** receives a hidden `<input name="_csrf" value="<?= generate_csrf_token() ?>">`
3. **Every POST handler** checks `verify_csrf_token($_POST['_csrf'])`
4. **Exclude** webhook/callback endpoints (Particle, bTraced, OAuth callbacks) from CSRF checks

### Files to Create
| File | Purpose |
|------|---------|
| `helpers/csrf.php` | Token generation + verification using `session` storage |

### Files to Modify
All files with POST handlers (~50 files). Prioritize:
- `checklogin.php`
- `PasswordChange.php`
- `members-new.php`, `users-new.php`
- `DailySheet.php`
- `MessagingPage.php`
- All `api/*.php` endpoints (token in header or body)

## F4: Session Security

### Current Issues
- 12-hour cookie lifetime (from DailySheet)
- No session fingerprinting (User-Agent, IP)
- No HTTPS enforcement (likely HTTP)

### Improvements
- Add session fingerprinting (`$_SESSION['fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'])`)
- Regenerate session ID on privilege escalation
- Consider HttpOnly + Secure + SameSite cookie flags
- Shorter session lifetime with refresh

## F5: Audit Trail Gaps

### Problem
The `audit` table records logins but not most data-modifying actions (member edits, flight changes, user creation).

### Improvement
Add audit logging to all write operations. The `LoggedMySQLi` wrapper (FUTURE_DEVELOPMENT_DB_LOGGING.md) can optionally log all commands, but explicit audit calls are more useful:
```php
audit_log($_SESSION['userid'], $_SESSION['memberid'], 'Updated member ' . $memberId . ': changed class');
```

## Phased Implementation

### Phase 1: Quick Wins
| Item | Effort | Why First |
|------|--------|-----------|
| F1: bcrypt migration | 4-8 hours | Highest impact, migration-friendly |
| F3: CSRF on key forms | 8-12 hours | Prevents account takeover |

### Phase 2: Deeper Work
| Item | Effort | Notes |
|------|--------|-------|
| F2: Parameterized queries (hot paths) | 20-40 hours | Large scope, focus on auth + billing |
| F4: Session security | 4-6 hours | Configuration + few code changes |

### Phase 3: Audit
| Item | Effort | Notes |
|------|--------|-------|
| F5: Audit logging | 10-20 hours | Add to all write operations |