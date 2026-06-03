<?php session_start(); ?>
<?php
require_once __DIR__ . '/helpers/logging.php';
logMsg("START");

$org = isset($_SESSION['org']) ? intval($_SESSION['org']) : 0;
$securityLevel = isset($_SESSION['security']) ? $_SESSION['security'] : 0;

if (!isset($_SESSION['security']) || !($securityLevel & 4)) {
    die("Security level too low for this page");
}

$con_params = require('./config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);

if (mysqli_connect_errno()) {
    die("Database connection failed");
}

$todayDate = date('Y-m-d');
$todayLocalDate = intval(date('Ymd'));

$nextSeq = 1;
$seqQ = "SELECT COALESCE(MAX(seq), 0) + 1 AS next_seq FROM flights WHERE org = $org AND localdate = $todayLocalDate";
$seqR = mysqli_query($con, $seqQ);
if ($seqR && ($seqRow = mysqli_fetch_assoc($seqR))) {
    $nextSeq = intval($seqRow['next_seq']);
}

$billingOptions = [];
$billQ = "SELECT id, name FROM billingoptions ORDER BY id";
$billR = mysqli_query($con, $billQ);
if ($billR) {
    while ($row = mysqli_fetch_assoc($billR)) {
        $billingOptions[] = ['id' => intval($row['id']), 'name' => $row['name']];
    }
}

mysqli_close($con);
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include 'jsLibraies.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
<?php
$inc = "./orgs/" . $org . "/heading2.css";
if (file_exists($inc)) { echo file_get_contents($inc); }
$inc = "./orgs/" . $org . "/menu1.css";
if (file_exists($inc)) { echo file_get_contents($inc); }
?>
body { background: #f5f5f5; font-family: Arial, Helvetica, sans-serif; min-height: 100vh; }
.container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 20px 24px; }
.page-header { display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; margin-bottom: 20px; border-bottom: 2px solid #063552; padding-bottom: 10px; }
.page-header h2 { margin: 0; color: #063552; font-size: 20px; font-weight: bold; }
.page-header .mode-badge { font-size: 11px; color: #f26120; background: #063552; border-radius: 4px; padding: 2px 8px; margin-left: 8px; vertical-align: middle; }
.flight-meta { color: #666; font-size: 14px; text-align: right; }
.flight-meta span { display: inline-block; margin-left: 12px; }
.form-group { margin-bottom: 16px; }
.form-group label { font-weight: 600; color: #333; font-size: 13px; display: block; margin-bottom: 4px; }
.form-group .form-control { font-size: 14px; }
.input-group .btn-default { border-color: #ccc; color: #333; }
.input-group .btn-default:disabled { opacity: 0.5; }

.autocomplete-wrap { position: relative; }
.autocomplete-dropdown { position: absolute; top: 100%; left: 0; right: 0; z-index: 1000; display: none; list-style: none; padding: 0; margin: 2px 0 0; background: #fff; border: 1px solid #ccc; border-radius: 4px; max-height: 240px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.12); }
.autocomplete-dropdown li { padding: 8px 12px; cursor: pointer; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
.autocomplete-dropdown li:last-child { border-bottom: none; }
.autocomplete-dropdown li:hover, .autocomplete-dropdown li.active { background: #063552; color: #f26120; }
.autocomplete-dropdown li .sub { font-size: 11px; color: #888; display: block; }
.autocomplete-dropdown li:hover .sub,
.autocomplete-dropdown li.active .sub { color: #ddd; }

.track-suggestion { display: none; background: #e8f5e9; border: 1px solid #a5d6a7; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; font-size: 13px; color: #2e7d32; }
.track-suggestion .times { font-weight: 600; font-size: 14px; }
.track-suggestion .reject-suggestion { float: right; cursor: pointer; color: #c62828; font-size: 12px; background: none; border: 1px solid #c62828; border-radius: 4px; padding: 2px 8px; }
.track-suggestion .reject-suggestion:hover { background: #c62828; color: #fff; }
.track-suggestion.none-found { background: #fff3e0; border-color: #ffcc80; color: #e65100; }

.pill { display: inline-block; background: #e8e8e8; color: #222; border-radius: 10px; padding: 2px 10px; font-size: 12px; white-space: nowrap; border: 1px solid #ccc; vertical-align: middle; }

.form-actions { display: flex; gap: 8px; }
.form-actions .btn-primary { flex: 1; }
.form-actions #cancel-btn { flex: 0 0 auto; min-width: 100px; }
.btn-primary { background-color: #063552; border-color: #042a40; color: #f26120; font-weight: 700; padding: 10px 16px; font-size: 15px; }
.btn-primary:hover { background-color: #0a4a70; border-color: #063552; color: #f26120; }
.btn-primary:disabled { opacity: 0.5; }
#cancel-btn { display: none; }

.alert { margin-top: 12px; }
.selected-member { display: inline-block; background: #e0e7ff; border: 1px solid #a0b4f0; border-radius: 4px; padding: 4px 8px; font-size: 13px; margin-top: 4px; }
.selected-member .remove { cursor: pointer; color: #c62828; margin-left: 6px; font-weight: bold; }
#member-badge { margin: 4px 0; }
#member-badge .pill { background: #e0e7ff; border-color: #a0b4f0; }

/* ---- Self-launch list ---- */
#self-launch-list-container { margin-bottom: 20px; }
#self-launch-list-container h4 { margin: 0 0 12px; color: #063552; font-size: 15px; font-weight: bold; }
.self-launch-table { margin-bottom: 0; font-size: 13px; }
.self-launch-table th { background: #063552; color: #f26120; font-size: 12px; padding: 6px 8px !important; }
.self-launch-table td { padding: 6px 8px !important; vertical-align: middle; }
.self-launch-table .actions { text-align: right; white-space: nowrap; }
.self-launch-table .actions .btn { margin-left: 4px; }
.self-launch-table .editing-row { background: #e8f0fe !important; border-left: 3px solid #063552; }
.self-launch-table .editing-row td:first-child { padding-left: 5px !important; }
.edit-btn { font-size: 12px; padding: 2px 8px; }
.delete-btn { font-size: 12px; padding: 2px 8px; line-height: 1.2; }

@media (max-width: 640px) {
    .container { margin: 8px 0; padding: 14px; border-radius: 0; }
    .page-header { margin-bottom: 16px; padding-bottom: 8px; }
    .page-header h2 { font-size: 20px; }
    #self-launch-list-container h4 { font-size: 20px; }
    .flight-meta { font-size: 12px; }
    .flight-meta span { margin-left: 8px; }
    .form-group { margin-bottom: 18px; }
    .form-group label { font-size: 15px; margin-bottom: 6px; }
    .form-group .form-control { font-size: 16px; height: auto; min-height: 44px; padding: 10px 12px; }
    .input-group .btn-default { font-size: 15px; min-height: 44px; padding: 10px 14px; }
    .btn-primary { font-size: 17px; padding: 14px 16px; min-height: 50px; }
    .autocomplete-dropdown li { padding: 12px 14px; font-size: 16px; }
    .autocomplete-dropdown li .sub { font-size: 13px; }
    .track-suggestion { padding: 12px 14px; font-size: 15px; }
    .track-suggestion .times { font-size: 16px; }
    .track-suggestion .reject-suggestion { font-size: 14px; padding: 6px 12px; }
    .pill { font-size: 14px; padding: 4px 12px; }
    .alert { font-size: 15px; padding: 12px 14px; }
    #track-suggestion { margin-bottom: 14px; }

    .self-launch-table thead { display: none; }
    .self-launch-table tbody { display: flex; flex-wrap: wrap; gap: 6px; }
    .self-launch-table tr {
        width: calc(50% - 3px); min-width: 240px; flex: 1 1 auto;
        border: 1px solid #ddd; border-radius: 6px;
        padding: 8px; background: #fff; display: block;
        position: relative;
    }
    .self-launch-table tr.editing-row { border-color: #063552; box-shadow: 0 0 0 2px rgba(6,53,82,0.15); }
    .self-launch-table td {
        display: block; border: none; padding: 3px 2px 3px 40% !important;
        text-align: left !important; font-size: 15px; position: relative;
        line-height: 1.4; min-width: 0;
    }
    .self-launch-table td::before {
        content: attr(data-label); position: absolute; left: 4px;
        font-weight: 600; color: #555; white-space: nowrap;
        width: calc(40% - 12px); overflow: hidden; text-overflow: ellipsis;
    }
    .self-launch-table td.actions { padding-left: 8px !important; }
    .self-launch-table td.actions::before { display: none; }
    .self-launch-table td[data-empty="1"] { display: none; }
    .edit-btn, .delete-btn { font-size: 14px; padding: 6px 14px; min-height: 36px; }
    .delete-btn { font-size: 16px; padding: 6px 14px; }
    #cancel-btn { min-height: 44px; font-size: 15px; }
    #self-launch-list-container { margin-bottom: 16px; }
}
</style>
</head>
<body>

<div class="no-padding-container">
    <?php $inc = "./orgs/" . $org . "/heading2.txt"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.txt"; if (file_exists($inc)) include $inc; ?>
</div>

<div class="container">

    <div class="form-group" style="margin-bottom:12px;">
        <input type="date" id="flight-date" class="form-control" value="<?php echo $todayDate; ?>">
    </div>

    <div id="message-area"></div>

    <div id="self-launch-list-container">
        <h4> Self-Launch Flights (<span id="self-launch-count">0</span>)</h4>
        <div id="self-launch-list-content"></div>
    </div>

    <form id="self-launch-form" autocomplete="off">

        <div class="page-header">
            <h2 id="page-title">Add New Self-Launch Flight</h2>
            <div class="flight-meta">
                <span id="display-date"><?php echo date('d M Y'); ?></span>
            </div>
        </div>        

        <div class="form-group">
            <label for="glider-input">Glider</label>
            <div class="input-group">
                <input type="text" id="glider-input" class="form-control" placeholder="Type 2+ chars to search..." autocomplete="off">
                <span class="input-group-btn">
                    <button type="button" id="suggest-btn" class="btn btn-default" disabled>Suggest</button>
                </span>
            </div>
            <div class="autocomplete-wrap">
                <ul id="glider-results" class="autocomplete-dropdown"></ul>
            </div>
            <input type="hidden" id="glider-rego" name="glider">
            <div id="glider-badge" style="margin-top:4px;display:none;"></div>
        </div>

        <div id="track-suggestion" class="track-suggestion"></div>

        <div class="form-group">
            <label for="pic-input">PIC</label>
            <input type="text" id="pic-input" class="form-control" placeholder="Type 2+ chars to search member..." autocomplete="off">
            <div class="autocomplete-wrap">
                <ul id="pic-results" class="autocomplete-dropdown"></ul>
            </div>
            <input type="hidden" id="pic-id" name="pic">
            <div id="pic-badge" style="margin-top:4px;display:none;"></div>
        </div>

        <div class="form-group">
            <label for="p2-input">P2 (optional)</label>
            <input type="text" id="p2-input" class="form-control" placeholder="Type 2+ chars to search member..." autocomplete="off">
            <div class="autocomplete-wrap">
                <ul id="p2-results" class="autocomplete-dropdown"></ul>
            </div>
            <input type="hidden" id="p2-id" name="p2">
            <div id="p2-badge" style="margin-top:4px;display:none;"></div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="start-time">Takeoff</label>
                    <input type="text" id="start-time" class="form-control time-input" placeholder="HH:MM">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="land-time">Land</label>
                    <input type="text" id="land-time" class="form-control time-input" placeholder="HH:MM">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="vector">Vector</label>
            <input type="text" id="vector" class="form-control" maxlength="2" placeholder="e.g. NN" style="width:80px;">
        </div>

        <div class="form-group">
            <label for="billing-option">Billing</label>
            <select id="billing-option" class="form-control">
                <?php foreach ($billingOptions as $bo): ?>
                <option value="<?php echo $bo['id']; ?>" <?php echo $bo['id'] === 2 ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($bo['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="comments">Comments</label>
            <input type="text" id="comments" class="form-control" maxlength="140" placeholder="Optional">
        </div>

        <div class="form-actions">
            <button type="submit" id="save-btn" class="btn btn-primary" disabled>Save Flight</button>
            <button type="button" id="cancel-btn" class="btn btn-default">Cancel</button>
        </div>
    </form>
</div>

<script>
var nextSeq = <?php echo $nextSeq; ?>;
var editingFlightId = null;
var editingSeq = null;

function makeTs(dateStr, timeStr) {
    if (!timeStr) return 0;
    return new Date(dateStr + 'T' + timeStr + ':00').getTime();
}

function formatDate(d) {
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
}

function formatMsTime(ms) {
    if (!ms) return '-';
    var d = new Date(parseInt(ms));
    return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
}

function esc(s) {
    if (!s) return '';
    return $('<span>').text(s).html();
}

// --- Autocomplete helper ---
function setupAutocomplete(inputId, resultsId, hiddenId, badgeId, apiUrl, renderItem, getValue) {
    var $input = $('#' + inputId);
    var $results = $('#' + resultsId);
    var $hidden = $('#' + hiddenId);
    var $badge = $('#' + badgeId);
    var timer = null;

    $input.on('input', function() {
        var val = $input.val().trim();
        if (timer) clearTimeout(timer);
        if (val.length < 2) {
            $results.hide();
            $results.empty();
            return;
        }
        timer = setTimeout(function() {
            $.get(apiUrl + '?search=' + encodeURIComponent(val), function(data) {
                $results.empty();
                if (data.length === 0) {
                    $results.hide();
                    return;
                }
                data.forEach(function(item) {
                    var li = $('<li></li>');
                    li.data('item', item);
                    li.html(renderItem(item));
                    li.on('click', function() {
                        var selected = $(this).data('item');
                        $hidden.val(getValue(selected));
                        var label = selected.displayname || selected.name || selected.rego_short;
                        $input.val(label);
                        $results.hide();
                        $results.empty();
                        showBadge($badge, label, function() {
                            $hidden.val('');
                            $input.val('').focus();
                            $badge.hide();
                            checkSaveReady();
                        });
                        checkSaveReady();
                        $input.trigger('itemselected', [selected]);
                    });
                    $results.append(li);
                });
                $results.show();
            }, 'json');
        }, 300);
    });

    $input.on('blur', function() {
        setTimeout(function() { $results.hide(); }, 200);
    });

    $input.on('focus', function() {
        if ($results.children().length > 0) $results.show();
    });
}

function showBadge($badge, text, onRemove) {
    $badge.show().html(
        '<span class="pill">' + $('<span>').text(text).html() +
        ' <span class="remove" style="cursor:pointer;color:#c62828;margin-left:4px;">&times;</span></span>'
    );
    $badge.find('.remove').on('click', onRemove);
}

function setBadgeValue($badge, text, hiddenId, val) {
    $('#' + hiddenId).val(val);
    showBadge($badge, text, function() {
        $('#' + hiddenId).val('');
        $badge.hide();
        checkSaveReady();
    });
}

function checkSaveReady() {
    var gliderOk = $('#glider-rego').val() !== '';
    var picOk = $('#pic-id').val() !== '';
    var startOk = $('#start-time').val() !== '';
    var landOk = $('#land-time').val() !== '';
    $('#save-btn').prop('disabled', !(gliderOk && picOk && startOk && landOk));
}

// --- Glider autocomplete ---
setupAutocomplete('glider-input', 'glider-results', 'glider-rego', 'glider-badge',
    '/api/aircraft',
    function(item) {
        return item.rego_short + ' <span class="sub">' + (item.make_model || '') + '</span>';
    },
    function(item) {
        return item.rego_short;
    }
);

$('#glider-input').on('itemselected', function(e, glider) {
    var dateStr = $('#flight-date').val();
    $('#track-suggestion').hide();
    $('#track-suggestion').removeClass('none-found');
    $('#suggest-btn').prop('disabled', false);
    fetchTrackSuggestion(glider.rego_short, dateStr);
});

$('#suggest-btn').on('click', function() {
    var rego = $('#glider-rego').val();
    var dateStr = $('#flight-date').val();
    if (rego) fetchTrackSuggestion(rego, dateStr);
});

function fetchTrackSuggestion(rego, dateStr) {
    $('#track-suggestion').hide();
    $.get('/api/track-flights?glider=' + encodeURIComponent(rego) + '&date=' + encodeURIComponent(dateStr),
        function(segments) {
            if (segments && segments.length > 0) {
                var seg = segments[0];
                var startTime = seg.start ? flatpickr.formatDate(new Date(seg.start), 'H:i') : '';
                var landTime = seg.land ? flatpickr.formatDate(new Date(seg.land), 'H:i') : '';
                if (startTime) $('#start-time').val(startTime);
                if (landTime) $('#land-time').val(landTime);
                if (startTime) {
                    var msg = 'Tracks suggest: <span class="times">' + startTime;
                    if (landTime) msg += ' &rarr; ' + landTime;
                    msg += '</span>';
                    msg += ' <button type="button" class="reject-suggestion">Clear</button>';
                    $('#track-suggestion').removeClass('none-found').show().html(msg);
                    $('#track-suggestion .reject-suggestion').on('click', function() {
                        $('#start-time').val('');
                        $('#land-time').val('');
                        $('#track-suggestion').hide();
                        checkSaveReady();
                    });
                    checkSaveReady();
                }
            } else {
                $('#track-suggestion').addClass('none-found').show().html(
                    'No tracks found for <strong>' + rego + '</strong> today. Enter times manually.'
                );
            }
        }
    ).fail(function() {
        $('#track-suggestion').addClass('none-found').show().html(
            'Could not load track data. Enter times manually.'
        );
    });
}

// --- Member autocomplete ---
setupAutocomplete('pic-input', 'pic-results', 'pic-id', 'pic-badge',
    '/api/member-search',
    function(item) { return item.name; },
    function(item) { return item.id; }
);

setupAutocomplete('p2-input', 'p2-results', 'p2-id', 'p2-badge',
    '/api/member-search',
    function(item) { return item.name; },
    function(item) { return item.id; }
);

// --- flatpickr time inputs ---
flatpickr('.time-input', {
    enableTime: true,
    noCalendar: true,
    dateFormat: 'H:i',
    time_24hr: true,
    onChange: function() { checkSaveReady(); }
});

// --- Self-launch list ---
function loadSelfLaunchList(dateStr) {
    $.get('/api/daily-flights?date=' + encodeURIComponent(dateStr), function(data) {
        var selfLaunches = [];
        if (data) {
            if (data.flights) {
                data.flights.forEach(function(f) {
                    if (parseInt(f.launchtype) === 3) selfLaunches.push(f);
                });
            }
            nextSeq = data.next_seq || 1;
        }
        $('#flight-seq').text(nextSeq);

        if (selfLaunches.length === 0) {
            $('#self-launch-list-content').html('<p style="color:#999;font-size:13px;margin:4px 0;">No self-launch flights today.</p>');
            $('#self-launch-count').text('0');
            $('#self-launch-list-container').show();
            return;
        }

        var html = '<table class="table table-condensed self-launch-table"><thead><tr>' +
            '<th>#</th><th>Glider</th><th>Takeoff</th><th>Land</th><th>PIC</th><th>P2</th><th>Billing</th><th></th></tr></thead><tbody>';

        selfLaunches.forEach(function(f) {
            var isEditing = editingFlightId == f.flight_id;
            html += '<tr class="' + (isEditing ? 'editing-row' : '') + '" data-id="' + f.flight_id + '">';
            html += '<td data-label="#' + f.seq + '">' + f.seq + '</td>';
            html += '<td data-label="Glider">' + esc(f.glider) + '</td>';
            html += '<td data-label="Takeoff">' + formatMsTime(f.start) + '</td>';
            html += '<td data-label="Land">' + formatMsTime(f.land) + '</td>';
            html += '<td data-label="PIC">' + esc(f.pic) + '</td>';
            html += '<td data-label="P2"' + (f.p2 ? '' : ' data-empty="1"') + '>' + (f.p2 ? esc(f.p2) : '-') + '</td>';
            html += '<td data-label="Billing">' + esc(f.billing) + '</td>';
            html += '<td class="actions">';
            if (isEditing) {
                html += '<span class="label label-warning" style="font-size:11px;">Editing</span>';
            } else {
                html += '<button type="button" class="btn btn-xs btn-default edit-btn" data-id="' + f.flight_id + '">Edit</button> ';
                html += '<button type="button" class="btn btn-xs btn-danger delete-btn" data-id="' + f.flight_id + '" data-seq="' + f.seq + '">&times;</button>';
            }
            html += '</td></tr>';
        });

        html += '</tbody></table>';
        $('#self-launch-list-content').html(html);
        $('#self-launch-count').text(selfLaunches.length);
        $('#self-launch-list-container').show();

        // Bind edit buttons
        $('.edit-btn').on('click', function() {
            var id = parseInt($(this).data('id'));
            var flight = selfLaunches.find(function(f) { return f.flight_id == id; });
            if (flight) enterEditMode(flight);
        });

        // Bind delete buttons
        $('.delete-btn').on('click', function() {
            var id = parseInt($(this).data('id'));
            var seq = parseInt($(this).data('seq'));
            deleteFlight(id, seq);
        });
    }).fail(function() {
        // Keep showing whatever we had
    });
}

function enterEditMode(flight) {
    editingFlightId = flight.flight_id;
    editingSeq = flight.seq;

    // Date
    var dateStr = $('#flight-date').val();

    // Glider
    $('#glider-rego').val(flight.glider);
    $('#glider-input').val(flight.glider);
    setBadgeValue($('#glider-badge'), flight.glider, 'glider-rego', flight.glider);
    $('#suggest-btn').prop('disabled', false);

    // PIC
    if (flight.pic_id) {
        $('#pic-id').val(flight.pic_id);
        $('#pic-input').val(flight.pic);
        setBadgeValue($('#pic-badge'), flight.pic, 'pic-id', flight.pic_id);
    }

    // P2
    if (flight.p2_id) {
        $('#p2-id').val(flight.p2_id);
        $('#p2-input').val(flight.p2);
        setBadgeValue($('#p2-badge'), flight.p2, 'p2-id', flight.p2_id);
    } else {
        $('#p2-id').val('');
        $('#p2-input').val('');
        $('#p2-badge').hide();
    }

    // Times via flatpickr
    var startPicker = document.querySelector('#start-time')._flatpickr;
    var landPicker = document.querySelector('#land-time')._flatpickr;
    if (startPicker && flight.start) startPicker.setDate(formatMsTime(flight.start), true, 'H:i');
    if (landPicker && flight.land) landPicker.setDate(formatMsTime(flight.land), true, 'H:i');

    // Billing
    $('#billing-option').val(flight.billing_id);

    // Comments
    $('#comments').val(flight.comments || '');

    // Switch UI
    $('#page-title').text('Edit Flight #' + flight.seq);
    $('#save-btn').text('Update Flight #' + flight.seq);
    $('#cancel-btn').show();
    checkSaveReady();

    // Re-render list to show editing badge
    loadSelfLaunchList($('#flight-date').val());

    // Scroll to form
    $('html, body').animate({ scrollTop: $('#self-launch-form').offset().top - 20 }, 300);
}

function cancelEdit() {
    editingFlightId = null;
    editingSeq = null;
    resetForm();
    $('#page-title').text('Self-Launch Flight');
    $('#save-btn').text('Save Flight');
    $('#cancel-btn').hide();
    loadSelfLaunchList($('#flight-date').val());
    $('html, body').animate({ scrollTop: $('#self-launch-list-container').offset().top - 10 }, 300);
}

function deleteFlight(flightId, flightSeq) {
    if (!confirm('Delete self-launch flight #' + flightSeq + '?')) return;

    $.ajax({
        url: '/api/flights',
        method: 'POST',
        data: JSON.stringify({ action: 'delete', id: flightId }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                if (editingFlightId == flightId) cancelEdit();
                loadSelfLaunchList($('#flight-date').val());
                $('#message-area').html(
                    '<div class="alert alert-success alert-dismissible">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    'Flight #' + flightSeq + ' deleted.</div>'
                );
            } else {
                alert('Delete failed: ' + (resp.message || 'Unknown error'));
            }
        },
        error: function() {
            alert('Delete request failed');
        }
    });
}

// --- Date change ---
$('#flight-date').on('change', function() {
    var d = new Date($(this).val() + 'T12:00:00');
    $('#display-date').text(formatDate(d));
    var rego = $('#glider-rego').val();
    if (rego) fetchTrackSuggestion(rego, $(this).val());
    loadSelfLaunchList($(this).val());
});

// --- Cancel button ---
$('#cancel-btn').on('click', function() {
    cancelEdit();
});

// --- Form submit ---
$('#self-launch-form').on('submit', function(e) {
    e.preventDefault();
    var dateStr = $('#flight-date').val();
    var startTs = makeTs(dateStr, $('#start-time').val());
    var landTs = makeTs(dateStr, $('#land-time').val());

    if (landTs <= startTs) {
        $('#message-area').html('<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Land time must be after takeoff time.</div>');
        return;
    }

    var data = {
        glider: $('#glider-rego').val(),
        pic: parseInt($('#pic-id').val()),
        p2: $('#p2-id').val() ? parseInt($('#p2-id').val()) : null,
        start: startTs,
        land: landTs,
        billingOption: parseInt($('#billing-option').val()),
        comments: $('#comments').val(),
        date: dateStr,
        launchType: 3,
        type: 1,
        vector: $('#vector').val().toUpperCase().trim()
    };

    if (editingFlightId) {
        data.id = editingFlightId;
    }

    $('#save-btn').prop('disabled', true).text('Saving...');

    $.ajax({
        url: '/api/flights',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                $('#message-area').html(
                    '<div class="alert alert-success alert-dismissible">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    'Flight saved! Flight #' + resp.seq + (resp.flightId ? ' (ID: ' + resp.flightId + ')' : '') +
                    '</div>'
                );

                if (editingFlightId) {
                    cancelEdit();
                } else {
                    nextSeq = (resp.seq || 0) + 1;
                    $('#flight-seq').text(nextSeq);
                    resetForm();
                }
                loadSelfLaunchList(dateStr);
            } else {
                $('#message-area').html('<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>' + (resp.message || 'Save failed') + '</div>');
                $('#save-btn').prop('disabled', false).text(editingFlightId ? 'Update Flight #' + editingSeq : 'Save Flight');
            }
        },
        error: function(xhr) {
            var msg = 'Request failed';
            try { var r = JSON.parse(xhr.responseText); msg = r.message || r.error || msg; } catch(e) {}
            $('#message-area').html('<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>' + msg + '</div>');
            $('#save-btn').prop('disabled', false).text(editingFlightId ? 'Update Flight #' + editingSeq : 'Save Flight');
        }
    });
});

function resetForm() {
    $('#glider-input').val('');
    $('#glider-rego').val('');
    $('#glider-badge').hide();
    $('#track-suggestion').hide();
    $('#pic-input').val('');
    $('#pic-id').val('');
    $('#pic-badge').hide();
    $('#p2-input').val('');
    $('#p2-id').val('');
    $('#p2-badge').hide();
    $('#start-time').val('');
    $('#land-time').val('');
    $('#comments').val('');
    $('#billing-option').val('2');
    $('#suggest-btn').prop('disabled', true);
    $('#save-btn').prop('disabled', true).text('Save Flight');
    $('#glider-input').focus();
}

// Init
checkSaveReady();
loadSelfLaunchList($('#flight-date').val());
</script>
</body>
</html>
