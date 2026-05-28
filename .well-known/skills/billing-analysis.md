---
description: How to analyze billing reports, identify broken calculations, and understand what can be cleaned up
mode: subagent
---

# Billing Analysis Guide

## Overview

The billing system has been rebuilt. `billing-report.php` (route `/BillingReport`) now correctly calculates charges using `helpers/billing-calc.php`. The old `Treasurer.php` has been deleted. The old org `accountrules.php` functions are no longer used by the report.

## Key Files

| File | Purpose |
|------|---------|
| billing-report.php | Monthly billing report (879 lines) with collapsible member rows, CSV export |
| helpers/billing-calc.php | Pure calculation functions (glider, launch, competition, 50/50) |
| Treasurer2.php | CSV export for GlideAccounts (legacy, kept for external dependency) |
| orgs/*/accountrules.php | Legacy billing functions (no longer used by main report) |

## Current Billing System

**Working correctly (verified against Nov 2025 Fee PDF):**
- Glider charges: $2.25/min for all club gliders, $1.50/min Youth rate on GGR/GPJ/GMB only
- Launch charges: WINCH first/day $39, relaunch $25; AEROTOW separate; Self-launch $25
- Trial flights shown in separate reconciliation panel
- Competition flights highlighted and excluded from normal billing (tow billed separately)
- No Charge flights shown as $0 rows
- 50/50 billing splits between PIC and P2
- Quarterly membership info (informational)
- DB rates updated: aircraft $2.25/min max $180, winch first $39 relaunch $25

**Calculation approach (billing-calc.php):**
- `calcGliderCharge()`: minutes × rate, with Youth discount on specific gliders
- `calcLaunchCharge()`: winch (first/relaunch), self-launch flat fee, aerotow returns "separate"
- `calcCompetitionAmount()`: returns null (tow billed separately)
- `calc5050Amount()`: splits total between PIC and P2
- `chargeLabel()`: human-readable charge source text

## Old System (Deleted/Deprecated)

The old `Treasurer.php` and its calculation pipeline (`CalcTowCharge`, `CalcTowCharge2`, `CalcGliderCharge` in `orgs/*/accountrules.php`) were fundamentally broken — hardcoded dummy values, inconsistent ID vs string lookups, and scheme logic that didn't match actual billing.

Files deleted:
- Treasurer.php, Treasurer-save.php
- TreasurerReportNew.php, TreasurerReportNew2.php, TreasurerReportNew3.php, TreasurerReportNew4.php

## Database Tables

**Currently used by billing-report.php:**
- flights (times, rego, launch type, billing options)
- aircraft (rego, charge_per_minute, club_glider)
- charges (winch first/relaunch rates)
- members (displayname, class)
- membership_class (class_name for rate lookup)

**Legacy (unused by new report):**
- incentive_schemes, scheme_subs - scheme logic deferred
- towcharges - rates now hardcoded per Fee PDF
- vouchers, vouchertype - never implemented