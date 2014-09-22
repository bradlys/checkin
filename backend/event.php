<?php

require_once 'settings.php';

/**
 * Sets eventAtrributes.on = '0' for 
 * eventAtrributes.event_id = '$eventID' AND
 * eventAtrributes.on = '1' AND
 * eventAtrributes.name = 'Event Date'
 * @param integer $eventID - Event ID
 * @return JSON
 */
function deleteEventDate($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        return returnJSONError("Event ID must be a positive integer.");
    }
    $sql = "UPDATE eventAtrributes
            SET eventAtrributes.on = '0'
            WHERE eventAtrributes.event_id = '$eventID'
            AND eventAtrributes.on = '1'
            AND eventAtrributes.name = 'Event Date'";
    mysql_query($sql) or die (returnSQLErrorInJSON($sql));
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
    $costs = $args['costs'];
    $date = $args['date'];
    if(!isInteger($organizationID) || $organizationID < 1){
        return returnJSONError("Organization ID needs to be an positive integer.");
    }
    if(!isInteger($eventID) || $eventID < 1){
        return returnJSONError("Event ID needs to be an positive integer.");
    }
    if(empty($name)){
        return returnJSONError('No name was entered for the event.');
    }
    if(empty($organizationID)){
        return returnJSONError('No organization ID.');
    }
    if(empty($eventID)){
        $sql = "INSERT INTO events VALUES('', '$organizationID', '$name', 1, CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (returnSQLError($sql));
        editEventCosts($costs, mysql_insert_id());
        return;
    }
    $sql = "UPDATE events
            SET name = '$name'
            WHERE organization_id = '$organizationID' AND id = '$eventID'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    editEventDate($eventID, $date);
    echo editEventCosts($costs, $eventID);
    return;
}

/**
 * Edits event costs. Stores the events costs as JSON in the database
 * under eventAttribute name "Event Costs".
 * @param Array $costs
 * @param integer $eventID
 * @throws Exception - when $eventID is not a positive integer
 */
function editEventCosts($costs, $eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID needs to be a positive integer.");
    }
    $JSON = json_encode($costs);
    if($costs && $costs[0]['item']){
        $arrayFromJSON = $costs;
        $arrayFromJSONCount = count($arrayFromJSON);
        for($i = 0; $i < $arrayFromJSONCount; $i++){
            $arrayFromJSON[$i]['item'] = mysql_real_escape_string($arrayFromJSON[$i]['item']);
            $arrayFromJSON[$i]['cost'] = mysql_real_escape_string($arrayFromJSON[$i]['cost']);
        }
        $JSON = json_encode($arrayFromJSON);
    }
    $sql = "SELECT * FROM eventAttributes
            WHERE event_id = '$eventID'
            AND name = 'Event Costs'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    if($result){
        $id = $result['id'];
        $sql = "UPDATE eventAttributes
                SET value = '$JSON'
                WHERE id = '$id'";
        $query = mysql_query($sql) or die (returnSQLError($sql));
    }
    else{
        $sql = "INSERT INTO eventAttributes VALUES
                (NULL, '$eventID', 'Event Costs', '$JSON', '1', CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (returnSQLError($sql));
    }
}

/**
 * Edits the Event's Date
 * @param int $eventID - Event ID
 * @param String $date - Date in MM/DD/YYYY format
 * @return JSON
 */
function editEventDate($eventID, $date){
    if(empty($date)){
        return deleteEventDate($eventID);
    }
    if(!isInteger($eventID) || $eventID < 1){
        return returnJSONError("Event ID must be a positive integer.");
    }
    $sql = "SELECT *
            FROM eventAttributes
            WHERE event_id = '$eventID'
            AND name = 'Event Date'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    $result = mysql_fetch_array($query);
    if($result){
        $id = $result['id'];
        $sql = "UPDATE eventAttributes
                SET eventAttributes.on = '1', value = '$date'
                WHERE id = '$id'";
        $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    } else {
        $sql = "INSERT INTO eventAttributes
                VALUES (NULL, '$eventID', 'Event Date', '$date', '1', CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    }
}

/**
 * Gets all checkins for the $eventID provided.
 * Returns them in a JSON String with information
 * like id, customer_id, event_id, and so forth.
 * @param int $eventID event ID
 * @return String json_encode String
 * @throws Exception
 */
function getEventCheckins($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $getEventCheckinsSQL =
    "SELECT id, customer_id, payment, on, timestamp
    FROM checkins
    WHERE checkins.event_id = '$eventID'
    ORDER BY checkins.timestamp DESC
    ";
    $query = mysql_query($getEventCheckinsSQL) or die(returnSQLError($sql));
    $result = array();
    while($curRow = mysql_fetch_array($query)){
        $result[] = $curRow;
    }
    return json_encode($result);
}

/**
 * Gets Event Costs as stored in eventAttributes table under Name = 'Event Costs'
 * @param array $args must have $args['eventID']
 * @return String json_encode String
 * @throws Exception when $args['eventID'] is not a positive integer
 */
function getEventCosts($args){
    if(isset($args['eventID'])){
        $eventID = $args['eventID'];
    } else {
        return returnJSONError("No eventID found");
    }
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $sql = "SELECT * FROM eventAttributes
            WHERE event_id = '$eventID'
            AND eventAttributes.on = '1'
            AND name = 'Event Costs'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    if($result){
        if(!empty($result['value'])){
            return $result['value'];
        }
    }
    return json_encode(array("" => ""));
}

/**
 * Gets the Event's Date
 * @param int $args - date at $args['date']
 * @return int
 */
function getEventDate($args){
    $eventID = $args['eventID'];
    if(!isInteger($eventID) || intval($eventID) < 1){
        return returnJSONError("Event ID must be a positive integer.");
    }
    $sql = "SELECT *
            FROM eventAttributes
            WHERE eventAttributes.event_id = '$eventID'
            AND eventAttributes.name = 'Event Date'
            AND eventAttributes.on = '1'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    $result = mysql_fetch_array($query);
    if($result){
        echo json_encode(array("date" => $result['value']));
    }
    else {
        echo json_encode(array("date" => ""));
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
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    if($result){
        return $result['name'];
    }
    return '';
}