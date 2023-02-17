<?php
include './helpers/timehelpers.php';

require dirname(__FILE__) . '/includes/classGlidingDB.php';
require dirname(__FILE__) . '/includes/classTracksDB.php';
$con_params = require( dirname(__FILE__) .'/config/database.php'); 
$DB = new GlidingDB($con_params['gliding']);
$DBArchive = new TracksDB($con_params['tracks']);

if (isset($_GET['flightid']))
{
    $flightid=$_GET['flightid'];
    $f = $DB->getFlightWithNames($flightid);
    if ($f)
    {
        $glider = $f['glider'];
        $trDateStart = new DateTime();
        $trDateLand = new DateTime();
        $trDateStart->setTimestamp(intval(floor($f['start'] / 1000)));
        $trDateLand->setTimestamp(intval(floor($f['land'] / 1000)));

        $r1 = null;
        if ($DB->numTracksForFlight($trDateStart,$trDateLand,$glider) > 0)
            $r1 = $DB->getTracksForFlight($trDateStart,$trDateLand,$glider);
        else
        if ($DBArchive->numTracksForFlight($trDateStart,$trDateLand,$glider) > 0)
            $r1 = $DBArchive->getTracksForFlight($trDateStart,$trDateLand,$glider);

        if ($r1 && $r1->num_rows > 0)
        {
            header('Content-type: text/csv'); 
            header('Content-Disposition: attachment;filename=gmaps-trace.csv'); 
            echo "WKT,name,description\r\n";
            echo "\"LINESTRING (";

            if ($track = $r1->fetch_array()){
                echo $track['longitude'];
                echo " ";
                echo $track['lattitude'];
            }

            while ($track = $r1->fetch_array() )
            {
                echo ", ";
                echo $track['longitude'];
                echo " ";
                echo $track['lattitude'];
            }

            echo ")\", ";
            echo $glider;
            echo ",";
        }
        else
            echo "No track points for flight";
    }
    else
    {
        echo "Flight record does not exist";
    }
}
else
{
    echo "No Flight Specified";
}
?>
