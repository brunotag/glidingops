# GlidingOps

A bunch of tools to assist gliding operations.
Based on original by Tim Hogan.

## Quick Reference

### Documentation (in `docs/`)

| Area | Documentation |
|------|---------------|
| **Start Here** | This file - overview and navigation |
| **Architecture** | [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) - Technical structure |
| **Database** | [docs/DATABASE.md](docs/DATABASE.md) - Tables, schemas, relationships |
| **Features** | [docs/FEATURES.md](docs/FEATURES.md) - What each feature does |
| **Routes** | [docs/ROUTES.md](docs/ROUTES.md) - URL routing via .htaccess |
| **Security** | [docs/SECURITY.md](docs/SECURITY.md) - Auth, permissions, roles |
| **Messaging** | [docs/MESSAGING.md](docs/MESSAGING.md) - Email/SMS system |
| **Dead Code** | [docs/DEAD_CODE.md](docs/DEAD_CODE.md) - What can be deleted |
| **Codebase** | [docs/CODEBASE_MAP.md](docs/CODEBASE_MAP.md) - File organization |
| **Web Auth** | [docs/WEB_AUTH.md](docs/WEB_AUTH.md) - Session handling |
| **Develop** | [docs/DEVELOP.md](docs/DEVELOP.md) - Dev workflow, logging, API patterns |
| **TODO** | [docs/TODO.md](docs/TODO.md) - Known issues and planned work |

### Dev Environment

```bash
# SSH into Vagrant
cd lrv; vagrant ssh

# PHP syntax check
vagrant ssh -c "php -l ./code/<path>" 2>&1

# Access
https://glidingops.test
Username: [dev-creds] / Password: [dev-creds]
```

### Database Config

Two config locations needed:

1. `lrv/.env` - Laravel config
2. `config/database.php` - Custom PHP config

### Key Files

- `helpers/api-base.php` - API error handling, `apiExit()`
- `helpers/logging.php` - `logMsg()` for local debug logging
- `log/app.log` - Debug log (local only, gitignored)
- `log/error.log` - PHP errors (local only, gitignored)

### Log Locations (Local Dev)

- `log/app.log`
- `log/error.log`

Read directly with `Read` tool - NO vagrant ssh needed.
