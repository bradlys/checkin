<?php

require_once 'settings.php';
require_once 'customer.php';
require_once 'event.php';
require_once 'organization.php';
require_once 'checkin.php';
require_once 'misc.php';

/**
 * Finds customers whose names match in the databased based upon LIKE %name% and eventID.
 * This function will limit the return of results to whatever limit is set to, or by default 11.
 * @param array $args contains an array with value for keys name, limit, and eventID
 * @return array with array['customers'] containing an array of customers with information. 
 * And array['numberOfExtra'] contains an integer displaying how many customers were not 
 * returned in the search results.
 */
function searchCustomers($eventID, $limit, $name){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer.");
    }
    if(!isInteger($limit) || $limit < 1){
        throw new Exception("Limit must be a positive integer.");
    }
    $numInSystemSQL =
        "SELECT COUNT(*) as count
        FROM customers
        WHERE status = '1'
        AND name LIKE '%$name%'
        ";
    $numInSystemQuery = mysql_query($numInSystemSQL) or die (mysql_error());
    $numInSystemNumber = mysql_fetch_array($numInSystemQuery);
    $numInSystemNumber = $numInSystemNumber['count'];
    $highestVisitsAndLikeName =
        "SELECT id as cid, name, visits, email, birthday
        FROM customers
        WHERE name LIKE '%$name%'
        AND status = '1'
        GROUP BY id
        ORDER BY visits DESC, name ASC, id DESC
        " . ($numInSystemNumber > ($limit + 1) ? ("LIMIT " .  $limit) : "");
    $visitquery = mysql_query($highestVisitsAndLikeName) or die (mysql_error());
    $customerArray = array();
    while($visit = mysql_fetch_array($visitquery)){
        $cid = $visit['cid'];
        $name = $visit['name'];
        $visits = $visit['visits'];
        $isCheckedIn = getCustomerCheckedInPayment($cid, $eventID);
        $checkinID = getCheckinIDForCustomerAndEvent($cid, $eventID);
        $usedFreeEntrance = $checkinID ? hasCustomerUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getCustomerNumberOfFreeEntrances($cid);
        array_push($customerArray, 
            array(
            "birthday" => $visit['birthday'],
            "checkinID" => $checkinID,
            "cid" => $cid,
            "email" => $visit['email'],
            "payment" => ($isCheckedIn == null ? "" : $isCheckedIn),
            "name" => $name,
            "visits" => $visits,
            "isCheckedIn" => ($isCheckedIn != null),
            "usedFreeEntrance" => $usedFreeEntrance,
            "numberOfFreeEntrances" => $numberOfFreeEntrances,
            )
        );
    }
    $resultsArray = array();
    $resultsArray['customers'] = $customerArray;
    if($numInSystemNumber > $limit + 1){
        $resultsArray['numberOfExtra'] = $numInSystemNumber - $limit;
    } else {
        $resultsArray['numberOfExtra'] = 0;
    }
    return $resultsArray;
}

/**
 * Searches the database for events that match LIKE %name% and returns them in JSON format
 * @param String $name Name of Event
 * @param int $organizationID Organization ID
 * @return array with array of events array[0]['id'] being eventid, and
 * array[0]['name'] being the event name
 * @throws Exception When Organization ID is not a positive integer.
 */
function searchEvents($name, $organizationID){
    if(!isInteger($organizationID) || $organizationID < 1){
        throw new Exception("Organization ID must be a positive integer.");
    }
    $sql = "SELECT *
            FROM events
            WHERE organization_id = '$organizationID'
            AND name LIKE '%$name%'
            ORDER BY date DESC";
    $query = mysql_query($sql) or die (mysql_error());
    $events = array();
    while($event = mysql_fetch_array($query)){
        array_push($events, array(
            "eventResultDate" => $event['date'],
            "eventResultID" => $event['id'],
            "eventResultName" => $event['name']
        ));
    }
    return $events;
}

/**
 * Searches the database for organizations that match LIKE %name% and returns them in JSON format
 * @param String $name Name of Organization
 * @return array with array of organizations array[0]['id'] being organizationid, and
 * array[0]['name'] being the organization name
 * @throws 
 */
function searchOrganizations($name){
    $sql = "SELECT * 
            FROM organizations 
            WHERE name LIKE '%$name%'
            AND status = '1'";
    $query = mysql_query($sql) or die (mysql_error());
    $organizations = array();
    while($organization = mysql_fetch_array($query)){
        array_push($organizations, array(
            "organizationResultID" => $organization['id'],
            "organizationResultName" => $organization['name']
        ));
    }
    return $organizations;
}