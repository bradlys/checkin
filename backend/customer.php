<?php


require_once 'settings.php';


/**
 * Sets customerAttributes.on = '0' for 
 * customerAttributes.customer_id = '$cid' AND
 * customerAttributes.on = '1' AND
 * customerAttributes.name = 'Customer Birthday'
 * @param int $cid Customer ID
 * @throws Exception When Customer ID is not a positive integer.
 */
function deleteCustomerBirthday($cid){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer.");
    }
    $sql = "UPDATE customerAttributes
            SET customerAttributes.on = '0'
            WHERE customerAttributes.customer_id = '$cid'
            AND customerAttributes.on = '1'
            AND customerAttributes.name = 'Customer Birthday'";
    mysql_query($sql) or die (mysql_error());
}

/**
 * Edits the Customer's Birthday
 * @param int $cid Customer ID
 * $param String $date Customer's Birthday
 */
function editCustomerBirthday($cid, $date){
    if(empty($date)){
        return deleteCustomerBirthday($cid);
    }
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer.");
    }
    $sql = "SELECT *
            FROM customerAttributes
            WHERE customer_id = '$cid'
            AND name = 'Customer Birthday'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        $id = $result['id'];
        $sql = "UPDATE customerAttributes
                SET customerAttributes.on = '1', value = '$date'
                WHERE id = '$id'";
        $query = mysql_query($sql) or die (mysql_error());
    } else {
        $sql = "INSERT INTO customerAttributes
                VALUES (NULL, '$cid', 'Customer Birthday', '$date', '1', CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (mysql_error());
    }
}

/**
 * Edits the number of free entrances the customer has to the newly provided number
 * @param int $cid customer id number
 * @param int $number number of free entrances
 * @throws Exception When $cid is not a positive integer. When $number is not a non-negative integer.
 */
function editCustomerNumberOfFreeEntrances($cid, $number){
    if($number < 0 || !isInteger($number)){
        throw new Exception("Number must be a non-negative integer for free entrances");
    }
    if($cid < 1 || !isInteger($cid)){
        throw new Exception("Customer ID must be a positive integer");
    }
    $sql = "SELECT *
            FROM customerAttributes
            WHERE customer_id = '$cid'
            AND name = 'Free Entrances'
            AND customerAttributes.on = '1' ";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        $id = $result['id'];
        $sql = "UPDATE customerAttributes SET value = '$number' WHERE id = '$id'";
        $query = mysql_query($sql) or die (mysql_error());
    }
    else{
        $sql = "INSERT INTO customerAttributes VALUES(NULL, '$cid', 'Free Entrances', '$number', '1', CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (mysql_error());
    }
}

/**
 * Gets checkin ID based off the event ID and customer ID
 * @param int $cid  customer ID
 * @param int $eventID  event ID
 * @return int
 * @throws Exception  When $cid or $checkinID is not a positive integer.
 */
function getCheckinIDForCustomerAndEvent($cid, $eventID){
    if($cid < 1 || !isInteger($cid)){
        throw new Exception("Customer ID must be a positive integer");
    }
    if($eventID < 1 || !isInteger($eventID)){
        throw new Exception("Event ID must be a positive integer");
    }
    $sql = "SELECT *
            FROM checkins
            WHERE customer_id = '$cid' 
            AND event_id = '$eventID'
            AND checkins.on = '1'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return $result['id'];
    }
    return null;
}

/**
 * Gets the Customer's birthday
 * @param int $cid Customer ID
 * @return String
 * @throws Exception When Customer ID is not a positive integer.
 */
function getCustomerBirthday($cid){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer.");
    }
    $sql = "SELECT *
            FROM customerAttributes
            WHERE customerAttributes.customer_id = '$cid'
            AND customerAttributes.name = 'Customer Birthday'
            AND customerAttributes.on = '1'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return $result['value'];
    }
    else {
        return "";
    }
}

function getCustomerByCheckinID($checkinID){
    if(!isInteger($checkinID) || $checkinID < 1){
        throw new Exception("Checkin ID must be a positive integer.");
    }
    $sql =
        "SELECT checkins.id as checkinID, customers.id as cid, 
        customers.name as name, customers.email as email, 
        checkins.payment as payment 
        FROM checkins
        LEFT JOIN customers
        ON checkins.customer_id = customers.id
        WHERE checkins.id = '$checkinID'";
    $query = mysql_query($sql) or die(mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        $cid = $result['cid'];
        $result['birthday'] = getCustomerBirthday($cid);
        $result['numberOfFreeEntrances'] = getCustomerNumberOfFreeEntrances($cid);
        $result['usedFreeEntrance'] = hasCustomerUsedFreeEntrance($cid, $checkinID);
    } else {
        throw new Exception("Invalid Checkin ID");
    }
    return $result;
}

/**
 * Gets Customer Checkin ID for Event ID provided
 * @param int $cid
 * @param int $eventID
 * @throws Exception When $cid or $eventID is not a positive integer
 * @return int
 */
function getCustomerCheckinID($cid, $eventID){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer");
    }
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $SQL = 
        "SELECT id
        FROM checkins
        WHERE checkins.customer_id = '$cid'
        AND checkins.event_id = '$eventID'
        AND checkins.on = '1'
        ORDER BY timestamp DESC";
    $query = mysql_query($SQL) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return $result['id'];
    }
    return null;
}

/**
 * Gets Customer Payment for the Event ID provided
 * @param int $cid Customer ID
 * @param int $eventID Event ID
 * @return int
 * @throws Exception When $cid or $eventID is not a positive integer
 */
function getCustomerCheckedInPayment($cid, $eventID){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer");
    }
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer");
    }
    $result = mysql_query("
        SELECT payment
        FROM checkins
        WHERE customer_id = '$cid'
        AND event_id = '$eventID'
        AND checkins.on = '1'
        ORDER BY timestamp DESC
        LIMIT 1") or die(mysql_error());
    $result = mysql_fetch_array($result);
    if($result){
        return $result['payment'];
    }
    else{
        return null;
    }
}

/**
 * Returns the email of the customer given the cid
 * @param int $cid Customer ID
 * @return String
 * @throws Exception When Customer ID is not a positive integer.
 */
function getCustomerEmail($cid){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer");
    }
    $sql = "SELECT email FROM customers WHERE id = '$cid'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    return $result['email'];
}

/**
 * Gets the number of free entrances the customer has.
 * @param int $cid customer ID number
 * @throws Exception When $cid is not a positive integer.
 */
function getCustomerNumberOfFreeEntrances($cid){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer");
    }
    $sql = "SELECT * FROM customerAttributes
            WHERE customer_id = '$cid'
            AND name = 'Free Entrances'
            AND customerAttributes.on = '1' ";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return $result['value'];
    } else {
        return 0;
    }
}

/**
 * Returns whether the customer has checked in
 * for the event ID provided.
 * @param int $cid customer ID
 * @param int $eventID event ID
 * @return boolean
 * @throws Exception when $cid or $eventID is not a positive integer
 */
function hasCustomerCheckedIn($cid, $eventID){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer.");
    }
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID must be a positive integer.");
    }
    $SQL =
        "SELECT *
        FROM checkins
        WHERE checkins.customer_id = '$cid'
        AND checkins.event_id = '$eventID'
        AND checkins.on = '1'";
    $query = mysql_query($SQL) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return true;
    }
    else {
        return false;
    }
}

/**
 * Returns true or false as to whether the customer has used a free
 * entrance for the given event ID
 * @param int $cid customer ID
 * @param int $checkinID checkin ID
 * @return boolean
 * @throws Exception When $cid or $checkinID is not a positive integer.
 */
function hasCustomerUsedFreeEntrance($cid, $checkinID){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer");
    }
    if(!isInteger($checkinID) || $checkinID < 1){
        throw new Exception("Checkin ID must be a positive integer");
    }
    $sql = "SELECT * FROM customerAttributes
            WHERE customer_id = '$cid'
            AND name = 'Used Free Entrance'
            AND value = '$checkinID'
            AND customerAttributes.on = '1'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return true;
    }
    else{
        return false;
    }
}

/**
 * Unuses a Free Entrance.
 * @param int $cid customer id
 * @param int $checkinID customers check-in ID
 * @throws Exception When trying to remove a used free entrance value when no used free entrance exists.
 * When $cid or $checkinID is not a positive integer.
 */
function unuseFreeEntrance($cid, $checkinID){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer");
    }
    if(!isInteger($checkinID) || $checkinID < 1){
        throw new Exception("Checkin ID must be a positive integer");
    }
    $sql = "SELECT *
           FROM customerAttributes
           WHERE customer_id = '$cid'
           AND name = 'Used Free Entrance'
           AND value = '$checkinID'
           AND customerAttributes.on = '1'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        $id = $result['id'];
        $sql = "UPDATE customerAttributes SET customerAttributes.on = '0' WHERE id = '$id'";
        $query = mysql_query($sql) or die (mysql_error());
        $databaseNumberOfFreeEntrances = getCustomerNumberOfFreeEntrances($cid);
        editCustomerNumberOfFreeEntrances($cid, $databaseNumberOfFreeEntrances + 1);
        return null;
    }
    throw new Exception("Tried to unuse a free entrance within unuseFreeEntrance method when no used free entrance existed.");
}

/**
 * Uses a Free Entrance
 * @param int $cid customer id
 * @param int $checkinID customers check-in ID
 * @throws Exception When trying to use a free entrance credit when no credit exists.
 * When $cid or $checkinID is not a positive integer.
 */
function useFreeEntrance($cid, $checkinID){
    if(!isInteger($cid) || $cid < 1){
        throw new Exception("Customer ID must be a positive integer");
    }
    if(!isInteger($checkinID) || $checkinID < 1){
        throw new Exception("Checkin ID must be a positive integer");
    }
    $sql = "SELECT * FROM customerAttributes WHERE customer_id = '$cid' AND name = 'Free Entrances'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        if($result['value'] > 0){
            $newFreeEntrancesAmount = $result['value'] - 1;
            $id = $result['id'];
            $sql = "UPDATE customerAttributes SET value = '$newFreeEntrancesAmount' WHERE id = '$id'";
            $query = mysql_query($sql) or die (mysql_error());
            $sql = "INSERT INTO customerAttributes VALUES (NULL, '$cid', 'Used Free Entrance', '$checkinID', 1, CURRENT_TIMESTAMP)";
            $query = mysql_query($sql) or die (mysql_error());
            return null;
        }
    }
    throw new Exception("Tried to use a free entrance within useFreeEntrance method when none were available.");
}