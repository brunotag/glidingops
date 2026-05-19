# External Dependencies & API Consumers

## Overview

This document registers known consumers of the GOPS APIs and PHP endpoints.
Any change to an API endpoint must be **non-breaking** (backward-compatible)
to avoid silently breaking external scripts.

## Dependency Registry

### Martien's Script (wwgc.py / wwgc_api.py)

| Field | Value |
|-------|-------|
| **Maintainer** | Martien |
| **Location** | `tmp/wwgc_api.py` (API version), `tmp/wwgc.py` (Selenium version) |
| **Purpose** | Flight data monitoring, member reporting, PNG graphs, Excel reports |
| **APIs consumed** | `GET /api/flights-report`, `GET /api/members` |
| **Auth method** | Session cookie via `POST /checklogin.php` |
| **Schedule** | Cron (weekly, Monday 06:00) |
| **Fields consumed** | See below |

#### `/api/flights-report` — fields consumed

| # | Field | Notes |
|---|-------|-------|
| 0 | `date` | DD/MM/YYYY |
| 1 | `seq` | Flight sequence number |
| 2 | `location` | Airport / location name |
| 3 | `launch_type` | e.g. "Tow Plane", "Winch" |
| 4 | `towplane` / `tow` | Tow plane registration |
| 5 | `glider` | Glider registration |
| 6 | `towpilot` / `towy` | Tow pilot display name |
| 7 | `pic` | Pilot in command |
| 8 | `p2` | Second pilot |
| 9 | `take_off` | Take-off time (HH:MM) |
| 10 | `land` | Landing time (HH:MM) |
| 11 | `duration` | Flight duration |
| E | `date` must remain sortable as YYYY-MM-DD / DD/MM/YYYY |
| E | `seq` must be numeric for sorting |

#### `/api/members` — fields consumed

| Field | Notes |
|-------|-------|
| `firstname` | Mapped to `first_name` |
| `surname` | Mapped to `last_name` |
| `displayname` | Mapped to `bill_to` |
| `class` | Mapped to `membership_type` |
| `status` | Mapped to `status` |
| A | `class` must stay as the class name string (not an ID) |

### Other Known Consumers

| Consumer | Endpoint | Auth | Notes |
|----------|----------|------|-------|
| AllFlightsReportNew.php (DataTables) | `/api/flights-report` | Session L1 | Internal — can be updated in lockstep |
| members-list-v2b.php (DataTables) | `/api/members` | Session L1 | Internal — can be updated in lockstep |

## API Contract Rules

1. **Never remove fields** from a JSON response — consumers may rely on them.
2. **Never rename fields** — add new fields alongside old ones if renaming is necessary.
3. **New fields must be additive** — existing consumers must not break when new fields appear.
4. **Response structure must remain stable** — the top-level keys (`data`, `draw`, `recordsTotal`, etc.) must not change.
5. **Pagination behaviour** — `start`, `length`, `recordsTotal`, `recordsFiltered` semantics must remain consistent.
6. **Sort order** — default sort order and column indices must not change without updating consumers.
7. **Date/time formats** — must remain consistent (DD/MM/YYYY for dates, H:i for times).
8. **Any API change** must be communicated to Martien before deployment.

## Adding a New Dependency

To register a new external consumer:
1. Add an entry to the table above
2. List every field consumed
3. Note any implicit assumptions about format, ordering, or semantics
4. Update this file in the same PR that makes the API change
