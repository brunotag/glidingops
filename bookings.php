<?php
session_start();
$org = isset($_SESSION['org']) ? (int)$_SESSION['org'] : 0;

require_once __DIR__ . '/helpers/permissions.php';
require_perm('bookings.view');

require_once __DIR__ . '/load_model.php';
require_once __DIR__ . '/helpers/logging.php';
require_once __DIR__ . '/helpers/timehelpers.php';

$memberId = isset($_SESSION['memberid']) ? (int)$_SESSION['memberid'] : 0;
if ($memberId <= 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'No member account linked to your user']);
        exit;
    }
    die('No member account linked to your user');
}
$isAdmin = has_perm('bookings.admin');
$tzName = orgTimezone(null, $org);
$tz = new DateTimeZone($tzName);

require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();
if (mysqli_connect_errno()) {
    die('Database connection failed');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    header('Content-Type: application/json; charset=utf-8');
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $booking = App\Models\Booking::find($id);
    if (!$booking || $booking->deleted) {
        echo json_encode(['success' => false, 'error' => 'Not found']);
        exit;
    }
    if (!$isAdmin && $booking->member_id !== $memberId) {
        echo json_encode(['success' => false, 'error' => 'Not authorised']);
        exit;
    }
    echo json_encode(['success' => true, 'booking' => $booking->toArray()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    try {
        $gcal = new App\Services\GoogleCalendarService();

        if ($action === 'create') {
            $date = isset($_POST['date']) ? trim($_POST['date']) : '';
            $intention = isset($_POST['intention']) ? trim($_POST['intention']) : '';
            $aircraftRego = isset($_POST['aircraft_rego']) ? trim($_POST['aircraft_rego']) : '';
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

            if (empty($date)) {
                echo json_encode(['success' => false, 'error' => 'Date is required']);
                exit;
            }

            $member = \App\Models\Member::find($memberId);
            if (!$member) {
                echo json_encode(['success' => false, 'error' => 'Member not found']);
                exit;
            }
            $dispName = $member->displayname ?: 'Unknown';

            $booking = new App\Models\Booking();
            $booking->org = $org;
            $booking->member_id = $memberId;
            $booking->booking_date = $date;
            $booking->intention = $intention;
            $booking->aircraft_rego = $aircraftRego;
            $booking->notes = $notes;
            $booking->save();

            $seq = $gcal->getNextSequence($date);

            $summaryParts = [$dispName];
            if (!empty($aircraftRego)) $summaryParts[] = $aircraftRego;
            if (!empty($intention)) $summaryParts[] = $intention;
            if (!empty($notes)) $summaryParts[] = "($notes)";
            $summary = implode(' - ', $summaryParts);

            $description = '';
            if (!empty($aircraftRego)) $description .= "glider: $aircraftRego\n";
            if (!empty($intention)) $description .= "intentions: $intention\n";
            if (!empty($notes)) $description .= "details: $notes\n";

            try {
                $eventId = $gcal->createEvent($date, $seq, $summary, $description);
                $booking->google_event_id = $eventId;
                $booking->save();
            } catch (\Exception $e) {
                $booking->delete();
                logMsg("Booking create failed (Calendar error): " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to create calendar event: ' . $e->getMessage()]);
                exit;
            }

            logMsg("Booking created: id={$booking->id} date=$date member=$memberId");
            echo json_encode(['success' => true, 'booking_id' => $booking->id]);
            exit;
        }

        if ($action === 'update') {
            $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
            $booking = App\Models\Booking::find($bookingId);

            if (!$booking || $booking->deleted) {
                echo json_encode(['success' => false, 'error' => 'Booking not found']);
                exit;
            }
            if (!$isAdmin && $booking->member_id !== $memberId) {
                echo json_encode(['success' => false, 'error' => 'Not authorised']);
                exit;
            }

            $intention = isset($_POST['intention']) ? trim($_POST['intention']) : '';
            $aircraftRego = isset($_POST['aircraft_rego']) ? trim($_POST['aircraft_rego']) : '';
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

            $booking->intention = $intention;
            $booking->aircraft_rego = $aircraftRego;
            $booking->notes = $notes;
            $booking->save();

            $memberQuery = mysqli_query($con, "SELECT displayname FROM members WHERE id = {$booking->member_id}");
            $memberRow = mysqli_fetch_array($memberQuery);
            $dispName = $memberRow ? $memberRow['displayname'] : 'Unknown';

            $summaryParts = [$dispName];
            if (!empty($aircraftRego)) $summaryParts[] = $aircraftRego;
            if (!empty($intention)) $summaryParts[] = $intention;
            if (!empty($notes)) $summaryParts[] = "($notes)";
            $summary = implode(' - ', $summaryParts);

            $description = '';
            if (!empty($aircraftRego)) $description .= "glider: $aircraftRego\n";
            if (!empty($intention)) $description .= "intentions: $intention\n";
            if (!empty($notes)) $description .= "details: $notes\n";

            $gcal->updateEvent($booking->google_event_id, $summary, $description);

            logMsg("Booking updated: id=$bookingId");
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'delete') {
            $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
            $googleEventId = isset($_POST['google_event_id']) ? trim($_POST['google_event_id']) : '';

            if ($bookingId) {
                $booking = App\Models\Booking::find($bookingId);
                if (!$booking || $booking->deleted) {
                    echo json_encode(['success' => false, 'error' => 'Booking not found']);
                    exit;
                }
                if (!$isAdmin && $booking->member_id !== $memberId) {
                    echo json_encode(['success' => false, 'error' => 'Not authorised']);
                    exit;
                }
                $gcal->deleteEvent($booking->google_event_id);
                $booking->deleted = true;
                $booking->save();
                logMsg("Booking deleted: id=$bookingId");
            } elseif ($googleEventId && $isAdmin) {
                $gcal->deleteEvent($googleEventId);
                logMsg("Booking deleted (calendar-only): event=$googleEventId");
            } else {
                echo json_encode(['success' => false, 'error' => 'Not authorised']);
                exit;
            }

            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;

    } catch (\Exception $e) {
        logMsg("Booking error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

$now = new DateTime('now', $tz);
$today = $now->format('Y-m-d');
$endDate = (clone $now)->modify('+90 days')->format('Y-m-d');

$calendarEvents = [];
$ourBookings = [];
$bookingsByEventId = [];

try {
    $gcal = new App\Services\GoogleCalendarService();
    $calendarEvents = $gcal->getEventsForDateRange($today, $endDate);
} catch (\Exception $e) {
    logMsg("Calendar fetch error (display fallback): " . $e->getMessage());
}

$ourBookings = App\Models\Booking::where('org', $org)
    ->where('deleted', false)
    ->whereNotNull('google_event_id')
    ->get()
    ->keyBy('google_event_id');

$dateGroups = [];
$seenEventIds = [];

foreach ($calendarEvents as $event) {
    $eventId = $event['id'];
    $startStr = $event['start'];
    $dateKey = substr($startStr, 0, 10);

    if ($dateKey < $today) continue;

    if (!isset($dateGroups[$dateKey])) {
        $dateGroups[$dateKey] = [];
    }

    $isOurs = isset($ourBookings[$eventId]);
    $booking = $isOurs ? $ourBookings[$eventId] : null;

    $dateGroups[$dateKey][] = [
        'id' => $eventId,
        'summary' => $event['summary'],
        'start' => $startStr,
        'is_ours' => $isOurs,
        'booking_id' => $booking ? $booking->id : null,
        'event_id' => $eventId,
        'member_id' => $booking ? $booking->member_id : null,
        'can_edit' => $isAdmin || ($isOurs && $booking->member_id === $memberId),
    ];

    $seenEventIds[$eventId] = true;
}

$orphaned = App\Models\Booking::where('org', $org)
    ->where('deleted', false)
    ->whereNotNull('google_event_id')
    ->whereNotIn('google_event_id', array_keys($seenEventIds))
    ->get();

foreach ($orphaned as $b) {
    $b->deleted = true;
    $b->save();
    logMsg("Booking auto-orphaned: id={$b->id} google_event_id={$b->google_event_id}");
}

ksort($dateGroups);
foreach ($dateGroups as $dateKey => &$events) {
    usort($events, function ($a, $b) {
        return strcmp($a['start'], $b['start']);
    });
}
unset($events);
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bookings</title>
    <?php include 'jsLibraies.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        body { background: #f5f5f5; font-family: Arial, Helvetica, sans-serif; margin: 0; }
        h1 { font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 22px; font-weight: 600; color: #222; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .date-group { margin-bottom: 24px; }
        .date-header {
            font-size: 16px; font-weight: bold; color: #333;
            padding: 8px 12px; background: #fff; border-radius: 6px 6px 0 0;
            border-bottom: 2px solid #337ab7;
        }
        .booking-row {
            display: flex; align-items: center; gap: 12px;
            padding: 8px 12px; background: #fff;
            border-bottom: 1px solid #eee; font-size: 14px;
        }
        .booking-row:last-of-type { border-bottom: none; border-radius: 0 0 6px 6px; }
        .booking-time {
            font-weight: bold; color: #555; min-width: 55px;
        }
        .booking-summary { flex: 1; color: #333; }
        .booking-actions { display: flex; gap: 6px; }
        .booking-actions button {
            border: none; background: none; cursor: pointer;
            font-size: 16px; padding: 2px 6px; border-radius: 3px;
        }
        .booking-actions button:hover { background: #eee; }
        .booking-actions .btn-edit { color: #337ab7; }
        .booking-actions .btn-delete { color: #d9534f; }
        .not-ours { opacity: 0.7; font-style: italic; }
        .btn-outline {
            display: inline-block;
            padding: 5px 12px;
            font-size: 12px;
            border: 1px solid #bbb;
            border-radius: 4px;
            background: #fff;
            color: #555;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-outline:hover {
            background: #f0f0f0;
            border-color: #999;
            color: #333;
            text-decoration: none;
        }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content {
            background: #fff; margin: 10% auto; padding: 24px; max-width: 500px;
            border-radius: 8px; position: relative;
        }
        .modal-close {
            position: absolute; right: 16px; top: 12px;
            font-size: 24px; cursor: pointer; color: #999; border: none; background: none;
        }
        .modal-close:hover { color: #333; }
        #loading-overlay {
            display: none; position: fixed; z-index: 9999;
            inset: 0; background: rgba(0,0,0,0.3);
        }
        #loading-overlay.show { display: block; }
        #loading-overlay .loading-box {
            position: absolute; left: 50%; top: 50%;
            transform: translate(-50%, -50%);
            display: flex; align-items: center; gap: 12px;
            background: #fff; padding: 20px 28px;
            border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.15);
            font-size: 18px; color: #555; white-space: nowrap;
        }
        #loading-overlay .spinner {
            width: 40px; height: 40px;
            border: 4px solid #ddd; border-top: 4px solid #337ab7;
            border-radius: 50%; animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .modal h3 { margin: 0 0 16px 0; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 4px; font-size: 13px; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;
            font-size: 14px; box-sizing: border-box;
        }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .form-group .radio-group { display: flex; gap: 16px; flex-wrap: wrap; }
        .form-group .radio-group label { font-weight: normal; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; }
        .form-group .radio-group input[type="radio"] { width: auto; margin: 0; }
        .form-actions { text-align: right; margin-top: 16px; }
        .form-actions button { padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .form-actions .btn-primary { background: #337ab7; color: #fff; }
        .form-actions .btn-primary:hover { background: #286090; }
        .form-actions .btn-default { background: #ccc; margin-right: 8px; }
        .form-actions .btn-default:hover { background: #bbb; }
        .error-message { color: #d9534f; margin-top: 8px; display: none; }
        .loading { text-align: center; padding: 40px; color: #999; }
        .no-bookings { text-align: center; padding: 40px; color: #999; font-size: 16px; }
        #other-gilder-row { display: none; margin-top: 8px; }
        #other-gilder-row input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; }

        .header-row { padding: 0 12px; }
        .text-right { text-align: right; }
        .no-print { }
        @media print { .no-print { display: none; } }
    <?php $inc = "./orgs/" . $org . "/heading2.css"; if (file_exists($inc)) include $inc; ?>
    <?php $inc = "./orgs/" . $org . "/menu1.css"; if (file_exists($inc)) include $inc; ?>
</style>
</head>
<body>
<?php
$inc = "./orgs/" . $org . "/heading2.txt";
if (file_exists($inc)) include $inc;
$inc = "./orgs/" . $org . "/menu1.txt";
if (file_exists($inc)) include $inc;
include __DIR__ . '/helpers/dev_mode_banner.php';
?>

<div class="container">
    <div class="header-row" style="display: flex; align-items: center; margin-bottom: 20px;">
        <h1 id="page-title" style="margin: 0; white-space: nowrap; flex: 1;">Upcoming Bookings</h1>
        <div class="text-right no-print" style="flex-shrink: 0; display: flex; gap: 8px;">
            <button class="btn-outline" onclick="openCreateModal()">+ Add Booking</button>
            <button class="btn-outline" onclick="doRefresh()">Refresh</button>
        </div>
    </div>

    <div id="booking-list">
        <?php if (empty($dateGroups)): ?>
            <div class="no-bookings">No upcoming bookings.</div>
        <?php else: ?>
            <?php foreach ($dateGroups as $dateKey => $events): ?>
                <?php
                $dt = new DateTime($dateKey, new DateTimeZone('UTC'));
                $dt->setTimezone($tz);
                $displayDate = $dt->format('l j F Y');
                ?>
                <div class="date-group">
                    <div class="date-header"><?php echo htmlspecialchars($displayDate) ?></div>
                    <?php $seq = 0; ?>
                    <?php foreach ($events as $event): ?>
                        <?php $seq++; ?>
                        <?php
                        $summary = $event['summary'];
                        $summaryFormatted = preg_replace('/ - /', "</br>", htmlspecialchars($summary));
                        ?>
                        <div class="booking-row <?php echo $event['is_ours'] ? '' : 'not-ours' ?>"<?php if ($event['booking_id']): ?> data-booking-id="<?php echo $event['booking_id'] ?>"<?php endif; ?>>
                            <span class="booking-time"><?php echo $seq ?></span>
                            <span class="booking-summary"><?php echo $summaryFormatted; ?></span>
                            <?php if ($event['can_edit']): ?>
                                <span class="booking-actions">
                                    <?php if ($event['booking_id']): ?>
                                    <button class="btn-edit" onclick="openEditModal(<?php echo $event['booking_id'] ?>)" title="Edit">&#9998;</button>
                                    <?php endif; ?>
                                    <button class="btn-delete" onclick="deleteBooking('<?php echo $event['booking_id'] ?: $event['event_id'] ?>', <?php echo $event['booking_id'] ? 'true' : 'false' ?>)" title="Delete">&#10005;</button>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="booking-modal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <h3 id="modal-title">New Booking</h3>
        <form id="booking-form" onsubmit="return submitForm(event)">
            <input type="hidden" id="form-action" value="create">
            <input type="hidden" id="form-booking-id" value="0">

            <div class="form-group">
                <label for="form-date">Date <span style="color:#d9534f">*</span></label>
                <input type="text" id="form-date" class="flatpickr-input" placeholder="Select date" readonly="readonly">
            </div>

            <div class="form-group">
                <label>Intentions <span style="color:#d9534f">*</span></label>
                <div class="radio-group" id="intention-radio-group">
                    <label><input type="radio" name="intention" value="To Solo"> To Solo</label>
                    <label><input type="radio" name="intention" value="To Soaring"> To Soaring</label>
                    <label><input type="radio" name="intention" value="To XCP"> To XCP</label>
                    <label><input type="radio" name="intention" value="BFR / ICR"> BFR / ICR</label>
                    <label><input type="radio" name="intention" value="Currency"> Currency</label>
                    <label><input type="radio" name="intention" value="PAX"> PAX</label>
                    <label><input type="radio" name="intention" value="Fly Solo - Local"> Fly Solo - Local</label>
                    <label><input type="radio" name="intention" value="Fly Solo - XC"> Fly Solo - XC</label>
                    <label><input type="radio" name="intention" value="Fly Solo - Badge"> Fly Solo - Badge</label>
                    <label><input type="radio" name="intention" value="__other__"> Other</label>
                </div>
                <div id="other-intention-row" style="display:none; margin-top:8px;">
                    <input type="text" id="form-other-intention" placeholder="Describe your intention" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:14px; box-sizing:border-box;">
                </div>
            </div>

            <div class="form-group">
                <label>Glider <span style="color:#d9534f">*</span></label>
                <div class="radio-group" id="glider-radio-group">
                    <label><input type="radio" name="glider" value="DG-1000"> DG-1000</label>
                    <label><input type="radio" name="glider" value="GMB"> GMB</label>
                    <label><input type="radio" name="glider" value="GNB"> GNB</label>
                    <label><input type="radio" name="glider" value="__other__"> Other</label>
                </div>
                <div id="other-gilder-row">
                    <input type="text" id="form-other-glider" placeholder="Enter glider registration">
                </div>
            </div>

            <div class="form-group">
                <label for="form-notes">Notes</label>
                <textarea id="form-notes" placeholder="Optional notes"></textarea>
            </div>

            <div class="error-message" id="form-error"></div>

            <div class="form-actions">
                <button type="button" class="btn-default" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="form-submit-btn">Create Booking</button>
            </div>
        </form>
    </div>
</div>

<div id="loading-overlay">
    <div class="loading-box">
        <div class="spinner"></div>
        <span>Loading...</span>
    </div>
</div>

<div class="modal" id="confirm-modal">
    <div class="modal-content" style="max-width:420px">
        <h3 id="confirm-title" style="margin:0 0 12px 0">Confirm</h3>
        <p id="confirm-message" style="margin:0 0 20px 0; font-size:14px; color:#555"></p>
        <div style="text-align:right">
            <button class="btn-default" onclick="document.getElementById('confirm-modal').style.display='none'" style="padding:8px 20px; border:none; border-radius:4px; cursor:pointer; background:#ccc; margin-right:8px">Cancel</button>
            <button id="confirm-yes-btn" class="btn-primary" style="padding:8px 20px; border:none; border-radius:4px; cursor:pointer; background:#d9534f; color:#fff">Delete</button>
        </div>
    </div>
</div>

<script>
flatpickr("#form-date", {
    dateFormat: "Y-m-d",
    minDate: "today",
    defaultDate: "today",
});

function wireRadioOther(name, otherId) {
    document.querySelectorAll('input[name="' + name + '"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.getElementById(otherId).style.display =
                this.value === '__other__' ? 'block' : 'none';
        });
    });
}
wireRadioOther('glider', 'other-gilder-row');
wireRadioOther('intention', 'other-intention-row');

var editBookings = {};

function clearRadio(name) {
    document.querySelectorAll('input[name="' + name + '"]').forEach(function(r) { r.checked = false; });
}
function openCreateModal() {
    document.getElementById('modal-title').textContent = 'New Booking';
    document.getElementById('form-action').value = 'create';
    document.getElementById('form-booking-id').value = '0';
    document.getElementById('form-date')._flatpickr.setDate(new Date());
    clearRadio('intention');
    clearRadio('glider');
    document.getElementById('other-intention-row').style.display = 'none';
    document.getElementById('form-other-intention').value = '';
    document.getElementById('other-gilder-row').style.display = 'none';
    document.getElementById('form-other-glider').value = '';
    document.getElementById('form-notes').value = '';
    document.getElementById('form-error').style.display = 'none';
    document.getElementById('form-submit-btn').textContent = 'Create Booking';
    document.getElementById('booking-modal').style.display = 'block';
}

function openEditModal(bookingId) {
    var eventEl = document.querySelector('[data-booking-id="' + bookingId + '"]');
    document.getElementById('modal-title').textContent = 'Edit Booking';
    document.getElementById('form-action').value = 'update';
    document.getElementById('form-booking-id').value = bookingId;
    document.getElementById('form-error').style.display = 'none';
    document.getElementById('form-submit-btn').textContent = 'Update Booking';

    fetch('/Bookings?action=get&id=' + bookingId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) { showError(data.error); return; }
            var b = data.booking;
            document.getElementById('form-date')._flatpickr.setDate(b.booking_date);
            document.getElementById('form-notes').value = b.notes || '';
            document.getElementById('form-error').style.display = 'none';

            var intentionVal = b.intention || '';
            var intRadio = document.querySelector('input[name="intention"][value="' + intentionVal + '"]');
            if (intRadio) {
                intRadio.checked = true;
                document.getElementById('other-intention-row').style.display = 'none';
            } else {
                document.querySelector('input[name="intention"][value="__other__"]').checked = true;
                document.getElementById('other-intention-row').style.display = 'block';
                document.getElementById('form-other-intention').value = intentionVal;
            }

            var rego = b.aircraft_rego || '';
            var gliderRadio = document.querySelector('input[name="glider"][value="' + rego + '"]');
            if (gliderRadio) {
                gliderRadio.checked = true;
                document.getElementById('other-gilder-row').style.display = 'none';
            } else {
                document.querySelector('input[name="glider"][value="__other__"]').checked = true;
                document.getElementById('other-gilder-row').style.display = 'block';
                document.getElementById('form-other-glider').value = rego;
            }
        });

    document.getElementById('booking-modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('booking-modal').style.display = 'none';
}

function submitForm(e) {
    e.preventDefault();
    var action = document.getElementById('form-action').value;
    var date = document.getElementById('form-date').value;
    var notes = document.getElementById('form-notes').value;
    var bookingId = document.getElementById('form-booking-id').value;

    var intentionRadio = document.querySelector('input[name="intention"]:checked');
    var intention = '';
    if (intentionRadio) {
        intention = intentionRadio.value === '__other__'
            ? document.getElementById('form-other-intention').value
            : intentionRadio.value;
    }

    var gliderRadio = document.querySelector('input[name="glider"]:checked');
    var aircraftRego = '';
    if (gliderRadio) {
        aircraftRego = gliderRadio.value === '__other__'
            ? document.getElementById('form-other-glider').value
            : gliderRadio.value;
    }

    if (!date) { showError('Date is required'); return; }
    if (!intention) { showError('Please select an intention'); return; }
    if (!aircraftRego) { showError('Please select a glider'); return; }

    var formData = new FormData();
    formData.append('action', action);
    formData.append('date', date);
    formData.append('intention', intention);
    formData.append('aircraft_rego', aircraftRego);
    formData.append('notes', notes);
    if (action === 'update') {
        formData.append('booking_id', bookingId);
    }

    showLoading('Saving...');
    document.getElementById('form-submit-btn').disabled = true;
    document.getElementById('form-submit-btn').textContent = 'Saving...';

    fetch('/Bookings', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('form-submit-btn').disabled = false;
            if (data.success) {
                closeModal();
                location.reload();
            } else {
                showError(data.error || 'An error occurred');
            }
        })
        .catch(function() {
            document.getElementById('form-submit-btn').disabled = false;
            document.getElementById('form-submit-btn').textContent = action === 'create' ? 'Create Booking' : 'Update Booking';
            showError('Network error');
        });
}

function deleteBooking(id, isDb) {
    var msg = isDb
        ? 'Are you sure you want to delete this booking?'
        : 'Google Calendar bookings can not be undeleted. Are you sure you want to delete?';
    document.getElementById('confirm-title').textContent = 'Delete Booking';
    document.getElementById('confirm-message').textContent = msg;
    document.getElementById('confirm-yes-btn').textContent = 'Delete';
    document.getElementById('confirm-yes-btn').onclick = function() {
        document.getElementById('confirm-modal').style.display = 'none';
        doDelete(id, isDb);
    };
    document.getElementById('confirm-modal').style.display = 'block';
}

function doDelete(id, isDb) {
    showLoading('Deleting...');
    var formData = new FormData();
    formData.append('action', 'delete');
    if (isDb) {
        formData.append('booking_id', id);
    } else {
        formData.append('google_event_id', id);
    }

    fetch('/Bookings', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Delete failed');
            }
        });
}

function showLoading(msg) {
    document.getElementById('loading-overlay').className = 'show';
    document.getElementById('loading-overlay').querySelector('.loading-box span').textContent = msg || 'Loading...';
}

function showError(msg) {
    var el = document.getElementById('form-error');
    el.textContent = msg;
    el.style.display = 'block';
}

function doRefresh() {
    showLoading('Refreshing...');
    location.reload();
}


</script>
</body>
</html>
<?php mysqli_close($con); ?>
