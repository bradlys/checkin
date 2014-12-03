<?php

require_once 'settings.php';

/**
 * Sets eventAtrributes.status = '0' for 
 * eventAtrributes.event_id = '$eventID' AND
 * eventAtrributes.status = '1' AND
 * eventAtrributes.name = 'Event Date'
 * @param integer $eventID - Event ID
 * @throws Exception When Event ID is not a positive integer.
 */
function deleteEventDate($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer.");
    }
    $sql = "UPDATE eventAtrributes
            SET eventAtrributes.status = '0'
            WHERE eventAtrributes.event_id = '$eventID'
            AND eventAtrributes.status = '1'
            AND eventAtrributes.name = 'Event Date'";
    mysql_query($sql) or die (mysql_error());
}

/**
 * Edits event information, with the provided event ID
 * @param String $costs JSON encoded string of costs
 * @param String $date Date of Event
 * @param int $eventID Event ID
 * @param String $name Name of Event
 * @param int $organizationID Organization ID
 */
function editEvent($costs, $date, $eventID, $name, $organizationID){
    if(!isInteger($organizationID) || $organizationID < 1){
        throw new Exception("Organization ID needs to be an positive integer.");
    }
    if($eventID != "" && (!isInteger($eventID) || $eventID < 0)){
        throw new Exception("Event ID needs to be a non-negative integer.");
    }
    if(empty($name)){
        throw new Exception('No name was entered for the event.');
    }
    if(empty($organizationID)){
        throw new Exception('No organization ID.');
    }
    $costs = json_decode($costs);
    if(empty($eventID) || $eventID == 0){
        $sql = "INSERT INTO events VALUES('', '$organizationID', '$name', 1, CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (mysql_error());
        editEventCosts($costs, mysql_insert_id());
        return;
    }
    $sql = "UPDATE events
            SET name = '$name'
            WHERE organization_id = '$organizationID' AND id = '$eventID'";
    $query = mysql_query($sql) or die (mysql_error());
    editEventDate($eventID, $date);
    editEventCosts($costs, $eventID);
}

/**
 * Edits event costs. Stores the events costs as JSON in the database
 * under eventAttribute name "Event Costs".
 * @param Array $costs Array with various costs in form of array[0]['item'],
 * array[0]['cost'], array[1]['item'], array[1]['cost'], etc.
 * @param integer $eventID Event ID
 * @throws Exception When Event ID is not a positive integer
 */
function editEventCosts($costs, $eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID needs to be a positive integer.");
    }
    if($costs && $costs[0]['item']){
        $arrayFromJSON = $costs;
        $arrayFromJSONCount = count($arrayFromJSON);
        for($i = 0; $i < $arrayFromJSONCount; $i++){
            $arrayFromJSON[$i]['item'] = mysql_real_escape_string($arrayFromJSON[$i]['item']);
            $arrayFromJSON[$i]['cost'] = mysql_real_escape_string($arrayFromJSON[$i]['cost']);
        }
        $JSON = json_encode($arrayFromJSON);
    } else {
        $JSON = json_encode($costs);
    }
    $sql = "SELECT * FROM eventAttributes
            WHERE event_id = '$eventID'
            AND name = 'Event Costs'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        $id = $result['id'];
        $sql = "UPDATE eventAttributes
                SET value = '$JSON'
                WHERE id = '$id'";
    }
    else{
        $sql = "INSERT INTO eventAttributes VALUES
                (NULL, '$eventID', 'Event Costs', '$JSON', '1', CURRENT_TIMESTAMP)";
    }
    $query = mysql_query($sql) or die (mysql_error());
}

/**
 * Edits the Event's Date, turns it off when empty string
 * for $date
 * @param int $eventID Event ID
 * @param String $date Date in MM/DD/YYYY format
 */
function editEventDate($eventID, $date){
    if(empty($date)){
        deleteEventDate($eventID);
        return;
    }
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer.");
    }
    $sql = "SELECT *
            FROM eventAttributes
            WHERE event_id = '$eventID'
            AND name = 'Event Date'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        $id = $result['id'];
        $sql = "UPDATE eventAttributes
                SET eventAttributes.status = '1', value = '$date'
                WHERE id = '$id'";
    } else {
        $sql = "INSERT INTO eventAttributes
                VALUES (NULL, '$eventID', 'Event Date', '$date', '1', CURRENT_TIMESTAMP)";
    }
    $query = mysql_query($sql) or die (mysql_error());
}

/**
 * Gets the provided event's average payment
 * from each checkin. e.g. If an event has checkins
 * with payments 0, 5, 10, and 10. This method would
 * return 6.25.
 * @param int $eventID An existing Event ID
 * @return float null if empty result, average payment otherwise
 * @throws Exception When $eventID is not a positive integer
 */
function getEventAveragePay($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $getEventAveragePaySQL =
    "SELECT AVG(checkins.payment) as avgpay
    FROM checkins
    WHERE checkins.event_id = '$eventID'
    AND checkins.status = '1'";
    $query = mysql_query($getEventAveragePaySQL) or die(mysql_error());
    $query = mysql_fetch_array($query);
    if($query){
        return floatval($query['avgpay']);
    }
    return null;
}

/**
 * Gets the provided event's total payment
 * from each checkin. e.g. If an event has checkins
 * with payments 0, 5, 10, and 10. This method would
 * return 25.
 * @param int $eventID An existing Event ID
 * @return float null if empty result, total payment otherwise
 * @throws Exception When $eventID is not a positive integer
 */
function getEventTotalPay($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $getEventTotalPaySQL =
    "SELECT SUM(checkins.payment) as totalpay
    FROM checkins
    WHERE checkins.event_id = '$eventID'
    AND checkins.status = '1'";
    $query = mysql_query($getEventTotalPaySQL) or die(mysql_error());
    $query = mysql_fetch_array($query);
    if($query){
        return floatval($query['totalpay']);
    }
    return null;
}

/**
 * Gets the provided event's total number of free
 * entrances used. e.g. If five customers checkin,
 * and two use a free entrance during their checkin
 * then this method will return 2.
 * @param int $eventID An existing Event ID
 * @return int the total number of free checkins used
 * @throws Exception When $eventID is not a positive integer
 */
function getEventNumberOfFreeEntrancesUsed($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    /*This SQL sucks. The alternative I thought about is about 50 lines longer
     * and bordering on impossible to decipher. It's also questionable
     * in performance because it's so heavy handed. (Joins,
     * subqueries, coalesce, wishing it never existed, 
     * why was I born into this world, et cetera et cetera etc...)
     * 
     * When this becomes a fucking issue, I'll deal with it then or
     * cry in a corner.
     */
    $getEventNumberOfFreeEntrancesUsedSQL =
    "SELECT COUNT(*) as totalused
    FROM customerattributes
    WHERE customerattributes.value = '$eventID'
    AND customerattributes.name = 'Used Free Entrance'
    AND customerattributes.status = '1'";
    $query = mysql_query($getEventNumberOfFreeEntrancesUsedSQL) or die(mysql_error());
    $query = mysql_fetch_array($query);
    if($query){
        return intval($query['totalused']);
    }
    return 0;
}


function getEventNumberOfNewCustomers($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    //Method 1:
    //Get customers who checked into this event (checkins table)
    //Get events before this event (events and eventAttributes table)
    //See if those customers have checked into those events (checkins cross correlation)
    //If not, they're a new customer. (+1 to tally)
    //Method 2:
    //Get to this line
    //???
    //Profit!
}

/**
 * Gets the number of checkins for the event ID provided.
 * e.g. If twenty five people checkin then this function
 * will return 25.
 * @param int $eventID Event ID
 * @return int Total number of checkins for the event ID provided
 * @throws Exception When $eventID is not a positive integer
 */
function getEventNumberOfCheckins($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $getEventNumberOfCheckinsSQL =
    "SELECT SUM(*) as totalCheckins
    FROM checkins
    WHERE checkins.event_id = '$eventID'
    AND checkins.status = '1'";
    $query = mysql_query($getEventNumberOfCheckinsSQL) or die(mysql_error());
    $query = mysql_fetch_array($query);
    if($query){
        return intval($query['totalCheckins']);
    }
    return 0;
}

/**
 * Gets the median time between checkins for an event in
 * seconds.
 * e.g. If four people checkin at 1:00PM, 1:05PM, 1:06PM,
 * and 1:08PM then this method would return 120 since
 * the median for 300(1:05-1:00), 60(1:06-1:05), and
 * 120(1:08-1:06) is 120 when sorted from smallest to 
 * largest.
 * @param int $eventID Event ID
 * @return float Returns the amount of time in seconds
 * @throws Exception When $eventID is not a positive integer
 */
function getEventMedianTimeBetweenCheckins($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $getEventMedianTimeBetweenCheckinsSQL =
    "SELECT checkins.id as id, checkins.timestamp as timestamp
    FROM checkins
    WHERE checkins.status = '1'
    AND checkins.event_id = '$eventID'
    ORDER BY checkins.timestamp DESC";
    $query = mysql_query($getEventMedianTimeBetweenCheckinsSQL) or die(mysql_error());
    $results = array();
    $time1 = mysql_fetch_array();
    $time1 = $time1['timestamp'];
    $time2 = 0;
    $len = 0;
    while($result = mysql_fetch_array($query)){
        $time2 = $result['timestamp'];
        $results[] = strtotime($time1) - strtotime($time2);
        $time1 = $time2;
        $len++;
    }
    asort($results);
    if($len % 2 == 0){
        return ($results[$len/2] + $results[$len/2 + 1])/2.0;
    }
    return floatval($results[$len/2]);
}

/**
 * Gets all checkins for the $eventID provided.
 * Returns them in an array with checkin id, 
 * customer_id, event_id, on, and timestamp.
 * @param int $eventID event ID
 * @return array
 * @throws Exception When Event ID is not a positive integer.
 */
function getEventCheckins($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $getEventCheckinsSQL =
    "SELECT checkins.id as id, checkins.customer_id as cid, name,
    payment, checkins.timestamp, checkins.status as status
    FROM checkins
    LEFT JOIN customers
    on customers.id = checkins.customer_id
    WHERE checkins.event_id = '$eventID'
    ORDER BY checkins.timestamp DESC
    ";
    $query = mysql_query($getEventCheckinsSQL) or die(mysql_error());
    $result = array();
    while($curRow = mysql_fetch_array($query)){
        $result[] = $curRow;
    }
    return $result;
}

/**
 * Gets Event Costs as stored in eventAttributes table under Name = 'Event Costs'
 * @param int $eventID Event ID
 * @return array
 * @throws Exception When Event ID is not a positive integer
 */
function getEventCosts($eventID){
    if(!isset($eventID) || !isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $sql = "SELECT * FROM eventAttributes
            WHERE event_id = '$eventID'
            AND eventAttributes.status = '1'
            AND name = 'Event Costs'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        if(!empty($result['value'])){
            return json_decode($result['value']);
        }
    }
    return '';
}

/**
 * Gets the Event's Date
 * @param int $eventID Event ID
 * @return String
 * @throws Exception When Event ID is not a positive integer.
 */
function getEventDate($eventID){
    if(!isInteger($eventID) || intval($eventID) < 1){
        throw new Exception("Event ID must be a positive integer.");
    }
    $sql = "SELECT *
            FROM eventAttributes
            WHERE eventAttributes.event_id = '$eventID'
            AND eventAttributes.name = 'Event Date'
            AND eventAttributes.status = '1'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return $result['value'];
    }
    return '';
}

/**
 * Returns the name of the event given the event ID
 * @param int $eventID event ID number
 * @return String name of the event
 * @throws Exception When $eventID is not a positive integer.
 */
function getEventName($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $sql = "SELECT name FROM events WHERE id = '$eventID'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return $result['name'];
    }
    return '';
}