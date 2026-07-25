# Content Security Policy (CSP)

## Status: Report-Only

CSP is deployed in **report-only mode** (`Content-Security-Policy-Report-Only`). It logs violations but does **not** block any resources. This allows us to observe real-world violations before enforcing.

## Current Policy (`.htaccess:37`)

```
default-src 'self';
script-src 'self' 'unsafe-inline' 'unsafe-eval'
  code.jquery.com maxcdn.bootstrapcdn.com cdnjs.cloudflare.com
  cdn.jsdelivr.net cdn.datatables.net unpkg.com maps.googleapis.com
  www.gstatic.com static.devt.nz;
style-src 'self' 'unsafe-inline'
  maxcdn.bootstrapcdn.com cdnjs.cloudflare.com cdn.jsdelivr.net
  cdn.datatables.net unpkg.com fonts.googleapis.com;
font-src 'self' data: fonts.gstatic.com;
img-src 'self' data: *.tile.openstreetmap.org
  maps.googleapis.com *.googleapis.com *.gstatic.com;
frame-src 'self';
connect-src 'self' maps.googleapis.com *.googleapis.com *.gstatic.com;
object-src 'none';
base-uri 'self';
report-uri /api/csp-report
```

HTTPS only — the header is not sent on HTTP (dev environment unaffected).

## Violation Logging

CSP reports are POSTed by the browser to `/api/csp-report` and logged to `log/csp.log`. Each entry includes:

- Timestamp, source IP, page URL
- Violated directive and blocked resource URL
- Full CSP report JSON

Monitor violations in real time:

```bash
tail -f log/csp.log
```

## Why `'unsafe-inline'` Is Required

The codebase has extensive use of legacy patterns that require `'unsafe-inline'`:

| Pattern | Count | Refactoring Needed |
|---------|-------|-------------------|
| Inline `<script>` blocks | ~85-100 | Move to external `.js` files |
| Inline event handlers (`onclick`, `onchange`, etc.) | ~200+ | Rewrite as `addEventListener()` |
| Inline `<style>` blocks | ~100-150 | Move to external `.css` files |

Removing `'unsafe-inline'` requires all of these to be refactored first.

## Why `'unsafe-eval'` Is Required

Chart.js uses `eval()` internally for its expression parser (the `options` callback system). This is a Chart.js limitation, not something in application code. The app itself has zero `eval()` or `new Function()` calls.

## External CDN Domains (10)

| Domain | Resource Type | Purpose |
|--------|--------------|---------|
| `code.jquery.com` | script | jQuery 1.12.4 / 3.6.0 |
| `maxcdn.bootstrapcdn.com` | script, style | Bootstrap 3.3.7 |
| `cdnjs.cloudflare.com` | script, style | Bootstrap Select 1.12.1 |
| `cdn.jsdelivr.net` | script, style | Bootstrap 3.4.1, flatpickr, Chart.js 4, bootstrap-select 1.13.14 |
| `cdn.datatables.net` | script, style | DataTables 1.13.7 |
| `unpkg.com` | script, style | Leaflet 1.9.4 |
| `maps.googleapis.com` | script | Google Maps JavaScript API |
| `www.gstatic.com` | script | Google Charts loader |
| `static.devt.nz` | script | devt analytics |
| `fonts.googleapis.com` | style | Google Fonts |

## Phase-Out Roadmap

### Phase 1 — Monitor (current)
Deploy report-only header, collect violations via browser console (no reporting endpoint configured). Observe which external resources and inline patterns trigger violations.

### Phase 2 — Consolidation
- Extract `goBack()` from 38 files into a single `helpers.js`
- Extract DataTables init into a shared JS file
- Move inline `<style>` blocks into `css/global.css` or per-page CSS files

### Phase 3 — Remove `'unsafe-eval'` from `script-src`
- Replace Chart.js with a version that doesn't use `eval()`, or pre-compile chart configs

### Phase 4 — Remove `'unsafe-inline'` from `script-src`
- Rewrite all inline event handlers (`onclick=`, `onchange=`, `onload=`) as `addEventListener()` calls in external JS files
- Move all inline `<script>` content to external files
- This is the largest effort (~285 changes)

### Phase 5 — Remove `'unsafe-inline'` from `style-src`
- Move all inline `<style>` blocks to external CSS files

### Phase 6 — Enforce
Switch from `Content-Security-Policy-Report-Only` to `Content-Security-Policy`

## Viewing Violations

Open the browser DevTools console — CSP violations are logged as console warnings when the report-only header is present. Example:

```
[Report Only] Refused to connect to 'https://example.com'...
```

Violations are also POSTed to `/api/csp-report` and written to `log/csp.log`:

```bash
# Local dev (read directly)
Get-Content log/csp.log -Tail 20

# Production
plink -ssh -pw '***' root@139.180.179.232 "tail -f /var/www/html/log/csp.log"
```
