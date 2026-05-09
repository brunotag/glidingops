# Known Issues & Improvements

## High Priority

### 0. Document Cron Jobs - DONE

All cron jobs documented in ARCHITECTURE.md

---

### 1. DailySheet Performance (Low Priority - Already Optimized)

**History:** 
- Previously created huge checkbox lists per row - unusable after 20-25 flights
- User already optimized to use dropdowns (Bootstrap Select)

**Current State:**
- Uses XML-based submission (neanderthal but functional)
- localStorage for offline capability (works, but could be modernized)
- JavaScript DOM manipulation for row management

**Future Opportunity:**
- Replace XML serialization with JSON
- Replace localStorage with IndexedDB or modern offline lib
- Consider React/Vue rewrite for maintainability

---

### 2. Mobile-Friendly (All List/Edit Pages)

**Problem:** All admin pages are not responsive - unusable on phones/tablets

**Scope:** All *-list.php and *.php edit pages
- members.php, members-list.php (most used)
- users.php, users-list.php
- All data maintenance pages

**Solution:** Add Tailwind CSS, make responsive

**Status:** Not started

---

### 2. Billing Calculation - ASSUME BROKEN/UNUSED

**Problem:** Treasurer.php calls billing functions but with hardcoded dummy values

**Evidence in Treasurer.php:**
```php
// Line 319 - Tow cost
$towcost = CalcTowCharge2($org, $launchtype, $towplane, $duration, $height, "", 1, 0);
//                                      ^^^^                  ^^^  ^^  ^^
//                                  empty string         hardcoded hardcoded
//                                  (not actual          club_glider is5050
//                                   member class)             

// Line 334 - Glider cost  
$glidcost = CalcGliderCharge($org, 1, $rego, 0, 0.00, 0, $mins, "");
//                                ^ ^ ^    ^   ^       ^    ^   ^
//                           hardcoded  hardcoded  hardcoded empty
//                           club_glider ignore_schemes iRateGlider memberclass
```

**What's wrong:**
- Member class = "" (empty, not actual member's class)
- club_glider = 1 (assumed true, not actual aircraft owner)
- is5050 = 0 (assumed false, doesn't check billing_option)
- SchemeCharge = 0 (ignores any incentive schemes)
- iRateGlider = 0.00 (hardcoded)

**Assumption:** Billing logic is effectively ignored - Treasurer shows times, not real fees.

**Consequences for cleanup:**
1. Delete all accountrules.php billing functions (CalcTowCharge, CalcTowCharge2, CalcGliderCharge, CalcOtherCharges)
2. Delete incentive_schemes and scheme_subs tables and related files
3. Simplify Treasurer.php to just show flight times, not calculate fees
4. Keep flights table as-is (times are accurate)
5. Keep towcharges table (might be useful for reference, or delete)

**Status:** Assumption to verify - confirm Treasurer output doesn't matter

---

## Medium Priority

### 3. Clean Up Dead Code

- Delete texts.php, texts-list.php, texts-list-last-200.php
- Decide on Laravel (lrv/) - delete or properly implement
- Remove unused tables
- Clean up voucher/airspace/controllers code

### 4. Security Hardening

- Upgrade from MD5 passwords (modern hashing)
- Fix SQL injection vulnerabilities
- Add CSRF protection
- Implement HTTPS

### 5. Messaging Improvements

- Clean up SMS dead code
- Improve MessagingPage UI
- Add proper email templates

---

## Low Priority / Feature Requests

### 6. Improve Daily Sheet Mobile Experience

The DailySheet already has some mobile optimizations (flatpickr, dropdown positioning). Could be improved further.

### 7. Reporting Enhancements

- Fix Treasurer report
- Add more/better reports
- Export to modern formats (PDF, etc.)

### 8. Member Self-Service

- Better MyFlights page
- Online payments (if needed)
- Profile management improvements

### 9. Integration Improvements

- Proper SMS gateway (Twilio, etc.)
- Flight tracking enhancements
- Club website integration

---

## Documentation Gaps

- No API documentation
- No deployment instructions
- No database backup procedures
- No user manual

---

## Technical Debt

1. **Mixed PHP/Laravel** - Two systems, poorly integrated
2. **Inconsistent patterns** - Every page slightly different
3. **No tests** - No test suite
4. **Old libraries** - jQuery 1.x, Bootstrap 3.x

---

## Quick Wins (Easy Fixes)

1. Add viewport meta tags to all pages (some missing)
2. Clean up styling with responsive CSS
3. Fix obvious SQL injection (high impact, low effort)
4. Add basic CSRF token to forms

---

## Questions for Future Investigation

1. Is the Twitter API integration in MessagingPage working?
2. Are the tracking features (spots, tracks) fully used?
3. What's the actual usage of incentive schemes?
4. Are voucher types ever intended to work?
5. What's the status of the hardware control (controllers/switches)?

---

## Notes for Next Developer

- Start with docs/ARCHITECTURE.md for overview
- Use docs/CODEBASE_MAP.md to find files
- Check docs/DEAD_CODE.md before adding features
- Security model documented in docs/SECURITY.md
- Database schema in docs/DATABASE.md