# Reports Modernization

**STATUS: PARTIALLY IMPLEMENTED** — `billing-report.php` built with 3-column charge model. Column X question still open.

## Accountant Feedback (2026-05-10)

### Current Usage

**Treasurer's Report** (most important)
- Supplies base information on flights for charging members
- Columns F through O and X are copied to Excel monthly
- Accountant manually enters charges into MYOB (complexity of rates makes automation impractical)
- **Obsolete columns removed:** Airways, Tow, Glider, Total — replaced by 3-column model (Glider $, Launch $, Total $) in `billing-report.php`

**All Flights Report** (secondary)
- Used to verify total flight count matches Treasurer's Report
- Just a reconciliation check

### What Accountant Does Manually
1. Export Treasurer's Report to Excel (now via `/BillingReport.csv`)
2. Copy columns A through N to their monthly sheet
3. Enter charges manually into MYOB
4. Run various checks/balances at bottom of their report

---

## Columns Reference

Based on the old `Treasurer.php` Members Accounts section. The new `billing-report.php` uses a clean 3-column charge model:

| Old Column | Header | Status in New Report |
|--------|--------|---------------------|
| A | DATE | Keep |
| B | LOCATION | Keep |
| C | GLIDER | Keep |
| D | PIC | Keep |
| E | P2 | Keep |
| F | TOW TIME / HEIGHT | Removed |
| G | DURATION | Keep (renamed to Time) |
| H | TYPE | Removed |
| I | CHARGING (billing option) | Keep |
| J | TOW (charge $) | Removed |
| K | GLIDER (charge $) | Replaced |
| L | AIRWAYS | Removed |
| M | TOTAL (old) | Removed |
| N | COMMENTS | Keep |

New charge columns: **Glider $, Launch $, Total $** (columns K-M in new report)

---

## Sections in Treasurer's Report (New)

1. **Trial Flights** - reconciliation panel (retained)
2. **Member Flight Charges** - main section (replaces old "Members Accounts")
3. **Membership Subscriptions** - informational panel (new)

Sections removed: Tug Only Check Flights (no longer relevant when no aerotow), No Charge Flights (shown inline with $0), Other Clubs (flights appear under billing member).

---

## What Was Built

`billing-report.php` at `/BillingReport`:
- 3 charge columns (Glider $, Launch $, Total $) replacing the old 6-column mess
- Collapsible member rows with expand/collapse all toggle
- CSV export at `/BillingReport.csv`
- Trial flights DataTable
- Membership subscriptions informational panel

## Remaining Questions (Still Open)

1. **Column X** - What is this column in the accountant's xlsm? Need to analyze `2026-04 April.xlsm`.
2. **Current billing options** - Which ones are actually used? (to possibly simplify the charging dropdown)