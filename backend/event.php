<?php

require_once 'settings.php';

/**
 * Functions related to getting, editing, creating, and
 * statistical analysis of events. Many functions reference the checkins table
 * as well as the events and eventattributes table. The reason is organizations 
 * have many events and events have many checkins.
 * 
 * Events are stored in the events table with this schema
 * Field           | Type         | Null | Key | Default           | Extra
 * id              | int(11)      | NO   | PRI | NULL              | auto_increment
 * organization_id | int(11)      | NO   | MUL | NULL              | 
 * name            | varchar(127) | NO   |     | NULL              | 
 * date            | timestamp    | YES  | MUL | NULL              | 
 * status          | tinyint(1)   | NO   |     | 1                 | 
 * timestamp       | timestamp    | NO   |     | CURRENT_TIMESTAMP | 
 * 
 * Event Attributes are stored in the eventattributes table with this schema
 * Field       | Type          | Null | Key | Default           | Extra
 * id          | int(11)       | NO   | PRI | NULL              | auto_increment
 * event_id    | int(11)       | NO   |     | NULL              | 
 * name        | varchar(128)  | NO   |     | NULL              | 
 * value       | varchar(8192) | NO   |     | NULL              | 
 * status      | tinyint(1)    | NO   |     | 1                 | 
 * timestamp   | timestamp     | NO   |     | CURRENT_TIMESTAMP | 
 */

/**
 * Sets event date to null
 * @param int $eventID Event ID
 * @throws Exception if $eventID is not a positive integer
 */
function deleteEventDate($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer.");
    }
    $sql = "UPDATE events
            SET date = 'NULL'
            WHERE id = '$eventID'";
    mysql_query($sql) or die (mysql_error());
}

/**
 * Edits event information, with the provided event ID
 * @param array $costs Array with various costs in form of array[0]['item'],
 * array[0]['cost'], array[1]['item'], array[1]['cost'], etc.
 * @param string $date Date of Event
 * @param int $eventID Event ID
 * @param string $name Name of Event
 * @param int $organizationID Organization ID
 * @return int Event ID of the newly created event or the existing event
 * @throws Exception if $organizationID is not a positive integer
 * @throws Exception if $eventID is not a non-negative integer
 * @throws Exception if $name is empty
 */
function editEvent($costs, $date, $eventID, $name, $organizationID){
    if(!isInteger($organizationID) || $organizationID < 1 || empty($organizationID)){
        throw new Exception("Organization ID needs to be a positive integer.");
    }
    if($eventID != "" && (!isInteger($eventID) || $eventID < 0)){
        throw new Exception("Event ID needs to be a non-negative integer.");
    }
    if(empty($name)){
        throw new Exception('No name was entered for the event.');
    }
    if(empty($eventID) || $eventID == 0){
        $sql = "INSERT INTO events VALUES('', '$organizationID', '$name', 'NULL', 1, CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (mysql_error());
        $eventID = mysql_insert_id();
        editEventCosts($costs, $eventID);
    } else {
        $sql = "UPDATE events
                SET name = '$name'
                WHERE organization_id = '$organizationID' AND id = '$eventID'";
        $query = mysql_query($sql) or die (mysql_error());
        editEventCosts($costs, $eventID);
    }
    editEventDate($eventID, $date);
    return intval($eventID);
}

/**
 * Edits event costs. Stores the events costs as JSON in the database
 * under eventAttribute name "Event Costs".
 * @param array $costs Array with various costs in form of array[0]['item'],
 * array[0]['cost'], array[1]['item'], array[1]['cost'], etc.
 * @param int $eventID Event ID
 * @throws Exception if $eventID is not a positive integer
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
    $sql = "SELECT * FROM eventattributes
            WHERE event_id = '$eventID'
            AND name = 'Event Costs'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        $id = $result['id'];
        $sql = "UPDATE eventattributes
                SET value = '$JSON'
                WHERE id = '$id'";
    }
    else{
        $sql = "INSERT INTO eventattributes VALUES
                (NULL, '$eventID', 'Event Costs', '$JSON', '1', CURRENT_TIMESTAMP)";
    }
    $query = mysql_query($sql) or die (mysql_error());
}

/**
 * Edits the Event's Date, turns it off when empty string
 * for $date
 * @param int $eventID Event ID
 * @param string $date Date in YYYY-MM-DD H:i:s format
 * @throws Exception if $eventID is not a positive integer
 * @throws Exception if $date is in invalid format
 * @throws Exception if $eventID is an invalid Event ID (event doesn't exist)
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
            FROM events
            WHERE id = '$eventID'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        $dt = DateTime::createFromFormat("Y-m-d H:i:s", $date);
        if($dt === false || array_sum($dt->getLastErrors()) > 0){
            throw new Exception("Date format is incorrect.");
        }
        if(empty($date)){
            $sql = "UPDATE events
                    SET date = 'NULL'
                    WHERE id = '$eventID'";
        } else {
            $sql = "UPDATE events
                    SET date = '$date'
                    WHERE id = '$eventID'";
        }
    } else {
        throw new Exception("Invalid Event ID");
    }
    $query = mysql_query($sql) or die (mysql_error());
}

/**
 * Gets the provided event's average payment
 * from each checkin. e.g. If an event has checkins
 * with payments 0, 5, 10, and 10. This method would
 * return 6.25.
 * @param int $eventID Event ID
 * @return float null if empty result, average payment otherwise
 * @throws Exception if $eventID is not a positive integer
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
 * @param int $eventID  Event ID
 * @return float null if empty result, total payment otherwise
 * @throws Exception if $eventID is not a positive integer
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
 * @param int $eventID Event ID
 * @return int the total number of free checkins used
 * @throws Exception if $eventID is not a positive integer
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

/**
 * Gets the number of new customers as of that event.
 * This is determined by whether or not a customer has checked into an event
 * that has a date before the provided event ID's date.
 * @param int $eventID Event ID
 * @return int
 * @throws Exception if $eventID is not a positive integer
 * @throws Exception if $eventID does not reference an event with a valid date
 * @throws Exception if $eventID is not an event in the database
 */
function getEventNumberOfNewCustomers($eventID){
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
    $selectCustomerIDsFromEventSQL = "
        SELECT customer_id
        FROM checkins
        WHERE checkins.event_id = '$eventID'
        AND checkins.status = '1'
        ";
    $selectEventIDsBeforeEventDateSQL = "
        SELECT id
        FROM events
        WHERE events.date < '$eventDate'
        AND events.date != '0000-00-00 00:00:00'
        ";
    $countCustomersBeforeEventDateSQL = "
        SELECT COUNT(*) as count
        FROM (
        SELECT top_ch.customer_id as cid
        FROM checkins as top_ch
        WHERE top_ch.status = '1'
        AND top_ch.event_id IN ($selectEventIDsBeforeEventDateSQL)
        AND top_ch.customer_id IN ($selectCustomerIDsFromEventSQL)
        GROUP BY top_ch.customer_id) as groupedresults
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

/**
 * Gets the number of checkins for the event ID provided.
 * e.g. If twenty five people checkin then this function
 * will return 25.
 * @param int $eventID Event ID
 * @return int Total number of checkins for the event ID provided
 * @throws Exception if $eventID is not a positive integer
 */
function getEventNumberOfCheckins($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $getEventNumberOfCheckinsSQL =
    "SELECT COUNT(*) as totalCheckins
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
 * @throws Exception if $eventID is not a positive integer
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
 * @throws Exception if $eventID is not a positive integer.
 */
function getEventCheckins($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $getEventCheckinsSQL =
    "SELECT checkins.id as id, checkins.customer_id as cid, name,
    payment, checkins.timestamp as timestamp, checkins.status as status
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
 * Gets Event Costs as stored in eventattributes table under Name = 'Event Costs'
 * @param int $eventID Event ID
 * @return array
 * @throws Exception if $eventID is not a positive integer
 */
function getEventCosts($eventID){
    if(!isset($eventID) || !isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $sql = "SELECT value 
            FROM eventattributes
            WHERE event_id = '$eventID'
            AND eventattributes.status = '1'
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
 * @throws Exception if $eventID is not a positive integer
 * @throws Exception if $eventID is an invalid Event ID (event doesn't exist)
 */
function getEventDate($eventID){
    if(!isInteger($eventID) || intval($eventID) < 1){
        throw new Exception("Event ID must be a positive integer.");
    }
    $sql = "SELECT date
            FROM events
            WHERE id = '$eventID'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return $result['date'];
    } else {
        throw new Exception("Invalid Event ID");
    }
    return '';
}

/**
 * Returns the name of the event given the event ID
 * @param int $eventID event ID number
 * @return string name of the event
 * @throws Exception if $eventID is not a positive integer
 */
function getEventName($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $sql = "SELECT name 
            FROM events 
            WHERE id = '$eventID'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return $result['name'];
    }
    return '';
}