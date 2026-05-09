# GlidingOps - Application Documentation

## Overview
This is a gliding club operations management system with two databases:
- `gliding` - main application (members, flights, billing, etc.)
- `tracks` - GPS tracking data from Flarm devices

The application is a mix of:
- **Root PHP** (~100 custom PHP files) - original bespoke system
- **Laravel 5.x (lrv/)** - partial installation, mostly dead code

## Quick Navigation

| Area | Documentation |
|------|---------------|
| **Architecture** | [ARCHITECTURE.md](ARCHITECTURE.md) - Technical structure |
| **Database** | [DATABASE.md](DATABASE.md) - Tables, schemas, relationships |
| **Features** | [FEATURES.md](FEATURES.md) - What each feature does |
| **Routes** | [ROUTES.md](ROUTES.md) - URL routing via .htaccess |
| **Security** | [SECURITY.md](SECURITY.md) - Auth, permissions, roles |
| **Messaging** | [MESSAGING.md](MESSAGING.md) - Email/SMS system |
| **Dead Code** | [DEAD_CODE.md](DEAD_CODE.md) - What can be deleted |
| **Codebase** | [CODEBASE_MAP.md](CODEBASE_MAP.md) - File organization |

## Key Concepts

### Multi-Tenant
- 5 organisations (clubs)
- Each has custom: headers, menus, billing rules
- Config in `/orgs/{1-5}/` folders

### Core Features (Used)
- Daily Ops - flight entry (StartDay.php, DailySheet.php)
- Flight Tracking - live map (MasterDisplay.php)
- Members/Users - management (messy, needs mobile-friendly)
- Reports - Treasurer, Engineer, Currency
- Messaging - broadcast emails
- Aircraft - gliders & towplanes
- Tracking devices - spots for Flarm

### Known Issues
- Billing/fees calculation broken ("fees are wrong but times can be trusted")
- Not mobile-friendly (all list/edit pages)
- Lots of dead code to clean up

## Quick Commands

```bash
# List all PHP files
ls *.php

# List all -list.php (list pages)
ls *-list.php

# Find a specific feature
grep -r "pattern" --include="*.php"
```

## Org-Specific Customization

| Org ID | Club | Status |
|--------|------|--------|
| 1 | Wellington Gliding Club | **Active - only one in use** |
| 2 | The Soaring Society of Boulder | Legacy - not used |
| 3 | Canterbury Gliding Club | Legacy - not used |
| 4 | Auckland Gliding Club Inc | Legacy - not used |
| 5 | Masterton Soaring Centre | Legacy - not used |

**Note:** Multi-tenant architecture was designed but only org 1 is active.

Custom files in `/orgs/{id}/`:
- `heading*.txt/css` - Headers/footers
- `menu1.txt/css` - Navigation menu
- `accountrules.php` - Billing calculations
- `orgHelpers.php` - Organization helpers