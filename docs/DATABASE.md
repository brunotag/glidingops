# Database Schema

## Overview

Two MySQL databases:
- `gliding` - Main application data (~30 tables)
- `tracks` - GPS tracking data (2 tables)

## gliding Database - Main Tables

### Core Entities

#### organisations
Multi-tenant organization (club) settings.

**Actually only org ID 1 is in use:**
- 1 = Wellington Gliding Club (the only active club)
- 2-5 = Legacy, not used (The Soaring Society of Boulder, Canterbury GC, Auckland GC, Masterton Soaring Centre)

```
id, name, addr1-4, country, contact_name, email
timezone, aircraft_prefix
tow_height_charging, tow_time_based (billing config)
default_location
def_launch_lat/lon, map_centre_lat/lon (map defaults)
twitter_* (API keys - mostly unused)
```

#### members
Pilot/member records. **40+ fields** - complex!
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

#### users
System user accounts (login).
```
id, name, usercode, password (MD5 hash!), org, expire
securitylevel (bitmask), member (FK to members), force_pw_reset
```

#### flights
**Core flight records** - most important table.
```
id, org, date, localdate (YYYYMMDD int), updseq
location, seq (flight number for day)
type (flighttypes FK), launchtype (launchtypes FK)
towplane (aircraft FK), glider (rego string)
towpilot, pic, p2 (members FK)
start, towland, land (BIGINT - Unix ms timestamps!)
height (aero tow height in feet)
billing_option (billingoptions FK)
billing_member1, billing_member2 (for "other" billing)
comments, finalised, deleted
```

**Time Storage - IMPORTANT:**
- `start`, `towland`, `land` stored as BIGINT (Unix timestamp in **milliseconds**)
- JavaScript handles all time calculations (DailySheet.js)
- Server-side PHP receives as millisecond timestamps
- Display uses flatpickr timepicker for input
- This is unusual - most systems store as TIME or seconds

**Flight Types:**
- 1 = Glider (99.9% of flights - 27,829 records)
- 2 = Tow plane check flight (10 flights)
- 3 = Tow plane retrieve (4 flights)
- 4 = Landing Charge (2 flights)

Only type 1 (Glider) really matters.

**Time Fields Note:**
- `start`, `towland`, `land` stored as BIGINT (milliseconds since epoch)
- Duration calculated as `land - start` (in ms)
- Convert to minutes: `floor((land - start) / 60000)`

### Reference Tables

#### aircraft
Gliders and towplanes.
```
id, org, registration, rego_short, type (aircrafttype FK)
make_model, seats, serial
club_glider, bookable
charge_per_minute, max_perflight_charge (billing rates)
next_annual, next_supplementary (maintenance dates)
flarm_ICAO, spot_id (tracking device links)
```

#### aircrafttype
Types of aircraft (Glider, Towplane, etc.)
```
id, org, name
```

#### launchtypes
Launch methods - **3 types, only Tow Plane is commonly used:**

| ID | Type | Usage |
|----|------|-------|
| 1 | Tow Plane | Standard - almost all launches |
| 2 | Winch | Rarely used |
| 3 | Self Launch | Rarely used (self-launching gliders) |

**Note:** Winch and Self Launch are mostly legacy - competitions occasionally use them.

#### flighttypes
Flight categories.
```
id, name
Standard values: "Glider", "Tow plane check flight", "Tow plane retrieve", "Landing Charge"
```

#### billingoptions
Billing schemes - **14 options, affects who gets charged:**

| ID | Name | Logic |
|----|------|-------|
| 1 | Charge P2 | Charge second pilot |
| 2 | Charge PIC | Charge pilot in command |
| 3 | Trial Cash on Day | Trial paid cash |
| 4 | Trial Club Voucher | Trial with voucher |
| 5 | Trial Grab-one/Treat | Trial promotion |
| 6 | Charge 50/50 | Split between PIC and P2 |
| 7 | Visiting Pilot PIC | Visitor PIC pays |
| 8 | Visiting Pilot P2 | Visitor P2 pays |
| 9 | No Charge | Free flight |
| 10 | Other Member | Another member pays |
| 13 | Charge GWR | Gliding Wellington Region |
| 14 | Competition Flight | Competition billing |

**Important for Treasurer report:**
- Different options change which member gets billed
- Affects monthly billing calculations
- Some options like "Trial" have different rates

#### towcharges
Tow pricing rules - complex multi-dimensional pricing.

**Current data:**
- 42 height-based rules (type=0) - different heights per aircraft
- 12 time-based rules (type=1) - retrieval charges
- Covers aircraft IDs: 5, 23, 25, 26, 65, 72, 75, 79, 80, 137

**Billing modes (from organisations table):**
- `tow_height_charging` (boolean) - charge by altitude
- `tow_time_based` (boolean) - charge by time

**Structure:**
```
id, org, plane (aircraft FK), type (0=height, 1=time)
height, club_glider, member_class, effective_from, cost
```

**How pricing works:**
1. Look up towcharge by: plane + type + height + club_glider + member_class
2. Type 0 = height-based (charge by 1000ft increments)
3. Type 1 = time-based (charge per minute for retrieve)
4. If member_class matches (Junior), use that rate
5. If no class match, use generic rate (member_class = NULL)

**Note:** Two different calculation functions exist:
- `CalcTowCharge()` - uses ID comparison
- `CalcTowCharge2()` - uses string comparison (JOINs with membership_class table)
This inconsistency is a source of billing bugs!

#### charges
Other charges (airways, landing fees, winch).
```
id, org, name, location, validfrom, amount
every_flight, max_once_per_day, monthly, comments
```

### Membership Structure

#### membership_class
Member categories - **41 different types!** Many are legacy.

**Heavily used (top 10 by count):**
| ID | Class | Count | Notes |
|----|-------|-------|-------|
| 5 | Short Term | 1999 | Most common |
| 22 | Trial Flight | 735 | Trial flights |
| 1 | Flying | 112 | Standard flying member |
| 2 | Youth | 93 | Youth pilots |
| 11 | Flying | 71 | Duplicate? |
| 18 | A Scheme | 69 | Incentive scheme |
| 19 | Youth | 33 | Duplicate? |
| 24 | Visiting Pilot | 49 | Visitors |
| 23 | B Scheme | 47 | Incentive scheme |
| 33 | Tow Pilot | 19 | Tow plane drivers |

**Full list:** Flying, Youth, Family, Life, Short Term, Non Flying, Regular, Associate, Family (dup), Limited, Flying (dup), Associate (dup), Junior, Student, Life (dup), Social, Visitor, A Scheme, Youth (dup), Social Only, Temporary Member, Trial Flight, B Scheme, Visiting Pilot, Volunteer, Not current, Mag only, Flying (dup), Visiting NZ, Potential, Parent, Tow Pilot, SummerCrew, Visiting - Overseas, Life Flying, Youth-A, Youth-B, Dual-A, Honorary, Non-member

**Key billing implications:**
- Some classes get discounted rates (Junior, Youth, etc.)
- Some classes trigger different billing options
- Code has logic per class - see accountrules.php

#### membership_status
Member status - **4 values, all used:**

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

#### roles
Position/role types.
```
id, name
Standard: "A/B Cat Instructor", "C Cat Instructor", "Tow Pilot", "Winch Driver", etc.
```

#### role_member
Which members have which roles.
```
id, org, role_id, member_id
```

#### groups
Member groups (not the same as roles).
```
id, org, name
```

#### group_member
Group membership.
```
gm_group_id, gm_member_id
```

### Incentive Schemes - **LEGACY - NOT USED**

#### incentive_schemes
**Status: Legacy - 16 schemes defined but no active use**

| ID | Name | Status |
|----|------|--------|
| 1 | GNB Syndicate | Legacy |
| 2 | All Year Incentive Scheme | Legacy |
| 4 | Summer Scheme | Legacy |
| 6 | Summer Crew | Legacy |
| 7 | Prepaid Scheme | Legacy |
| 8 | Astir Owners | Legacy |
| 9 | Youth Glide Members | Legacy |
| 10 | TF20 | Legacy |
| 11 | TF30 | Legacy |
| 12 | TFWinch | Legacy |
| 13 | B Scheme | Legacy |
| 14 | A Scheme | Legacy |
| 15 | Youth Winch | Legacy |
| 16 | Youth 2000' Aerotow | Legacy |

```
id, org, name, specific_glider_list (comma-separated regos)
rate_glider (per minute rate), charge_tow (boolean), charge_airways (boolean), cost
```

**Fields:**
- `specific_glider_list`: If set, only applies to these gliders (comma-separated)
- `rate_glider`: Override per-minute rate for the glider
- `charge_tow`: Include tow in scheme (or member pays separately)
- `charge_airways`: Include airspace fees
- `cost`: Fixed cost (for non-glider charges)

#### scheme_subs
**Status: Legacy - NOT USED**

```
id, org, member (FK), start, end, scheme (incentive_schemes FK)
```

**Current state:** Only 1 subscription exists in DB, billing logic ignores it.

**How it SHOULD work (but doesn't):**
1. When calculating flight cost, check if member has active scheme subscription
2. If yes, use scheme's rate instead of aircraft's default rate
3. Check both specific-glider schemes and general schemes
4. Apply to calculate glider cost, optionally tow and airways

**What to delete:**
- Files: incentive_schemes.php, incentive_schemes-list.php, scheme_subs.php, scheme_subs-list.php
- Tables: incentive_schemes, scheme_subs
- Logic: All queries to scheme_subs in orgs/*/accountrules.php

### Operations

#### duty
Roster/duty assignments.
```
id, org, type (dutytypes FK), localdate, member
```

#### dutytypes
Duty types.
```
id, name
Values: "Instructor", "Tow Pilot", "Winch Driver", "Launch Point Controller", etc.
```

#### spots
Tracking device configuration (Flarm/OGN).
```
id, org, rego_short, spotkey, polltimelast, polltimeall, lastreq, lastlistreq
```

### Messages (Dead SMS Layer)

#### messages
Broadcast messages.
```
id, org, create_time, msg (160 char), txt_sender_member_id, is_broadcast
```

#### texts
**Mostly dead** - was SMS routing, now just email alias.
```
txt_id, txt_unique, txt_msg_id, txt_member_id, txt_to (phone)
txt_status (0=pending, 1=sent, 2=error, 3=sent via email)
txt_timestamp_create, txt_timestamp_sent, txt_timestamp_recv
```
**Note:** `txt_timestamp_create` has `DEFAULT_GENERATED on update CURRENT_TIMESTAMP`, meaning it resets to the current time on ANY update to the row. This makes it unreliable as a creation timestamp — prefer `messages.create_time` for message timing instead.

### Audit & Diagnostics

#### audit
Login/action log.
```
id, eventtime, userid, memberid, description
```

#### diag
Diagnostic log (rarely used).
```
id, create_time, data
```

---

## tracks Table (in gliding DB!)

**Note:** There are TWO track-related things:
1. `tracks` table in `gliding` database - 2,757,407 rows (live GPS data)
2. Separate `particletrack` database - also exists

### tracks (in gliding DB)
Live GPS points from tracking devices.
```
id, org, user, create_time, trip_id
glider, point_id, point_time, point_time_milli
lattitude, longitude, altitude, accuracy
```

**tracksarchive does NOT exist** - was planned but never implemented.

---

## Unused Tables (Can Delete)

These tables exist but have no active use:
- `address`, `address_type` - Not used
- `airspace`, `airspacecoords` - Was for airspace alerts, now unused
- `controllers`, `switches` - Hardware control (defunct)
- `msguser`, `msg` - Internal messaging (defunct)
- `vouchers`, `vouchertype` - Voucher system (never implemented)
- `testy` - Test table left behind