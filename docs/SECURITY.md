# Security Model

## Authentication

### Public Pages

These pages require no authentication or authorization:

- `messages-list.php` — Broadcast messages feed, requires `?org=N` query param (e.g. `?org=1`). Consumed by external systems like the club website.
- `api/magic-link-request.php` — Magic link request endpoint
- `api/magic-link-verify.php` — Magic link verification endpoint

---

## Login Page (Login.php)

The login page has two tabs:

#### Password Tab
1. User enters username (`users.usercode`) and password
2. POSTs to `checklogin.php` which:
   - Looks up user by `usercode`
   - Compares MD5(password) against `users.password`
   - On success, creates session
3. If `force_pw_reset=1`, redirects to `PasswordChange.php`

#### Email or Register Tab (Magic Link)
1. User enters the email address they used when joining the club
2. AJAX POST to `api/magic-link-request.php`
3. Server looks up user by `usercode` or `members.email` (via JOIN)
4. If no user found, checks `members.email` for a member without a user account
   - Auto-creates a `users` record with `force_pw_reset=1` and a random password
5. Generates a 64-char hex token, stores in `magic_link_tokens` table
6. Emails a link: `https://gops.wwgc.co.nz/api/magic-link-verify?token=XXXX`
7. Token rate-limited to max 3 unused per user
8. Always returns `{"success":true}` (don't reveal if email exists)

#### Magic Link Verify Flow (`api/magic-link-verify.php`)
1. Validates token exists, not used (`used_at IS NULL`), not expired (15 min)
2. Marks token as `used_at = NOW()`
3. Creates session (same vars as password login)
4. Sets `$_SESSION['auth_via_magic_link'] = 1`
5. Redirects to `/PasswordChange` to allow setting a password
6. On error, redirects to `Login.php?error=invalid_link|link_used|link_expired`

#### Password Change via Magic Link
After magic link login, `PasswordChange.php` skips the old-password field (checks `auth_via_magic_link` flag). User sets a new password directly. On success, `auth_via_magic_link` is unset and user is redirected to home.

### Session Variables
```php
$_SESSION['userid']     // users.id
$_SESSION['who']        // usercode (username/email)
$_SESSION['memberid']   // members.id (linked member, nullable)
$_SESSION['org']        // organisations.id (0 if admin)
$_SESSION['security']   // computed from personas via compute_security_bitmask()
$_SESSION['timezone']   // org timezone
$_SESSION['dispname']   // users.name (display name)
```

### Password Storage
- **MD5 hashing** - NOT secure by modern standards
- Rainbow table vulnerable
- No salting

### Session Security
- 12-hour cookie lifetime (from DailySheet)
- Basic session_start() - no fingerprinting
- No HTTPS enforcement (likely HTTP)

## Authorization - Security Levels (Legacy Bitmask)

The system still maintains a `$_SESSION['security']` bitmask for backward compatibility with home.php display-level conditionals. It is now **computed from personas** at login time via `compute_security_bitmask()` rather than read from the database.

| Level | Value | Name | Description |
|-------|-------|------|-------------|
| 0 | 0 | None | Not logged in |
| 1 | 1 | Member | Basic member access |
| 2 | 2 | Booking Admin | Can manage bookings |
| 3 | 3 | Member + Booking | Combined |
| 4 | 4 | Daily Ops | Can enter flights |
| 5 | 1+4 | Member + Daily Ops | Combined |
| 8 | 8 | CFO/Treasurer | Billing access |
| 16 | 16 | CFI | Chief Flight Instructor |
| 32 | 32 | Engineer | Engineering reports |
| 64 | 64 | Admin | Full admin |
| 128 | 128 | God | Super admin |

### Common Combinations
```php
// Member level (1)
$_SESSION['security'] & 1

// Admin level (64+)
$_SESSION['security'] & 64

// Treasurer + Admin (8 + 64 = 72)
$_SESSION['security'] & 72

// Full access (255 = 1+2+4+8+16+32+64+128)
$_SESSION['security'] & 255
```

### Role-Based Checks in Code

Every protected page has this pattern at top:
```php
if(isset($_SESSION['security'])){
  if (!($_SESSION['security'] & 4)){  // Example: level 4
    die("Security level too low for this page");
  }
}else{
  header('Location: Login.php');
  die("Please logon");
}
```

### Security Levels by Feature

| Feature | Required Level |
|---------|---------------|
| View home | 1 (Member) |
| MyFlights | 1 (Member) |
| MessagingPage | 1 (Member) |
| DailySheet/Log | 4 (Daily Ops) |
| Roles/Groups | 4 (Daily Ops) |
| Treasurer Report | 8 (CFO) |
| Incentive Schemes | 8 (CFO) |
| Billing Options | 8 (CFO) |
| Engineer Report | 32 (Engineer) |
| Currency Report | 32 (Engineer) |
| Aircraft Types | 64 (Admin) |
| Launch Types | 64 (Admin) |
| Duty Types | 64 (Admin) |
| Aircraft | 120 (Admin + Engineer) |
| Users | 64 (Admin) |
| Organisations | 128 (God) |

## Role System (Separate from Security Levels)

### roles table
Predefined positions (not enforced, informational):
- A/B Cat Instructor
- C Cat Instructor
- Tow Pilot
- Winch Driver
- Launch Point Controller
- Engineer
- Committee/Management Team
- Member

### role_member table
Links members to roles:
- role_id (FK to roles)
- member_id (FK to members)
- org (organization scope)

**Note:** This is separate from security levels - a member can have role "Tow Pilot" without having elevated security access.

## Member Class System

### membership_class
Categories that affect billing:
- Senior
- Junior
- Short Term
- Non Flying

Used in billing calculations (different rates for juniors, etc.)

### membership_status
Current status:
- Active
- Passive
- Resigned
- Deceased

Inactive members hidden from some lists.

## Input Security

### InputChecker Function
Used in forms to sanitize:
```php
function InputChecker($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
```
- Basic XSS protection
- Not comprehensive (no parameterized queries everywhere)

### SQL Injection
**Vulnerable:** Many pages use string concatenation in SQL:
```php
$q = "SELECT * FROM users WHERE usercode='$myusername'";
```

**Should be:** Parameterized queries:
```php
$stmt = $con->prepare("SELECT * FROM users WHERE usercode = ?");
$stmt->bind_param("s", $myusername);
```

## Known Security Issues

1. **MD5 Passwords** - Easily crackable
2. **No HTTPS** - Credentials sent cleartext
3. **SQL Injection** - Widespread in older code
4. **XSS** - Incomplete sanitization
5. **No CSRF Protection** - Forms vulnerable
6. **Weak Session** - No fingerprinting
7. **No Rate Limiting** - Brute force possible
8. **Admin Has Org=0** - Can see all orgs (confusing)

### Admin Special Case

Admins (security level 64+) have `$_SESSION['org'] = 0`:
- Allows access to all clubs
- $org = 0 bypasses org filtering in queries
- Non-admins are restricted to their org

## Audit Trail

### audit table
Records login and actions:
```sql
INSERT INTO audit (userid, memberid, description) 
VALUES (user_id, member_id, 'Login description');
```

Access via `/Audits` route (requires level 64).

## Password Reset

- `Forgotten.php` - Request password reset
- `changepw.php` - Change password (force reset supported)
- `force_pw_reset` flag in users table - forces change on next login