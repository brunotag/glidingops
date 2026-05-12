---
description: Daily operations workflow - StartDay to DailySheet to CompletedSheet, flight entry, offline capability, and XML-based data submission
mode: subagent
---

# Daily Operations Guide

## Flow Overview

```
StartDay.php → DailySheet.php → DailyLogSheet.php → CompletedSheet.php
                    ↓
              updflights.php (AJAX save)
                    ↓
              localStorage (offline support)
```

## StartDay.php

**Purpose:** Begin a flying day at a location

1. Select organization and date
2. Choose launch location (dropdown or org default)
3. Submit → redirects to DailySheet.php?org=X&location=Y&date=YYYYMMDD

**Security:** Requires level 4 (Daily Ops)

## DailySheet.php (~1302 lines)

**Purpose:** Entry form for recording flights - the most complex page in the app

### Features

- Add/edit/delete flights for the day
- Flight sequence numbering (auto-increment per location)
- Launch type selection (Tow, Self Launch, Winch)
- Glider registration entry with Bootstrap Select autocomplete
- Towplane selection (dropdown from aircraft table)
- PIC, P2, Towpilot selection (CrewSelect.js - member autocomplete)
- Time entry: takeoff, tow landing, landing (flatpickr timepicker)
- Height field (for aero tows)
- Billing option selection (ChargesSelect.js)
- Comments field

### Time Storage

Times stored as BIGINT (Unix timestamp in **milliseconds**):
- `start`, `towland`, `land` in flights table
- JavaScript handles time calculations
- Duration = `land - start` (in ms)
- Convert to minutes: `floor((land - start) / 60000)`

## Offline Capability (Unique Pattern!)

The app uses XML-based submission with localStorage for offline support:

### How It Works

```
[PHP generates initial XML]
        ↓
[JavaScript parses into xmlDoc]
        ↓
[Store in localStorage]
        ↓
[Changes happen in local XML DOM]
        ↓
[Sync back via POST to updflights.php]
```

### Offline Flow

1. **Initial Load (DailySheet.php lines ~1140-1181):**
   ```javascript
   lxml = localStorage.getItem(datestring);
   
   // Compare update sequence numbers
   local_updseq > server_updseq → use local, show "Not Synchronised"
   local_updseq <= server_updseq → use server data
   
   localStorage.setItem(datestring, fxml);
   ```

2. **Update Sequence (updseq):**
   - Every change increments a local counter
   - Stored in XML: `<updseq>123</updseq>`
   - Compared on reload to detect unsynced changes

3. **Sync Status Display:**
   - Green "Sync" = in sync with server
   - Red "Not Synchronised" = local changes pending

4. **Sending to Server:**
   ```javascript
   function sendXMLtoServer() {
     var params = "org=" + org + "&upd=" + xml2Str(xmlDoc);
     xmlhttp.open("POST", "updflights.php", true);
     xmlhttp.send(params);
   }
   ```

## Key JavaScript Files

| File | Purpose |
|------|---------|
| DailySheet.js | Main UI logic, row management |
| DailySheetEntry.js | Entry type (tow/winch/self) dropdowns |
| DailySheetEntryType.js | Launch type selection |
| XMLSelect.js | Generic XML-based select dropdowns |
| CrewSelect.js | Member selection (PIC, P2, Towpilot) |
| ChargesSelect.js | Billing option selection |

## Flight Entry Data Flow

1. User loads DailySheet → PHP generates XML of existing flights
2. JavaScript parses XML into xmlDoc → stores in localStorage
3. User edits → changes in memory (xmlDoc) → saves to localStorage
4. User clicks Sync → POST xmlDoc to updflights.php
5. Server parses XML → saves to flights table → returns new updseq
6. On reconnect: compare updseq, merge if needed

## Billing Options

| ID | Name | Who Gets Charged |
|----|------|------------------|
| 1 | Charge P2 | Second pilot |
| 2 | Charge PIC | Pilot in command |
| 3 | Trial Cash on Day | Trial member paid cash |
| 4 | Trial Club Voucher | Trial with voucher |
| 5 | Trial Grab-one/Treat | Trial promotion |
| 6 | Charge 50/50 | Split between PIC and P2 |
| 7 | Visiting Pilot PIC | Visitor PIC pays |
| 8 | Visiting Pilot P2 | Visitor P2 pays |
| 9 | No Charge | Free flight |
| 10 | Other Member | Another member pays |
| 13 | Charge GWR | Gliding Wellington Region |
| 14 | Competition Flight | Competition billing |

## Time Input

All time inputs use flatpickr (lightweight datetime picker):
```javascript
flatpickr(".time-input", {
  enableTime: true,
  noCalendar: true,
  dateFormat: "H:i",
  time_24hr: true
})
```

## Row ID Format

Flight rows use format: `R_[location]_[seq]_[date]`
Example: `R_1_3_20260512` = location 1, sequence 3, date 2026-05-12

## CompletedSheet.php

**Purpose:** Finalize a flying day

- Summary of all flights
- Calculates totals
- Can mark day as complete
- Email summary option

**Security:** Requires level 4 (Daily Ops)