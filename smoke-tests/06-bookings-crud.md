# 06 — Bookings CRUD

**Route:** /Bookings → bookings.php (standalone PHP)
**Perms:** bookings.view (Bruno has it), bookings.admin (Bruno has it via zzz_god)
**Tools:** Chrome DevTools, plink (SSH)
**Purpose:** Verify booking creation, verification, and deletion

## Steps

1. **Browse to /Bookings**

2. **Verify page renders**
   - Visual: "Bookings" heading
   - Loading spinner appears briefly, then date-grouped list of upcoming bookings
   - "Add Booking" button visible

3. **Verify existing bookings display**
   - Visual: Bookings grouped by date in future order
   - Each booking shows: date, intention, glider/rego, member name, notes
   - "Our" bookings render differently from external Google Calendar events
   - Edit (pencil) and Delete (X) icons on each booking

4. **⚠️ ASK USER before creating a booking**
   - Creating a booking = real Google Calendar event

5. **Create a test booking**
   - Click "Add Booking" button
   - Visual: Modal opens with form fields
   - Fill: Date = today's date (flatpickr), Intention = "Smoke Test - will delete", Glider = "DG-1000" or other available, Notes = "created-by-smoke-test"
   - Submit the form
   - Visual: Modal closes, new booking appears in the date-grouped list

6. **Verify booking in DB**
   - SSH: `echo "SELECT id, booking_date, intention, aircraft_rego, notes, deleted FROM bookings WHERE notes LIKE '%Smoke Test%' ORDER BY id DESC LIMIT 1;" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: 1 row returned, deleted = 0
   - Take note of the booking `id` for cleanup

7. **Verify booking in UI**
   - Visual: Find the test booking in the list — intention and glider match what was entered
   - Scroll to the correct date group if needed

8. **Delete the test booking**
   - Click the Delete (X) icon on the test booking
   - Visual: Confirmation modal appears
   - Confirm deletion
   - Visual: Booking disappears from the list

9. **Verify deletion in DB**
   - SSH: Same query as step 6
   - Expected: deleted = 1 (soft delete), or row gone
   - If hard delete: 0 rows

## Expected Result
Booking created, visible in list, then soft-deleted. UI matches DB state at every step.

10. **Update this file's test-run stamp**
    - Run from repo root: $hash = $(git log --oneline -1 | ForEach-Object { # 06 — Bookings CRUD

**Route:** /Bookings → bookings.php (standalone PHP)
**Perms:** bookings.view (Bruno has it), bookings.admin (Bruno has it via zzz_god)
**Tools:** Chrome DevTools, plink (SSH)
**Purpose:** Verify booking creation, verification, and deletion

## Steps

1. **Browse to /Bookings**

2. **Verify page renders**
   - Visual: "Bookings" heading
   - Loading spinner appears briefly, then date-grouped list of upcoming bookings
   - "Add Booking" button visible

3. **Verify existing bookings display**
   - Visual: Bookings grouped by date in future order
   - Each booking shows: date, intention, glider/rego, member name, notes
   - "Our" bookings render differently from external Google Calendar events
   - Edit (pencil) and Delete (X) icons on each booking

4. **⚠️ ASK USER before creating a booking**
   - Creating a booking = real Google Calendar event

5. **Create a test booking**
   - Click "Add Booking" button
   - Visual: Modal opens with form fields
   - Fill: Date = today's date (flatpickr), Intention = "Smoke Test - will delete", Glider = "DG-1000" or other available, Notes = "created-by-smoke-test"
   - Submit the form
   - Visual: Modal closes, new booking appears in the date-grouped list

6. **Verify booking in DB**
   - SSH: `echo "SELECT id, booking_date, intention, aircraft_rego, notes, deleted FROM bookings WHERE notes LIKE '%Smoke Test%' ORDER BY id DESC LIMIT 1;" | plink -ssh -pw '...' root@139.180.179.232 "mysql gliding"`
   - Expected: 1 row returned, deleted = 0
   - Take note of the booking `id` for cleanup

7. **Verify booking in UI**
   - Visual: Find the test booking in the list — intention and glider match what was entered
   - Scroll to the correct date group if needed

8. **Delete the test booking**
   - Click the Delete (X) icon on the test booking
   - Visual: Confirmation modal appears
   - Confirm deletion
   - Visual: Booking disappears from the list

9. **Verify deletion in DB**
   - SSH: Same query as step 6
   - Expected: deleted = 1 (soft delete), or row gone
   - If hard delete: 0 rows

## Expected Result
Booking created, visible in list, then soft-deleted. UI matches DB state at every step.


11. **Update this file test-run stamp**
    - From repo root run PowerShell:
      $hash = (git log --oneline -1).Split(' ')[0]; $date = Get-Date -Format 'yyyy-MM-dd'; (Get-Content 'smoke-tests/06-bookings-crud.md') -replace '[*]Last tested:.*[*]', ('[*]Last tested: ' + $date + ' on commit ' + $hash + '[*]') | Set-Content 'smoke-tests/06-bookings-crud.md'

---

*Last tested:* (set by step 11 above)