<?php

include_once "database.php";
include_once "misc.php";

define('PRODUCTION_SERVER', false);

/**
 * Helper function. Takes error message string and returns a JSON with { error : message }
 * @param String $errorMessage the message
 * @return array
 */
function returnArrayError($errorMessage){
    return array("error" => $errorMessage);
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
    return "Problem SQL: $sql, Reason: " . mysql_error();
}

/**
 * Returns a JSON error message that represents the error caused by the SQL command
 * 
 * @param String $sql SQL statement that triggered the error
 * @param String $optiontext Optional error message text to return
 * @return array
 */
function returnSQLErrorInArray($sql, $optiontext = null){
    if($optiontext){
        return $optiontext . $sql;
    }
    return array("error" => "Error: $sql, Reason: " . mysql_error());
}
