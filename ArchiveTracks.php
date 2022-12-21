<?php
require dirname(__FILE__) . '/includes/classGlidingDB.php';
require dirname(__FILE__) . '/includes/classTracksDB.php';
$con_params = include dirname(__FILE__) .'/config/database.php'; 
$DB = new GlidingDB($con_params['gliding']);
$DBArchive = new TracksDB($con_params['tracks']);

$dtNow = new DateTime('now');
// Go back 3 days
$dtPrev = new DateTime();
$dtPrev->setTimestamp($dtNow->getTimestamp() - (3600*24*3));

$r = $DB->allTracksOlderThan($dtPrev->format('Y-m-d'));
while ($t = $r->fetch_array()) 
{
    unset($t['id']);
    unset($t['user']);
    unset($t['create_time']);
    unset($t['trip_id']);
    unset($t['point_id']);
    $DBArchive->create_from_array('tracksarchive', $t); 
    
}
$DB->deleteTracksOlderThan($dtPrev->format('Y-m-d'));
?>