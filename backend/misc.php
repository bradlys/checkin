<?php

/**
 * Returns whether the object is an integer or not
 * is_int doesn't work on strings. This does.
 * @param mixed $input
 * @return boolean
 */
function isInteger($input){
    return(ctype_digit(strval($input)));
}

function validateOrganizationName($name){
    if ( $name !== "" && strlen($name) > TABLE_MAX_ORGANIZATION_NAME_LENGTH ){
        throw new Exception("name is above " . TABLE_MAX_ORGANIZATION_NAME_LENGTH . " characters long");
    }
}

function validateOrganizationEmail($email){
    if ( $email !== "" && strlen($email) > TABLE_MAX_ORGANIZATION_EMAIL_LENGTH ){
        throw new Exception("email is above " . TABLE_MAX_ORGANIZATION_EMAIL_LENGTH . " characters long");
    }
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        throw new Exception("email is invalid format");
    }
}

function validateOrganizationID($organizationID){
    if( $organizationID !== "" && (!isInteger($organizationID) || $organizationID < 1) ){
        throw new Exception("organizationID must be a positive integer");
    }
}

function validateEventName($name){
    if ( $name !== "" && strlen($name) > TABLE_MAX_EVENT_NAME_LENGTH ){
        throw new Exception("name is above " . TABLE_MAX_EVENT_NAME_LENGTH . " characters long");
    }
}

function validateEventID($eventID){
    if( $eventID !== "" && (!isInteger($eventID) || $eventID < 1) ){
        throw new Exception("eventID must be a positive integer");
    }
}

function validateEventDate($date){
    if($date != "0000-00-00 00:00:00" && $date !== "" ){
        $dt = DateTime::createFromFormat("Y-m-d H:i:s", $date);
        if($dt === false || array_sum($dt->getLastErrors()) > 0){
            throw new Exception("Date format is incorrect");
        }
    }
}

function validateCustomerBirthday($birthday){
    if($birthday != "0000-00-00 00:00:00" && $birthday !== "" ){
        $dt = DateTime::createFromFormat("Y-m-d H:i:s", $birthday);
        if($dt === false || array_sum($dt->getLastErrors()) > 0){
            throw new Exception("Date format is incorrect");
        }
    }
}

function validateCustomerID($cid){
    if( $cid !== "" && (!isInteger($cid) || $cid < 1) ){
        throw new Exception("cid must be a positive integer");
    }
}

function validateCustomerName($name){
    if ( $name !== "" && strlen($name) > TABLE_MAX_CUSTOMER_NAME_LENGTH ){
        throw new Exception("name is above " . TABLE_MAX_CUSTOMER_NAME_LENGTH . " characters long");
    }
}

function validateCustomerEmail($email){
    if ( $email !== "" && strlen($email) > TABLE_MAX_CUSTOMER_EMAIL_LENGTH ){
        throw new Exception("email is above " . TABLE_MAX_CUSTOMER_EMAIL_LENGTH . " characters long");
    }
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        throw new Exception("email is invalid format");
    }
}

function validateCustomerVisits($visits){
    if( $visits !== "" && (!isInteger($visits) || $visits < 0) ){
        throw new Exception("visits must be a non-negative integer");
    }
}

function validateCheckinID($checkinID){
    if( $checkinID !== "" && (!isInteger($checkinID) || $checkinID < 1) ){
        throw new Exception("checkinID must be a positive integer");
    }
}

function validateCheckinPayment($payment){
    if($payment !== "" && (!isInteger($payment) || $payment < 0) ){
        throw new Exception("payment must be a non-negative integer");
    }
}

/**
 * Parses $_POST arguments by running post values through
 * mysql_real_escape_string() and then returning
 * them in an associate array in the same key+value
 * pair as they were before.
 * 
 * @return array
 */
function parse_post_arguments(){
    $args = array();
    unset($_POST['purpose']);
    $keys = array_keys($_POST);
    foreach ($keys as $key){
        if($key == "costs"){
            if(!empty($_POST[$key])){
                $args[$key] = json_decode($_POST[$key], true);
                for($i = 0; $i < count($args[$key]); $i++){
                    $costKeys = array_keys($args[$key][$i]);
                    foreach($costKeys as $costKey){
                        $args[$key][$i][$costKey] = mysql_real_escape_string($args[$key][$i][$costKey]);
                    }
                }
            } else {
                $args[$key] = $_POST[$key];
            }
        } else {
            $args[$key] = mysql_real_escape_string($_POST[$key]);
        }
    }
    sort($keys, SORT_STRING);
    $newargs = array();
    for($i = 0; $i < count($keys); $i++){
        $newargs[$i] = $args[$keys[$i]];
    }
    return $newargs;
}