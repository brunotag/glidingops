<?php

if (!function_exists('buildRecapEmail')) {

function buildRecapEmail($orgName, $memberDisplayName, $flights, $dateStr, $stats)
{
    $headerBg = '#063552';
    $orange = '#f26120';
    $lightBg = '#f5f5f5';
    $borderColor = '#ddd';

    $html = '<!DOCTYPE HTML>';
    $html .= '<html><head><meta charset="utf-8"></head>';
    $html .= '<body style="margin:0;padding:0;background-color:#e8e8e8;font-family:Arial,Helvetica,sans-serif;">';

    $html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#e8e8e8;">';
    $html .= '<tr><td align="center" style="padding:20px 10px;">';
    $html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:100%;background-color:#ffffff;border-radius:6px;overflow:hidden;">';

    // Header
    $html .= '<tr><td style="background-color:' . $headerBg . ';padding:20px 24px;">';
    $html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">';
    $html .= '<tr><td>';
    $html .= '<span style="font-size:36px;font-weight:900;font-family:\'Arial Narrow\',Arial,sans-serif;letter-spacing:-1px;line-height:1;">';
    $html .= '<span style="color:' . $orange . ';">WWGC</span>';
    $html .= '<span style="color:#ffffff;font-weight:300;letter-spacing:0;margin-left:4px;">GOPS</span>';
    $html .= '</span>';
    $html .= '</td></tr>';
    $html .= '<tr><td style="color:#ffffff;font-size:13px;padding-top:2px;opacity:0.8;">' . htmlspecialchars($orgName) . ' &mdash; flight operations</td></tr>';
    $html .= '</table>';
    $html .= '</td></tr>';

    // Greeting
    $html .= '<tr><td style="padding:24px 24px 6px 24px;font-size:16px;color:#333333;">';
    $html .= 'Hi ' . htmlspecialchars($memberDisplayName) . ',';
    $html .= '</td></tr>';

    // Date
    $html .= '<tr><td style="padding:0 24px 16px 24px;font-size:14px;color:#666666;">';
    $html .= 'Your flights for <strong>' . htmlspecialchars($dateStr) . '</strong>';
    $html .= '</td></tr>';

    // Summary bar
    $totalFlights = (int)$stats['total_flights_today'];
    $totalDurMin = (int)$stats['total_duration_today_min'];
    $durStr = '';
    if ($totalDurMin > 0) {
        $h = floor($totalDurMin / 60);
        $m = $totalDurMin % 60;
        $durStr = $h > 0 ? $h . 'h ' . $m . 'm' : $m . 'm';
    }
    $summaryParts = array();
    $summaryParts[] = $totalFlights . ' flight' . ($totalFlights != 1 ? 's' : '');
    if ($durStr) $summaryParts[] = $durStr;

    $html .= '<tr><td style="padding:0 24px 16px 24px;">';
    $html .= '<table role="presentation" cellpadding="12" cellspacing="0" border="0" width="100%" style="background-color:' . $lightBg . ';border-radius:6px;">';
    $html .= '<tr><td style="font-size:15px;color:#333333;text-align:center;font-weight:600;">';
    $html .= 'You flew ' . implode(' &mdash; ', $summaryParts) . ' today';
    $html .= '</td></tr>';
    $html .= '</table>';
    $html .= '</td></tr>';

    // Flights table
    if (count($flights) > 0) {
        $html .= '<tr><td style="padding:0 24px 16px 24px;">';
        $html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">';

        $html .= '<tr>';
        $html .= '<th style="padding:8px 6px;border-bottom:2px solid ' . $headerBg . ';font-size:11px;color:#888;text-align:left;text-transform:uppercase;letter-spacing:0.5px;">Glider</th>';
        $html .= '<th style="padding:8px 6px;border-bottom:2px solid ' . $headerBg . ';font-size:11px;color:#888;text-align:left;text-transform:uppercase;letter-spacing:0.5px;"></th>';
        $html .= '<th style="padding:8px 6px;border-bottom:2px solid ' . $headerBg . ';font-size:11px;color:#888;text-align:left;text-transform:uppercase;letter-spacing:0.5px;">Start</th>';
        $html .= '<th style="padding:8px 6px;border-bottom:2px solid ' . $headerBg . ';font-size:11px;color:#888;text-align:left;text-transform:uppercase;letter-spacing:0.5px;">Land</th>';
        $html .= '<th style="padding:8px 6px;border-bottom:2px solid ' . $headerBg . ';font-size:11px;color:#888;text-align:right;text-transform:uppercase;letter-spacing:0.5px;">Dur</th>';
        $html .= '<th style="padding:8px 6px;border-bottom:2px solid ' . $headerBg . ';font-size:11px;color:#888;text-align:center;text-transform:uppercase;letter-spacing:0.5px;">Role</th>';
        $html .= '<th style="padding:8px 6px;border-bottom:2px solid ' . $headerBg . ';font-size:11px;color:#888;text-align:left;text-transform:uppercase;letter-spacing:0.5px;">With</th>';
        $html .= '</tr>';

        $alt = false;
        foreach ($flights as $f) {
            $bg = $alt ? '#fafafa' : '#ffffff';
            $html .= '<tr style="background-color:' . $bg . ';">';
            $html .= '<td style="padding:8px 6px;border-bottom:1px solid ' . $borderColor . ';font-size:14px;color:#333;">' . htmlspecialchars($f['glider']) . '</td>';
            $html .= '<td style="padding:8px 6px;border-bottom:1px solid ' . $borderColor . ';font-size:14px;color:#333;"></td>';
            $html .= '<td style="padding:8px 6px;border-bottom:1px solid ' . $borderColor . ';font-size:14px;color:#333;">' . htmlspecialchars($f['start_time']) . '</td>';
            $html .= '<td style="padding:8px 6px;border-bottom:1px solid ' . $borderColor . ';font-size:14px;color:#333;">' . htmlspecialchars($f['land_time']) . '</td>';
            $html .= '<td style="padding:8px 6px;border-bottom:1px solid ' . $borderColor . ';font-size:14px;color:#333;text-align:right;white-space:nowrap;">' . htmlspecialchars($f['duration_display']) . '</td>';
            $html .= '<td style="padding:8px 6px;border-bottom:1px solid ' . $borderColor . ';font-size:14px;color:#333;text-align:center;">' . htmlspecialchars($f['role']) . '</td>';
            $html .= '<td style="padding:8px 6px;border-bottom:1px solid ' . $borderColor . ';font-size:14px;color:#333;">' . htmlspecialchars($f['other_pilot']) . '</td>';
            $html .= '</tr>';
            $alt = !$alt;
        }

        $html .= '</table>';
        $html .= '</td></tr>';
    }

    // Stats panel
    $statsLines = array();
    if ($stats['monthly_flights'] > 0) {
        $mf = (int)$stats['monthly_flights'];
        $statsLines[] = $mf . ' flight' . ($mf != 1 ? 's' : '') . ' this month';
        $mh = (int)$stats['monthly_hours'];
        if ($mh > 0) {
            $hh = floor($mh / 60);
            $mm = $mh % 60;
            $statsLines[] = $hh > 0 ? $hh . 'h ' . $mm . 'm this month' : $mm . 'm this month';
        }
    }
    if ($stats['leaderboard_rank'] > 0) {
        $statsLines[] = '#' . (int)$stats['leaderboard_rank'] . ' on the monthly leaderboard';
    }
    if ((int)$stats['streak_months'] > 1) {
        $statsLines[] = (int)$stats['streak_months'] . '-month streak running!';
    }

    if (count($statsLines) > 0) {
        $html .= '<tr><td style="padding:0 24px 16px 24px;">';
        $html .= '<table role="presentation" cellpadding="12" cellspacing="0" border="0" width="100%" style="background-color:' . $lightBg . ';border-radius:6px;">';
        $html .= '<tr><td style="font-size:13px;color:#555;line-height:1.7;">';
        $html .= implode('<br>', $statsLines);
        $html .= '</td></tr>';
        $html .= '</table>';
        $html .= '</td></tr>';
    }

    // Birthday banner
    if ($stats['is_birthday_month']) {
        $html .= '<tr><td style="padding:0 24px 16px 24px;">';
        $html .= '<table role="presentation" cellpadding="10" cellspacing="0" border="0" width="100%" style="background-color:#fff3cd;border:1px solid #ffc107;border-radius:6px;">';
        $html .= '<tr><td style="font-size:14px;color:#856404;text-align:center;font-weight:600;">';
        $html .= 'Happy birthday this month! We hope you have a great one.';
        $html .= '</td></tr>';
        $html .= '</table>';
        $html .= '</td></tr>';
    }

    // CTA buttons
    $html .= '<tr><td style="padding:0 24px 20px 24px;">';
    $html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">';
    $html .= '<tr><td style="text-align:center;">';
    $html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="display:inline-block;">';
    $html .= '<tr>';
    $html .= '<td style="padding:0 6px;"><a href="https://gops.wwgc.co.nz/MyFlights" style="display:inline-block;padding:10px 24px;background-color:' . $orange . ';color:#ffffff;text-decoration:none;border-radius:20px;font-size:14px;font-weight:600;">View Full Stats</a></td>';
    $html .= '<td style="padding:0 6px;"><a href="https://gops.wwgc.co.nz/home" style="display:inline-block;padding:10px 24px;background-color:' . $headerBg . ';color:#ffffff;text-decoration:none;border-radius:20px;font-size:14px;font-weight:600;">GOPS Home</a></td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html .= '</td></tr>';
    $html .= '</table>';
    $html .= '</td></tr>';

    // Footer
    $html .= '<tr><td style="padding:20px 24px;background-color:#fafafa;border-top:1px solid ' . $borderColor . ';">';
    $html .= '<p style="margin:0 0 4px 0;font-size:12px;color:#999;text-align:center;">Wellington Gliding Club &mdash; Greytown, New Zealand</p>';
    $html .= '<p style="margin:0;font-size:12px;color:#999;text-align:center;">Generated by <a href="https://gops.wwgc.co.nz" style="color:#999;text-decoration:underline;">GOPS</a></p>';
    $html .= '</td></tr>';

    $html .= '</table>';
    $html .= '</td></tr>';
    $html .= '</table>';
    $html .= '</body></html>';

    return $html;
}

} // end if (!function_exists)


if (!function_exists('getMemberRecapData')) {

function getMemberRecapData($con, $org, $memberId, $dateStr, $currentYm, $isInstructor)
{
    $rows = array();
    $memberDisplayName = '';
    $memberEmail = '';

    $memberQ = "SELECT displayname, email FROM members WHERE id = " . intval($memberId);
    $memberR = mysqli_query($con, $memberQ);
    if ($memberR && $memberRow = mysqli_fetch_array($memberR)) {
        $memberDisplayName = $memberRow['displayname'];
        $memberEmail = $memberRow['email'];
    }

    $flightData = array();
    $totalFlightsToday = 0;
    $totalDurationTodayMs = 0;

    $q1 = "SELECT flights.glider, flights.location, (flights.land - flights.start) as duration_ms, "
            . "flights.height, flights.launchtype, a.acronym, flights.pic, flights.p2, "
            . "flights.start as start_ts, flights.land as land_ts, "
            . "m2.displayname as p2_name, m3.displayname as pic_name "
            . "from flights "
            . "LEFT JOIN launchtypes a on a.id = flights.launchtype "
            . "LEFT JOIN members m2 on flights.p2 = m2.id "
            . "LEFT JOIN members m3 on flights.pic = m3.id "
            . "where flights.org = " . intval($org) . " and flights.localdate=" . intval($dateStr)
            . " and flights.finalised = 1 and (flights.pic = " . intval($memberId) . " or flights.p2 = " . intval($memberId) . ") "
            . "order by flights.seq ASC";
    $r2 = mysqli_query($con, $q1);
    if ($r2) {
        while ($row2 = mysqli_fetch_array($r2)) {
            $totalFlightsToday++;
            $durationMs = (int)$row2['duration_ms'];
            $totalDurationTodayMs += $durationMs;

            $startTs = (int)$row2['start_ts'] / 1000;
            $landTs = (int)$row2['land_ts'] / 1000;
            $startDt = (new DateTime())->setTimestamp($startTs);
            $landDt = (new DateTime())->setTimestamp($landTs);
            $nzTz = new DateTimeZone("Pacific/Auckland");
            $startDt->setTimezone($nzTz);
            $landDt->setTimezone($nzTz);
            $startTime = ($startTs == 0) ? "" : $startDt->format('G:i');
            $landTime = ($landTs == 0) ? "" : $landDt->format('G:i');

            $totalMin = (int)($durationMs / 60000);
            $h = (int)($totalMin / 60);
            $m = $totalMin % 60;
            $durStr = ($h > 0 ? $h . 'h ' : '') . $m . 'm';

            if ($row2['pic'] == $memberId) {
                $role = ((int)$row2['p2'] > 0) ? ($isInstructor ? 'I' : 'P1') : 'P';
            } else {
                $role = 'P2';
            }

            if ($row2['pic'] == $memberId) {
                $otherPilot = ((int)$row2['p2'] > 0) ? $row2['p2_name'] : 'Solo';
            } else {
                $otherPilot = $row2['pic_name'];
            }

            $flightData[] = array(
                'glider' => $row2['glider'],
                'start_time' => $startTime,
                'land_time' => $landTime,
                'duration_display' => $durStr,
                'role' => $role,
                'other_pilot' => $otherPilot ?: '',
            );
        }
    }

    // Monthly stats
    $monthStart = $currentYm . '01';
    $monthEnd = $currentYm . '31';
    $monthlyFlights = 0;
    $monthlyHours = 0;
    $qMonth = "SELECT COUNT(*) as cnt, COALESCE(SUM(land - start), 0) / 60000 as total_min "
            . "FROM flights WHERE pic = " . intval($memberId) . " AND org = " . intval($org)
            . " AND localdate >= " . intval($monthStart) . " AND localdate <= " . intval($monthEnd)
            . " AND deleted = 0";
    $rMonth = mysqli_query($con, $qMonth);
    if ($rMonth && $monthRow = mysqli_fetch_array($rMonth)) {
        $monthlyFlights = (int)$monthRow['cnt'];
        $monthlyHours = (int)($monthRow['total_min'] ?? 0);
    }

    // Leaderboard rank
    $leaderboardRank = 0;
    if ($monthlyFlights > 0) {
        $qRank = "SELECT COUNT(*) + 1 as rank FROM ("
                . "SELECT pic, COUNT(*) as cnt FROM flights "
                . "WHERE org = " . intval($org) . " AND localdate >= " . intval($monthStart) . " AND localdate <= " . intval($monthEnd)
                . " AND deleted = 0 AND pic IS NOT NULL GROUP BY pic HAVING cnt > " . $monthlyFlights
                . ") as ahead";
        $rRank = mysqli_query($con, $qRank);
        if ($rRank && $rankRow = mysqli_fetch_array($rRank)) {
            $leaderboardRank = (int)$rankRow['rank'];
        }
    }

    // Streak
    $streakMonths = 0;
    $checkYm = (int)$currentYm;
    $stillGoing = true;
    while ($stillGoing) {
        $ymStart = $checkYm . '01';
        $ymEnd = $checkYm . '31';
        $qStreak = "SELECT COUNT(*) as cnt FROM flights "
                . "WHERE pic = " . intval($memberId) . " AND org = " . intval($org)
                . " AND localdate >= " . intval($ymStart) . " AND localdate <= " . intval($ymEnd)
                . " AND deleted = 0";
        $rStreak = mysqli_query($con, $qStreak);
        if ($rStreak && $streakRow = mysqli_fetch_array($rStreak)) {
            if ((int)$streakRow['cnt'] > 0) {
                $streakMonths++;
                $year = (int)($checkYm / 100);
                $month = $checkYm % 100;
                $month--;
                if ($month < 1) { $month = 12; $year--; }
                if ($year < 2010) break;
                $checkYm = $year * 100 + $month;
            } else {
                $stillGoing = false;
            }
        } else {
            $stillGoing = false;
        }
    }

    // Birthday check
    $memberDob = '';
    $dobQ = "SELECT date_of_birth FROM members WHERE id = " . intval($memberId);
    $dobR = mysqli_query($con, $dobQ);
    if ($dobR && $dobRow = mysqli_fetch_array($dobR)) {
        $memberDob = $dobRow['date_of_birth'];
    }
    $isBirthdayMonth = false;
    if (!empty($memberDob)) {
        $bdayParts = explode('-', $memberDob);
        if (count($bdayParts) >= 2) {
            $bdayMonth = (int)$bdayParts[1];
            $currentMonth = (int)substr($currentYm, 4, 2);
            if ($bdayMonth == $currentMonth) $isBirthdayMonth = true;
        }
    }

    $stats = array(
        'total_flights_today' => $totalFlightsToday,
        'total_duration_today_min' => (int)($totalDurationTodayMs / 60000),
        'monthly_flights' => $monthlyFlights,
        'monthly_hours' => $monthlyHours,
        'leaderboard_rank' => $leaderboardRank,
        'streak_months' => $streakMonths,
        'is_birthday_month' => $isBirthdayMonth,
    );

    return array(
        'display_name' => $memberDisplayName,
        'email' => $memberEmail,
        'flights' => $flightData,
        'stats' => $stats,
    );
}

} // end if (!function_exists)
