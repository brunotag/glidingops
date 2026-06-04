<?php
/**
 * CLI script: systematically test each persona against all pages.
 * Run: vagrant ssh -c "cd /home/vagrant/code && php test-access-matrix.php"
 *
 * For each persona, assigns ONLY that persona to fgordon,
 * logs in once, tests ALL pages, reports pass/fail.
 */

// Turn off output buffering
if (ob_get_level()) ob_end_flush();

// DB connection
$db = require __DIR__ . '/config/database.php';
$p = $db['gliding'];
$con = mysqli_connect($p['hostname'], $p['username'], $p['password'], $p['dbname']);
if (!$con) { die("DB fail: " . mysqli_connect_error() . "\n"); }

$PERSONAS = ['booking', 'daily-ops', 'cfo', 'cfi', 'engineer', 'admin', 'god'];

$PAGES = [
    // [route, method, expected_personas, body_params]
    // Auth-only
    ['/home',                               'GET',  '*',           null],
    ['/MyFlights',                          'GET',  '*',           null],
    ['/PasswordChange',                     'GET',  '*',           null],
    ['/AllMembers',                         'GET',  '*',           null],
    ['/AllFlightsReportNew',                'GET',  '*',           null],
    ['/AllFlightsMobile',                   'GET',  '*',           null],
    ['/DailyLogSheet.php?org=1',            'GET',  '*',           null],
    ['/Spots',                              'GET',  '*',           null],
    ['/tracks',                             'GET',  '*',           null],
    ['/Bookings',                           'GET',  '*',           null],
    ['/analytics-season-trends',            'GET',  '*',           null],

    // booking OR daily-ops
    ['/EditMyDetails',                      'GET',  'booking,daily-ops', null],
    ['/MemberNew',                          'GET',  'booking,daily-ops', null],

    // daily-ops
    ['/DailySheet',                         'GET',  'daily-ops',  null],
    ['/StartDay.php?org=1',                 'GET',  'daily-ops',  null],
    ['/EditDailySheet?org=1',               'GET',  'daily-ops',  null],
    ['/CompletedSheet',                     'GET',  'daily-ops',  null],
    ['/MessagingPage',                      'GET',  'daily-ops',  null],
    ['/MessagesTree',                       'GET',  'daily-ops',  null],
    ['/SentMessages',                       'GET',  'daily-ops',  null],


    // cfo
    ['/BillingReport',                      'GET',  'cfo',        null],
    ['/TowCharges',                         'GET',  'cfo',        null],
    ['/TowCharge',                          'GET',  'cfo',        null],
    ['/OtherCharges',                       'GET',  'cfo',        null],
    ['/OtherCharge',                        'GET',  'cfo',        null],
    ['/IncentiveSchemes',                   'GET',  'cfo',        null],
    ['/SubsToSchemes',                      'GET',  'cfo',        null],
    ['/TreasurerReportNew3',                'GET',  'cfo',        null],
    ['/TreasurerReportNew4',                'GET',  'cfo',        null],

    // admin
    ['/Users',                              'GET',  'admin',      null],
    ['/UsersList',                          'GET',  'admin',      null],
    ['/Roles',                              'GET',  'admin',      null],
    ['/Role',                               'GET',  'admin',      null],
    ['/AircraftTypes',                      'GET',  'admin',      null],
    ['/AircraftType',                       'GET',  'admin',      null],
    ['/FlightTypes',                        'GET',  'admin',      null],
    ['/FlightType',                         'GET',  'admin',      null],
    ['/LaunchTypes',                        'GET',  'admin',      null],
    ['/LaunchType',                         'GET',  'admin',      null],
    ['/BillingOptions',                     'GET',  'admin',      null],
    ['/BillingOption',                      'GET',  'admin',      null],
    ['/membership_class',                   'GET',  'admin',      null],
    ['/membership_status',                  'GET',  'admin',      null],
    ['/Analytics',                          'GET',  'admin',      null],

    // engineer
    ['/Engineer',                           'GET',  'engineer',   null],
    ['/last-flights-list',                  'GET',  'engineer',   null],

    // god
    ['/Organisations',                      'GET',  'god',        null],
    ['/Organisation',                       'GET',  'god',        null],
    ['/ViewAs',                             'GET',  'god',        null],
    ['/InviteUsers',                        'GET',  'god',        null],

    // multi-persona OR
    ['/AllAircraft',                        'GET',  'admin,engineer,cfi,cfo', null],
    ['/Aircraft',                           'GET',  'admin,engineer,cfi,cfo', null],
    ['/AllFlights',                         'GET',  'admin,cfo',  null],
];

$APIS = [
    ['GET',  '/api/daily-flights?org=1',    '*',                  null],
    ['GET',  '/api/members',                '*',                  null],
    ['GET',  '/api/member-form',            '*',                  null],
    ['GET',  '/api/member-search?search=t', '*',                  null],
    ['GET',  '/api/members-email?search=t', '*',                  null],
    ['GET',  '/api/aircraft',               '*',                  null],
    ['GET',  '/api/track-flights',          '*',                  null],
    ['GET',  '/api/myflights',              '*',                  null],
    ['GET',  '/api/flights-report',         '*',                  null],
    ['GET',  '/api/analytics-data',         '*',                  null],
    ['GET',  '/api/analytics-trends',       '*',                  null],
    ['GET',  '/api/favourites',             '*',                  null],
    ['GET',  '/api/flights',                'daily-ops',          null],
    ['GET',  '/api/texts',                  'daily-ops',          null],
    ['GET',  '/api/users',                  'admin',              null],
    ['GET',  '/api/user-form',              'admin',              null],
];

function setPersonas($con, $names) {
    $r = mysqli_query($con, "SELECT id FROM users WHERE usercode='fgordon'");
    $u = mysqli_fetch_assoc($r);
    $uid = intval($u['id']);
    mysqli_query($con, "DELETE FROM user_personas WHERE user_id=$uid");
    if (!empty($names)) {
        $q = "INSERT INTO user_personas (user_id, persona_id)
              SELECT $uid, p.id FROM personas p WHERE p.name IN ('" . implode("','", $names) . "')";
        mysqli_query($con, $q);
    }
}

function isAllowed($persona, $expected) {
    if ($expected === '*') return true;
    $personas = explode(',', $expected);
    return in_array($persona, $personas);
}

function loginCurl() {
    $ckfile = tempnam(sys_get_temp_dir(), 'CURLCOOKIE');

    $ch = curl_init('http://glidingops.test/checklogin.php');
    curl_setopt_array($ch, [
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => 'user=fgordon&pcode=fgordon',
        CURLOPT_COOKIEJAR  => $ckfile,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS  => 3,
        CURLOPT_TIMEOUT    => 30,
    ]);
    curl_exec($ch);
    curl_close($ch);

    // Follow to home to cement session
    $ch = curl_init('http://glidingops.test/home');
    curl_setopt_array($ch, [
        CURLOPT_COOKIEFILE => $ckfile,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS  => 3,
        CURLOPT_TIMEOUT    => 15,
    ]);
    curl_exec($ch);
    curl_close($ch);

    return $ckfile;
}

function testUrl($ckfile, $method, $url, $postData = null) {
    $ch = curl_init('http://glidingops.test' . $url);
    $opts = [
        CURLOPT_COOKIEFILE => $ckfile,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT    => 15,
        CURLOPT_HEADER     => true,
        CURLOPT_NOBODY     => false,
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($postData) {
            $opts[CURLOPT_POSTFIELDS] = $postData;
        }
    }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($resp, $headerSize);
    curl_close($ch);

    // Determine if access was denied
    $denied = $httpCode === 302
        || $httpCode === 403
        || stripos($body, 'Not authorized') !== false
        || stripos($body, 'Security level') !== false
        || stripos($body, 'Please logon') !== false;

    return [$httpCode, $denied, substr($body, 0, 100)];
}

// ── Main ───────────────────────────────────────────────────────────────────

echo "\n=== ACCESS MATRIX TEST ===\n\n";

// ── Ensure fgordon exists and has user_personas records ──
$r = mysqli_query($con, "SELECT id FROM users WHERE usercode='fgordon'");
if (!$r || !mysqli_num_rows($r)) { die("fgordon not found\n"); }

// ── Headers ──
$headerRow = str_pad('Page/API', 40) . ' | ';
foreach ($PERSONAS as $p) {
    $headerRow .= str_pad($p, 12) . ' | ';
}
echo $headerRow . "\n";
echo str_repeat('-', strlen($headerRow)) . "\n";

$failures = [];
$totalPages = count($PAGES);

foreach ($PAGES as $idx => [$url, $method, $expected, $body]) {
    printf("\rPage %d/%d", $idx + 1, $totalPages);

    $row = str_pad(substr($url, 0, 38), 40) . ' | ';

    foreach ($PERSONAS as $persona) {
        setPersonas($con, [$persona]);
        $ckfile = loginCurl();

        [$code, $denied, $snippet] = testUrl($ckfile, $method, $url, $body);
        unlink($ckfile);

        $allowed = isAllowed($persona, $expected);

        if ($allowed && !$denied && $code === 200) {
            $status = 'OK';
        } elseif (!$allowed && $denied) {
            $status = 'DENY';
        } else {
            $status = 'FAIL';
            $failures[] = "$persona $url: expected=" . ($allowed ? 'ALLOW' : 'DENY') . " got=$code denied=$denied";
        }

        $row .= str_pad($status, 12) . ' | ';
    }

    // Clear the progress line
    echo str_repeat(' ', 80) . "\r";
    echo $row . "\n";
}

// ── API endpoints ──
echo "\n\n=== API ENDPOINTS ===\n\n";
$totalApis = count($APIS);

foreach ($APIS as $idx => [$method, $url, $expected, $body]) {
    printf("\rAPI %d/%d", $idx + 1, $totalApis);

    $row = str_pad(substr($method . ' ' . $url, 0, 38), 40) . ' | ';

    foreach ($PERSONAS as $persona) {
        setPersonas($con, [$persona]);
        $ckfile = loginCurl();

        [$code, $denied, $snippet] = testUrl($ckfile, $method, $url, $body);
        unlink($ckfile);

        $allowed = isAllowed($persona, $expected);

        if ($allowed && $code === 200) {
            $status = 'OK';
        } elseif (!$allowed && ($code === 403 || $code === 401)) {
            $status = 'DENY';
        } else {
            $status = 'FAIL';
            $failures[] = "$persona $method $url: expected=" . ($allowed ? 'ALLOW' : 'DENY') . " got=$code";
        }

        $row .= str_pad($status, 12) . ' | ';
    }

    echo str_repeat(' ', 80) . "\r";
    echo $row . "\n";
}

// ── Summary ──
echo "\n\n";
if (empty($failures)) {
    echo "\n*** ALL PASS *** — Every persona grants exactly the expected access.\n";
} else {
    echo "\n*** FAILURES (" . count($failures) . ") ***\n";
    foreach ($failures as $f) {
        echo "  - $f\n";
    }
}

// Restore fgordon to full access
setPersonas($con, $PERSONAS);
mysqli_close($con);
echo "\nDone.\n";
