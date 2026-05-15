<?php
require_once __DIR__ . '/load_model.php';
require_once __DIR__ . '/helpers/logging.php';

use App\Models\Booking;

$cutoff = date('Y-m-d', strtotime('-30 days'));

$deleted = Booking::where('deleted', true)
    ->where('booking_date', '<', $cutoff)
    ->get();

$count = count($deleted);
foreach ($deleted as $booking) {
    $booking->delete();
}

logMsg("Cleanup-bookings: hard-deleted $count old soft-deleted bookings (before $cutoff)");
echo "Cleaned up $count bookings\n";
