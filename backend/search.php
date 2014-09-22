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

$functions = array("checkinCustomer", "editEvent", "editOrganization", "getCustomerBirthday", "getEventCosts", "getEventDate", "searchCustomers", "searchEvents", "searchOrganizations");
$method = $_SERVER['REQUEST_METHOD'];
if( strtolower($method) != 'post'){
    return;
}

if(isset($_POST['purpose'])){
    $jsonarray = array();
    $args = parse_post_arguments();
    $purpose = $args['purpose'];
    if(in_array($purpose, $functions)){
        echo call_user_func_array($purpose, array($args));
        return;
    }
}

/**
 * Parses post arguments by running post values through
 * mysql_real_escape_string() and then returning
 * them in an associate array in the same key+value
 * pair as they were before.
 * 
 * @return array
 */
function parse_post_arguments(){
    $args = array();
    $keys = array_keys($_POST);
    foreach ($keys as $key){
        if($key === "costs"){
            $args[$key] = $_POST[$key];
        } else {
            $args[$key] = mysql_real_escape_string($_POST[$key]);
        }
    }
    return $args;
}

/**
 * Finds customers whose names match in the databased based upon LIKE %name% and eventID.
 * This function will limit the return of results to whatever limit is set to, or by default 11.
 * @param array $args contains an array with value for keys name, limit, and eventID
 * @return String json_encode() with array['customers'] containing an array of customers with information. 
 * And array['numberOfExtra'] contains an integer displaying how many customers were not 
 * returned in the search results.
 */
function searchCustomers($args){
    $name = $args['name'];
    $limit = $args['limit'];
    $eventID = $args['eventID'];
    $numInSystemSQL =
            "SELECT COUNT(*) as count
            FROM customers
            WHERE customers.on = '1'
            AND name LIKE '%$name%'
            ";
    $numInSystemQuery = mysql_query($numInSystemSQL) or die (returnSQLError($numInSystemSQL));
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
    $visitquery = mysql_query($highestVisitsAndLikeName) or die (returnSQLError(mysql_error()));
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
    $returnJSON = array();
    $returnJSON['customers'] = $customerArray;
    if($numInSystemNumber > $limit + 1){
        $returnJSON['numberOfExtra'] = $numInSystemNumber - $limit;
    } else {
        $returnJSON['numberOfExtra'] = 0;
    }
    return json_encode($returnJSON);
}

/**
 * Searches the database for events that match LIKE %name% and returns them in JSON format
 * @param array $args Array of arguments: array['name'] being the name of the event
 * @return JSON json_encode string with array of events array[0]['id'] being eventid, and
 * array[0]['name'] being the event name
 */
function searchEvents($args){
    $name = mysql_real_escape_string($args['name']);
    $organizationID = mysql_real_escape_string($args['organizationID']);
    $sql = "SELECT *
            FROM events
            WHERE organization_id = '$organizationID'
            AND name LIKE '%$name%'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $events = array();
    while($event = mysql_fetch_array($query)){
        array_push($events, array(
            "eventResultID" => $event['id'],
            "eventResultName" => $event['name']
        ));
    }
    $returnJSON = json_encode($events);
    return $returnJSON;
}

/**
 * Searches the database for organizations that match LIKE %name% and returns them in JSON format
 * @param array $args Array of arguments: array['name'] being the name of the organization
 * @return String json_encode string with array of organizations array[0]['id'] being organizationid, and
 * array[0]['name'] being the organization name
 */
function searchOrganizations($args){
    $name = mysql_real_escape_string($args['name']);
    $sql = "SELECT * 
            FROM organizations 
            WHERE name LIKE '%$name%'
            AND organizations.on = '1'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    $organizations = array();
    while($organization = mysql_fetch_array($query)){
        array_push($organizations, array(
            "organizationResultID" => $organization['id'],
            "organizationResultName" => $organization['name']
        ));
    }
    $returnJSON = json_encode($organizations);
    return $returnJSON;
}