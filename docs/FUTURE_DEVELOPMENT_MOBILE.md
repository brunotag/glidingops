# Mobile Responsiveness

**STATUS: NOT IMPLEMENTED** — All items below are future work.

## Overview

Most admin pages are not responsive and are unusable on phones/tablets. The DailySheet and map already have mobile optimizations, but list pages and edit forms do not.

## G1: Viewport Meta Tags

### Problem
Several pages are missing the viewport meta tag, causing mobile browsers to render at desktop width.
```html
<meta name="viewport" content="width=device-width, initial-scale=1">
```

### Pages to Check
- All `*-list.php` admin pages
- All `*.php` edit forms
- Login and error pages

## G2: List Pages

### Pages
- `aircraft-list.php`
- `aircrafttype-list.php`
- `launchtypes-list.php`
- `flighttypes-list.php`
- `billingoptions-list.php`
- `charges-list.php`
- `towcharges-list.php`
- `roles-list.php`
- `membership_class-list.php`
- `membership_status-list.php`
- `spots-list.php`

### Approach
- Add Bootstrap 3 responsive table wrapper (`.table-responsive`)
- DataTables pages (`members-list-v2b.php`, `users-list-v2b.php`) already partially responsive — add `responsive: true` DataTables option
- Reduce column count on mobile (hide less important columns via DataTables `responsive` or `columnDefs`)
- Increase tap target sizes for mobile

## G3: Edit/Create Forms

### Pages
- `aircraft.php`
- `aircrafttype.php`
- `launchtypes.php`
- `flighttypes.php`
- `billingoptions.php`
- `charges.php`
- `towcharges.php`
- `roles.php`
- `membership_class.php`
- `membership_status.php`
- `spots.php`

### Approach
- Stack form fields vertically on mobile (Bootstrap `form-group` already does this)
- Full-width inputs
- Larger font sizes for form labels
- Ensure date/time pickers (flatpickr) are touch-friendly

## G4: Navigation

### Problem
The `orgs/*/menu1.txt` navigation is desktop-oriented. On mobile, the menu wraps or overflows.

### Approach
- Collapse navigation into hamburger menu on small screens
- Currently uses Bootstrap 3 navbar — `.navbar-collapse` should work if enabled

## Files to Modify

| File | Change |
|------|--------|
| All `*-list.php` files | Add viewport meta, responsive tables, DataTables responsive |
| All `*.php` edit forms | Viewport meta, mobile-friendly layout |
| `home.php` | Responsive card grid |
| `helpers/jsLibraies.php` | Include DataTables responsive extension if not present |