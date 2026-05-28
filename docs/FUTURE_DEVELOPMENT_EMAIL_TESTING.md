# Email Testing on Local Dev

**STATUS: PARTIALLY IMPLEMENTED** — A Python SMTP logger on port 1025 captures emails to `~/emails.log`. See `DEVELOP.md` (Email Testing in Dev section) for the current setup.

## The Problem

On localhost (local dev machine), there's no real email server. When `mail()` is called, PHP's `mail()` function returns `true` because the local MTA accepted the message, but the email goes into a black hole - no actual delivery happens.

## Current Solution (Implemented)

A Python `smtpd.SMTPServer` logger runs on port 1025 in the Vagrant box:
- PHP `mail()` routes through `ssmtp` to `127.0.0.1:1025`
- Python logger captures each email to `~/emails.log`
- Starts automatically in `after.sh` on `vagrant provision`
- View: `cat ~/emails.log` or `tail -f ~/emails.log`

**What's missing:** No web UI. Viewing captured emails requires SSH into vagrant and reading a log file.

## Future Enhancements (Not Implemented)

### Option 1: Dry-Run / Test Mode

Add a checkbox on the messaging page: "Test mode - don't actually send"

**Behavior when checked:**
- Skip `Mail::SendMail()` call
- Still run through the full flow (insert into messages, texts tables, etc.)
- Show all recipients as "would be sent" in the result
- No actual email is sent

### Option 2: MailCatcher / MailHog

Install a local SMTP server that captures all outgoing emails and provides a web UI.

**Setup:**
- MailHog: `docker run -p 1025:1025 -p 8025:8025 mailhog/mailhog`
- Or MailCatcher: `gem install mailcatcher`

**Access:** Open `http://localhost:8025` to see captured emails in a web UI.

### Option 3: Dev Warning Banner

Detect localhost/dev environment and show a prominent warning:
> "⚠️ DEVELOPMENT SERVER - Emails are not actually being sent"

### Option 4: Add Web UI to Python Logger

The simplest enhancement to the current solution — add an HTTP endpoint to the existing Python SMTP logger to display captured emails in a browser.

## Recommended Next Step

Add a web UI to the existing Python SMTP logger — minimal code, gives the same benefit as MailCatcher without Docker dependencies.
