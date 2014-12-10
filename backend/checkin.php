<?php

require_once 'settings.php';
require_once 'customer.php';
require_once 'organization.php';

/**
 * Below are functions related to checking in and checking out customers.
 * Typically, one of these two functions is called through an AJAX request,
 * and it is to check in a brand new customer, check in an existing customer,
 * edit an existing check in, or to check out a customer.
 * 
 * If a new customer is being checked in then we have to create the customer in 
 * the customer table as well as create an entry in the checkins table for them.
 * 
 * If an existing customer is being checked in then we need to update their
 * information in the customers table appropriately (along with any possible
 * information that goes in the customerattributes table) and check them in.
 * 
 * If an existing customer is simply updating their customer information or
 * check in information (their name, payment amount, number of free entrances, 
 * etc.) then we need to find their checkin entry and update it. Perform
 * the same for their customer entry.
 * 
 * If an existing customer who is checked in needs to be checked out then
 * we need to update their customer information and turn off the checkin entry.
 * 
 * Events have many checkins and customers have many checkins. In other words, 
 * events are one to many checkins and customers are one to many checkins.
 * 
 * checkins are stored in the events table with this schema
 * Field           | Type         | Null | Key | Default           | Extra
 * id              | int(11)      | NO   | PRI | NULL              | auto_increment
 * customer_id     | int(11)      | NO   | MUL | NULL              | 
 * event_id        | int(11)      | NO   | MUL | NULL              | 
 * payment         | int(11)      | NO   |     | NULL              | 
 * status          | tinyint(1)   | NO   |     | 1                 | 
 * timestamp       | timestamp    | NO   |     | CURRENT_TIMESTAMP | 
 */

/**
 * Creates an entry in the checkin table
 * 
 * @param int $cid customer ID
 * @param int $eventID event ID
 * @param int $payment payment
 * @param boolean $useFreeEntrance
 * @return int checkinID
 * @throws Exception if $cid is not a positive integer
 * @throws Exception if $eventID is not a positive integer
 * @throws Exception if $payment is not a non-negative integer
 * @throws Exception if $cid, $eventID checkin combination already exists
 */
function createCheckin($cid, $eventID, $payment){
    if(empty($cid) || !isInteger($cid) || $cid < 1){
        throw new Exception("cid must be a positive integer");
    }
    if(empty($eventID) || !isInteger($eventID) || $eventID < 1){
        throw new Exception("eventID must be a positive integer");
    }
    if(!isInteger($payment) || $payment < 0){
        throw new Exception("payment must be a non-negative integer");
    }
    $countExistingCheckinsSQL = "
        SELECT COUNT(*) as count
        FROM checkins
        WHERE checkins.event_id = '$eventID'
        AND checkins.customer_id '$cid'
        AND checkins.status = '1'";
    $countExistingCheckinsQuery = mysql_query($countExistingCheckinsSQL) or die(mysql_error());
    $count = mysql_fetch_array($countExistingCheckinsQuery);
    $count = $count['count'];
    if($count > 0){
        throw new Exception("checkin already exists for current customer");
    } else {
        $insertNewCheckinSQL = "INSERT INTO checkins VALUES('', '$cid', '$eventID', '$payment', '1', CURRENT_TIMESTAMP)";
        mysql_query($insertNewCheckinSQL) or die(mysql_error());
        return mysql_insert_id();
    }
}

/**
 * Reads a checkin
 * @param int $checkinID checkin ID
 * @return array
 * @throws Exception if $checkinID is not a positive integer
 * @throws Exception if $checkinID checkin doesn't exists
 */
function readCheckin($checkinID){
    if(!isInteger($checkinID) || $checkinID < 1){
        throw new Exception("checkinID must be a positive integer");
    }
    $selectExistingCheckinSQL = "
        SELECT *
        FROM checkins
        WHERE checkins.id = '$checkinID'";
    $selectExistingCheckinQuery = mysql_query($selectExistingCheckinSQL) or die(mysql_error());
    $existingCheckin = mysql_fetch_array($selectExistingCheckinQuery);
    if($existingCheckin){
        return $existingCheckin;
    } else {
        throw new Exception("checkinID must refer to an existing checkin");
    }
}

/**
 * Updates an existing checkin
 * @param int $checkinID checkin ID
 * @param int $cid customer ID
 * @param int $eventID event ID
 * @param int $payment payment
 * @throws Exception if $checkinID is not a positive integer
 * @throws Exception if $cid is not a positive integer
 * @throws Exception if $eventID is not a positive integer
 * @throws Exception if $payment is not a non-negative integer
 * @throws Exception if $checkinID checkin doesn't exists
 */
function updateCheckin($checkinID, $cid, $eventID, $payment){
    if(!isInteger($checkinID) || $checkinID < 1){
        throw new Exception("checkinID must be a positive integer");
    }
    if(empty($cid) || !isInteger($cid) || $cid < 1){
        throw new Exception("cid must be a positive integer");
    }
    if(empty($eventID) || !isInteger($eventID) || $eventID < 1){
        throw new Exception("eventID must be a positive integer");
    }
    if(!isInteger($payment) || $payment < 0){
        throw new Exception("payment must be a non-negative integer");
    }
    $existingCheckin = readCheckin($checkinID);
    if($existingCheckin){
        $updateExistingCheckinSQL = "
            UPDATE checkins
            SET checkins.customer_id = '$cid', checkins.event_id = '$eventID',
                checkins.payment = '$payment'
            WHERE checkins.id = '$checkinID'";
        mysql_query($updateExistingCheckinSQL) or die(mysql_query());
    } else {
        throw new Exception("checkinID must refer to an existing checkin");
    }
}

/**
 * Deletes a checkin
 * @param int $checkinID checkin ID
 * @throws Exception if $checkinID is not a positive integer
 * @throws Exception if $checkinID checkin doesn't exists
 */
function deleteCheckin($checkinID){
    if(!isInteger($checkinID) || $checkinID < 1){
        throw new Exception("checkinID must be a positive integer");
    }
    $existingCheckin = readCheckin($checkinID);
    if($existingCheckin){
        $deleteCheckinSQL = "
            UPDATE checkins
            SET checkins.status = '0'
            WHERE checkins.id = '$checkinID'";
        mysql_query($deleteCheckinSQL) or die(mysql_query());
    } else {
        throw new Exception("checkinID must refer to an existing checkin");
    }
}

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
 * @throws Exception if $eventID is not a positive integer
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
        throw new Exception("name must be not empty");
    }
    if(empty($eventID) || !isInteger($eventID) || $eventID < 1){
        throw new Exception("eventID must be a positive integer");
    }
    if(!isInteger($payment) || $payment < 0){
        throw new Exception("payment must be a non-negative integer");
    }
    if(!isInteger($checkinID) || $checkinID < 0){
        throw new Exception("checkinID must be a non-negative integer");
    }
    //if customer doesn't exist then make them
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