# Reports Modernization

## Accountant Feedback (2026-05-10)

### Current Usage

**Treasurer's Report** (most important)
- Supplies base information on flights for charging members
- Columns F through O and X are copied to Excel monthly
- Accountant manually enters charges into MYOB (complexity of rates makes automation impractical)
- **Obsolete columns to remove:** Airways, Tow, Glider, Total (not reflecting current charges)

**All Flights Report** (secondary)
- Used to verify total flight count matches Treasurer's Report
- Just a reconciliation check

### What Accountant Does Manually
1. Export Treasurer's Report to Excel
2. Copy columns F through O and X to their monthly sheet
3. Enter charges manually into MYOB
4. Run various checks/balances at bottom of their report

---

## Columns Reference

Based on Treasurer.php Members Accounts section (lines 666-674):

| Column | Header | Status |
|--------|--------|--------|
| A | DATE | Keep |
| B | LOCATION | Keep |
| C | GLIDER | Keep |
| D | PIC | Keep |
| E | P2 | Keep |
| F | TOW TIME (if time-based) | Remove - obsolete |
| G | DURATION | Keep |
| H | HEIGHT or TYPE | Remove - obsolete |
| I | CHARGING (billing option) | Keep |
| J | TOW | Remove - obsolete |
| K | GLIDER | Remove - obsolete |
| L | AIRWAYS | Remove - obsolete |
| M | TOTAL | Remove - obsolete |
| N | COMMENTS | Keep |
| O | INCENTIVE SCHEME USED | Keep |
| ... | ... | ... |
| X | (unknown - needs xlsm analysis) | TBD |

---

## Sections in Treasurer's Report

1. **Tug Only Check Flights** - check flights for tugs
2. **No Charge Flights** - flights marked as no charge
3. **Trial Flights** - trial/introduction flights
4. **Other Clubs** - external club flights
5. **Members Accounts** - main section with charges (most important)
6. **Summary** - totals and reconciliation

---

## Proposed Modernization

### Goals
1. Keep Treasurer's Report but remove obsolete columns (Airways, Tow, Glider, Total)
2. Simplify output - focus on what accountant actually copies
3. Improve export format (CSV/Excel ready)
4. Modernize UI with cleaner layout

### Proposed Changes

**Treasurer's Report v2:**
- Remove Airways column (CalcOtherCharges result)
- Remove Tow column
- Remove Glider column
- Remove Total column (redundant - accountant calculates)
- Keep: Date, Location, Glider, PIC, P2, Duration, Charging (billing option), Comments, Incentive Scheme

**Sections to keep:**
- Members Accounts (core)
- Summary section (useful stats)

**Sections to remove:**
- Tug Only Check Flights (not needed for charging)
- No Charge Flights (no charge, not accountant's concern)
- Trial Flights (may be needed)
- Other Clubs (may be needed)

### Export Format
- CSV with only relevant columns
- Named for easy import into MYOB-compatible spreadsheet
- Date format: DD/MM/YYYY

---

## Files to Modify

| File | Changes |
|------|---------|
| `Treasurer.php` | Remove obsolete columns, simplify sections, improve export |

---

## Next Steps

1. Analyze `2026-04 April.xlsm` to identify column X and exact column mapping
2. Confirm which sections are essential vs can be removed
3. Build Treasurer v2 with clean column set and modern UI
4. Test export matches accountant's needs

---

## Questions for Accountant

1. **Column X** - What is this column? Need to check xlsm file
2. **Other Clubs section** - Needed or can be removed?
3. **Trial Flights section** - Needed or can be removed?
4. **Tug Only Check Flights** - Needed or can be removed?
5. **Summary section** - Useful or can be removed?
6. **Incentive Scheme** - Still relevant/useful?
7. **Current billing options** - Which ones are actually used? (to possibly simplify the charging dropdown)