<?php

include_once "database.php";

/**
 * Returns true/false based on whether a value of
 * (id, $organizationID, 'FreeCheckins', 'true', '1', TIMESTAMP)
 * is in organizationAttributes.
 * @param type $organizationID - organization ID
 * @return boolean
 * @throws Exception - When $organizationID is not an integer or less than 1.
 */
function isFreeEntranceEnabled($organizationID){
    if(!is_int($organizationID) || $organizationID < 1){
        throw new Exception("organizationID in isFreeEntranceEnabled must be a positive integer");
    }
    $organizationID = mysql_real_escape_string($organizationID);
    $sql = "SELECT *
            FROM organizationAttributes
            WHERE organization_id = '$organizationID'
            AND name = 'FreeCheckins'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    if(!empty($result) && $result['on'] == "1" && $result['value'] == "true"){
        return true;
    }
    return false;
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

?>