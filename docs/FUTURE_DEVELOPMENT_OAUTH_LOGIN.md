# OAuth Social Login (Google + Facebook + Apple)

## Overview

Add OAuth 2.0 login as an alternative to email+password. Members can sign in with their existing Google, Facebook, or Apple ID accounts. The email returned by the provider becomes the bridge to the existing `users` / `members` system.

**Principle:** Email+password login remains available as fallback. Social login is additive.

---

## Current System Context

### Existing Auth Flow

1. Admin creates `members` record (with email) from paper form
2. Member visits `/RegisterMe` -> enters email -> system creates `users` record with `member` FK, random MD5 password, `force_pw_reset=1`
3. Login: `Login.php` -> `checklogin.php` -> validates MD5 -> sets session vars

### Key Tables

**members:** `id, email, displayname, org, status`
**users:** `id, usercode` (email), `password` (MD5), `member` (FK->members.id), `securitylevel`, `org`, `force_pw_reset`

### Session Variables Set On Login

```php
$_SESSION['userid']     // users.id
$_SESSION['who']        // usercode (email)
$_SESSION['memberid']   // users.member -> members.id
$_SESSION['org']        // users.org
$_SESSION['security']   // users.securitylevel
$_SESSION['timezone']   // organisations.timezone
$_SESSION['dispname']   // users.name
```

### Account Linkage

`users.member` FK to `members.id`, with the critical join being `members.email = users.usercode`.

---

## Database Changes

### New Table: user_providers

```sql
CREATE TABLE user_providers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  provider VARCHAR(20) NOT NULL,    -- 'google', 'facebook', 'apple'
  provider_id VARCHAR(255) NOT NULL, -- sub/uid from the provider
  created_at DATETIME NOT NULL,
  last_login DATETIME DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  UNIQUE KEY uq_provider (provider, provider_id),
  INDEX idx_user_id (user_id)
);
```

Allows multiple providers per user. Prevents duplicate links.

---

## Provider Comparison

| Feature | Google | Facebook | Apple |
|---------|--------|----------|-------|
| Auth flow | Standard OAuth 2.0 | Standard OAuth 2.0 | OAuth 2.0 + `id_token` JWT |
| Email source | Profile API (`/userinfo`) | Profile API (`/me?fields=email`) | `id_token` JWT claims |
| Token type | Access token | Access token | `id_token` (RS256 JWT) + code |
| Verification | API call | API call | JWT signature via Apple JWKS |
| Dev setup cost | Free (Google Cloud) | Free (Meta Dev) | $99/yr (Apple Developer) |
| Private relay email | No | No | Yes (hide-my-email) |
| Scope needed | `openid email profile` | `email public_profile` | `name email` |

**Note on Apple private relay:** If the user chooses "Hide My Email", Apple returns a proxy email. This won't match `users.usercode`. The user would need to:
1. Link their real email to their existing account (password required)
2. Or re-register with the proxy email (no existing members record match)

Handle this by checking: if no `users.usercode` match, show an error suggesting they use "Share My Email" during Apple login, or link via password.

---

## Flow

```
Login.php
  |
  +-- [Email + Password] --> checklogin.php (existing, unchanged)
  |
  +-- [Sign in with Google/Facebook/Apple]
        |
        v
  oauth-login.php?provider=google
        |
        v
  Redirect to provider consent screen
        |
        v
  User approves --> callback to oauth-callback.php?provider=google&state=...&code=...
        |
        v
  1. Verify state parameter (CSRF)
  2. Exchange code for tokens
  3. Get email from provider (API or JWT)
  4. Look up users.usercode = email
        |
        +-- Found? --> Create/update user_providers row
        |              Create session (same vars as checklogin.php)
        |              Redirect to home
        |
        +-- Not found?
               |
               +-- Provider has a real email?
               |     +-- Yes --> "No account found with that email.
               |     |            Register first or link to existing account."
               |     |            Offer: Link (requires password) or Register
               |     |
               |     +-- No (Apple proxy) --> "Please use Share My Email
               |                              or sign in with email+password"
               |
               +-- Email matches a members record but no users record?
                     --> Offer to register via /RegisterMe first
```

### Account Linking (for social email not matching any user)

If the social email doesn't match any `users.usercode`, show a page with two options:

1. **Link to existing account** - User enters their existing email + MD5 password for verification, then their `users.id` is linked to the OAuth provider
2. **Register new account** - Redirect to `/RegisterMe` (which will fail if no `members` record exists for that email)

---

## New Files

### oauth-login.php

Initiates the OAuth dance. For each provider:
- Generate CSRF `state` token, store in session
- Build authorize URL with correct scopes
- Redirect user to provider

Structure:
```php
<?php
session_start();
require_once __DIR__ . '/../config/oauth.php';

$provider = $_GET['provider'] ?? '';
$providers = ['google', 'facebook', 'apple'];

if (!in_array($provider, $providers)) {
    header('Location: Login.php');
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_provider'] = $provider;

$config = $oauth_config[$provider];
// Build authorize URL...
// Redirect...
```

### oauth-callback.php

Handles the provider callback. For each provider:
- Verify `state` matches session
- Exchange `code` for tokens (POST to provider token endpoint)
- Extract email (via userinfo API for Google/Facebook, JWT decode for Apple)
- Look up `users.usercode` match
- Create/update `user_providers`
- Build session + audit log
- Redirect to home (or linking page)

Apple JWT verification uses `firebase/php-jwt`:
- Fetch Apple's JWKS from `https://appleid.apple.com/auth/keys`
- Find key matching `kid` in JWT header
- Verify RS256 signature
- Decode claims (`sub`, `email`)

### oauth-unlink.php

Allows authenticated users to disconnect a provider from their account settings page.

---

## Config (gitignored)

### config/oauth.php.sample

```php
<?php
return [
    'redirect_base' => 'https://gops.wwgc.co.nz',
    'google' => [
        'client_id' => '',
        'client_secret' => '',
        'scope' => 'openid email profile',
        'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
    ],
    'facebook' => [
        'app_id' => '',
        'app_secret' => '',
        'scope' => 'email public_profile',
        'auth_url' => 'https://www.facebook.com/v19.0/dialog/oauth',
        'token_url' => 'https://graph.facebook.com/v19.0/oauth/access_token',
        'userinfo_url' => 'https://graph.facebook.com/me?fields=id,name,email',
    ],
    'apple' => [
        'client_id' => '',          // Service ID / App ID
        'team_id' => '',             // Apple Team ID
        'key_id' => '',              // Private key ID
        'private_key' => '',         // Path to .p8 file
        'scope' => 'name email',
        'auth_url' => 'https://appleid.apple.com/auth/authorize',
        'token_url' => 'https://appleid.apple.com/auth/token',
        'jwks_url' => 'https://appleid.apple.com/auth/keys',
    ],
];
```

### config/oauth.php (gitignored)

Actual credentials, never committed.

---

## Dependency

Install `firebase/php-jwt` via composer (for Apple JWT verification only):

```bash
cd lrv && composer require firebase/php-jwt
```

No other PHP libraries needed. Google and Facebook flows use plain cURL.

---

## Login.php Changes

Add social login buttons below the existing form:

```html
<p style="text-align:center;margin-top:20px;">&mdash; or sign in with &mdash;</p>
<div style="text-align:center;">
  <a href="oauth-login.php?provider=google" style="display:inline-block;margin:5px;">
    <img src="img/btn-google.svg" alt="Sign in with Google">
  </a>
  <a href="oauth-login.php?provider=facebook" style="display:inline-block;margin:5px;">
    <img src="img/btn-facebook.svg" alt="Sign in with Facebook">
  </a>
  <a href="oauth-login.php?provider=apple" style="display:inline-block;margin:5px;">
    <img src="img/btn-apple.svg" alt="Sign in with Apple">
  </a>
</div>
```

Need to download SVG buttons for each provider.

---

## .htaccess Routes

```
# OAuth login
RewriteRule ^oauth-login$ oauth-login.php [L,QSA]
RewriteRule ^oauth-callback$ oauth-callback.php [L,QSA]
RewriteRule ^oauth-unlink$ oauth-unlink.php [L,QSA]
```

---

## Session Creation Logic (shared with checklogin.php)

After OAuth verification, must set the same session vars:

```php
$_SESSION['userid'] = $user['id'];
$_SESSION['who'] = $user['usercode'];
$_SESSION['memberid'] = $user['member'];
$_SESSION['org'] = $user['org'];
if ($_SESSION['org'] === null) $_SESSION['org'] = 0;
$_SESSION['security'] = $user['securitylevel'];
$_SESSION['dispname'] = $user['name'];

// Timezone
if ($_SESSION['org'] != 0) {
    $q = "SELECT timezone FROM organisations WHERE id = " . intval($_SESSION['org']);
    // set $_SESSION['timezone']
}

// Audit log
$desc = 'Login via ' . $provider;
// INSERT INTO audit (userid, memberid, description) VALUES (...)
```

---

## Dev Environment

OAuth providers require HTTPS redirect URIs. Options:

1. **ngrok** - tunnel localhost to public HTTPS URL
   ```bash
   ngrok http 80
   # Register https://xxxx.ngrok-free.app as dev redirect URI
   ```
2. **Provider dev mode**:
   - Google allows `http://localhost` in dev credentials
   - Facebook allows `http://localhost` in dev app settings
   - Apple requires HTTPS (use ngrok)

The `redirect_base` in `config/oauth.php` can be changed per environment.

---

## Security Considerations

1. **CSRF protection** - `state` parameter stored in session, verified on callback
2. **Rate limiting** - Limit OAuth callback attempts per IP (same as login)
3. **Account linking verification** - Before linking an OAuth account to an existing user, require password confirmation if no session exists
4. **Provider_id is the source of truth** - Not email. Email can change at the provider. Once linked, future logins match on `(provider, provider_id)`
5. **Apple JWT verification** - Verify `kid` rotation, `iss` claim, `aud` claim, expiry
6. **No new password storage** - OAuth users don't need a password (but keep the existing MD5 for fallback)

---

## Edge Cases

| Scenario | Behavior |
|----------|----------|
| No `users` record for email | Show linking page (password verify) or redirect to /RegisterMe |
| No `members` record for email | /RegisterMe will reject it - admin must create member record first |
| Apple private relay email | If `members.email` differs from Apple relay, show helpful error |
| User deletes social account | Can still login via email+password if they set one |
| Provider changes email | Second login fails `(provider, provider_id)` lookup - user sees email-not-found flow. Existing link is still based on `provider_id` |
| Multiple users same email | Not possible - `users.usercode` is unique |
| User has both Google and Facebook linked | Both work independently (multiple rows in user_providers) |

---

## Implementation Order

1. Create `user_providers` table (SQL migration)
2. Create `config/oauth.php.sample` + `config/oauth.php`
3. Install `firebase/php-jwt` via composer
4. Implement `oauth-login.php` (generic provider redirect with CSRF state)
5. Implement `oauth-callback.php` (token exchange, profile fetch, JWT decode for Apple, session create, audit)
6. Implement account linking page (optional: if social email doesn't match existing user)
7. Modify `Login.php` (add social buttons)
8. Add `.htaccess` routes
9. Add social button SVGs to `img/`
10. Test end-to-end for each provider:
    - First-time flow (no existing user_providers row)
    - Returning user flow (existing user_providers row)
    - Email not found flow
    - Apple private relay flow

---

## Relation to Magic Link

This is complementary to the magic-link plan in `FUTURE_DEVELOPMENT_MAGIC_LINK.md`. The final auth page (`Login.php`) would offer three methods:

1. Email + Password (existing, unchanged)
2. Magic Link (passwordless email)
3. Social Login (Google / Facebook / Apple)

All three converge to the same session creation code and `user_providers` / `magic_link_tokens` tables.
