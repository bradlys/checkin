<?php

require_once 'settings.php';
require_once 'customer.php';
require_once 'organization.php';

/**
 * This file handles checking in and checking out customers for events.
 */

/**
 * Checks in the customer
 * @param string $birthday Customer's birthday in YYYY-MM-DD H:i:s format
 * @param int $checkinID Customer's previous checkin ID, if applicable. This is
 * used to update the previous checkin. (Say you changed the payment amount or
 * the customer birthday, etc.) Put in 0 if a new checkin.
 * @param int $cid Customer ID number
 * @param string $email Customer's email address
 * @param int $eventID Event ID that the customer is being checked into
 * @param string $name Customer name
 * @param int $numberOfFreeEntrances Number of free entrances they have currently
 * @param int $payment Amount they paid for this checkin
 * @param boolean $useFreeEntrance Whether or not to use a free entrance for this
 * checkin
 * @return int Returns the checkin ID number of the current checkin. If the 
 * checkin is a new one then it returns that. If it is an old one then it returns
 * the checkin ID you gave for $checkinID.
 * @throws Exception if $name is empty
 * @throws Exception if $eventID is not a non-negative integer
 * @throws Exception if $payment is not a non-negative integer
 * @throws Exception if $checkinID is not a non-negative integer
 * @throws Exception if $numberOfFreeEntrances is not a non-negative integer
 * @throws Exception if $numberOfFreeEntrances is 0, the checkin hasn't already used
 * a free entrance, and you try to use a free entrance for the current checkin
 * @throws Exception if $payment is 0, a free entrance hasn't already been used
 * for the current checkin and you don't use a free entrance to get in.
 */
function checkinCustomer($birthday, $checkinID, $cid, $email, $eventID, $name, $numberOfFreeEntrances, $payment, $useFreeEntrance){
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
    if(!isInteger($checkinID) || $checkinID < 0){
        throw new Exception("CheckinID must be a non-negative integer.");
    }
    //if Customer doesn't exist then make them
    if(empty($cid)){
        $sql = "INSERT INTO customers VALUES ('', '$name', '$email', NULL, NULL, 0, 1, CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (mysql_error());
        $cid = mysql_insert_id();
    }
    $sql = "SELECT ch.id as checkin_id, ch.status as checkin_status
            FROM checkins AS ch
            JOIN customers AS cu
            ON ch.customer_id = cu.id
            WHERE ch.customer_id = '$cid'
            AND ch.event_id = '$eventID'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($checkinID == 0){
        $checkinID = $result['checkin_id'];
    }
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
        incrementCustomerVisits($cid);
    } else {
        if($result['checkin_status'] == 0){
            incrementCustomerVisits($cid);
        }
        $sql = "UPDATE checkins
                SET payment = '$payment', checkins.status = '1'
                WHERE id = '$checkinID'";
        $query = mysql_query($sql) or die (mysql_error());
        $sql = "UPDATE customers
                SET name = '$name', email = '$email', customers.status = '1'
                WHERE id = '$cid'";
        $query = mysql_query($sql) or die (mysql_error());
        if($isFreeEntranceEnabled && $hasUsedFreeEntrance && !$useFreeEntrance && $checkinID){
            unuseFreeEntrance($cid, $checkinID);
        }
        if($isFreeEntranceEnabled && $useFreeEntrance && !$hasUsedFreeEntrance && $checkinID){
            useFreeEntrance($cid, $checkinID);
        }
    }
    editCustomerBirthday($cid, $birthday);
    $toReturn = array();
    $toReturn['checkinID'] = $checkinID;
    return $toReturn;
}

/**
 * Checks out the customer.
 * @param int $checkinID Checkin ID
 * @param int $cid Customer ID
 * @param int $eventID Event ID
 * @throws Exception if $eventID is not a non-negative integer
 * @throws Exception if $cid is not a non-negative integer
 * @throws Exception if $checkinID is not a non-negative integer
 * @throws Exception if customer is not checked in
 */
function checkoutCustomer($checkinID, $cid, $eventID){
    if(!isInteger($eventID) || intval($eventID) < 1){
        throw new Exception("Event ID must be a positive integer.");
    }
    if(!isInteger($cid) || intval($cid) < 1){
        throw new Exception("Customer ID must be a positive integer.");
    }
    $hasCheckedIn = hasCustomerCheckedIn($cid, $eventID);
    if(!$hasCheckedIn){
        throw new Exception("Cannot checkout a customer who is not checked in.");
    }
    if(!isInteger($checkinID) || intval($checkinID) < 1){
        throw new Exception("Checkin ID must be a positive integer.");
    }
    $hasCustomerUsedFreeEntrance = hasCustomerUsedFreeEntrance($cid, $checkinID);
    if($hasCustomerUsedFreeEntrance){
        unuseFreeEntrance($cid, $checkinID);
    }
    $checkoutCustomerSQL =
        "UPDATE checkins
        SET checkins.status = '0'
        WHERE checkins.id = '$checkinID'";
    mysql_query($checkoutCustomerSQL) or die (mysql_error());
    $decrementCustomerVisitsSQL =
        "UPDATE customers
        SET customers.visits = customers.visits - 1
        WHERE customers.id = '$cid'";
    mysql_query($decrementCustomerVisitsSQL) or die (mysql_error());
    $toReturn = array();
    $toReturn['checkinID'] = $checkinID;
    $toReturn['numberOfFreeEntrances'] = getCustomerNumberOfFreeEntrances($cid);
    return $toReturn;
}