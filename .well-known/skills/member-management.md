---
description: Member system architecture - classes, statuses, roles, groups, and the difference between member types and user accounts
mode: subagent
---

# Member Management Guide

## Core Entities

### members table
Pilot/member records - **40+ fields** - most complex table:
```
id, member_id, org, firstname, surname, displayname
date_of_birth
mem_addr1-4, mem_city, mem_country, mem_postcode (postal)
emerg_addr1-4, emerg_city, emerg_country, emerg_postcode (emergency contact)
gnz_number, qgp_number (Gliding NZ IDs)
class (membership_class FK), status (membership_status FK)
phone_home, phone_mobile, phone_work
email
gone_solo, enable_text, enable_email (communication prefs)
medical_expire, bfr_expire, icr_expire (medical dates)
official_observer, first_aider (flags)
```

### users table
System user accounts (login) - separate from members:
```
id, name, usercode, password (MD5 hash!), org, expire
securitylevel (bitmask), member (FK to members), force_pw_reset
```

## Key Concept: Member vs User

A **member** is a pilot/person in the gliding club.
A **user** is a system login account.

They are SEPARATE tables linked by `users.member = members.id`.

A person can be:
- Member only (no system account)
- User only (admin with no linked member)
- Both member AND user (most common)

## Membership Classes (membership_class)

**41 different types** - affects billing rates:

| ID | Class | Count | Notes |
|----|-------|-------|-------|
| 5 | Short Term | 1999 | Most common |
| 22 | Trial Flight | 735 | Trial flights |
| 1 | Flying | 112 | Standard flying member |
| 2 | Youth | 93 | Youth pilots |
| 18 | A Scheme | 69 | Incentive scheme |
| 24 | Visiting Pilot | 49 | Visitors |

**Key billing implications:**
- Some classes get discounted rates (Junior, Youth, etc.)
- Code has logic per class in accountrules.php
- Class is stored as FK to membership_class.id (NOT the name!)

## Membership Status (membership_status)

**4 values** - affects who appears in lists:

| ID | Status | Usage |
|----|--------|-------|
| 1 | Active | Currently flying members |
| 2 | Passive | Non-flying, still in system |
| 3 | Resigned | Left the club |
| 4 | Deceased | Deceased members (kept for records) |

**Logic implications:**
- Active members appear in dropdowns, can be assigned to flights
- Passive/Resigned may be hidden from some lists
- Deceased kept for historical flight records

## Roles (roles table)

Predefined positions - informational, NOT security enforcement:
- A/B Cat Instructor
- C Cat Instructor
- Tow Pilot
- Winch Driver
- Launch Point Controller
- Engineer
- Committee/Management Team
- Member

**role_member table** links members to roles:
```
id, org, role_id, member_id
```

Note: This is SEPARATE from security levels - a member can have role "Tow Pilot" without having elevated security access.

## Groups (groups table)

Member groups (different from roles):
```
id, org, name
```

**group_member table:**
```
gm_group_id, gm_member_id
```

Used for daily ops grouping (Group A, Group B, etc.)

## Security Levels (users.securitylevel)

Bitmask stored in `$_SESSION['security']`:

| Level | Value | Name | Description |
|-------|-------|------|-------------|
| 0 | 0 | None | Not logged in |
| 1 | 1 | Member | Basic member access |
| 2 | 2 | Booking Admin | Can manage bookings |
| 4 | 4 | Daily Ops | Can enter flights |
| 8 | 8 | CFO/Treasurer | Billing access |
| 16 | 16 | CFI | Chief Flight Instructor |
| 32 | 32 | Engineer | Engineering reports |
| 64 | 64 | Admin | Full admin |
| 128 | 128 | God | Super admin |

Check: `$_SESSION['security'] & LEVEL` (non-zero = has access)

## Modern vs Legacy Pages

**Modern (DataTables, AJAX):**
- `members-list-v2b.php` - Route: `/AllMembers`
- `members-new.php` - Route: `/MemberNew` (add/edit)

**Legacy (table-based, no AJAX):**
- `members.php` - Route: `/Member`
- `members-list.php` - Route: `/MembersListOld`

## API Endpoints for Members

| Endpoint | Purpose |
|----------|---------|
| `api/members.php` | DataTables server-side AJAX for member list |
| `api/member-form.php` | GET: classes/statuses/roles, POST: save member |
| `api/members-email.php` | Autocomplete member search by email |

## Key Fields for Flight Assignment

When assigning a member to a flight (PIC, P2, Towpilot):
- `members.id` - primary key
- `members.displayname` - shown in dropdown
- `members.firstname`, `members.surname` - for display
- `members.email` - for notifications

## Member Form Sections (members-new.php)

1. **Member Details**: name, DOB, contact info, GNZ/QGP numbers
2. **Address**: postal address
3. **Emergency Contact**: emerg_addr*, emerg_phone
4. **Roles**: checkboxes for role assignments

## Photo Upload

Photos uploaded to: `/media/members/<org>/`
Format: Members display photo on list pages (60px thumbnail)

## Enable/Disable Fields

- `enable_email` - email notifications (always true on new forms)
- `enable_text` - SMS opt-in (**DEAD** - never implemented)
- `gone_solo` - solo flight flag

## Database Query Pattern

When joining members with users:
```sql
SELECT m.*, u.securitylevel 
FROM members m 
LEFT JOIN users u ON u.member = m.id 
WHERE m.id = ?
```

Note: `members.id` NOT `members.member_id` (common mistake!)