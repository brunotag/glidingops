<?php
session_start();
$org = isset($_SESSION['org']) ? (int)$_SESSION['org'] : 0;

if (!isset($_SESSION['security']) || !($_SESSION['security'] & 1)) {
    header('Location: /Login.php');
    exit;
}

require_once __DIR__ . '/load_model.php';
require_once __DIR__ . '/helpers/logging.php';

$memberId = isset($_SESSION['memberid']) ? (int)$_SESSION['memberid'] : 0;
if ($memberId <= 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'No member account linked to your user']);
        exit;
    }
    die('No member account linked to your user');
}
$isAdmin = isset($_SESSION['security']) && ($_SESSION['security'] & 64);

$con_params = require('./config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
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

$today = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+90 days'));

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
        'member_id' => $booking ? $booking->member_id : null,
        'can_edit' => $isOurs && ($isAdmin || $booking->member_id === $memberId),
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
        body { background: #f5f5f5; }
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
            font-weight: bold; color: #555; min-width: 40px;
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
        .add-btn {
            display: inline-block; margin-bottom: 20px;
            padding: 10px 20px; background: #337ab7; color: #fff;
            border: none; border-radius: 4px; font-size: 14px; cursor: pointer;
        }
        .add-btn:hover { background: #286090; }
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
        <?php
        $inc = "./orgs/" . $org . "/menu1.css";
        if (file_exists($inc)) include $inc;
        ?>
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
    <h2 style="margin-top:0;">BOOKINGS</h2>
    <button class="add-btn" onclick="openCreateModal()">+ Add Booking</button>

    <div id="booking-list">
        <?php if (empty($dateGroups)): ?>
            <div class="no-bookings">No upcoming bookings.</div>
        <?php else: ?>
            <?php foreach ($dateGroups as $dateKey => $events): ?>
                <?php
                $displayDate = date('l j F Y', strtotime($dateKey));
                ?>
                <div class="date-group">
                    <div class="date-header"><?php echo htmlspecialchars($displayDate) ?></div>
                    <?php foreach ($events as $event): ?>
                        <?php
                        $seqNum = '';
                        if (!empty($event['start'])) {
                            $ts = strtotime($event['start']);
                            $mins = (int)date('H', $ts) * 60 + (int)date('i', $ts);
                            $seqNum = '#' . (($mins - 9 * 60) + 1);
                        }
                        ?>
                        <div class="booking-row <?php echo $event['is_ours'] ? '' : 'not-ours' ?>"<?php if ($event['booking_id']): ?> data-booking-id="<?php echo $event['booking_id'] ?>"<?php endif; ?>>
                            <span class="booking-time"><?php echo htmlspecialchars($seqNum) ?></span>
                            <span class="booking-summary"><?php echo htmlspecialchars($event['summary']) ?></span>
                            <?php if ($event['can_edit']): ?>
                                <span class="booking-actions">
                                    <button class="btn-edit" onclick="openEditModal(<?php echo $event['booking_id'] ?>)" title="Edit">&#9998;</button>
                                    <button class="btn-delete" onclick="deleteBooking(<?php echo $event['booking_id'] ?>)" title="Delete">&#10005;</button>
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
                <label for="form-date">Date</label>
                <input type="text" id="form-date" class="flatpickr-input" placeholder="Select date" readonly="readonly">
            </div>

            <div class="form-group">
                <label for="form-intention">Intentions</label>
                <textarea id="form-intention" placeholder="What do you want to do?"></textarea>
            </div>

            <div class="form-group">
                <label>Glider</label>
                <div class="radio-group" id="glider-radio-group">
                    <label><input type="radio" name="glider" value="DG-1000"> DG-1000</label>
                    <label><input type="radio" name="glider" value="GM"> GM</label>
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

<script>
flatpickr("#form-date", {
    dateFormat: "Y-m-d",
    minDate: "today",
    defaultDate: "today",
});

document.querySelectorAll('input[name="glider"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.getElementById('other-gilder-row').style.display =
            this.value === '__other__' ? 'block' : 'none';
    });
});

var editBookings = {};

function openCreateModal() {
    document.getElementById('modal-title').textContent = 'New Booking';
    document.getElementById('form-action').value = 'create';
    document.getElementById('form-booking-id').value = '0';
    document.getElementById('form-date')._flatpickr.setDate(new Date());
    document.getElementById('form-intention').value = '';
    document.querySelector('input[name="glider"][value="DG-1000"]').checked = false;
    document.querySelector('input[name="glider"][value="GM"]').checked = false;
    document.querySelector('input[name="glider"][value="GNB"]').checked = false;
    document.querySelector('input[name="glider"][value="__other__"]').checked = false;
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
            if (!data.success) { alert(data.error); return; }
            var b = data.booking;
            document.getElementById('form-date')._flatpickr.setDate(b.booking_date);
            document.getElementById('form-intention').value = b.intention || '';
            document.getElementById('form-notes').value = b.notes || '';
            var rego = b.aircraft_rego || '';
            var radio = document.querySelector('input[name="glider"][value="' + rego + '"]');
            if (radio) {
                radio.checked = true;
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
    var intention = document.getElementById('form-intention').value;
    var notes = document.getElementById('form-notes').value;
    var bookingId = document.getElementById('form-booking-id').value;

    var gliderRadio = document.querySelector('input[name="glider"]:checked');
    var aircraftRego = '';
    if (gliderRadio) {
        aircraftRego = gliderRadio.value === '__other__'
            ? document.getElementById('form-other-glider').value
            : gliderRadio.value;
    }

    if (!date) { showError('Date is required'); return; }

    var formData = new FormData();
    formData.append('action', action);
    formData.append('date', date);
    formData.append('intention', intention);
    formData.append('aircraft_rego', aircraftRego);
    formData.append('notes', notes);
    if (action === 'update') {
        formData.append('booking_id', bookingId);
    }

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

function deleteBooking(bookingId) {
    if (!confirm('Are you sure you want to delete this booking?')) return;

    var formData = new FormData();
    formData.append('action', 'delete');
    formData.append('booking_id', bookingId);

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

function showError(msg) {
    var el = document.getElementById('form-error');
    el.textContent = msg;
    el.style.display = 'block';
}
</script>
</body>
</html>
<?php mysqli_close($con); ?>
