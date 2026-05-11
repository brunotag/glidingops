# Email Testing on Local Dev

## The Problem

On localhost (local dev machine), there's no real email server. When `mail()` is called, PHP's `mail()` function returns `true` because the local MTA accepted the message, but the email goes into a black hole - no actual delivery happens.

**Current behavior:** No indication to the user that emails aren't actually being sent.

## Options for Email Testing

### Option 1: Dry-Run / Test Mode (Recommended)

Add a checkbox on the messaging page: "Test mode - don't actually send"

**Behavior when checked:**
- Skip `Mail::SendMail()` call
- Still run through the full flow (insert into messages, texts tables, etc.)
- Show all recipients as "would be sent" in the result
- No actual email is sent

**Pros:** Simple to implement, clear UX, doesn't require any infrastructure setup
**Cons:** Doesn't test real email delivery

---

### Option 2: MailCatcher / Mailhog

Install a local SMTP server that captures all outgoing emails and provides a web UI.

**Setup:**
- MailHog: `docker run -p 1025:1025 -p 8025:8025 mailhog/mailhog`
- Or MailCatcher: `gem install mailcatcher`

**Configure PHP to use it:**
```php
// In php.ini or .htaccess
sendmail_path = /usr/local/bin/mailcatcher --host 127.0.0.1 --port 1025
```

**Access:** Open `http://localhost:8025` to see captured emails in a web UI.

**Pros:** Real email testing with full SMTP flow, web UI to view emails
**Cons:** Requires Docker or additional tools installed, more setup

---

### Option 3: Dev Warning Banner

Detect localhost/dev environment and show a prominent warning:
> "⚠️ DEVELOPMENT SERVER - Emails are not actually being sent"

**Implementation:**
```php
function isDevServer() {
    return $_SERVER['HTTP_HOST'] === 'glidingops.test'
        || $_SERVER['REMOTE_ADDR'] === '127.0.0.1'
        || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
}
```

**Pros:** Clear indication, no infrastructure needed
**Cons:** Warning might be ignored, doesn't enable actual email testing

---

### Option 4: Log Emails to File

In dev mode, instead of calling `mail()`, append email content to a log file:
```
log/email-dev.log
```

**Format:**
```
[2026-05-11 15:30:45]
To: john@example.com
Subject: WWGC Msg | Mon 11 May 3:30 PM
---
Message body here
---
```

**Pros:** Simple, no extra tools, persistent record
**Cons:** No web UI, harder to click links in emails, no HTML rendering

---

## Recommended Approach

**Implement Option 1 (Dry-Run Mode)** as the simplest first step:

1. Add checkbox: "Test mode (don't send)"
2. When checked, skip `Mail::SendMail()` but complete all other steps
3. Show preview of what would have been sent in the result modal

**Future enhancement:** Add Option 2 (MailCatcher) for more realistic testing when needed.

---

## Files to Modify

- `MessagingPage.php` — Add test mode checkbox, modify send flow
- `helpers/mail.php` — Potentially add a `SendMailDryRun()` method that just logs
- Possibly add config flag in `config/database.php` or environment check
