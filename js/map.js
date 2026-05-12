var PALETTE = [
  '#e6194b','#3cb44b','#ffe119','#4363d8','#f58231','#911eb4',
  '#42d4f4','#f032e6','#bfef45','#fabed4','#469990','#dcbeff',
  '#9a6324','#fffac8','#800000','#aaffc3','#808000','#ffd8b1',
  '#000075','#a9a9a9','#ffb3b3','#b3d4ff','#c2f0c2','#e6c3e6'
];

var flights = [];
var duties = [];
var selectedSeq = null;
var map = null;
var flightLayers = {};
var pollcnt = 0;
var clockOffset = 0;
var tickId = null;
var currentDate = TODAY_DATE;
var isViewingToday = true;

function parseDateInput(s) {
  s = s.trim().replace(/\//g, '-');
  var m = s.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (m) return m[1] + '-' + m[2] + '-' + m[3];
  m = s.match(/^(\d{4})(\d{2})(\d{2})$/);
  if (m) return m[1] + '-' + m[2] + '-' + m[3];
  return null;
}

function dateYmd(ymd) {
  return ymd.replace(/-/g, '');
}

function getTodayYmd() {
  return dateYmd(TODAY_DATE);
}

function getHiddenKey() {
  return 'hiddenGliders_' + ORG + '_' + dateYmd(currentDate);
}

function getHidden() {
  try { return JSON.parse(localStorage.getItem(getHiddenKey()) || '[]'); }
  catch (e) { return []; }
}

function setHidden(arr) {
  localStorage.setItem(getHiddenKey(), JSON.stringify(arr));
}

function isHidden(regoShort) {
  return getHidden().indexOf(regoShort) !== -1;
}

function toggleHidden(regoShort) {
  var h = getHidden();
  var idx = h.indexOf(regoShort);
  if (idx === -1) { h.push(regoShort); } else { h.splice(idx, 1); }
  setHidden(h);
  renderMap(flights);
}

function filterOutliers(points) {
  if (points.length < 3) return points;
  var MAX_SPEED = 300;
  var result = [points[0]];
  var ref = points[0];
  for (var i = 1; i < points.length; i++) {
    var p = points[i];
    var dt = (p.t - ref.t) / 3600;
    if (dt <= 0) {
      result.push(p);
      ref = p;
      continue;
    }
    var maxDist = MAX_SPEED * dt * 1.5;
    var actualDist = distKm(ref.lt, ref.ln, p.lt, p.ln);
    if (actualDist <= maxDist) {
      result.push(p);
      ref = p;
    }
  }
  return result;
}

function regoShortFromFull(full) {
  if (full.length >= 2) return full.slice(-2);
  return full;
}

function lerpColor(c1, c2, t) {
  var r1 = parseInt(c1.slice(1,3), 16), g1 = parseInt(c1.slice(3,5), 16), b1 = parseInt(c1.slice(5,7), 16);
  var r2 = parseInt(c2.slice(1,3), 16), g2 = parseInt(c2.slice(3,5), 16), b2 = parseInt(c2.slice(5,7), 16);
  var r = Math.round(r1 + (r2 - r1) * t);
  var g = Math.round(g1 + (g2 - g1) * t);
  var b = Math.round(b1 + (b2 - b1) * t);
  return '#' + [r,g,b].map(function(v) { return v.toString(16).padStart(2,'0'); }).join('');
}

function altitudeColor(altFeet) {
  if (altFeet <= 0) return '#e6194b';
  if (altFeet < 5000) return lerpColor('#e6194b', '#ffe119', altFeet / 5000);
  if (altFeet < 10000) return lerpColor('#ffe119', '#3cb44b', (altFeet - 5000) / 5000);
  if (altFeet < 20000) return lerpColor('#3cb44b', '#000075', (altFeet - 10000) / 10000);
  return '#000075';
}

function distKm(lat1, lon1, lat2, lon2) {
  var R = 6371;
  var dLat = (lat2 - lat1) * Math.PI / 180;
  var dLon = (lon2 - lon1) * Math.PI / 180;
  var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
    Math.sin(dLon/2) * Math.sin(dLon/2);
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function secondsToTimer(s) {
  s = Math.floor(s);
  var h = Math.floor(s / 3600);
  var m = Math.floor((s % 3600) / 60);
  var sec = s % 60;
  if (h > 0) return h + ':' + String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
  return String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
}

function secondsToAge(s) {
  s = Math.floor(s);
  if (s < 60) return s + 's';
  if (s < 3600) return Math.floor(s / 60) + 'm';
  return Math.floor(s / 3600) + 'h';
}

function getNodeText(node, tag) {
  var el = node.getElementsByTagName(tag);
  return el.length ? (el[0].textContent || '') : '';
}

function parseXML(xmlDoc) {
  var result = { flights: [], duties: [] };

  var dutyNodes = xmlDoc.getElementsByTagName('duty');
  for (var i = 0; i < dutyNodes.length; i++) {
    result.duties.push({
      type: getNodeText(dutyNodes[i], 't'),
      name: getNodeText(dutyNodes[i], 'n')
    });
  }

  var flightNodes = xmlDoc.getElementsByTagName('flight');
  for (var i = 0; i < flightNodes.length; i++) {
    var fn = flightNodes[i];
    var glider = getNodeText(fn, 'glider');
    var regoShort = regoShortFromFull(glider);
    var seq = parseInt(getNodeText(fn, 'seq'), 10) || i + 1;
    var start = parseFloat(getNodeText(fn, 'start')) || 0;
    var dur = parseFloat(getNodeText(fn, 'dur')) || 0;
    var landed = parseInt(getNodeText(fn, 'landed'), 10) === 1 ? 1 : 0;

    var points = [];
    var pNodes = fn.getElementsByTagName('p');
    for (var j = 0; j < pNodes.length; j++) {
      var pn = pNodes[j];
      points.push({
        t: parseFloat(getNodeText(pn, 't')) || 0,
        lt: parseFloat(getNodeText(pn, 'lt')) || 0,
        ln: parseFloat(getNodeText(pn, 'ln')) || 0,
        al: parseFloat(getNodeText(pn, 'al')) || 0
      });
    }

    result.flights.push({
      seq: seq,
      glider: glider,
      regoShort: regoShort,
      landed: landed,
      name1: getNodeText(fn, 'name1'),
      name2: getNodeText(fn, 'name2'),
      start: start,
      dur: dur,
      points: filterOutliers(points)
    });
  }

  return result;
}

function renderGliderFilter() {
  var el = document.getElementById('filter-list');
  var seen = {};
  var items = [];
  flights.forEach(function(f) {
    if (seen[f.regoShort]) return;
    seen[f.regoShort] = true;
    var hidden = isHidden(f.regoShort);
    var checked = hidden ? '' : 'checked';
    items.push(
      '<label class="filter-item">' +
      '<input type="checkbox" data-rego="' + escapeHtml(f.regoShort) + '" ' + checked + ' />' +
      escapeHtml(f.glider) + ' (' + escapeHtml(f.name1) + ')' +
      '</label>'
    );
  });
  el.innerHTML = items.join('');

  el.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
    cb.addEventListener('change', function() {
      toggleHidden(this.getAttribute('data-rego'));
    });
  });
}

function renderDuties() {
  var el = document.getElementById('duties');
  if (!duties || duties.length === 0) {
    el.innerHTML = '';
    return;
  }
  el.innerHTML = duties.map(function(d) {
    return '<div class="duty-row"><span class="duty-type">' +
      escapeHtml(d.type) + ':</span><span class="duty-name">' +
      escapeHtml(d.name) + '</span></div>';
  }).join('');
}

function escapeHtml(s) {
  if (!s) return '';
  var d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function renderSidebar() {
  var flyingEl = document.getElementById('flying-list');
  var completedEl = document.getElementById('completed-list');
  var now = Date.now() / 1000 + clockOffset;

  var flyingRows = [];
  var completedRows = [];

  flights.forEach(function(f, idx) {
    var dotColor;
    if (selectedSeq !== null) {
      dotColor = f.seq === selectedSeq ? '#e94560' : '#555';
    } else {
      dotColor = PALETTE[idx % PALETTE.length];
    }

    var elapsed;
    if (f.landed) {
      elapsed = f.dur;
    } else {
      elapsed = now - f.start;
    }
    var timer = elapsed > 0 ? secondsToTimer(elapsed) : '00:00';
    var lastPt = f.points.length > 0 ? f.points[f.points.length - 1] : null;
    var altMsl = lastPt ? Math.round(lastPt.al * 3.28084) : 0;
    var altAgl = lastPt ? Math.max(0, Math.round(lastPt.al * 3.28084 - LAUNCH_ELEVATION)) : 0;
    var dist = lastPt ? distKm(LAUNCH_LAT, LAUNCH_LON, lastPt.lt, lastPt.ln) : 0;
    var distStr = dist < 1 ? Math.round(dist * 1000) + 'm' : dist.toFixed(1) + 'km';
    var ageStr = lastPt ? secondsToAge(now - lastPt.t) : '-';

    var selClass = selectedSeq === f.seq ? ' selected' : '';

    var row = '<div class="flight-row' + selClass + '" data-seq="' + f.seq + '" data-landed="' + f.landed + '">' +
      '<span class="color-dot" style="background:' + dotColor + '"></span>' +
      '<span class="rego">' + escapeHtml(f.regoShort) + '</span>' +
      '<span class="pilot">' + escapeHtml(f.name1) + (f.name2 ? '/' + escapeHtml(f.name2) : '') + '</span>' +
      '<span class="timer">' + timer + '</span>' +
      '<span class="altitude-msl">' + altMsl + '\'</span>' +
      '<span class="altitude-agl">' + altAgl + '\'</span>' +
      '<span class="distance">' + distStr + '</span>' +
      '<span class="age">' + ageStr + '</span>' +
      '</div>';

    if (f.landed) {
      completedRows.push(row);
    } else {
      flyingRows.push(row);
    }
  });

  flyingEl.innerHTML = flyingRows.join('');
  completedEl.innerHTML = completedRows.join('');
}

function renderMap(dataFlights) {
  for (var key in flightLayers) {
    if (flightLayers[key]) {
      if (flightLayers[key].polyline) map.removeLayer(flightLayers[key].polyline);
      if (flightLayers[key].segments) {
        flightLayers[key].segments.forEach(function(s) { map.removeLayer(s); });
      }
      if (flightLayers[key].glow) map.removeLayer(flightLayers[key].glow);
      if (flightLayers[key].marker) map.removeLayer(flightLayers[key].marker);
    }
  }
  flightLayers = {};

  var hidden = getHidden();
  var bounds = [];
  var hasVisible = false;
  var now = Date.now() / 1000 + clockOffset;

  dataFlights.forEach(function(f, idx) {
    if (hidden.indexOf(f.regoShort) !== -1) return;

    if (selectedSeq !== null && f.seq !== selectedSeq) return;

    var color;
    var useAltitudeColor = (selectedSeq !== null && f.seq === selectedSeq);

    var latlngs = f.points.filter(function(p) { return p.lt !== 0 && p.ln !== 0; })
      .map(function(p) { return [p.lt, p.ln]; });

    if (latlngs.length < 2) {
      if (latlngs.length === 1) {
        var dotColor = useAltitudeColor ? altitudeColor(f.points[f.points.length - 1].al * 3.28084) : PALETTE[idx % PALETTE.length];
        L.circleMarker(latlngs[0], {
          radius: 7,
          color: dotColor,
          fillColor: dotColor,
          fillOpacity: 1,
          weight: 3,
          opacity: 1
        }).addTo(map).bindTooltip(f.regoShort + ' - ' + f.name1);
        flightLayers[f.seq] = { polyline: null, segments: null, glow: null, marker: null };
        bounds.push(latlngs[0]);
        hasVisible = true;
      }
      return;
    }

    var segments = null;
    var polyline = null;
    var markerColor;
    var extraGlow = null;

    function makeLine(pts, clr, w) {
      return L.polyline(pts, { color: clr, weight: w, opacity: 0.9, pane: 'overlayPane' });
    }
    function makeGlow(pts, clr, w) {
      return L.polyline(pts, { color: clr, weight: w + 4, opacity: 0.2, pane: 'overlayPane' });
    }

    if (useAltitudeColor) {
      segments = [];
      for (var i = 1; i < latlngs.length; i++) {
        var altFeet = f.points[i].al * 3.28084;
        var segColor = altitudeColor(altFeet);
        var segGlow = makeGlow([latlngs[i - 1], latlngs[i]], segColor, 4);
        var line = makeLine([latlngs[i - 1], latlngs[i]], segColor, 4);
        segGlow.addTo(map);
        line.addTo(map);
        segments.push(segGlow, line);
      }
      markerColor = altitudeColor(f.points[f.points.length - 1].al * 3.28084);
    } else {
      color = PALETTE[idx % PALETTE.length];
      extraGlow = makeGlow(latlngs, color, 4);
      polyline = makeLine(latlngs, color, 4);
      extraGlow.addTo(map);
      polyline.addTo(map);
      markerColor = color;
    }

    var lastLatLng = latlngs[latlngs.length - 1];
    var dot = L.circleMarker(lastLatLng, {
      radius: 8,
      color: '#fff',
      fillColor: markerColor,
      fillOpacity: 1,
      weight: 3,
      opacity: 1
    }).addTo(map);
    L.circleMarker(lastLatLng, {
      radius: 5,
      color: markerColor,
      fillColor: markerColor,
      fillOpacity: 1,
      weight: 0
    }).addTo(map);

    var altMsl = f.points.length > 0 ? Math.round(f.points[f.points.length - 1].al * 3.28084) : 0;
    dot.bindTooltip(f.regoShort + ' - ' + f.name1 + ' (' + altMsl + '\')');

    flightLayers[f.seq] = { polyline: polyline, segments: segments, glow: extraGlow, marker: dot };

    latlngs.forEach(function(ll) { bounds.push(ll); });
    hasVisible = true;
  });

  if (hasVisible) {
    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
  } else {
    map.setView([MAP_LAT, MAP_LON], 11);
  }
}

function updateTimers() {
  var now = Date.now() / 1000 + clockOffset;
  flights.forEach(function(f) {
    var elapsed;
    if (f.landed) {
      elapsed = f.dur;
    } else {
      elapsed = now - f.start;
    }
    var timer = elapsed > 0 ? secondsToTimer(elapsed) : '00:00';

    var rows = document.querySelectorAll('.flight-row[data-seq="' + f.seq + '"]');
    rows.forEach(function(row) {
      var timerEl = row.querySelector('.timer');
      if (timerEl) timerEl.textContent = timer;

      var lastPt = f.points.length > 0 ? f.points[f.points.length - 1] : null;
      if (lastPt) {
        var ageEl = row.querySelector('.age');
        if (ageEl) ageEl.textContent = secondsToAge(now - lastPt.t);
      }
    });
  });
}

function setDate(raw) {
  var parsed = parseDateInput(raw);
  if (!parsed) {
    document.getElementById('date-picker').value = currentDate;
    return;
  }
  currentDate = parsed;
  isViewingToday = (parsed === TODAY_DATE);
  document.getElementById('date-picker').value = parsed;
  deselectAll();
  flights = [];
  duties = [];
  renderSidebar();
  renderMap(flights);
  fetchData();
}

function fetchData() {
  var url = 'todayxml.php?org=' + ORG;
  var d = dateYmd(currentDate);
  if (!isViewingToday) url += '&date=' + d;

  var xhr = new XMLHttpRequest();
  xhr.open('GET', url, true);
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        try {
          var xml = xhr.responseXML;
          if (!xml) {
            var parser = new DOMParser();
            xml = parser.parseFromString(xhr.responseText, 'text/xml');
          }
          var data = parseXML(xml);
          flights = data.flights;
          duties = data.duties;
          renderDuties();
          renderGliderFilter();
          renderSidebar();
          renderMap(flights);
        } catch (e) {
          console.error('Parse error:', e);
        }
      }
    }
  };
  xhr.send();
}

function selectFlight(seq) {
  if (selectedSeq === seq) {
    deselectAll();
    return;
  }
  selectedSeq = seq;
  document.getElementById('show-all-btn').classList.remove('hidden');
  renderSidebar();
  renderMap(flights);
}

function deselectAll() {
  selectedSeq = null;
  document.getElementById('show-all-btn').classList.add('hidden');
  renderSidebar();
  renderMap(flights);
}

function handleFlightClick(seq) {
  var landing = false;
  flights.forEach(function(f) {
    if (f.seq === seq && f.landed) landing = true;
  });
  if (landing) {
    deselectAll();
    return;
  }
  selectFlight(seq);
}

function handleOverlayFlightClick(seq) {
  closeOverlay();
  handleFlightClick(seq);
}

function openOverlay() {
  var overlay = document.getElementById('overlay');
  var content = document.getElementById('overlay-content');
  content.innerHTML = document.getElementById('sidebar').innerHTML;
  overlay.classList.add('open');
  content.querySelectorAll('.flight-row').forEach(function(row) {
    var seq = parseInt(row.getAttribute('data-seq'), 10);
    row.addEventListener('click', function() { handleOverlayFlightClick(seq); });
  });
}

function closeOverlay() {
  document.getElementById('overlay').classList.remove('open');
}

function init() {
  clockOffset = 0;

  map = L.map('map', {
    center: [MAP_LAT, MAP_LON],
    zoom: 11,
    zoomControl: true,
    attributionControl: true
  });

  L.tileLayer('https://tile.opentopomap.org/{z}/{x}/{y}.png', {
    maxZoom: 17,
    attribution: 'Map data: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> contributors'
  }).addTo(map);

  document.addEventListener('click', function(e) {
    var row = e.target.closest('.flight-row');
    if (row) {
      var seq = parseInt(row.getAttribute('data-seq'), 10);
      handleFlightClick(seq);
    }
  });

  document.getElementById('show-all-btn').addEventListener('click', deselectAll);

  document.getElementById('overlay-toggle').addEventListener('click', openOverlay);
  document.getElementById('overlay-close').addEventListener('click', closeOverlay);

  document.getElementById('filter-header').addEventListener('click', function() {
    var list = document.getElementById('filter-list');
    var toggle = document.getElementById('filter-toggle');
    list.classList.toggle('collapsed');
    toggle.classList.toggle('collapsed');
  });

  var datePicker = document.getElementById('date-picker');
  datePicker.value = TODAY_DATE;
  function goDate() {
    var val = datePicker.value.trim();
    if (!val) return;
    setDate(val);
  }
  datePicker.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') goDate();
  });
  datePicker.addEventListener('change', goDate);
  document.getElementById('date-go-btn').addEventListener('click', goDate);
  document.getElementById('date-today-btn').addEventListener('click', function() {
    datePicker.value = TODAY_DATE;
    setDate(TODAY_DATE);
  });

  if (DATE_PARAM) {
    setDate(DATE_PARAM);
  } else {
    fetchData();
  }

  tickId = setInterval(function() {
    pollcnt++;
    if (!isViewingToday) return;
    updateTimers();
    if (pollcnt % 30 === 0) {
      fetchData();
    }
  }, 1000);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
