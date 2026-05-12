---
description: How to analyze billing reports, identify broken calculations, and understand what can be cleaned up
mode: subagent
---

# Billing Analysis Guide

## Overview

The billing system (in Treasurer.php and orgs/*/accountrules.php) is ASSUMED BROKEN. The Treasurer report shows times that are accurate, but fees are wrong.

## Key Files

| File | Purpose |
|------|---------|
| Treasurer.php | Monthly billing report (867 lines) |
| Treasurer2.php | CSV export for GlideAccounts |
| orgs/*/accountrules.php | Billing calculation functions per org |

## Broken Calculations

Treasurer.php calls billing functions with HARDCODED dummy values:

```php
$towcost = CalcTowCharge2($org, $launchtype, $towplane, $duration, $height, "", 1, 0);
//                                                                    ^^  ^  ^
//                                                             empty string     is5050
//                                                             (not actual      hardcoded
//                                                              member class)    club_glider=1

$glidcost = CalcGliderCharge($org, 1, $rego, 0, 0.00, 0, $mins, "");
//                          ^ ^ ^    ^   ^       ^    ^   ^
//                     hardcoded  hardcoded empty
//                     club_glider ignore_schemes iRateGlider
```

## What Works vs What's Broken

**WORKING (trust these):**
- Flight times (start, towland, land)
- Flight dates and sequence numbers
- PIC/P2 assignments
- Launch types

**BROKEN (don't rely on):**
- Tow charges (CalcTowCharge, CalcTowCharge2)
- Glider charges (CalcGliderCharge)
- All scheme-related billing (incentive_schemes, scheme_subs)
- "No Charge" logic calculations

## Functions to Delete (After Verification)

In all 5 orgs/*/accountrules.php:
- `CalcTowCharge()` - uses ID comparison (inconsistent)
- `CalcTowCharge2()` - uses string comparison (inconsistent)
- `CalcGliderCharge()`
- `CalcOtherCharges()`
- All scheme_subs queries

## Database Tables Related to Billing

**Active/Used:**
- flights (times are good)
- aircraft (rego, rates)
- towcharges (height/time pricing rules)
- billingoptions (who gets charged)

**Legacy (can delete after verification):**
- incentive_schemes (16 schemes, no active use)
- scheme_subs (only 1 exists, billing ignores it)
- vouchers, vouchertype (never implemented)

## Cleanup Priority

1. **Safe to delete files:**
   - incentive_schemes.php, incentive_schemes-list.php
   - scheme_subs.php, scheme_subs-list.php
   - vouchers.php, vouchers-list.php, vouchertype.php, vouchertype-list.php

2. **After testing Treasurer output:**
   - Remove CalcTowCharge*, CalcGliderCharge, CalcOtherCharges from all accountrules.php
   - Simplify Treasurer.php to show times only

3. **Database cleanup (verify first):**
   - incentive_schemes table
   - scheme_subs table
   - vouchers, vouchertype tables

## Verification Steps

Before cleanup, confirm:
1. Treasurer.php output for fees is NOT used by anyone
2. No external system relies on calculated fees
3. Members don't expect accurate billing from this system