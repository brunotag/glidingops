# Passwordless Magic Link Login

## Overview

Implement email-based passwordless login (magic links) to reduce password friction and improve user experience. Users enter their email, receive a link via email, and click to log in.

**Key principle:** Email+password login remains available as fallback.

---

## Requirements

1. Email + password still works (backup login method)
2. Magic link sent when user enters email
3. Token: single-use, expires in ~15 minutes
4. Users stay logged in after clicking link (standard session)
5. Every user has a matching `members` record with email
6. `users.usercode` = email (login name)

---

## Current System Context

- `helpers/mail.php` - existing Mail class with `SendMailPlainText()` and `SendMailHtml()` methods
- `Login.php` → `checklogin.php` → PHP session
- `Forgotten.php` already sends emails (can use as reference)
- `users.member` FK to `members` table (which has member email)

---

## Implementation Plan

### 1. Database - New table `magic_link_tokens`

```sql
CREATE TABLE magic_link_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  INDEX idx_token (token),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Notes:**
- `token` - 64 character random string (sufficient entropy)
- `created_at` - needed to check expiry
- `used_at` - NULL means unused, DATETIME if used
- User FK ensures tokens are tied to valid users

### 2. New Files

| File | Purpose |
|------|---------|
| `api/magic-link-request.php` | Generate token & send email |
| `api/magic-link-verify.php` | Validate token & create session |
| `Login.php` | Modified to show magic link option |

### 3. API Endpoints

#### `POST /api/magic-link-request`

**Request:**
```json
{ "email": "user@example.com" }
```

**Flow:**
1. Validate email format
2. Look up user by `usercode` (email)
3. Get member record for user's `member` ID
4. Generate random 64-char token
5. Store token in DB with `created_at = NOW()`
6. Send email with magic link
7. Return success (always - don't reveal if user exists)

**Response:**
```json
{ "success": true }
```

**Email content:**
```
Subject: Your Gliding Ops Login Link

Click the link below to log in:
https://glidingops.test/Login.php?token=XXXXXXXX&action=verify

This link expires in 15 minutes and can only be used once.
```

#### `GET /api/magic-link-verify?token=XXX`

**Flow:**
1. Look up token in DB
2. Check if token exists → error "Invalid link"
3. Check if `used_at` IS NOT NULL → error "Link already used"
4. Check if `created_at` > 15 minutes ago → error "Link expired"
5. All checks pass → mark `used_at = NOW()`, create session, redirect to home

**Response on error:**
```json
{ "error": "link_expired" } // or "link_used", "invalid_link"
```

**Response on success:**
Redirect to home.php with valid session.

### 4. Login.php Modifications

Add a "Login with email link" option alongside the existing email+password form.

**Options:**
- Tab-based: "Password Login" | "Email Link Login"
- Or: Link to switch to email-only form
- Or: Show password field initially, with "Forgot password? Login via email link instead"

**UI Flow:**
1. User enters email
2. Clicks "Send Login Link"
3. Sees "Check your email" message
4. User opens email, clicks link
5. Redirected to home (logged in)

### 5. Token Validation Rules

| Check | Error Message |
|-------|---------------|
| Token doesn't exist | "Invalid link" |
| Token already used | "Link already used, request a new one" |
| Token older than 15 min | "Link expired, request a new one" |
| User deleted/inactive | "Invalid link" |

### 6. Security Considerations

1. **Timing attacks:** Even if user doesn't exist, return same message ("Check your email")
2. **Rate limiting:** Limit magic link requests per email (e.g., 3 per hour)
3. **Single use:** Mark token as used immediately on successful login
4. **Short expiry:** 15 minutes is reasonable
5. **HTTPS:** All links must use HTTPS in production

### 7. Edge Cases

| Scenario | Behavior |
|----------|----------|
| Email not found | "If an account exists, a login link has been sent" (don't reveal existence) |
| User without member record | Still allow magic link, but email must come from users.usercode |
| Multiple tokens pending | Old tokens still valid (until used or expired) - user could get multiple emails |
| User logs out | Optionally invalidate all pending tokens for that user (scope creep - skip for v1) |

---

## File Changes Summary

### New Files
- `api/magic-link-request.php`
- `api/magic-link-verify.php`
- `docs/FUTURE_DEVELOPMENT_MAGIC_LINK.md` (this file)

### Modified Files
- `Login.php` - add magic link option
- `.htaccess` - add routes for new API endpoints

### Database
- Create `magic_link_tokens` table

---

## Implementation Order

1. Create `magic_link_tokens` table
2. Implement `api/magic-link-verify.php` (verify logic first, easier to test)
3. Implement `api/magic-link-request.php` (generate & send)
4. Add `.htaccess` routes for both endpoints
5. Modify `Login.php` to include magic link option
6. Test end-to-end flow

---

## Configuration

```php
// magic link settings
const MAGIC_LINK_EXPIRY_MINUTES = 15;
const MAGIC_LINK_TOKEN_LENGTH = 64;
```

---

## TODO

- [ ] Create magic_link_tokens table
- [ ] Implement api/magic-link-verify.php
- [ ] Implement api/magic-link-request.php
- [ ] Add .htaccess routes
- [ ] Modify Login.php
- [ ] Test end-to-end