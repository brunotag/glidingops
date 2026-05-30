var PALETTE = [
  '#e6194b','#3cb44b','#ffe119','#4363d8','#f58231','#911eb4',
  '#42d4f4','#f032e6','#bfef45','#fabed4','#469990','#dcbeff',
  '#9a6324','#fffac8','#800000','#aaffc3','#808000','#ffd8b1',
  '#000075','#a9a9a9','#ffb3b3','#b3d4ff','#c2f0c2','#e6c3e6'
];

var flights = [];
var duties = [];
var selectedFlights = [];
var traceBrightness = 80;
var map = null;
var flightLayers = {};
var pollcnt = 0;
var refreshInterval = 30 + Math.floor(Math.random() * 31);
var clockOffset = 0;
var tickId = null;
var currentDate = TODAY_DATE;
var isViewingToday = true;
var darkenLayer = null;
var flyingOnlyActive = false;
var initialLoad = true;
var waypointLayer = null;
var wpState = 0;

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

function regoShortFromFull(full) {
  if (full.length >= 2) return full.slice(-2);
  return full;
}

function adjustBrightness(hex, factor) {
  var r = parseInt(hex.slice(1,3), 16);
  var g = parseInt(hex.slice(3,5), 16);
  var b = parseInt(hex.slice(5,7), 16);
  return '#' + [r,g,b].map(function(c) {
    return Math.min(255, Math.round(c * factor)).toString(16).padStart(2, '0');
  }).join('');
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

function secondsToTimer(s) {
  s = Math.floor(s);
  var h = Math.floor(s / 3600);
  var m = Math.floor((s % 3600) / 60);
  if (h > 0) return h + 'h ' + String(m).padStart(2, '0') + 'm';
  return m + 'm';
}

function secondsToTimerFlying(s) {
  s = Math.floor(s);
  var h = Math.floor(s / 3600);
  var m = Math.floor((s % 3600) / 60);
  var sec = s % 60;
  if (h > 0) return h + ':' + String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
  return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
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

function renderDuties() {
  var el = document.getElementById('duties');
  if (!el) return;
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
  var FLYING_HEADER = '<div class="flight-header">' +
    '<span class="color-dot"></span>' +
    '<span class="seq">#</span>' +
    '<span class="rego">Reg</span>' +
    '<span class="timer">Time</span>' +
    '<span class="altitude-msl">MSL</span>' +
    '<span class="altitude-agl">AGL</span>' +
    '<span class="age">Age</span>' +
    '</div>';

  var flyingEl = document.getElementById('flying-list');
  var completedEl = document.getElementById('completed-list');
  var now = Date.now() / 1000 + clockOffset;
  var hasSelection = selectedFlights.length > 0;

  var flyingRows = [];
  var completedRows = [];

  flights.forEach(function(f, idx) {
    var isInView = !hasSelection || selectedFlights.indexOf(f.seq) !== -1;
    var dotColor;
    if (hasSelection) {
      dotColor = isInView ? PALETTE[idx % PALETTE.length] : '#555';
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
    var flyingTimer = elapsed > 0 ? secondsToTimerFlying(elapsed) : '00:00';
    var lastPt = f.points.length > 0 ? f.points[f.points.length - 1] : null;
    var altMsl = lastPt ? Math.round(lastPt.al * 3.28084) : 0;
    var altAgl = lastPt ? Math.max(0, Math.round(lastPt.al * 3.28084 - LAUNCH_ELEVATION)) : 0;
    var dist = lastPt ? distKm(LAUNCH_LAT, LAUNCH_LON, lastPt.lt, lastPt.ln) : 0;
    var distStr = dist < 1 ? Math.round(dist * 1000) + 'm' : dist.toFixed(1) + 'km';
    var ageStr = lastPt ? secondsToAge(now - lastPt.t) : '-';

    var selClass = isInView && hasSelection ? ' selected' : '';

    var row1 = '<div class="flight-row">' +
      '<span class="color-dot" style="background:' + dotColor + '"></span>' +
      '<span class="seq">' + f.seq + '</span>' +
      '<span class="rego">' + escapeHtml(f.regoShort) + '</span>' +
      '<span class="timer">' + (f.landed ? timer : flyingTimer) + '</span>';

    if (!f.landed) {
      row1 += '<span class="altitude-msl">' + altMsl + '\'</span>' +
        '<span class="altitude-agl">' + altAgl + '\'</span>' +
        '<span class="age">' + ageStr + '</span>';
    }

    row1 += '</div>';

    var row2 = '<div class="flight-subrow">' +
      '<span class="pilot-name">' + escapeHtml(f.name1) + '</span>';
    if (f.name2) {
      row2 += '<span class="pilot-sep">;</span><span class="pilot-name">' + escapeHtml(f.name2) + '</span>';
    }
    row2 += '</div>';

    var wrapper = '<div class="flight-wrapper' + selClass + '" data-seq="' + f.seq + '" data-landed="' + f.landed + '">' +
      row1 + row2 + '</div>';

    if (f.landed) {
      var doneRow = '<div class="flight-row">' +
        '<span class="color-dot" style="background:' + dotColor + '"></span>' +
        '<span class="seq">' + f.seq + '</span>' +
        '<span class="rego">' + escapeHtml(f.regoShort) + '</span>' +
        '<span class="timer">' + timer + '</span>' +
        '<span class="pilot-inline">' + escapeHtml(f.name1) + (f.name2 ? '; ' + escapeHtml(f.name2) : '') + '</span>' +
        '</div>';
      completedRows.push('<div class="flight-wrapper' + selClass + '" data-seq="' + f.seq + '" data-landed="1">' + doneRow + '</div>');
    } else {
      flyingRows.push(wrapper);
    }
  });

  flyingEl.innerHTML = FLYING_HEADER + flyingRows.join('');
  completedEl.innerHTML = completedRows.join('');
}

function makeGliderIcon(color, label) {
  var r = parseInt(color.slice(1,3), 16), g = parseInt(color.slice(3,5), 16), b = parseInt(color.slice(5,7), 16);
  var lum = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
  var sz = MODE === 'mobile' ? 22 : 34;
  var anchor = Math.round(sz / 2);
  return L.divIcon({
    className: '',
    html: '<div style="width:' + sz + 'px;height:' + sz + 'px;border-radius:50%;background:' + color +
      ';display:flex;align-items:center;justify-content:center;font-size:' + (sz < 30 ? 10 : 12) + 'px;font-weight:800;color:' +
      (lum > 0.5 ? '#111' : '#fff') + ';border:2px solid rgba(255,255,255,0.4)">' + label + '</div>',
    iconSize: [sz, sz], iconAnchor: [anchor, anchor]
  });
}

function renderMap(dataFlights, preserveView) {
  for (var key in flightLayers) {
    if (flightLayers[key]) {
      if (flightLayers[key].polyline) map.removeLayer(flightLayers[key].polyline);
      if (flightLayers[key].segments) {
        flightLayers[key].segments.forEach(function(s) { map.removeLayer(s); });
      }
      if (flightLayers[key].marker) map.removeLayer(flightLayers[key].marker);
    }
  }
  flightLayers = {};

  var savedCenter, savedZoom;
  if (preserveView) {
    savedCenter = map.getCenter();
    savedZoom = map.getZoom();
  }

  var bounds = [];
  var hasVisible = false;
  var hasSelection = selectedFlights.length > 0;

  dataFlights.forEach(function(f, idx) {
    if (hasSelection && selectedFlights.indexOf(f.seq) === -1) return;

    var brightFactor = hasSelection && selectedFlights.length === 1 ? traceBrightness / 100 : 1;

    var latlngs = f.points.filter(function(p) { return p.lt !== 0 && p.ln !== 0; })
      .map(function(p) { return [p.lt, p.ln]; });

    if (latlngs.length < 2) {
      if (latlngs.length === 1) {
        var dotColor = adjustBrightness(PALETTE[idx % PALETTE.length], brightFactor);
        L.marker(latlngs[0], { icon: makeGliderIcon(dotColor, f.regoShort) }).addTo(map).bindTooltip(f.regoShort + ' - ' + f.name1);
        flightLayers[f.seq] = { polyline: null, segments: null, marker: null };
        bounds.push(latlngs[0]);
        hasVisible = true;
      }
      return;
    }

    var segments = null;
    var polyline = null;
    var markerColor;

    if (brightFactor < 1) {
      segments = [];
      for (var i = 1; i < latlngs.length; i++) {
        var segColor = adjustBrightness(PALETTE[idx % PALETTE.length], brightFactor);
        var seg = L.polyline([latlngs[i - 1], latlngs[i]], {
          color: segColor, weight: 3, opacity: 0.95
        }).addTo(map);
        segments.push(seg);
      }
      markerColor = adjustBrightness(PALETTE[idx % PALETTE.length], brightFactor);
    } else {
      var color = PALETTE[idx % PALETTE.length];
      polyline = L.polyline(latlngs, {
        color: color, weight: 3, opacity: 0.95
      }).addTo(map);
      markerColor = color;
    }

    var lastLatLng = latlngs[latlngs.length - 1];
    var dot = L.marker(lastLatLng, { icon: makeGliderIcon(markerColor, f.regoShort) }).addTo(map);

    var altMsl = f.points.length > 0 ? Math.round(f.points[f.points.length - 1].al * 3.28084) : 0;
    dot.bindTooltip(f.regoShort + ' - ' + f.name1 + ' (' + altMsl + '\')');

    flightLayers[f.seq] = { polyline: polyline, segments: segments, marker: dot };

    latlngs.forEach(function(ll) { bounds.push(ll); });
    hasVisible = true;
  });

  if (preserveView && savedCenter) {
    map.setView(savedCenter, savedZoom);
  } else if (hasVisible) {
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
    var flyingTimer = elapsed > 0 ? secondsToTimerFlying(elapsed) : '00:00';

    var wrappers = document.querySelectorAll('.flight-wrapper[data-seq="' + f.seq + '"]');
    wrappers.forEach(function(w) {
      var timerEl = w.querySelector('.timer');
      if (timerEl) {
        var isLanded = w.getAttribute('data-landed') === '1';
        timerEl.textContent = isLanded ? timer : flyingTimer;
      }

      if (!f.landed) {
        var ageEl = w.querySelector('.age');
        var lastPt = f.points.length > 0 ? f.points[f.points.length - 1] : null;
        if (ageEl && lastPt) ageEl.textContent = secondsToAge(now - lastPt.t);
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
  document.getElementById('flying-section').style.display = isViewingToday ? '' : 'none';
  document.getElementById('completed-header-label').textContent = isViewingToday ? 'COMPLETED TODAY' : 'FLIGHTS OF THE DAY';
  deselectAll();
  flights = [];
  duties = [];
  renderSidebar();
  renderMap(flights);
  fetchData();
  var showRefresh = isViewingToday ? '' : 'none';
  document.getElementById('refresh-btn').style.display = showRefresh;
  document.getElementById('last-updated').style.display = showRefresh;
}

function fetchData() {
  var url = '/todayxml.php?org=' + ORG;
  var d = dateYmd(currentDate);
  if (!isViewingToday) url += '&date=' + d;

  var xhr = new XMLHttpRequest();
  xhr.open('GET', url, true);
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      document.getElementById('refresh-btn').classList.remove('spin');
      var updEl = document.getElementById('last-updated');
      updEl.textContent = ' ' + new Date().toLocaleTimeString();
      updEl.classList.remove('pulse');
      void updEl.offsetWidth;
      updEl.classList.add('pulse');
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
          if (isViewingToday && initialLoad) {
            initialLoad = false;
            var anyFlying = false;
            var flyingSeqs = [];
            flights.forEach(function(f) {
              if (!f.landed) { anyFlying = true; flyingSeqs.push(f.seq); }
            });
            if (anyFlying) {
              flyingOnlyActive = true;
              selectedFlights = flyingSeqs;
            }
            var btn = document.getElementById('flying-only-btn');
            if (btn) {
              if (flyingOnlyActive) { btn.classList.add('active'); } else { btn.classList.remove('active'); }
            }
            document.getElementById('sidebar-show-all').style.display = selectedFlights.length > 0 ? 'block' : 'none';
          }
          if (flyingOnlyActive) {
            selectedFlights = [];
            flights.forEach(function(f) { if (!f.landed) selectedFlights.push(f.seq); });
          }
          renderDuties();
          renderSidebar();
          renderMap(flights, true);
          updateFlyingOnlyBtnVisibility();
          pollcnt = 0;
          refreshInterval = 30 + Math.floor(Math.random() * 31);
        } catch (e) {
          console.error('Parse error:', e);
        }
      }
    }
  };
  xhr.send();
}

var lastClick = { seq: -1, time: 0 };

function handleFlightClick(seq) {
  var now = Date.now();
  var isDbl = (seq === lastClick.seq && now - lastClick.time < 350);
  lastClick = { seq: seq, time: now };

  if (flyingOnlyActive) {
    flyingOnlyActive = false;
    var fob = document.getElementById('flying-only-btn');
    if (fob) fob.classList.remove('active');
    selectedFlights = [seq];
    showBrightnessUI(true);
    document.getElementById('sidebar-show-all').style.display = 'block';
    renderSidebar();
    renderMap(flights);
    updateFlyingOnlyBtnVisibility();
    return;
  }

  if (isDbl) {
    selectedFlights = [seq];
  } else if (selectedFlights.length === 0) {
    selectedFlights = [seq];
  } else if (selectedFlights.length === 1 && selectedFlights[0] === seq) {
    return;
  } else {
    var idx = selectedFlights.indexOf(seq);
    if (idx !== -1) {
      if (selectedFlights.length > 1) {
        selectedFlights.splice(idx, 1);
      } else {
        return;
      }
    } else {
      selectedFlights.push(seq);
    }
  }
  showBrightnessUI(selectedFlights.length === 1);
  document.getElementById('sidebar-show-all').style.display = selectedFlights.length > 0 ? 'block' : 'none';
  renderSidebar();
  renderMap(flights);
  updateFlyingOnlyBtnVisibility();
}

function showBrightnessUI(show) {
  var el = document.getElementById('brightness-label') || document.getElementById('brightness-wrap');
  if (el) el.style.display = show ? '' : 'none';
  if (!show && traceBrightness !== 80) {
    traceBrightness = 80;
    document.getElementById('brightness-slider').value = 80;
    renderMap(flights, true);
  }
}

function updateFlyingOnlyBtnVisibility() {
  var hasFlying = false;
  var allFlyingSelected = true;
  var hasCompletedSelected = false;
  flights.forEach(function(f) {
    if (!f.landed) {
      hasFlying = true;
      if (selectedFlights.indexOf(f.seq) === -1) allFlyingSelected = false;
    } else {
      if (selectedFlights.indexOf(f.seq) !== -1) hasCompletedSelected = true;
    }
  });
  var show = hasFlying && (!allFlyingSelected || hasCompletedSelected);
  var btn = document.getElementById('flying-only-btn');
  if (btn) btn.style.display = show ? '' : 'none';
}

function deselectAll() {
  selectedFlights = [];
  if (flyingOnlyActive) {
    flyingOnlyActive = false;
    var btn = document.getElementById('flying-only-btn');
    if (btn) btn.classList.remove('active');
  }
  document.getElementById('sidebar-show-all').style.display = 'none';
  var bl = document.getElementById('brightness-label') || document.getElementById('brightness-wrap');
  if (bl) bl.style.display = 'none';
  renderSidebar();
  renderMap(flights);
  updateFlyingOnlyBtnVisibility();
}

function toggleFlyingOnly() {
  flyingOnlyActive = !flyingOnlyActive;
  if (flyingOnlyActive) {
    var flyingSeqs = [];
    flights.forEach(function(f) {
      if (!f.landed) flyingSeqs.push(f.seq);
    });
    selectedFlights = flyingSeqs;
  } else {
    selectedFlights = [];
  }
  var btn = document.getElementById('flying-only-btn');
  if (btn) {
    if (flyingOnlyActive) { btn.classList.add('active'); } else { btn.classList.remove('active'); }
  }
  showBrightnessUI(selectedFlights.length === 1);
  document.getElementById('sidebar-show-all').style.display = selectedFlights.length > 0 ? 'block' : 'none';
  renderSidebar();
  renderMap(flights);
  updateFlyingOnlyBtnVisibility();
}

function renderWaypoints() {
  if (waypointLayer) { map.removeLayer(waypointLayer); waypointLayer = null; }
  if (wpState === 0) return;
  waypointLayer = L.layerGroup();
  WAYPOINTS.forEach(function(wp) {
    var dotClass = 'wp-dot';
    if (wp.style !== 1) dotClass += ' wp-dot-runway';
    var html = '<div class="' + dotClass + '" style="width:10px;height:10px;background:#e066ff';
    if (wp.style !== 1) html += ';box-shadow:inset 0 0 0 2px #000';
    html += '"></div><div class="wp-label" style="font-size:11px">' + wp.name + '</div>';
    L.marker([wp.lat, wp.lon], {
      icon: L.divIcon({ className: 'wp-marker', html: html, iconSize: [12, 12], iconAnchor: [6, 6] })
    }).addTo(waypointLayer);
  });
  waypointLayer.addTo(map);
}

function toggleWaypoints() {
  wpState = wpState === 0 ? 2 : 0;
  renderWaypoints();
  var btn = document.getElementById('waypoints-btn');
  if (!btn) return;
  btn.classList.remove('active', 'active-md');
  if (wpState === 2) { btn.classList.add('active-md'); btn.textContent = 'Waypoints'; }
  else { btn.textContent = 'Waypoints'; }
}

function initDivider() {
  var divider = document.getElementById('divider-handle');
  var overlay = document.getElementById('overlay');
  if (!divider || !overlay) return;

  var mapPanel = document.getElementById('map-panel');
  var startY, startHeight;

  function onDragStart(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'BUTTON' || e.target.closest('input,button,textarea,select')) return;
    startY = e.clientY || e.touches[0].clientY;
    startHeight = overlay.offsetHeight;
    document.body.style.cursor = 'row-resize';
    document.body.style.userSelect = 'none';
    document.addEventListener('mousemove', onDragMove);
    document.addEventListener('mouseup', onDragEnd);
    document.addEventListener('touchmove', onDragMove, { passive: false });
    document.addEventListener('touchend', onDragEnd);
    e.preventDefault();
  }

  function onDragMove(e) {
    var clientY = e.clientY || e.touches[0].clientY;
    var dy = startY - clientY;
    var newHeight = startHeight + dy;
    var viewportHeight = window.visualViewport ? window.visualViewport.height : window.innerHeight;
    newHeight = Math.max(120, Math.min(viewportHeight * 0.95, newHeight));
    overlay.style.height = newHeight + 'px';
    map._onResize();
    e.preventDefault();
  }

  function onDragEnd() {
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
    document.removeEventListener('mousemove', onDragMove);
    document.removeEventListener('mouseup', onDragEnd);
    document.removeEventListener('touchmove', onDragMove);
    document.removeEventListener('touchend', onDragEnd);
    try {
      localStorage.setItem('mapBottomHeight', overlay.style.height);
    } catch(e) {}
  }

  divider.addEventListener('mousedown', onDragStart);
  divider.addEventListener('touchstart', onDragStart, { passive: false });

  var header = document.getElementById('overlay-header');
  if (header) {
    header.style.cursor = 'row-resize';
    header.addEventListener('mousedown', onDragStart);
    header.addEventListener('touchstart', onDragStart, { passive: false });
  }

  try {
    var saved = localStorage.getItem('mapBottomHeight');
    if (saved) {
      overlay.style.height = saved;
    }
  } catch(e) {}
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

  map.createPane('darkenPane');
  map.getPane('darkenPane').style.zIndex = 350;
  darkenLayer = L.rectangle([[90, -180], [-90, 180]], {
    pane: 'darkenPane',
    color: '#000',
    fillColor: '#000',
    fillOpacity: 0.25,
    weight: 0,
    interactive: false
  }).addTo(map);

  document.addEventListener('click', function(e) {
    var wrapper = e.target.closest('.flight-wrapper');
    if (wrapper) {
      var seq = parseInt(wrapper.getAttribute('data-seq'), 10);
      handleFlightClick(seq);
    }
  });

  document.getElementById('sidebar-show-all').addEventListener('click', deselectAll);

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

  document.getElementById('brightness-slider').addEventListener('input', function() {
    traceBrightness = parseInt(this.value, 10);
    renderMap(flights, true);
  });

  function doRefresh() {
    document.getElementById('refresh-btn').classList.add('spin');
    fetchData();
  }
  document.getElementById('refresh-btn').addEventListener('click', doRefresh);

  document.getElementById('flying-only-btn').addEventListener('click', toggleFlyingOnly);
  document.getElementById('waypoints-btn').addEventListener('click', toggleWaypoints);

  document.getElementById('overlay-slider').addEventListener('input', function() {
    var val = this.value / 100;
    darkenLayer.setStyle({ fillOpacity: val });
    var dev = document.getElementById('dev-overlay');
    if (dev) dev.value = this.value;
  });

  initDivider();

  if (IS_DEV) {
    document.getElementById('dev-overlay').addEventListener('input', function() {
      var val = this.value / 100;
      darkenLayer.setStyle({ fillOpacity: val });
      document.getElementById('overlay-slider').value = this.value;
    });
    document.getElementById('dev-track').addEventListener('input', function() {
      var val = this.value / 100;
      document.getElementById('dev-track-val').textContent = val.toFixed(2);
      for (var key in flightLayers) {
        if (flightLayers[key]) {
          if (flightLayers[key].polyline) flightLayers[key].polyline.setStyle({ opacity: val });
          if (flightLayers[key].segments) {
            flightLayers[key].segments.forEach(function(s) { s.setStyle({ opacity: val }); });
          }
        }
      }
    });
  }

  if (DATE_PARAM) {
    setDate(DATE_PARAM);
  } else {
    document.getElementById('flying-section').style.display = '';
    fetchData();
  }

  tickId = setInterval(function() {
    pollcnt++;
    if (!isViewingToday) return;
    updateTimers();
    if (pollcnt >= refreshInterval) {
      fetchData();
    }
  }, 1000);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
