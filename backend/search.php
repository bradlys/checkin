<?php

require_once 'settings.php';
require_once 'customer.php';

/**
 * Searches the customers table and returns the results
 * for a specific event (this is function is used for the 
 * front-end events.php page)
 * @param int $eventID Event ID
 * @param int $limit Limit the returned search results to a specific amount
 * @param string $name Name of the customer
 * @return array Where $array['customers'] contains an array of customers
 * from index 0 up to $limit with keys such as birthday, checkinID, cid,
 * email, payment, name, visits, isCheckedIn, usedFreeEntrance, and
 * numberOfFreeEntrances. It also contains a second key called 
 * $array['numberOfExtra'] which specifies how many people were not included
 * in the returned result but matched the criteon of LIKE %$name%
 * @throws Exception if $eventID is not a positive integer
 * @throws Exception if $limit is not a positive integer
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
 * Searches the database for events that match LIKE %name% and returns 
 * them in an array. Results are returned in order by events.date descending.
 * @param string $name Name of Event
 * @param int $organizationID Organization ID
 * @return array with keys $array[a] to $array[b] where a = 0 and b = infinite
 * each containing an event. An event contains keys date, id, and name. So,
 * $array[0]['date'] would give me the date of the most recent event and
 * $array[0]['name'] would give me the name of the most recent event and
 * $array[2]['id'] would give me the ID number of the third most recent event
 * and so on.
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
 * Searches the database for organizations that match LIKE %name%
 * and returns them in an array
 * @param string $name name to search for
 * @return array with array of organizations $array[0]['id'] being the
 * organization's id number and $array[0]['name'] being the organization name.
 * $array[2] would give me the array of the 3rd organization that matched the
 * search result.
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