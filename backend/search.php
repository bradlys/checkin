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
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once "settings.php";
$functions = array("checkinCustomer", "editEvent", "editOrganization", "getEmail", "getEvent", "getOrganization", "searchCustomers", "searchEvents", "searchOrganizations", );
$method = $_SERVER['REQUEST_METHOD'];
if( strtolower($method) != 'post'){
    return '';
}

if(isset($_POST['purpose'])){
    $jsonarray = array();
    $args = parse_post_arguments();
    $purpose = $args['purpose'];
    if(in_array($purpose, $functions)){
        echo call_user_func_array($purpose, array($args));
        return '';
    }
}

/**
 * Checks in the customer.
 * @param array $args - Parsed arguments
 * @return JSON
 */
function checkinCustomer($args){
    $money = $args['money'];
    $eventid = $args['eventid'];
    $email = $args['email'];
    $name = $args['name'];
    $cid = $args['cid'];
    $organizationID = inferOrganizationID($eventid);
    $isFreeEntranceEnabled = isFreeEntranceEnabled($organizationID);
    if($isFreeEntranceEnabled){
        $useFreeEntrance = $args['useFreeEntrance'];
        if($useFreeEntrance == "false"){
            $useFreeEntrance = false;
        }
        $numberOfFreeEntrances = $args['numberOfFreeEntrances'];
        if($useFreeEntrance && $numberOfFreeEntrances == 0){
            return returnJSONError("Not enough Free Entrances to use a Free Entrance");
        }
        if(!isInteger($numberOfFreeEntrances) || $numberOfFreeEntrances < 0){
            return returnJSONError("Free Entrances must be non-negative integer");
        }
    }
    if(empty($name)){
        return returnJSONError("Please input a name");
    }
    if(empty($eventid) || !isInteger($eventid)){
        return returnJSONError("Please input a non-negative integer event id");
    }
    if(!isInteger($money)){
        return returnJSONError("Please input a non-negative integer for payment.");
    }
    if(empty($cid)){
        $sql = "INSERT INTO customers VALUES ('', '$name', '$email', 1, CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
        $cid = mysql_insert_id();
    }
    $sql = "SELECT ch.id as checkin_id
            FROM checkins AS ch
            JOIN customers AS cu
            ON ch.customer_id = cu.id
            WHERE ch.customer_id = '$cid'
            AND ch.event_id = '$eventid'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    $result = mysql_fetch_array($query);
    $checkinID = $result['checkin_id'];
    if($isFreeEntranceEnabled){
        $hasUsedFreeEntrance = hasUsedFreeEntrance($cid, $checkinID);
        if(empty($money) && $money != "0" && !$useFreeEntrance && !$hasUsedFreeEntrance){
            return returnJSONError("Please input payment or use Free Entrance");
        }
        $databaseNumberOfFreeEntrances = getNumberOfFreeEntrances($cid);
        if($databaseNumberOfFreeEntrances != $numberOfFreeEntrances){
            editNumberOfFreeEntrances($cid, $numberOfFreeEntrances);
        }
    } else {
        if(empty($money) && $money != "0"){
            return returnJSONError("Please input payment");
        }
    }
    if(!$result){
        $sql = "INSERT INTO checkins VALUES
                ('', '$cid', '$eventid', '$money', '1', CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
        if($isFreeEntranceEnabled && $useFreeEntrance){
            useFreeEntrance($cid, mysql_insert_id());
        }
        return;
    }
    $sql = "UPDATE checkins
            SET payment = $money
            WHERE customer_id = '$cid' AND event_id = '$eventid'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    $sql = "UPDATE customers
            SET name = '$name', email = '$email'
            WHERE id = '$cid'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    if($isFreeEntranceEnabled && $hasUsedFreeEntrance && !$useFreeEntrance){
        unuseFreeEntrance($cid, $checkinID);
    }
    if($isFreeEntranceEnabled && $useFreeEntrance && !$hasUsedFreeEntrance){
        useFreeEntrance($cid, $checkinID);
    }
}

/**
 * Edits event information, with the provided event ID
 * @param Array $args - parsed params
 * @return JSON
 */
function editEvent($args){
    $eventID = $args['eventid'];
    $name = $args['name'];
    $organizationID = $args['organizationID'];
    if(empty($name)){
        return returnJSONError('No name was entered for the event.');
    }
    if(empty($organizationID)){
        return returnJSONError('No organization ID.');
    }
    if(empty($eventID)){
        $sql = "INSERT INTO events VALUES('', '$organizationID', '$name', 1, CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (returnSQLError($sql));
        return;
    }
    $sql = "UPDATE events
            SET name = '$name'
            WHERE organization_id = '$organizationID' AND id = '$eventID'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    return;
}

/**
 * Edits the number of free entrances the customer has to the newly provided number
 * @param integer $cid - customer id number
 * @param integer $number - number of free entrances
 * @throws Exception - When $cid is not a positive integer. When $number is not a non-negative integer.
 */
function editNumberOfFreeEntrances($cid, $number){
    if($number < 0 || !isInteger($number)){
        throw new Exception("Number must be a non-negative integer for free entrances");
    }
    if($cid < 1 || !isInteger($cid)){
        throw new Exception("Customer ID must be a positive integer");
    }
    $sql = "SELECT * FROM customerAttributes WHERE customer_id = '$cid' AND name = 'Free Entrances'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    if($result){
        $id = $result['id'];
        $sql = "UPDATE customerAttributes SET value = '$number' WHERE id = '$id'";
        $query = mysql_query($sql) or die (returnSQLError($sql));
    }
    else{
        $sql = "INSERT INTO customerAttributes VALUES(NULL, '$cid', 'Free Entrances', '$number', '1', CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (returnSQLError($sql));
    }
}

/**
 * Method for editing the organization information
 * 
 * @param Array $args - Array with parameters name, email, and organizationID (when applicable)
 * @return JSON
 */
function editOrganization($args){
    $name = isset($args['name']) ? $args['name'] : "";
    $email = isset($args['email']) ? $args['email'] : "";
    $organizationID = isset($args['organizationID']) ? $args['organizationID'] : "";
    $jsonarray['organizationID'] = $args['organizationID'];
    $jsonarray['neworganization'] = false;
    if(!$name){
        $jsonarray['error'] = 'Please enter a name';
        return json_encode($jsonarray);
    }
    if(!$organizationID){
        //create new organization
        $sql = "INSERT INTO organizations VALUES('', '$name', '$email', 1, CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
        $jsonarray['success'] = "You created a new organization!";
        $jsonarray['organizationID'] = mysql_insert_id();
        $jsonarray['neworganization'] = true;
        return json_encode($jsonarray);
    }
    $sql = "SELECT * FROM organizations WHERE id = '$organizationID'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    if(!mysql_fetch_array($query)){
        $jsonarray['error'] = "No organization exists under id = $organizationID";
        return json_encode($jsonarray);
    }
    $sql = "UPDATE organizations
            SET name = '$name', email = '$email'
            WHERE id = '$organizationID'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    $jsonarray['success'] = "Successfully saved changes.";
    return json_encode($jsonarray);
}

/**
 * Gets checkin ID based off the event ID and customer ID
 * @param integer $cid - customer ID
 * @param integer $eventID - event ID
 * @return int
 * @throws Exception - When $cid or $checkinID is not a positive integer.
 */
function getCheckinIDForCustomerAndEvent($cid, $eventID){
    if($cid < 1 || !isInteger($cid)){
        throw new Exception("Customer ID must be a positive integer");
    }
    if($eventID < 1 || !isInteger($eventID)){
        throw new Exception("Event ID must be a positive integer");
    }
    $sql = "SELECT *
            FROM checkins
            WHERE customer_id = '$cid' 
            AND event_id = '$eventID'
            AND checkins.on = '1'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    $result = mysql_fetch_array($query);
    if($result){
        return $result['id'];
    }
    return null;
}

/**
 * Returns the email of the customer given the cid
 * @param array $args - array with cid of customer
 * @return JSON
 */
function getEmail($args){
    $cid = $args['cid'];
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer");
    }
    $sql = "SELECT email FROM customers WHERE id = '$cid'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    return json_encode(array("email"=>$result['email']));
}

/**
 * Returns the name of the event given the eventID
 * @param array $args - array with eventID of event
 * @return JSON
 * @throws Exception - When $args['eventid'] is not a positive integer.
 */
function getEvent($args){
    $eventid = $args['eventid'];
    if(!isInteger($eventid) || $eventid < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $sql = "SELECT name FROM events WHERE id = '$eventid'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    return json_encode(array("name"=>$result['name']));
}

/**
 * Gets the number of free entrances the customer has.
 * @param integer $cid - customer ID number
 * @throws Exception - When $cid is not a positive integer.
 */
function getNumberOfFreeEntrances($cid){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer");
    }
    $sql = "SELECT * FROM customerAttributes WHERE customer_id = '$cid' AND name = 'Free Entrances'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    $result = mysql_fetch_array($query);
    if($result){
        return $result['value'];
    } else {
        return 0;
    }
}

/**
 * Gets the organization name provided the ID.
 * @param array $args - array with organizationID of event
 * @return JSON
 */
function getOrganization($args){
    $organizationID = $args['organizationID'];
    if(!isInteger($organizationID) || $organizationID < 1){
        throw new Exception("Organization ID must be a positive integer");
    }
    $sql = "SELECT name FROM organizations WHERE id = '$organizationID'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    return json_encode(array("name" => $result['name']));
}

/**
 * Returns true or false as to whether the customer has used a free
 * entrance for the given event ID
 * @param integer $cid - customer ID
 * @param integer $checkinID - checkin ID
 * @return boolean
 * @throws Exception - When $cid or $checkinID is not a positive integer.
 */
function hasUsedFreeEntrance($cid, $checkinID){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer");
    }
    if(!isInteger($checkinID) || $checkinID < 1){
        throw new Exception("Checkin ID must be a positive integer");
    }
    $sql = "SELECT * FROM customerAttributes
            WHERE customer_id = '$cid'
            AND name = 'Used Free Entrance'
            AND value = '$checkinID'
            AND customerAttributes.on = '1'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    if($result){
        return true;
    }
    else{
        return false;
    }
}

/**
 * Infers the organization ID based off the event ID.
 * @param int $eventID - event ID
 * @throws Exception - When event ID is not a positive integer or refers to a non-existent event.
 */
function inferOrganizationID($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID in inferOrganizationID must be a positive integer.");
    }
    $sql = "SELECT * FROM events WHERE id = '$eventID'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    if($result){
        return $result['organization_id'];
    } else {
        throw new Exception("Invalid event ID given to inferOrganizationID.");
    }
}

/**
 * Looks for escape strings in $_POST and returns everything into an array
 * 
 * @return Array
 */
function parse_post_arguments(){
    $args = array();
    $keys = array_keys($_POST);
    foreach ($keys as $key){
        $args[$key] = mysql_real_escape_string($_POST[$key]);
    }
    return $args;
}

/**
 * Finds customers whose names match in the databased based upon LIKE %name% and eventID.
 * This function will limit the return of results to whatever limit is set to, or by default 11.
 * @param Array $args - contains an array with value for name, limit, and eventID
 * @return JSON array - array['customers'] contains an array of customers with information. 
 * array['numberOfExtra'] contains an integer displaying how many customers were not 
 * returned in the search results.
 */
function searchCustomers($args){
    $name = $args['name'];
    $limit = $args['limit'];
    $eventID = $args['eventID'];
    $highestVisitsAndLikeName =
    "CREATE TEMPORARY TABLE highestVisitsAndLikeName
    SELECT COUNT(*) AS visits, cu.id AS cid, cu.name AS name, cu.email AS email
    FROM checkins AS ch
    JOIN customers AS cu ON ch.customer_id = cu.id
    WHERE cu.name LIKE '%$name%'
    GROUP BY cu.id
    ORDER BY visits DESC
    ";
    mysql_query($highestVisitsAndLikeName) or die (returnSQLError($highestVisitsAndLikeName));
    $numInSystemSQL = "SELECT COUNT(*) as count FROM highestVisitsAndLikeName";
    $numInSystemQuery = mysql_query($numInSystemSQL) or die (returnSQLError($numInSystemSQL));
    $numInSystemNumber = mysql_fetch_array($numInSystemQuery);
    $numInSystemNumber = $numInSystemNumber['count'];
    $visitsql = "SELECT * FROM highestVisitsAndLikeName " . ($numInSystemNumber > ($limit + 1) ? ("LIMIT " .  $limit) : "");
    $visitquery = mysql_query($visitsql) or die (returnSQLError($visitsql));
    $alreadycheckedinsql =
    "SELECT customers.name AS cname, checkins.customer_id AS customerid, checkins.payment AS payment
    FROM checkins
    JOIN customers
    ON checkins.customer_id = customers.id
    WHERE event_id = '$eventID'
    AND customer_id IN (SELECT customer_id 
                        FROM highestVisitsAndLikeName)";
    $alreadycheckedinquery = mysql_query($alreadycheckedinsql) or die (returnSQLError($alreadycheckedinsql));
    $alreadycheckedin = array();
    while($tmp = mysql_fetch_array($alreadycheckedinquery)){
        $alreadycheckedin[$tmp['cname']] = $tmp['payment'];
    }
    $keysAlreadyCheckedIn = array_keys($alreadycheckedin);
    $customerArray = array();
    while($visit = mysql_fetch_array($visitquery)){
        $name = $visit['name'];
        $visits = $visit['visits'];
        $isCheckedIn = in_array($name, $keysAlreadyCheckedIn);
        $checkinID = getCheckinIDForCustomerAndEvent($visit['cid'], $eventID);
        $usedFreeEntrance = $checkinID ? hasUsedFreeEntrance($visit['cid'], $checkinID) : false;
        $numberOfFreeEntrances = getNumberOfFreeEntrances($visit['cid']);
        array_push($customerArray, 
            array(
            "cid" => $visit['cid'],
            "email" => $visit['email'],
            "payment" => ($isCheckedIn ? $alreadycheckedin[$name] : ''),
            "name" => $name,
            "visits" => $visits,
            "isCheckedIn" => $isCheckedIn,
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
 * @param Array $args - Array of arguments: array['name'] being the name of the event
 * @return JSON
 */
function searchEvents($args){
    $name = mysql_real_escape_string($args['name']);
    $organizationID = mysql_real_escape_string($args['organizationID']);
    $sql = "SELECT * FROM events WHERE organization_id = '$organizationID' AND name LIKE '%$name%'";
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
 * @param Array $args - Array of arguments: array['name'] being the name of the organization
 * @return JSON
 */
function searchOrganizations($args){
    $name = mysql_real_escape_string($args['name']);
    $sql = "SELECT * FROM organizations WHERE name LIKE '%$name%'";
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

/**
 * Unuses a Free Entrance.
 * @param integer $cid - customer id
 * @param integer $checkinID - customers check-in ID
 * @return null
 * @throws Exception - When trying to remove a used free entrance value when no used free entrance exists.
 * When $cid or $checkinID is not a positive integer.
 */
function unuseFreeEntrance($cid, $checkinID){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer");
    }
    if(!isInteger($checkinID) || $checkinID < 1){
        throw new Exception("Checkin ID must be a positive integer");
    }
    $sql = "SELECT *
           FROM customerAttributes
           WHERE customer_id = '$cid'
           AND name = 'Used Free Entrance'
           AND value = '$checkinID'
           AND customerAttributes.on = '1'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    $result = mysql_fetch_array($query);
    if($result){
        $id = $result['id'];
        $sql = "UPDATE customerAttributes SET customerAttributes.on = '0' WHERE id = '$id'";
        $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
        $databaseNumberOfFreeEntrances = getNumberOfFreeEntrances($cid);
        editNumberOfFreeEntrances($cid, $databaseNumberOfFreeEntrances + 1);
        return null;
    }
    throw new Exception("Tried to unuse a free entrance within unuseFreeEntrance method when no used free entrance existed.");
}

/**
 * Uses a Free Entrance
 * @param integer $cid - customer id
 * @param integer $checkinID - customers check-in ID
 * @return null
 * @throws Exception - When trying to use a free entrance credit when no credit exists.
 * When $cid or $checkinID is not a positive integer.
 */
function useFreeEntrance($cid, $checkinID){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer");
    }
    if(!isInteger($checkinID) || $checkinID < 1){
        throw new Exception("Checkin ID must be a positive integer");
    }
    $sql = "SELECT * FROM customerAttributes WHERE customer_id = '$cid' AND name = 'Free Entrances'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    $result = mysql_fetch_array($query);
    if($result){
        if($result['value'] > 0){
            $newFreeEntrancesAmount = $result['value'] - 1;
            $id = $result['id'];
            $sql = "UPDATE customerAttributes SET value = '$newFreeEntrancesAmount' WHERE id = '$id'";
            $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
            $sql = "INSERT INTO customerAttributes VALUES (NULL, '$cid', 'Used Free Entrance', '$checkinID', 1, CURRENT_TIMESTAMP)";
            $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
            return null;
        }
    }
    throw new Exception("Tried to use a free entrance within useFreeEntrance method when none were available.");
}

?>