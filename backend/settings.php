<?php

include_once "database.php";
include_once "misc.php";

define('PRODUCTION_SERVER', false);

/**
 * Infers the organization ID based off the event ID.
 * @param int $eventID event ID
 * @throws Exception When event ID is not a positive integer or refers to a non-existent event.
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
 * Returns true/false based on whether a value of
 * (id, $organizationID, 'Free Entrances Feature On', 'true', '1', TIMESTAMP)
 * is in organizationAttributes.
 * @param int $organizationID organization ID
 * @return boolean
 * @throws Exception When $organizationID is not an integer or less than 1.
 */
function isFreeEntranceEnabled($organizationID){
    if(!isInteger($organizationID) || $organizationID < 1){
        throw new Exception("organizationID in isFreeEntranceEnabled must be a positive integer");
    }
    $organizationID = mysql_real_escape_string($organizationID);
    $sql = "SELECT *
            FROM organizationAttributes
            WHERE organization_id = '$organizationID'
            AND name = 'Free Entrances Feature On'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    if(!empty($result) && $result['on'] == "1" && $result['value'] == "true"){
        return true;
    }
    return false;
}


/**
 * Helper function. Takes error message string and returns a JSON with { error : message }
 * @param String $errorMessage the message
 * @return String json_encode String
 */
function returnJSONError($errorMessage){
    return json_encode(array("error" => $errorMessage));
}

/**
 * Returns an error message that represents the error caused by the SQL command
 * 
 * @param String $sql SQL statement that triggered the error
 * @param String $optiontext Optional error message text to return
 * @return String
 */
function returnSQLError($sql, $optiontext = null){
    if($optiontext){
        return $optiontext . $sql;
    }
    return "Error: $sql, Reason: " . mysql_error();
}

/**
 * Returns a JSON error message that represents the error caused by the SQL command
 * 
 * @param String $sql SQL statement that triggered the error
 * @param String $optiontext Optional error message text to return
 * @return String json_encode String
 */
function returnSQLErrorInJSON($sql, $optiontext = null){
    if($optiontext){
        return $optiontext . $sql;
    }
    return json_encode(array("error" => "Error: $sql, Reason: " . mysql_error()));
}
