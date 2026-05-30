# Real-Time Glider Map

The live tracking map at `/wgc` uses Leaflet.js + OpenTopoMap tiles (free, no API key). Served via device detection: desktop gets a sidebar + map layout, mobile gets a full-screen map with a draggable overlay.

## Principles

- No `/wgc/desktop` or `/wgc/mobile` sub-routes — only the router at `/wgc`
- No twin-element syncing — each mode has its own HTML file, no `refreshOverlay()`
- Shared JS/CSS avoids duplication; mode-specific behaviour guarded by `MODE` global

## Files

| File | Purpose |
|------|---------|
| `map/MasterDisplayDesktop.php` | Desktop layout: sidebar + map, `MODE='desktop'` |
| `map/MasterDisplayMobile.php` | Mobile layout: full map + overlay + drag divider, `MODE='mobile'` |
| `map/MasterDisplayRouter.php` | Device detection (UA check), includes desktop or mobile |
| `map/map-shared.js` | Shared logic: fetch, parse, render, timers, selections, auto-refresh |
| `map/map-shared.css` | Shared styles, scoped to `.desktop-mode` / `.mobile-mode` |

## Routes

| Route | Target | Notes |
|-------|--------|-------|
| `/wgc` | `map/MasterDisplayRouter.php?org=1` | Device detection — only route, no sub-routes |

## Features

- **24-color unique palette** per flight (Mode A)
- **Altitude gradient coloring** when a single flight is selected (Mode B)
- **Multi-select** flight toggle; Flying Only mode filters to airborne gliders
- **Brightness slider** (visible when exactly 1 flight selected, resets to 80 on hide)
- **Contrast slider** (desktop only, range 0–80, opacity 0–0.8)
- **Compact flying section**: `flex: 0 0 auto` (sizes to content), completed section fills rest
- **Dark headers**: `#080a14` background with `#e94560` accent bottom border
- **Glider markers**: 34px circle with 2-letter rego, text color auto-contrasts via luminance check
- **Auto-refresh**: random 10–60s interval preserves flight selections
- **Timer**: mm:ss / h:mm:ss format, sans-serif 11px, updated every 1s
- **Refresh button** (↻ icon) + last-updated timestamp (hidden on past dates)
- **Flying Only button** — hidden when all flying gliders selected unless completed flights also selected
- **Show all button** — visible on initial load when flying gliders auto-selected
- **Waypoints overlay** — toggles 346 waypoints from `PAP_LONG_24P.cup` (off/medium). Purple dots (10px) for landmarks, purple dots with black inset bar for airfields. Names always visible.
- **Selected rows** use `inset box-shadow` — no extra height from outline
- **Mobile divider** — drag up to 95% of viewport height
- **Cache-busted CSS/JS** via `?v=filemtime()`
- **Drag divider** for mobile overlay height
- **Data feed**: `todayxml.php` (absolute `/todayxml.php` URL in JS fetch)

## Glider Naming

Short label: last 2 letters of rego — "ZK-BBF" shows as "BF", "ZK-ABCD" as "CD".

## Dead Code (Pending Deletion)

See `DEAD_CODE.md` for full list. Summary:
- `map/MasterDisplayNew.php`, `map/map.css`, `map/map.js` — old single-file version (was at `/wgc-mixed`)
- `map/MasterDisplay.php`, `map/mapiconmaker.js` — original Google Maps version (was at `/wgc-old`)
- `FlyingNow.php` — replaced by sidebar flying panel
