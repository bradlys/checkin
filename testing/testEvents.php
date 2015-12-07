<?php

require_once '../backend/settings.php';
require_once '../backend/event.php';

//Joins version of getEventNumberOfNewCustomers as oppossed to
//the current version which is nested queries. (Nested queries work better)
function getEventNumberOfNewCustomers2($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $selectEventDateSQL = "SELECT date FROM events WHERE events.id = '$eventID'";
    $selectEventDateQuery = mysql_query($selectEventDateSQL) or die(mysql_error());
    $eventDate = mysql_fetch_array($selectEventDateQuery);
    if($eventDate['date'] == '0000-00-00 00:00:00' || empty($eventDate)){
        throw new Exception("Provided Event ID does not have a date.");
    }
    $eventDate = $eventDate['date'];
    $countCustomersBeforeEventDateSQL = "
        SELECT COUNT(DISTINCT top_ch.customer_id) as count
        FROM checkins as top_ch
        INNER JOIN events as top_ev
        INNER JOIN checkins as bot_ch
        ON top_ev.id = top_ch.event_id AND bot_ch.customer_id = top_ch.customer_id
        WHERE top_ev.date < '$eventDate'
        AND top_ev.date != '0000-00-00 00:00:00'
        AND bot_ch.status = '1'
        AND bot_ch.event_id = '$eventID'
        ";
    $countCustomersBeforeEventDateQuery = mysql_query($countCustomersBeforeEventDateSQL) or die(mysql_error());
    $numberOfExistingCustomers = mysql_fetch_array($countCustomersBeforeEventDateQuery);
    if(!empty($numberOfExistingCustomers)){
        $countCustomersFromEventSQL = "
            SELECT COUNT(*) as count
            FROM checkins
            WHERE checkins.event_id = '$eventID'
            AND checkins.status = '1'";
        $countCustomersFromEventQuery = mysql_query($countCustomersFromEventSQL) or die(mysql_error());
        $numberOfCustomers = mysql_fetch_array($countCustomersFromEventQuery);
        if(empty($numberOfCustomers)){
            throw new Exception("Invalid Event ID provided.");
        }
        return intval($numberOfCustomers['count'] - $numberOfExistingCustomers['count']);
    }
    throw new Exception("Invalid Event ID provided.");
}

$allEventsSQL = "SELECT id FROM events WHERE events.status = '1'";
$allEventsQuery = mysql_query($allEventsSQL) or die(mysql_error());
$allEventsArray = array();
while($event = mysql_fetch_array($allEventsQuery)){
    $allEventsArray[] = $event['id'];
}
$eventsCount = count($allEventsArray) - 1;
for($i = 0; $i < $eventsCount; $i++){
    $currEvent = $allEventsArray[$i];
    $start = microtime(true);
    echo "<br/> getEventNumberOfNewCustomers($currEvent): " . getEventNumberOfNewCustomers($currEvent);
    $end = microtime(true);
    echo "<br/> getEventNumberOfNewCustomers($currEvent) time: " . ($end-$start);
    mysql_query("RESET QUERY CACHE") or die(mysql_error());
    $start = microtime(true);
    echo "<br/> <b>getEventNumberOfNewCustomers2($currEvent): " . getEventNumberOfNewCustomers2($currEvent);
    $end = microtime(true);
    echo "<br/> getEventNumberOfNewCustomers2($currEvent) time: " . ($end-$start) . "</b>";
    mysql_query("RESET QUERY CACHE") or die(mysql_error());
}
