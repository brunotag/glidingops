<script>
function renderTrends(data) {
    var el = document.getElementById('trends-grid');
    el.innerHTML = '';

    var t = data.main.totals;
    var items = [
        { key: 'Flights', val: t.flights, comp: data.compare ? data.compare.totals.flights : null },
        { key: 'Solo', val: t.solo, comp: data.compare ? data.compare.totals.solo : null },
        { key: 'Dual', val: t.dual, comp: data.compare ? data.compare.totals.dual : null },
        { key: 'Long (>1h)', val: t.long, comp: data.compare ? data.compare.totals.long : null },
        { key: 'Mid (30m-1h)', val: t.mid, comp: data.compare ? data.compare.totals.mid : null },
        { key: 'Training (<=30m)', val: t.short, comp: data.compare ? data.compare.totals.short : null }
    ];

    items.forEach(function(item) {
        var div = document.createElement('div');
        div.className = 'trend-item';
        var valSpan = document.createElement('div');
        valSpan.className = 'val';
        valSpan.textContent = item.val;
        div.appendChild(valSpan);

        var labelSpan = document.createElement('div');
        labelSpan.className = 'label';
        labelSpan.textContent = item.key;
        div.appendChild(labelSpan);

        if (item.comp !== null && item.comp !== undefined) {
            var diff = document.createElement('div');
            var delta = item.val - item.comp;
            var pct = item.comp > 0 ? Math.round((delta / item.comp) * 100) : 0;
            diff.className = 'diff';
            if (delta > 0) { diff.classList.add('up'); diff.textContent = '+' + delta + ' (+' + pct + '%)'; }
            else if (delta < 0) { diff.classList.add('down'); diff.textContent = delta + ' (' + pct + '%)'; }
            else { diff.classList.add('flat'); diff.textContent = '0 (0%)'; }
            div.appendChild(diff);
        }

        el.appendChild(div);
    });
}
</script>
