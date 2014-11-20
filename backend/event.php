<?php

require_once 'settings.php';

/**
 * Sets eventAtrributes.on = '0' for 
 * eventAtrributes.event_id = '$eventID' AND
 * eventAtrributes.on = '1' AND
 * eventAtrributes.name = 'Event Date'
 * @param integer $eventID - Event ID
 * @throws Exception When Event ID is not a positive integer.
 */
function deleteEventDate($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer.");
    }
    $sql = "UPDATE eventAtrributes
            SET eventAtrributes.on = '0'
            WHERE eventAtrributes.event_id = '$eventID'
            AND eventAtrributes.on = '1'
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
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID needs to be an positive integer.");
    }
    if(empty($name)){
        throw new Exception('No name was entered for the event.');
    }
    if(empty($organizationID)){
        throw new Exception('No organization ID.');
    }
    $costs = json_decode($costs);
    if(empty($eventID)){
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
        $query = mysql_query($sql) or die (mysql_error());
    }
    else{
        $sql = "INSERT INTO eventAttributes VALUES
                (NULL, '$eventID', 'Event Costs', '$JSON', '1', CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (mysql_error());
    }
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
                SET eventAttributes.on = '1', value = '$date'
                WHERE id = '$id'";
        $query = mysql_query($sql) or die (mysql_error());
    } else {
        $sql = "INSERT INTO eventAttributes
                VALUES (NULL, '$eventID', 'Event Date', '$date', '1', CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (mysql_error());
    }
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
    payment, checkins.timestamp, checkins.on as status
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
            AND eventAttributes.on = '1'
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
            AND eventAttributes.on = '1'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return $result['value'];
    }
    else {
        return '';
    }
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