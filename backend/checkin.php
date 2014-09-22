<?php

require_once 'settings.php';
require_once 'customer.php';

/**
 * Checks in the customer.
 * @param array $args Parsed arguments. Uses money, eventid,
 * email, name, cid, useFreeEntrance, and numberOfFreeEntrances.
 * @return String json_encode String
 */
function checkinCustomer($args){
    $money = $args['money'];
    $eventid = $args['eventid'];
    $email = $args['email'];
    $name = $args['name'];
    $cid = $args['cid'];
    $date = $args['date'];
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
        $sql = "INSERT INTO customers VALUES ('', '$name', '$email', NULL, 1, CURRENT_TIMESTAMP)";
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
        if($checkinID){
            $hasUsedFreeEntrance = hasCustomerUsedFreeEntrance($cid, $checkinID);
        }
        if(empty($money) && $money != "0" && !$useFreeEntrance && !$hasUsedFreeEntrance){
            return returnJSONError("Please input payment or use Free Entrance");
        }
        $databaseNumberOfFreeEntrances = getCustomerNumberOfFreeEntrances($cid);
        if($databaseNumberOfFreeEntrances != $numberOfFreeEntrances){
            editCustomerNumberOfFreeEntrances($cid, $numberOfFreeEntrances);
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
    if($isFreeEntranceEnabled && $hasUsedFreeEntrance && !$useFreeEntrance && $checkinID){
        unuseFreeEntrance($cid, $checkinID);
    }
    if($isFreeEntranceEnabled && $useFreeEntrance && !$hasUsedFreeEntrance && $checkinID){
        useFreeEntrance($cid, $checkinID);
    }
    if($date){
        echo editCustomerBirthday($cid, $date);
    }
}