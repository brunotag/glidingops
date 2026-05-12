# Improved Real-Time Map

## Overview

Replace the current Google Maps-based real-time map with a modern, free alternative using Leaflet + OpenTopoMap. Add an interactive sidebar with clickable flights, a dual color mode, and a mobile-responsive layout.

**Key principle:** Keep existing `MasterDisplay.php` as-is, create new parallel implementation.

---

## Requirements

1. Free (no Google Maps API key required)
2. Full two-panel sidebar: Flying Now + Completed Today, each listing flights with pilot names, duration/timer, altitude, distance from launch
3. Clicking a flight in the sidebar isolates it: only that flight visible on map, trail switches to altitude coloring
4. Color mode A (default, all flights visible): each flight gets a unique color, cycling through a palette large enough that no two flights share a color
5. Color mode B (single flight selected): trail colored by altitude gradient — red (low) -> yellow -> green -> dark blue (high)
6. Unselect returns to color mode A with all flights visible
7. Glider selection persists in localStorage per day/org
8. Polling: XML fetch every 30s, UI timer ticks every 1s
9. Single org at a time
10. Mobile: full-screen map with collapsible overlay panel

---

## Technical Approach

### Map Technology

- **Library:** Leaflet.js (https://leafletjs.com)
- **Tiles:** OpenTopoMap (https://opentopomap.org) for default terrain view
- **No API key required** - completely free tier

### New Files

| File | Purpose |
|------|---------|
| `MasterDisplayNew.php` | New map page: PHP shell that injects org config (map center, launch point), renders HTML skeleton |
| `css/map.css` | All styles: sidebar layout, glider list, color swatches, mobile overlay, responsive breakpoints |
| `js/map.js` | All logic: XML fetch + parse, Leaflet map init, polyline/marker rendering, sidebar DOM, color modes, localStorage, 1s timer |

### Modified Files

| File | Change |
|------|--------|
| `todayxml.php` | None - reused as-is (XML format) |

---

## Layout

### Desktop

```
+----------------------------------------+
|  #sidebar (320px)      |  #map         |
|  +-- #duties           |  (fill rest)  |
|  +-- #flying-header    |               |
|  +-- #flying-list      |               |
|  +-- #completed-header |               |
|  +-- #completed-list   |               |
+----------------------------------------+
```

Each flight in sidebar is a row:
```
[color-swatch] [rego-short] [pilot] [timer/dur] [alt] [dist]
```

Clicking a row:
- Sets `selectedFlight = seq`
- Hides all other polylines/markers from map
- Re-colors the selected flight's trail to altitude gradient
- Sidebar row gets `.selected` highlight

Clicking the same row again or a "Show all" button:
- Clears `selectedFlight`
- Shows all flights with unique colors

### Mobile

```
+---------------------------+
|  #map (full viewport)     |
|                           |
|  #overlay-toggle [btn]    |
|                           |
|  #overlay (bottom sheet)  |
|  +-- duties               |
|  +-- flying-list          |
|  +-- completed-list       |
|  +-- [close]              |
+---------------------------+
```

- Map fills entire viewport
- Floating button to open overlay
- Overlay slides up as a bottom sheet, takes ~60% of screen height
- Swipe down or tap close to dismiss

---

## Color System

### Mode A: Unique Per Flight (default)

Palette of distinct, visually separable colors. At least 24 entries, cycling via modulo:

```
#e6194b  (red)
#3cb44b  (green)
#ffe119  (yellow)
#4363d8  (blue)
#f58231  (orange)
#911eb4  (purple)
#42d4f4  (cyan)
#f032e6  (magenta)
#bfef45  (lime)
#fabed4  (pink)
#469990  (teal)
#dcbeff  (lavender)
#9a6324  (brown)
#fffac8  (beige)
#800000  (maroon)
#aaffc3  (mint)
#808000  (olive)
#ffd8b1  (apricot)
#000075  (navy)
#a9a9a9  (grey)
#ffb3b3  (salmon)
#b3d4ff  (sky)
#c2f0c2  (sage)
#e6c3e6  (lilac)
```

Assignment: `palette[flightIndex % palette.length]`

### Mode B: Altitude Gradient (single flight selected)

3-stop gradient from low altitude to high:

| Altitude (feet) | Color |
|-----------------|-------|
| 0 | `#e6194b` (red) |
| 5000 | `#ffe119` (yellow) |
| 10000 | `#3cb44b` (green) |
| 20000+ | `#000075` (dark blue) |

Implementation: interpolate between stops based on altitude. Each segment of the polyline gets its own color based on the altitude of its start point. This produces a gradient along the trail showing where the glider climbed/descended.

```js
function altitudeColor(altFeet) {
  if (altFeet <= 0)    return '#e6194b';
  if (altFeet < 5000)  return lerpColor('#e6194b', '#ffe119', altFeet / 5000);
  if (altFeet < 10000) return lerpColor('#ffe119', '#3cb44b', (altFeet - 5000) / 5000);
  if (altFeet < 20000) return lerpColor('#3cb44b', '#000075', (altFeet - 10000) / 10000);
  return '#000075';
}
```

Each polyline segment colored individually so the trail shows a smooth height transition.

---

## Data Flow

### Polling Cycle

```
setInterval(tick, 1000):
  pollcnt++
  updateFlightTimers()   // update elapsed time spans every second
  updateAgeCounters()    // update "last seen" age spans every second
  if (pollcnt % 30 === 0):
    fetch('todayxml.php?org=X')
    parseXML(response)
    rebuildSidebar()
    rebuildMap()
```

### XML Parsing (from todayxml.php)

```xml
<resp>
  <duties>
    <duty><t>Duty Instructor</t><n>Fred Gordon</n></duty>
  </duties>
  <flights>
    <flight>
      <seq>1</seq>
      <glider>GX</glider>
      <landed>0</landed>       <!-- 0=flying, 1=landed -->
      <name1>Fred Gordon</name1>
      <name2></name2>
      <start>1715472000</start>  <!-- Unix seconds -->
      <dur>2700</dur>            <!-- duration in seconds -->
      <points>
        <p>
          <t>1715472100</t>     <!-- timestamp -->
          <lt>-41.234</lt>      <!-- latitude -->
          <ln>175.345</ln>      <!-- longitude -->
          <al>1500</al>         <!-- altitude in meters -->
        </p>
      </points>
    </flight>
  </flights>
</resp>
```

### Org Config (PHP → JS)

```php
$orgConfig = [
    'map_centre_lat' => -41.123,
    'map_centre_lon' => 175.456,
    'def_launch_lat' => -41.100,
    'def_launch_lon' => 175.400,
    'zoom' => 11
];
```

Injected as a JS global in `MasterDisplayNew.php`.

---

## UI Components

### Sidebar Flight Row

```
<div class="flight-row" data-seq="1" data-landed="0">
  <span class="color-dot" style="background: #e6194b"></span>
  <span class="rego">BF</span>
  <span class="pilot">Fred Gordon</span>
  <span class="timer" id="timeId_1">12:34</span>
  <span class="altitude-msl">4500'</span>
  <span class="altitude-agl">3200'</span>
  <span class="distance">3.2km</span>
  <span class="age" id="ageId_1">12s</span>
</div>
```

Columns (via CSS or header row):
- Color dot | Glider | Pilot | Flight time | Alt MSL | Alt AGL | Dist from launch | Last update

The `age` span shows how long since the last track point was received (e.g., "12s", "3m", "45s"). Updated every 1s alongside the flight timer. For landed flights, age shows time since landing.

Labels shown via CSS pseudo-elements or column headers. AGL calculated as `MSL - launchPointElevation`. Launch point elevation obtained from org config or from the first track point's altitude on the ground.

- Clicking sets `selectedFlight = seq`, re-renders map in altitude mode
- `.selected` class for highlighting
- Timer updates every 1s for flying flights
- Distance/bearing calculated from `def_launch_lat/lon` to last track point

### Duties Section

```
<div class="duty-row">
  <span class="duty-type">Duty Instructor</span>
  <span class="duty-name">Fred Gordon</span>
</div>
```

Simple list above the flights.

### Show All Button

Appears when a flight is selected. Clicking clears selection, returns to mode A.

---

## localStorage Schema

| Key | Value |
|-----|-------|
| `hiddenGliders_{org}_{date}` | JSON array of rego short codes to hide |
| `selectedFlight_{org}_{date}` | `seq` number or null |

Note: `selectedFlight` is per-session (not persisted long-term), so using sessionStorage may be more appropriate or clearing it on page load.

---

## Mobile Overlay

```css
#overlay {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  max-height: 60vh;
  background: white;
  border-radius: 12px 12px 0 0;
  transform: translateY(100%);  /* hidden by default */
  transition: transform 0.3s ease;
  overflow-y: auto;
  z-index: 1000;
}
#overlay.open {
  transform: translateY(0);
}
#overlay-toggle {
  position: fixed;
  bottom: 16px;
  right: 16px;
  z-index: 1001;
  /* floating action button */
}
```

On mobile:
- Map is full viewport
- Small floating button (FAB) to open overlay
- Overlay slides up with duties + flying + completed lists
- Close button or tap outside to dismiss

Media query breakpoint: `@media (max-width: 768px)`

---

## Route

```
RewriteRule ^wgc-new$ MasterDisplayNew.php?org=1 [L,QSA]
```

Accessible at `/wgc-new`. The old `/wgc` still points to `MasterDisplay.php` until the new version is verified.

---

## Implementation Steps

### Step 1: Create skeleton files
- Create `MasterDisplayNew.php` with HTML skeleton, Leaflet CDN, org config injection, `<body onload="init()">`
- Create `css/map.css` with desktop sidebar layout, mobile overlay, color swatches, responsive breakpoints
- Create `js/map.js` with `init()`, placeholder functions for fetch, parse, render

### Step 2: Implement XML fetch + parse
- `fetchData(org, date)` → fetches todayxml.php
- `parseXML(xml)` → returns `{ flights: [...], duties: [...] }`
- Each flight object: `{ seq, glider, regoShort, landed, name1, name2, start, dur, points: [{t, lt, ln, al}] }`

### Step 3: Implement sidebar rendering
- `renderDuties(duties)` → populates #duties div
- `renderSidebar(flights)` → populates #flying-list and #completed-list
- Click handlers on `.flight-row` to select/deselect
- Timer update loop: every 1s, update all `#timeId_*` spans

### Step 4: Implement Leaflet map (Mode A)
- `renderMap(flights)`:
  - Clear existing layers
  - For each flight: create polyline with `palette[index]`, add marker at last point
  - Zoom to fit bounds of all flights
- Store `flightLayers[seq] = { polyline, marker }` for show/hide

### Step 5: Implement color modes
- Mode A: `palette[flightIndex % palette.length]` per flight
- Mode B (selected): iterate over polyline segments, call `altitudeColor(altFeet)` for each
- `lerpColor(c1, c2, t)` for smooth interpolation
- Altitude conversion: `meters * 3.28084`

### Step 6: Implement show/hide + selection
- `selectFlight(seq)`: hide all layers, re-render selected flight with altitude colors
- `deselect()`: show all flight layers with palette colors
- Checkbox list: toggle `flightLayers[seq]` visibility, persist to localStorage
- "Show all" button clears both localStorage keys and deselects

### Step 7: Mobile layout
- CSS media queries
- FAB button toggle for overlay
- Touch events, prevent map pan interference with overlay scroll

### Step 8: Add glider naming + tooltips
- Short label: last 2 letters of rego
- Tooltip on hover: full rego + pilot name

---

## Glider Naming

Short label: last 2 letters of rego
- "ZK-BBF" shows as "BF"
- "ZK-GX" shows as "GX"
- "ZK-ABCD" shows as "CD"

Full tooltip on hover: full rego + pilot name (e.g., "ZK-BBF - Fred Gordon").

---

## Open Questions

- [x] ~~What tile provider for satellite view?~~ → OpenTopoMap
- [x] ~~Keep 30 sec polling or change interval?~~ → 30s confirmed, 1s timer for UI
- [x] ~~Add altitude color-coding to trails?~~ → Yes, in selection mode (Mode B)
- [x] ~~Map marker style~~ → Simple dot (circle marker), colored to match flight, no label
- [x] ~~Altitude display~~ → MSL used for color gradient. Sidebar shows MSL ("4500' MSL") and optionally AGL ("3200' AGL") side by side or as a combined display like "4500' / 3200'". AGL calculated as MSL - ground elevation at launch point.

---

## Future Considerations

- Layer switcher for tile providers (OpenTopoMap, OSM standard, satellite)
- Flight replay/playback (step through track points in time)
- Weather overlay (wind barbs, thermal forecasts)
- Historical flight track archive viewer