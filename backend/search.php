<?php
/**
 * This is where all POST requests are handled and all the back-end work is done.
 * Security is very minimal at this point.
 * POST requests are submitted to this page with a JSON format.
 * When submitted, the JSON is combed through and actions are made based upon the purpose declared
 * in the JSON string submitted. Once purpose is properly determined, it will check for the proper
 * data that should follow and will then do work based upon the purpose and data. Once the work is done,
 * this page will return a string containing the information desired. (Or an error)
 * @author Bradly Schlenker
 */

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
            WHERE customers.on = '1'
            AND name LIKE '%$name%'
            ";
    $numInSystemQuery = mysql_query($numInSystemSQL) or die (mysql_error());
    $numInSystemNumber = mysql_fetch_array($numInSystemQuery);
    $numInSystemNumber = $numInSystemNumber['count'];
    //Turns out that around 14000 results from above,
    //one query starts to become much faster than the other.
    if($numInSystemNumber > 14000){
        $highestVisitsAndLikeName =
        "SELECT customers.id as cid, customers.name as name, customers.email as email, IFNULL(checkins.visits, 0) as visits
        FROM customers 
        LEFT JOIN
            (SELECT COUNT(checkins.on) AS visits, customer_id 
            FROM checkins 
            GROUP BY customer_id)
        AS checkins 
        ON checkins.customer_id=customers.id 
        WHERE customers.name LIKE '%$name%'
        AND customers.on = '1'
        ORDER BY checkins.visits DESC, customers.name ASC, customers.id DESC
        " .($numInSystemNumber > ($limit + 1) ? ("LIMIT " .  $limit) : "");
    }
    else{
        $highestVisitsAndLikeName =
        "SELECT COUNT(ch.on) AS visits, cu.id AS cid, cu.name AS name, cu.email AS email
        FROM checkins AS ch
        RIGHT OUTER JOIN customers AS cu ON ch.customer_id = cu.id
        WHERE cu.name LIKE '%$name%'
        AND cu.on = '1'
        GROUP BY cu.id
        ORDER BY visits DESC, name ASC, cu.id DESC
        " . ($numInSystemNumber > ($limit + 1) ? ("LIMIT " .  $limit) : "");
    }
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
            AND name LIKE '%$name%'";
    $query = mysql_query($sql) or die (mysql_error());
    $events = array();
    while($event = mysql_fetch_array($query)){
        array_push($events, array(
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
            AND organizations.on = '1'";
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