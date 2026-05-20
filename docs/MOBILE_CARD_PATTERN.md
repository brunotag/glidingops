# Mobile Card Pattern

## Overview

Convert a wide DataTable-style table into responsive cards on mobile using CSS transforms + JS data attributes. Reference implementation: `MyFlights.php`.

## How It Works

### 1. data-label Attributes

Every `<td>` gets a `data-label` attribute matching its column header:

```js
html += '<td data-label="Date">15/05/2026</td>';
html += '<td data-label="Glider" class="text-right">DG-1000</td>';
```

On mobile, the table header (`<thead>`) is hidden, and each `td::before` shows the label via `content: attr(data-label)`.

### 2. CSS Table-to-Card Transform

At `@media (max-width: 767px)`, the table elements are forced to `display: block`. Each `<tr>` becomes a card, and each `<td>` becomes a label/value row:

```css
#my-section .table thead { display: none; }
#my-section .table { display: block; }
#my-section .table tbody { display: flex; flex-wrap: wrap; gap: 8px; }
#my-section .table tr {
    width: calc(50% - 4px);
    border: 1px solid #ddd; border-radius: 6px;
    padding: 6px 10px; background: #fff; box-sizing: border-box;
    min-width: 0;
}

/* Do NOT use overflow:hidden on tr - it clips card content vertically
   (especially the last card's bottom fields like Comments).
   overflow:hidden also prevents flex items from shrinking properly.
   Use min-width:0 instead for flex shrink behavior. */
#my-section .table > tbody > tr > td {
    display: block; border: none; padding: 2px 2px 2px 40%;
    text-align: left !important; font-size: 13px; position: relative;
    line-height: 1.3; overflow-wrap: break-word; word-break: break-word;
    min-width: 0;
}
#my-section .table td::before {
    content: attr(data-label); position: absolute; left: 2px;
    font-weight: 600; color: #555; white-space: nowrap;
}
```

**Important:** Scope selectors to your section (e.g. `#my-section .table`) to avoid breaking other tables on the page.

**Important:** Use `.table > tbody > tr > td` (not `.table td`) to match Bootstrap's specificity for the `padding` override.

### 3. Multi-Column Cards

A flexbox `tbody` with `flex-wrap: wrap` arranges cards in a grid. To switch between 2-column and 1-column:

```css
/* 2 columns: 501px - 767px (applied via the 767px block above) */
#my-section .table tbody { display: flex; flex-wrap: wrap; gap: 8px; }
#my-section .table tr { width: calc(50% - 4px); }

/* 1 column: <= 440px */
@media (max-width: 440px) {
    #my-section .table tbody { display: flex; flex-direction: column; gap: 10px; }
    #my-section .table tr { width: 100%; }
    #my-section .table > tbody > tr > td:last-child { padding-bottom: 10px; }
}
```

The 440px breakpoint must come AFTER the 767px block so it overrides correctly.

### 4. Body Height

Add `min-height: 100vh` to body to ensure the page fills the viewport. Without this, the last card's content can be clipped if the body is shorter than the window:

```css
body { min-height: 100vh; }
```

### 5. No table-responsive Wrapper

Do NOT wrap the table in `<div class="table-responsive">`. The `table-responsive` wrapper has `overflow-x: auto` which can interfere with the flex card layout on mobile. Render the `<table>` directly without the wrapper.

### 6. Hiding Empty Fields

Add a `data-empty="1"` attribute when the value is empty. This prevents empty fields from taking up space in mobile cards, which is critical when many columns are shown:

```js
function e(v) { return (v || '').toString().trim() === ''; }
html += '<td data-label="Comments"' + (e(comments) ? ' data-empty="1"' : '') + '>' + comments + '</td>';
```

CSS hides these in card mode:

```css
#my-section .table td[data-empty="1"] { display: none; }
```

This only affects mobile since the selector is inside the 767px media query.

### 7. Desktop-vs-Mobile Fields

Some columns are only meaningful on desktop (e.g. separate Start/Land/Duration). Use `hide-mobile` and `show-mobile` classes:

```css
.show-mobile { display: none; }   /* hidden on desktop */
@media (max-width: 767px) {
    .hide-mobile { display: none !important; }
    .show-mobile { display: block !important; }
}
```

```js
html += '<td data-label="Duration" class="text-right hide-mobile">...</td>';
html += '<td data-label="Time" class="text-right show-mobile">10:30 - 11:15 (00:45)</td>';
```

### 8. Summary as Inline Pills

Move summary data out of the table and into inline pills next to the title:

```html
<div class="row header-row">
    <h1>Title</h1>
    <div id="summary-inline"></div>
    <div>[buttons]</div>
</div>
```

Rendered by JS as styled pills:

```css
.summary-pill {
    display:inline-block; background:#e8e8e8; color:#222; border-radius:10px;
    padding:2px 10px; font-size:12px; white-space:nowrap; border:1px solid #ccc;
    margin-right:3px;
}
.summary-pill:last-child { margin-right:0; }
```

### 9. Consistent Alignment

Wrap both the header row and the table section in `.row` divs so they share the same left/right margins. Apply matching `padding` to each:

```css
.header-row { padding: 0 12px; }
#my-section { margin: 8px 0; padding: 0 12px; }
```

On mobile, override to tighter values:

```css
#my-section { margin: 0 8px; padding: 0; }
.header-row { padding: 0 8px; }
```

## Reference

- **Implementation:** `MyFlights.php`
- **Breakpoints:** 767px (table→card), 440px (2-col→1-col)
- **Colors:** Pill bg `#e8e8e8`, text `#222`, border `#ccc`
- **Bootstrap version:** 3.x (the CSS overrides Bootstrap's table styles)
