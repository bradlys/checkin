<?php

require_once 'settings.php';
require_once 'customer.php';
require_once 'organization.php';
require_once 'misc.php';

/**
 * Checks in the customer.
 */
function checkinCustomer($cid, $date, $email, $eventID, $name, $numberOfFreeEntrances, $payment, $useFreeEntrance){
    $organizationID = inferOrganizationID($eventID);
    $isFreeEntranceEnabled = isFreeEntranceEnabled($organizationID);
    if(empty($name)){
        throw new Exception("Please input a name");
    }
    if(empty($eventID) || !isInteger($eventID) || $eventID < 1){
        throw new Exception("Please input a non-negative integer event id");
    }
    if(!isInteger($payment) || $payment < 0){
        throw new Exception("Please input a non-negative integer for payment.");
    }
    if(empty($cid)){
        $sql = "INSERT INTO customers VALUES ('', '$name', '$email', NULL, 1, CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (mysql_error());
        $cid = mysql_insert_id();
    }
    $sql = "SELECT ch.id as checkin_id
            FROM checkins AS ch
            JOIN customers AS cu
            ON ch.customer_id = cu.id
            WHERE ch.customer_id = '$cid'
            AND ch.event_id = '$eventID'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    $checkinID = $result['checkin_id'];
    if($isFreeEntranceEnabled){
        if($useFreeEntrance == "false"){
            $useFreeEntrance = false;
        }
        if(!isInteger($numberOfFreeEntrances) || $numberOfFreeEntrances < 0){
            throw new Exception("Free Entrances must be non-negative integer");
        }
        if($checkinID){
            $hasUsedFreeEntrance = hasCustomerUsedFreeEntrance($cid, $checkinID);
        }
        if($useFreeEntrance && $numberOfFreeEntrances == 0 && !$hasUsedFreeEntrance){
            throw new Exception("Not enough Free Entrances to use a Free Entrance");
        }
        if(empty($payment) && $payment != "0" && !$useFreeEntrance && !$hasUsedFreeEntrance){
            throw new Exception("Please input payment or use Free Entrance");
        }
        $databaseNumberOfFreeEntrances = getCustomerNumberOfFreeEntrances($cid);
        if($databaseNumberOfFreeEntrances != $numberOfFreeEntrances){
            editCustomerNumberOfFreeEntrances($cid, $numberOfFreeEntrances);
        }
    } else {
        if(empty($payment) && $payment != "0"){
            throw new Exception("Please input payment");
        }
    }
    if(!$result){
        $sql = "INSERT INTO checkins VALUES
                ('', '$cid', '$eventID', '$payment', '1', CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (mysql_error());
        if($isFreeEntranceEnabled && $useFreeEntrance){
            useFreeEntrance($cid, mysql_insert_id());
        }
    } else {
        $sql = "UPDATE checkins
                SET payment = '$payment'
                WHERE customer_id = '$cid' AND event_id = '$eventID'";
        $query = mysql_query($sql) or die (mysql_error());
        $sql = "UPDATE customers
                SET name = '$name', email = '$email'
                WHERE id = '$cid'";
        $query = mysql_query($sql) or die (mysql_error());
        if($isFreeEntranceEnabled && $hasUsedFreeEntrance && !$useFreeEntrance && $checkinID){
            unuseFreeEntrance($cid, $checkinID);
        }
        if($isFreeEntranceEnabled && $useFreeEntrance && !$hasUsedFreeEntrance && $checkinID){
            useFreeEntrance($cid, $checkinID);
        }
    }
    if($date){
        editCustomerBirthday($cid, $date);
    }
}

/**
 * Checks out the customer.
 * @param int $cid Customer ID
 * @param int $eventID Event ID
 * @throws Exception When Customer ID or Event ID are not positive integers.
 * When trying to checkout a customer who is not checked in.
 */
function checkoutCustomer($cid, $eventID){
    if(!isInteger($eventID) || intval($eventID) < 1){
        throw new Exception("Event ID must be a positive integer.");
    }
    if(!isInteger($cid) || intval($cid) < 1){
        throw new Exception("Customer ID must be a positive integer.");
    }
    $hasCheckedIn = hasCustomerCheckedIn($cid, $eventID);
    $checkinID = getCustomerCheckinID($cid, $eventID);
    if(!$hasCheckedIn || !$checkinID){
        throw new Exception("Cannot checkout a customer who is not checked in.");
    }
    $checkoutCustomerSQL =
        "UPDATE checkins
        SET checkins.on = '0'
        WHERE checkins.id = '$checkinID'";
    mysql_query($checkoutCustomerSQL) or die (mysql_error());
    $hasCustomerUsedFreeEntrance = hasCustomerUsedFreeEntrance($cid, $checkinID);
    if($hasCustomerUsedFreeEntrance){
        unuseFreeEntrance($cid, $checkinID);
    }
    $toReturn = array();
    $toReturn['checkinID'] = $checkinID;
    $toReturn['numberOfFreeEntrances'] = getCustomerNumberOfFreeEntrances($cid);
    return $toReturn;
}