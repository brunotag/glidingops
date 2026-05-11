# Improved Real-Time Map

## Overview

Replace the current Google Maps-based Real-Time map with a modern, free alternative using OpenStreetMap + Leaflet. Add glider selection UI to show/hide individual aircraft.

**Key principle:** Keep existing `MasterDisplay.php` as-is, create new parallel implementation.

---

## Requirements

1. Free (no Google Maps API key required)
2. Mobile-friendly (responsive, touch controls)
3. Glider selection via checkbox list (show/hide individual aircraft)
4. Selection persists in localStorage per day/org
5. Reset button to show all gliders
6. Polling interval < 60 seconds (can reuse existing 30 sec)
7. Single org at a time

---

## Technical Approach

### Map Technology

- **Library:** Leaflet.js (https://leafletjs.com)
- **Tiles:** OpenStreetMap (free) + OpenTopoMap for terrain/satellite alternative
- **No API key required** - completely free tier

### New Files

| File | Purpose |
|------|---------|
| `MasterDisplayNew.php` | New map page with Leaflet + selection UI |
| `css/map.css` | Map and glider list styles |
| `js/map.js` | Leaflet map logic, glider selection state, localStorage |

### Modified Files

| File | Change |
|------|--------|
| `todayxml.php` | Can stay XML (existing JS parsing works) |

### Glider Selection

- **UI:** Checkbox list above map showing today's gliders
- **Label:** Last 2 letters of rego (e.g., "ZK-BBF" shows as "BF")
- **Storage:** `localStorage` key `hiddenGliders_{date}_{org}`
- **Reset:** Button clears hidden list (shows all)

### Mobile Optimization

- Responsive layout
- Collapsible glider panel
- Touch-friendly controls
- Map takes full viewport

---

## Implementation Steps

### Step 1: Create skeleton files
- Create `MasterDisplayNew.php` with basic structure
- Add Leaflet CSS/JS from CDN
- Add placeholder map div

### Step 2: Implement Leaflet map
- Initialize Leaflet with OSM tiles
- Load existing todayxml.php data
- Render polylines and markers (mimic current behavior)

### Step 3: Add glider selection UI
- Parse today's gliders from XML
- Generate checkbox list with short labels (last 2 chars of rego)
- Toggle visibility on checkbox change
- Save hidden state to localStorage

### Step 4: Add reset button
- "Show All" button clears localStorage for current day/org

### Step 5: Mobile polish
- CSS responsive adjustments
- Collapsible panel for glider list
- Touch-friendly sizing

---

## Glider Naming

Short label: last 2 letters of rego
- "ZK-BBF" shows as "BF"
- "ZK-XXX" shows as "XX"

Full tooltip on hover: full rego + pilot name.

---

## Route

Add to `.htaccess`:
```
RewriteRule wgc-new MasterDisplayNew.php?org=1 [L,QSA]
RewriteRule wgc2 MasterDisplayNew.php?org=1 [L,QSA]
```

Or reuse existing wgc but with toggle param:
```
/wgc?new=1
```

---

## Open Questions

- [ ] What tile provider for satellite view?
- [ ] Keep 30 sec polling or change interval?
- [ ] Add altitude color-coding to trails?
