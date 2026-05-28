<?php
// Billing calculation functions — pure logic, no DB or session dependencies.
// Used by billing-report.php and unit tests.

function calcGliderCharge($clubGlider, $regoShort, $totMins, $memberClassName, $chargePerMinute, $maxPerFlightCharge)
{
    if (!$clubGlider) return 0.0;

    $rate = (float)$chargePerMinute;
    if (strcasecmp($memberClassName, 'Youth') == 0)
    {
        $youthDiscountGliders = ['GGR', 'GPJ', 'GMB'];
        if (in_array($regoShort, $youthDiscountGliders))
            $rate = 1.50;
    }

    if ($rate == 0) return 0.0;

    $mins = $totMins;
    if ($maxPerFlightCharge > 0 && $mins * $rate > (float)$maxPerFlightCharge)
        $mins = (float)$maxPerFlightCharge / $rate;

    return round($mins * $rate, 2);
}

function calcLaunchCharge($launchType, $isFirstWinch, $towLaunchId, $winchLaunchId, $selfLaunchId)
{
    if ($launchType == $winchLaunchId)
        return $isFirstWinch ? 39.00 : 25.00;
    if ($launchType == $selfLaunchId)
        return 25.00;
    if ($launchType == $towLaunchId)
        return 0.0;
    return 0.0;
}

function isCompetitionFlight($launchType, $towLaunchId)
{
    return $launchType == $towLaunchId;
}

function isFiftyFifty($billPic, $billP2)
{
    return ($billPic > 0 && $billP2 > 0);
}

function getLaunchLabel($launchType, $towLaunchId, $winchLaunchId, $selfLaunchId)
{
    if ($launchType == $towLaunchId) return 'AEROTOW';
    if ($launchType == $winchLaunchId) return 'WINCH';
    if ($launchType == $selfLaunchId) return 'SELF';
    return 'OTHER';
}
