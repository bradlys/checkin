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
 * 
 * @param array $args - Parsed arguments
 * @return JSON
 */
function checkinCustomer($args){
    $money = $args['money'];
    $eventid = $args['eventid'];
    $email = $args['email'];
    $name = $args['name'];
    $cid = $args['cid'];
    $useFreeEntrance = $args['useFreeEntrance'];
    if($useFreeEntrance == "false"){
        $useFreeEntrance = false;
    }
    $numberOfFreeEntrances = $args['numberOfFreeEntrances'];
    if($useFreeEntrance && $numberOfFreeEntrances == 0){
        return returnJSONError("Not enough Free Entrances to use a Free Entrance");
    }
    if(!is_int($numberOfFreeEntrances) || $numberOfFreeEntrances < 0){
        return returnJSONError("Free Entrances must be non-negative integer");
    }
    if(empty($name)){
        return returnJSONError("Please input a name");
    }
    if(empty($eventid) || !is_int($eventid)){
        return returnJSONError("Please input a non-negative integer event id");
    }
    if(!is_int($money)){
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
    $hasUsedFreeEntrance = hasUsedFreeEntrance($cid, $checkinID);
    if(empty($money) && $money != "0" && !$useFreeEntrance && !$hasUsedFreeEntrance){
        return returnJSONError("Please input payment or use Free Entrance");
    }
    $databaseNumberOfFreeEntrances = getNumberOfFreeEntrances($cid);
    if($databaseNumberOfFreeEntrances != $numberOfFreeEntrances){
        editNumberOfFreeEntrances($cid, $numberOfFreeEntrances);
    }
    if(!$result){
        $sql = "INSERT INTO checkins VALUES
                ('', '$cid', '$eventid', '$money', '1', CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
        if($useFreeEntrance){
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
    if($hasUsedFreeEntrance && !$useFreeEntrance){
        unuseFreeEntrance($cid, $checkinID);
    }
    if($useFreeEntrance && !$hasUsedFreeEntrance){
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
 * @throws Exception - If $cid is less than 1 and if $number is less than 0. (Both are invalid)
 */
function editNumberOfFreeEntrances($cid, $number){
    if($number < 0){
        throw new Exception("Cannot have less than 0 free entrances");
    }
    if($cid < 1){
        throw new Exception("Cannot have less than 1 for customer ID number");
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
 * @throws Exception - when $cid or $eventID is less than 1.
 */
function getCheckinIDForCustomerAndEvent($cid, $eventID){
    if($cid < 1){
        throw new Exception("Cannot have less than 1 for customer ID number");
    }
    if($eventID < 1){
        throw new Exception("Cannot have less than 1 for event ID number");
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
    $sql = "SELECT email FROM customers WHERE id = '$cid'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    return json_encode(array("email"=>$result['email']));
}

/**
 * Returns the name of the event given the eventID
 * @param array $args - array with eventID of event
 * @return JSON
 */
function getEvent($args){
    $eventid = $args['eventid'];
    $sql = "SELECT name FROM events WHERE id = '$eventid'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    return json_encode(array("name"=>$result['name']));
}

/**
 * Gets the number of free entrances the customer has.
 * @param integer $cid - customer ID number
 * @throws Exception - When $cid is less than 0.
 */
function getNumberOfFreeEntrances($cid){
    if($cid < 0){
        throw new Exception("Cannot have customer ID less than 0.");
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
 * @throws Exception - When customer ID or checkin ID is less than 1.
 */
function hasUsedFreeEntrance($cid, $checkinID){
    if($cid < 1){
        throw new Exception("Cannot have less than 1 for customer ID number");
    }
    if($checkinID < 1){
        throw new Exception("Cannot have less than 1 for checkin ID number");
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
 * Helper function. Takes error message string and returns a JSON with { error : message }
 * @param String $errorMessage - the message
 * @return JSON
 */
function returnJSONError($errorMessage){
    return json_encode(array("error" => $errorMessage));
}

/**
 * Returns an error message that represents the error caused by the SQL command
 * 
 * @param String $sql - SQL statement that triggered the error
 * @param String $optiontext - Optional error message text to return
 * @return String
 */
function returnSQLError($sql, $optiontext = null){
    if($optiontext){
        return $optiontext . $sql;
    }
    return "We didn't start the fire but something went wrong with $sql";
}

/**
 * Returns a JSON error message that represents the error caused by the SQL command
 * 
 * @param String $sql - SQL statement that triggered the error
 * @param String $optiontext - Optional error message text to return
 * @return JSON
 */
function returnSQLErrorInJSON($sql, $optiontext = null){
    if($optiontext){
        return $optiontext . $sql;
    }
    return json_encode(array("error" => "We didn't start the fire but something went wrong with $sql"));
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
 * When $cid is less than 1. When $checkinID is less than 1.
 */
function unuseFreeEntrance($cid, $checkinID){
    if($cid < 1){
        throw new Exception("Cannot have less than 1 for customer ID number");
    }
    if($checkinID < 1){
        throw new Exception("Cannot have less than 1 for checkin ID number");
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
 * When $cid is less than 0. When $checkinID is less than 0.
 */
function useFreeEntrance($cid, $checkinID){
    if($cid < 0){
        throw new Exception("Cannot have less than 0 for customer ID number");
    }
    if($checkinID < 0){
        throw new Exception("Cannot have less than 0 for checkin ID number");
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