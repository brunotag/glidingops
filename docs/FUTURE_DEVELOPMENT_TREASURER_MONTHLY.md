# Treasurer Monthly Billing Report

## Overview

A new Treasurer report page that calculates real charges for each member's flights in a given month, following the Nov 2025 Schedule of Fees (`docs/FUTURE_DEVELOPMENT_FEES.md`).

## File & Routes

| Item | Value |
|---|---|
| File | `billing-report.php` |

| Route | `/BillingReport` → `billing-report.php` |

| Export | `/BillingReport.csv` → `billing-report.php` |

## DB Rate Updates Required

The database has outdated rates that must match the Fee PDF before the report is usable.

### Glider rates (`aircraft` table)

| Field | Old Value | New Value |
|---|---|---|
| `charge_per_minute` | $1.50 | $2.25 |
| `max_perflight_charge` | $180.00 | $180.00 (unchanged) |

### Winch charges (`charges` table)

The DB has two rows ($50 standard, $30 youth) that must become $39 (first launch) and $25 (relaunch). The Fee PDF has no youth differentiation for winch — youth pay the same as full members.

---

## Page Layout

### Panel 1: Month Selector + Summary Bar

- Month/year dropdown with "Current Month" button
- Summary line: total flights, competition (aerotow) flights flagged, total $ billed, # members

### Panel 2: Trial Flights (reconciliation)

- All flights with trial billing options (Trial Cash on Day, Trial Club Voucher, Trial Grab-one/Treat)
- Columns: Date, Glider, PIC, P2, Launch type, Duration, Trial type, Package price
- Read-only; no charges calculated
- DataTable sorted by date

### Panel 3: Member Flight Charges (main table)

Every non-trial flight in the period, grouped by billing member, sorted alphabetically.

**3 charge columns** (replaces the previous 6-column mess: Tow, Glider, Airways, Total, Scheme, Ledger):

| Column | Calculation |
|---|---|
| **Glider** | `aircraft.charge_per_minute` × minutes, capped at `max_perflight_charge`. Youth discount on GGR/GPJ/GMB = $1.50/min. Youth on GNB = full $2.25. |
| **Launch** | Winch: 1st launch of day per billing member = $39, relaunch = $25. Self-launch: landing fee $25. Aerotow: blank + "Competition" note. |
| **Total** | Sum of glider + launch (excludes aerotow rows) |

**Special row types:**
- **Aerotow**: Highlighted yellow with "COMP" badge, Total = $0 (tow operator bills separately for competitions)
- **No Charge**: All columns populated, Total = $0.00
- **50/50 splits**: Charge halved when `bill_pic=1 AND bill_p2=1`, flight appears under both billing members

Per-member subtotal row with flight count and $ total.

### Panel 4: Quarterly Membership (informational)

- **Mar/Jun/Sep/Dec**: Full Flying members ($337.50/qtr), Youth ($55/qtr)
- **Jul**: Additional Family ($250/yr), Associate ($100/yr)
- **Every month**: Short Term ($150/mo)

Just a list — no invoicing, just "these members are due".

---

## Calculation Engine

Not reliant on `accountrules.php`. Four clean inline functions:

```
calcGliderCharge(clubGlider, regoShort, totMins, memberClassName,
                 chargePerMinute, maxPerFlightCharge)
  → if Youth + (GGR|GPJ|GMB) → use $1.50 (hardcoded per Fee PDF)
  → if Youth + GNB → use full $2.25 (no discount)
  → apply cap → return $

calcLaunchCharge(launchType, isFirstWinchLaunchOfDay)
  → Winch + firstLaunch → $39
  → Winch + !firstLaunch → $25
  → Self-launch → $25
  → Aerotow → $0 (flagged as competition)
  → else → $0

isFirstWinchLaunchForBillingMember(con, org, memberId, localdate, seq)
  → checks if billing member has any winch flights on same day with lower seq
  → returns bool
```

---

## CSV Export

Flat rows (one per flight). Columns: Surname, FirstName, Date, Location, Glider, PIC, P2, Launch, Duration, BillingOption, GliderCharge, LaunchCharge, Total, Notes.

Includes trial flights section and member flights section, matching the HTML panels.

---

## .htaccess Routes

```apache
RewriteRule ^BillingReport$ billing-report.php [L,QSA]
RewriteRule ^BillingReport\.csv$ billing-report.php [L,QSA]
```

---

## What's Removed vs Old Reports

| Old Feature | Status | Reason |
|---|---|---|
| Aerotow tow charge (height-based) | Dropped | No aerotow in normal ops; competition flagged separately |
| "Other clubs" section | Dropped | Individual flights appear under their billing member |
| Incentive scheme logic | Dropped | Will return later, not needed now |
| Airways charges | Dropped | No airways charges exist in DB for org 1 |
| 6 charge columns | Replaced with 3 | Glider, Launch, Total |
