<?php

require_once '../backend/settings.php';
require_once '../backend/search.php';

PRODUCTION_SERVER ? die() : "";
/**
 * This file is used to benchmark CIA.
 * It helps finding out which kind of SQL
 * queries are fast and which are not.
 * Mostly this is used as a development space.
 */


function stats_standard_deviation($aValues, $bSample = false)
{
    $fMean = array_sum($aValues) / count($aValues);
    $fVariance = 0.0;
    foreach ($aValues as $i)
    {
        $fVariance += pow($i - $fMean, 2);
    }
    $fVariance /= ( $bSample ? count($aValues) - 1 : count($aValues) );
    return (float) sqrt($fVariance);
}

function median($arr) {
    sort($arr);
    $count = count($arr); //total numbers in array
    $middleval = floor(($count-1)/2); // find the middle value, or the lowest middle value
    if($count % 2) { // odd number, middle is the median
        $median = $arr[$middleval];
    } else { // even number, calculate avg of 2 medians
        $low = $arr[$middleval];
        $high = $arr[$middleval+1];
        $median = (($low+$high)/2);
    }
    return $median;
}

function searchCustomersDropTable($args){
    $timingArray = array();
    
    $name = $args['name'];
    $limit = $args['limit'];
    $eventID = $args['eventID'];
    mysql_query("DROP TEMPORARY TABLE IF EXISTS `highestVisitsAndLikeName`") or die ("temp table drop fail");
    //This SQL statement is very slow and needs a way to be avoided.
    $highestVisitsAndLikeName =
    "CREATE TEMPORARY TABLE highestVisitsAndLikeName
    SELECT COUNT(ch.on) AS visits, cu.id AS cid, cu.name AS name, cu.email AS email
    FROM checkins AS ch
    RIGHT OUTER JOIN customers AS cu ON ch.customer_id = cu.id
    WHERE cu.name LIKE '%$name%'
    AND cu.on = '1'
    GROUP BY cu.id
    ORDER BY visits DESC, name ASC
    ";
    $start_time = microtime(TRUE);
    mysql_query($highestVisitsAndLikeName) or die (returnSQLError($highestVisitsAndLikeName));
    $end_time = microtime(TRUE);
    $timingArray[0] = ($end_time - $start_time);
    $numInSystemSQL = "SELECT COUNT(*) as count FROM highestVisitsAndLikeName";
    $start_time = microtime(TRUE);
    $numInSystemQuery = mysql_query($numInSystemSQL) or die (returnSQLError($numInSystemSQL));
    $numInSystemNumber = mysql_fetch_array($numInSystemQuery);
    $end_time = microtime(TRUE);
    $timingArray[1] = ($end_time - $start_time);
    $numInSystemNumber = $numInSystemNumber['count'];
    $visitsql = "SELECT * FROM highestVisitsAndLikeName " . ($numInSystemNumber > ($limit + 1) ? ("LIMIT " .  $limit) : "");
    $start_time = microtime(TRUE);
    $visitquery = mysql_query($visitsql) or die (returnSQLError($visitsql));
    $end_time = microtime(TRUE);
    $timingArray[2] = ($end_time - $start_time);
    $alreadycheckedinsql =
    "SELECT customers.name AS cname, checkins.customer_id AS cid, checkins.payment AS payment
    FROM checkins
    JOIN customers
    ON checkins.customer_id = customers.id
    WHERE event_id = '$eventID'
    AND checkins.on = '1'
    AND customers.on = '1'
    AND customer_id IN (SELECT customer_id 
                        FROM highestVisitsAndLikeName)";
    $start_time = microtime(TRUE);
    $alreadycheckedinquery = mysql_query($alreadycheckedinsql) or die (returnSQLError($alreadycheckedinsql));
    $alreadycheckedin = array();
    while($tmp = mysql_fetch_array($alreadycheckedinquery)){
        $alreadycheckedin[$tmp['cid']] = $tmp['payment'];
    }
    $end_time = microtime(TRUE);
    $timingArray[3] = ($end_time - $start_time);
    $keysAlreadyCheckedIn = array_keys($alreadycheckedin);
    $customerArray = array();
    $start_time = microtime(TRUE);
    while($visit = mysql_fetch_array($visitquery)){
        $cid = $visit['cid'];
        $name = $visit['name'];
        $visits = $visit['visits'];
        $isCheckedIn = in_array($cid, $keysAlreadyCheckedIn);
        $checkinID = getCheckinIDForCustomerAndEvent($cid, $eventID);
        $usedFreeEntrance = $checkinID ? hasCustomerUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getCustomerNumberOfFreeEntrances($cid);
        array_push($customerArray, 
            array(
            "cid" => $cid,
            "email" => $visit['email'],
            "payment" => ($isCheckedIn ? $alreadycheckedin[$cid] : ''),
            "name" => $name,
            "visits" => $visits,
            "isCheckedIn" => $isCheckedIn,
            "usedFreeEntrance" => $usedFreeEntrance,
            "numberOfFreeEntrances" => $numberOfFreeEntrances,
            )
        );
    }
    $end_time = microtime(TRUE);
    $timingArray[4] = ($end_time - $start_time);
    $returnJSON = array();
    $returnJSON['customers'] = $customerArray;
    $returnJSON['timingArray'] = $timingArray;
    if($numInSystemNumber > $limit + 1){
        $returnJSON['numberOfExtra'] = $numInSystemNumber - $limit;
    } else {
        $returnJSON['numberOfExtra'] = 0;
    }
    return json_encode($returnJSON);
}

/**
 * Faster version of searchCustomers
 * @param type $args
 * @return type
 */
function searchCustomersFaster($args){
    $timingArray = array();
    
    $name = $args['name'];
    $limit = $args['limit'];
    $eventID = $args['eventID'];
    //Avoiding creating temporary table now. This improves things significantly.
    $highestVisitsAndLikeName =
    "SELECT COUNT(ch.on) AS visits, cu.id AS cid, cu.name AS name, cu.email AS email
    FROM checkins AS ch
    RIGHT OUTER JOIN customers AS cu ON ch.customer_id = cu.id
    WHERE cu.name LIKE '%$name%'
    AND cu.on = '1'
    GROUP BY cu.id
    ORDER BY visits DESC, name ASC
    ";
    $numInSystemSQL = "SELECT COUNT(*) as count FROM ($highestVisitsAndLikeName) as high";
    $start_time = microtime(TRUE);
    $numInSystemQuery = mysql_query($numInSystemSQL) or die (returnSQLError($numInSystemSQL));
    $numInSystemNumber = mysql_fetch_array($numInSystemQuery);
    $end_time = microtime(TRUE);
    $timingArray[0] = ($end_time - $start_time);
    $numInSystemNumber = $numInSystemNumber['count'];
    $visitsql = "SELECT * FROM ($highestVisitsAndLikeName) as high " . ($numInSystemNumber > ($limit + 1) ? ("LIMIT " .  $limit) : "");
    $start_time = microtime(TRUE);
    $visitquery = mysql_query($visitsql) or die (returnSQLError($visitsql));
    $end_time = microtime(TRUE);
    $timingArray[1] = ($end_time - $start_time);
    $alreadycheckedinsql =
    "SELECT customers.name AS cname, checkins.customer_id AS cid, checkins.payment AS payment
    FROM checkins
    JOIN customers
    ON checkins.customer_id = customers.id
    WHERE event_id = '$eventID'
    AND checkins.on = '1'
    AND customers.on = '1'
    AND customer_id IN (SELECT customer_id 
                        FROM ($highestVisitsAndLikeName) as high)";
    $start_time = microtime(TRUE);
    $alreadycheckedinquery = mysql_query($alreadycheckedinsql) or die (returnSQLError($alreadycheckedinsql));
    $alreadycheckedin = array();
    while($tmp = mysql_fetch_array($alreadycheckedinquery)){
        $alreadycheckedin[$tmp['cid']] = $tmp['payment'];
    }
    $end_time = microtime(TRUE);
    $timingArray[2] = ($end_time - $start_time);
    $keysAlreadyCheckedIn = array_keys($alreadycheckedin);
    $customerArray = array();
    $start_time = microtime(TRUE);
    while($visit = mysql_fetch_array($visitquery)){
        $cid = $visit['cid'];
        $name = $visit['name'];
        $visits = $visit['visits'];
        $isCheckedIn = in_array($cid, $keysAlreadyCheckedIn);
        $checkinID = getCheckinIDForCustomerAndEvent($cid, $eventID);
        $usedFreeEntrance = $checkinID ? hasCustomerUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getCustomerNumberOfFreeEntrances($cid);
        array_push($customerArray, 
            array(
            "cid" => $cid,
            "email" => $visit['email'],
            "payment" => ($isCheckedIn ? $alreadycheckedin[$cid] : ''),
            "name" => $name,
            "visits" => $visits,
            "isCheckedIn" => $isCheckedIn,
            "usedFreeEntrance" => $usedFreeEntrance,
            "numberOfFreeEntrances" => $numberOfFreeEntrances,
            )
        );
    }
    $end_time = microtime(TRUE);
    $timingArray[3] = ($end_time - $start_time);
    $returnJSON = array();
    $returnJSON['customers'] = $customerArray;
    $returnJSON['timingArray'] = $timingArray;
    if($numInSystemNumber > $limit + 1){
        $returnJSON['numberOfExtra'] = $numInSystemNumber - $limit;
    } else {
        $returnJSON['numberOfExtra'] = 0;
    }
    return json_encode($returnJSON);
}

/**
 * Faster version of searchCustomers
 * @param type $args
 * @return type
 */
function searchCustomersFastest($args){
    $timingArray = array();
    $countArray = array();
    
    $name = $args['name'];
    $limit = $args['limit'];
    $eventID = $args['eventID'];
    $numInSystemSQL =
            "SELECT COUNT(*) as count
            FROM customers
            WHERE customers.on = '1'
            AND name LIKE '%$name%'
            ";
    $start_time = microtime(TRUE);
    $numInSystemQuery = mysql_query($numInSystemSQL) or die (returnSQLError($numInSystemSQL));
    $numInSystemNumber = mysql_fetch_array($numInSystemQuery);
    $end_time = microtime(TRUE);
    $timingArray[0] = ($end_time - $start_time);
    $numInSystemNumber = $numInSystemNumber['count'];
    
    $countArray[0] = $numInSystemNumber;
    
    $highestVisitsAndLikeName =
    "SELECT COUNT(ch.on) AS visits, cu.id AS cid, cu.name AS name, cu.email AS email
    FROM checkins AS ch
    RIGHT OUTER JOIN customers AS cu ON ch.customer_id = cu.id
    WHERE cu.name LIKE '%$name%'
    AND cu.on = '1'
    GROUP BY cu.id
    ORDER BY visits DESC, name ASC, cu.id DESC
    " . ($numInSystemNumber > ($limit + 1) ? ("LIMIT " .  $limit) : "");
    $start_time = microtime(TRUE);
    $visitquery = mysql_query($highestVisitsAndLikeName) or die (returnSQLError(mysql_error()));
    $end_time = microtime(TRUE);
    $timingArray[1] = ($end_time - $start_time);
    $alreadycheckedinsql =
    "SELECT customers.name AS cname, checkins.customer_id AS cid, checkins.payment AS payment
    FROM checkins
    JOIN customers
    ON checkins.customer_id = customers.id
    WHERE event_id = '$eventID'
    AND checkins.on = '1'
    AND customers.on = '1'
    AND customer_id IN (
        SELECT cu.id AS cid
        FROM checkins AS ch
        RIGHT OUTER JOIN customers AS cu ON ch.customer_id = cu.id
        WHERE cu.name LIKE '%$name%'
        AND cu.on = '1'
        GROUP BY cu.id
        ORDER BY COUNT(ch.on) DESC, name ASC, cu.id DESC
        )";
    $start_time = microtime(TRUE);
    $alreadycheckedinquery = mysql_query($alreadycheckedinsql) or die (returnSQLError($alreadycheckedinsql));
    $end_time = microtime(TRUE);
    $timingArray[2] = ($end_time - $start_time);
    $alreadycheckedin = array();
    while($tmp = mysql_fetch_array($alreadycheckedinquery)){
        $alreadycheckedin[$tmp['cid']] = $tmp['payment'];
    }
    $keysAlreadyCheckedIn = array_keys($alreadycheckedin);
    $customerArray = array();
    $start_time = microtime(TRUE);
    while($visit = mysql_fetch_array($visitquery)){
        $st = microtime(TRUE);
        $cid = $visit['cid'];
        $name = $visit['name'];
        $visits = $visit['visits'];
        $isCheckedIn = in_array($cid, $keysAlreadyCheckedIn);
        $checkinID = getCheckinIDForCustomerAndEvent($cid, $eventID);
        $usedFreeEntrance = $checkinID ? hasCustomerUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getCustomerNumberOfFreeEntrances($cid);
        array_push($customerArray, 
            array(
            "cid" => $cid,
            "email" => $visit['email'],
            "payment" => ($isCheckedIn ? $alreadycheckedin[$cid] : ''),
            "name" => $name,
            "visits" => $visits,
            "isCheckedIn" => $isCheckedIn,
            "usedFreeEntrance" => $usedFreeEntrance,
            "numberOfFreeEntrances" => $numberOfFreeEntrances,
            )
        );
        $timingArray[4] = (microtime(TRUE) - $st);
    }
    $end_time = microtime(TRUE);
    $timingArray[3] = ($end_time - $start_time);
    $returnJSON = array();
    $returnJSON['customers'] = $customerArray;
    $debugArray = array();
    $debugArray['timing'] = $timingArray;
    $debugArray['counts'] = $countArray;
    $returnJSON['debuging'] = $debugArray;
    if($numInSystemNumber > $limit + 1){
        $returnJSON['numberOfExtra'] = $numInSystemNumber - $limit;
    } else {
        $returnJSON['numberOfExtra'] = 0;
    }
    return json_encode($returnJSON);
}

function searchCustomersFastestRedux($args){
    $timingArray = array();
    $countArray = array();
    
    $name = $args['name'];
    $limit = $args['limit'];
    $eventID = $args['eventID'];
    $numInSystemSQL =
            "SELECT COUNT(*) as count
            FROM customers
            WHERE customers.on = '1'
            AND name LIKE '%$name%'
            ";
    $start_time = microtime(TRUE);
    $numInSystemQuery = mysql_query($numInSystemSQL) or die (returnSQLError($numInSystemSQL));
    $numInSystemNumber = mysql_fetch_array($numInSystemQuery);
    $end_time = microtime(TRUE);
    $timingArray[0] = ($end_time - $start_time);
    $numInSystemNumber = $numInSystemNumber['count'];
    
    $countArray[0] = $numInSystemNumber;
    
    $highestVisitsAndLikeName =
    "SELECT customers.id as cid, customers.name as name, customers.email as email, IFNULL(checkins.visits, 0) as visits
    FROM customers 
    LEFT JOIN
        (SELECT COUNT(checkins.on) AS visits, customer_id 
        FROM checkins 
        GROUP BY customer_id)
    AS checkins 
    ON checkins.customer_id=customers.id 
    WHERE customers.name LIKE '%$name%'
    AND customers.on = '1'
    ORDER BY checkins.visits DESC, customers.name ASC, customers.id DESC
    " .($numInSystemNumber > ($limit + 1) ? ("LIMIT " .  $limit) : "");
    $start_time = microtime(TRUE);
    $visitquery = mysql_query($highestVisitsAndLikeName) or die (returnSQLError(mysql_error()));
    $end_time = microtime(TRUE);
    $timingArray[1] = ($end_time - $start_time);
    $customerArray = array();
    $start_time = microtime(TRUE);
    while($visit = mysql_fetch_array($visitquery)){
        $st = microtime(TRUE);
        $cid = $visit['cid'];
        $name = $visit['name'];
        $visits = $visit['visits'];
        $isCheckedIn = getCustomerCheckedInPayment($cid, $eventID);
        $checkinID = getCheckinIDForCustomerAndEvent($cid, $eventID);
        $usedFreeEntrance = $checkinID ? hasCustomerUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getCustomerNumberOfFreeEntrances($cid);
        array_push($customerArray, 
            array(
            "cid" => $cid,
            "email" => $visit['email'],
            "payment" => ($isCheckedIn == null ? "" : $isCheckedIn),
            "name" => $name,
            "visits" => $visits,
            "isCheckedIn" => ($isCheckedIn != null),
            "usedFreeEntrance" => $usedFreeEntrance,
            "numberOfFreeEntrances" => $numberOfFreeEntrances,
            )
        );
        $timingArray[3] = (microtime(TRUE) - $st);
    }
    $end_time = microtime(TRUE);
    $timingArray[2] = ($end_time - $start_time);
    $returnJSON = array();
    $returnJSON['customers'] = $customerArray;
    $debugArray = array();
    $debugArray['timing'] = $timingArray;
    $debugArray['counts'] = $countArray;
    $returnJSON['debuging'] = $debugArray;
    if($numInSystemNumber > $limit + 1){
        $returnJSON['numberOfExtra'] = $numInSystemNumber - $limit;
    } else {
        $returnJSON['numberOfExtra'] = 0;
    }
    return json_encode($returnJSON);
}


function searchCustomersFastestReduxCombo($args){
    $timingArray = array();
    $countArray = array();
    
    $name = $args['name'];
    $limit = $args['limit'];
    $eventID = $args['eventID'];
    $numInSystemSQL =
            "SELECT COUNT(*) as count
            FROM customers
            WHERE customers.on = '1'
            AND name LIKE '%$name%'
            ";
    $start_time = microtime(TRUE);
    $numInSystemQuery = mysql_query($numInSystemSQL) or die (returnSQLError($numInSystemSQL));
    $numInSystemNumber = mysql_fetch_array($numInSystemQuery);
    $end_time = microtime(TRUE);
    $timingArray[0] = ($end_time - $start_time);
    $numInSystemNumber = $numInSystemNumber['count'];
    
    $countArray[0] = $numInSystemNumber;
    
    $highestVisitsAndLikeName =
    "SELECT COUNT(ch.on) AS visits, cu.id AS cid, cu.name AS name, cu.email AS email
    FROM checkins AS ch
    RIGHT OUTER JOIN customers AS cu ON ch.customer_id = cu.id
    WHERE cu.name LIKE '%$name%'
    AND cu.on = '1'
    GROUP BY cu.id
    ORDER BY visits DESC, name ASC, cu.id DESC
    " . ($numInSystemNumber > ($limit + 1) ? ("LIMIT " .  $limit) : "");
    $start_time = microtime(TRUE);
    $visitquery = mysql_query($highestVisitsAndLikeName) or die (returnSQLError(mysql_error()));
    $end_time = microtime(TRUE);
    $timingArray[1] = ($end_time - $start_time);
    $customerArray = array();
    $start_time = microtime(TRUE);
    while($visit = mysql_fetch_array($visitquery)){
        $st = microtime(TRUE);
        $cid = $visit['cid'];
        $name = $visit['name'];
        $visits = $visit['visits'];
        $isCheckedIn = getCustomerCheckedInPayment($cid, $eventID);
        $checkinID = getCheckinIDForCustomerAndEvent($cid, $eventID);
        $usedFreeEntrance = $checkinID ? hasCustomerUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getCustomerNumberOfFreeEntrances($cid);
        array_push($customerArray, 
            array(
            "cid" => $cid,
            "email" => $visit['email'],
            "payment" => ($isCheckedIn == null ? "" : $isCheckedIn),
            "name" => $name,
            "visits" => $visits,
            "isCheckedIn" => ($isCheckedIn != null),
            "usedFreeEntrance" => $usedFreeEntrance,
            "numberOfFreeEntrances" => $numberOfFreeEntrances,
            )
        );
        $timingArray[3] = (microtime(TRUE) - $st);
    }
    $end_time = microtime(TRUE);
    $timingArray[2] = ($end_time - $start_time);
    $returnJSON = array();
    $returnJSON['customers'] = $customerArray;
    $debugArray = array();
    $debugArray['timing'] = $timingArray;
    $debugArray['counts'] = $countArray;
    $returnJSON['debuging'] = $debugArray;
    if($numInSystemNumber > $limit + 1){
        $returnJSON['numberOfExtra'] = $numInSystemNumber - $limit;
    } else {
        $returnJSON['numberOfExtra'] = 0;
    }
    return json_encode($returnJSON);
}

function searchCustomersFinalFrontier($args){
    $timingArray = array();
    $countArray = array();
    
    $name = $args['name'];
    $limit = $args['limit'];
    $eventID = $args['eventID'];
    $numInSystemSQL =
            "SELECT COUNT(*) as count
            FROM customers
            WHERE customers.on = '1'
            AND name LIKE '%$name%'
            ";
    $start_time = microtime(TRUE);
    $numInSystemQuery = mysql_query($numInSystemSQL) or die (returnSQLError($numInSystemSQL));
    $numInSystemNumber = mysql_fetch_array($numInSystemQuery);
    $end_time = microtime(TRUE);
    $timingArray[0] = ($end_time - $start_time);
    $numInSystemNumber = $numInSystemNumber['count'];
    
    $countArray[0] = $numInSystemNumber;
    
    if($numInSystemNumber > 14000){
        $highestVisitsAndLikeName =
        "SELECT customers.id as cid, customers.name as name, customers.email as email, IFNULL(checkins.visits, 0) as visits
        FROM customers 
        LEFT JOIN
            (SELECT COUNT(checkins.on) AS visits, customer_id 
            FROM checkins 
            GROUP BY customer_id)
        AS checkins 
        ON checkins.customer_id=customers.id 
        WHERE customers.name LIKE '%$name%'
        AND customers.on = '1'
        ORDER BY checkins.visits DESC, customers.name ASC, customers.id DESC
        " .($numInSystemNumber > ($limit + 1) ? ("LIMIT " .  $limit) : "");
    }
    else{
        $highestVisitsAndLikeName =
        "SELECT COUNT(ch.on) AS visits, cu.id AS cid, cu.name AS name, cu.email AS email
        FROM checkins AS ch
        RIGHT OUTER JOIN customers AS cu ON ch.customer_id = cu.id
        WHERE cu.name LIKE '%$name%'
        AND cu.on = '1'
        GROUP BY cu.id
        ORDER BY visits DESC, name ASC, cu.id DESC
        " . ($numInSystemNumber > ($limit + 1) ? ("LIMIT " .  $limit) : "");
    }
    $start_time = microtime(TRUE);
    $visitquery = mysql_query($highestVisitsAndLikeName) or die (returnSQLError(mysql_error()));
    $end_time = microtime(TRUE);
    $timingArray[1] = ($end_time - $start_time);
    $customerArray = array();
    $start_time = microtime(TRUE);
    while($visit = mysql_fetch_array($visitquery)){
        $st = microtime(TRUE);
        $cid = $visit['cid'];
        $name = $visit['name'];
        $visits = $visit['visits'];
        $isCheckedIn = getCustomerCheckedInPayment($cid, $eventID);
        $checkinID = getCheckinIDForCustomerAndEvent($cid, $eventID);
        $usedFreeEntrance = $checkinID ? hasCustomerUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getCustomerNumberOfFreeEntrances($cid);
        array_push($customerArray, 
            array(
            "cid" => $cid,
            "email" => $visit['email'],
            "payment" => ($isCheckedIn == null ? "" : $isCheckedIn),
            "name" => $name,
            "visits" => $visits,
            "isCheckedIn" => ($isCheckedIn != null),
            "usedFreeEntrance" => $usedFreeEntrance,
            "numberOfFreeEntrances" => $numberOfFreeEntrances,
            )
        );
        $timingArray[3] = (microtime(TRUE) - $st);
    }
    $end_time = microtime(TRUE);
    $timingArray[2] = ($end_time - $start_time);
    $returnJSON = array();
    $returnJSON['customers'] = $customerArray;
    $debugArray = array();
    $debugArray['timing'] = $timingArray;
    $debugArray['counts'] = $countArray;
    $returnJSON['debuging'] = $debugArray;
    if($numInSystemNumber > $limit + 1){
        $returnJSON['numberOfExtra'] = $numInSystemNumber - $limit;
    } else {
        $returnJSON['numberOfExtra'] = 0;
    }
    return json_encode($returnJSON);
}

function searchCustomersCerb($args){
    $timingArray = array();
    $countArray = array();
    
    $name = $args['name'];
    $limit = $args['limit'];
    $eventID = $args['eventID'];
    $numInSystemSQL =
            "SELECT COUNT(*) as count
            FROM customers
            WHERE customers.on = '1'
            AND name LIKE '%$name%'
            ";
    $start_time = microtime(TRUE);
    $numInSystemQuery = mysql_query($numInSystemSQL) or die (returnSQLError($numInSystemSQL));
    $numInSystemNumber = mysql_fetch_array($numInSystemQuery);
    $end_time = microtime(TRUE);
    $timingArray[0] = ($end_time - $start_time);
    $numInSystemNumber = $numInSystemNumber['count'];
    
    $countArray[0] = $numInSystemNumber;
    
    $highestVisitsAndLikeName =
    "SELECT tcu.cid as cid, tcu.name as name, tcu.email as email, IFNULL(visits,0) AS visits
    FROM (
        SELECT id as cid, name, email
        FROM customers
        WHERE customers.on = '1'
        AND name LIKE '%$name%'
    ) AS tcu
    LEFT JOIN (
        SELECT customer_id as cid, COUNT(checkins.on) AS visits
        FROM checkins
        GROUP BY customer_id
    ) AS tch ON tcu.cid = tch.cid
    ORDER BY visits DESC, tcu.name ASC, tcu.cid DESC
    " . ($numInSystemNumber > ($limit + 1) ? ("LIMIT " .  $limit) : "");
    $start_time = microtime(TRUE);
    $visitquery = mysql_query($highestVisitsAndLikeName) or die (returnSQLError(mysql_error()));
    $end_time = microtime(TRUE);
    $timingArray[1] = ($end_time - $start_time);
    $customerArray = array();
    $start_time = microtime(TRUE);
    while($visit = mysql_fetch_array($visitquery)){
        $st = microtime(TRUE);
        $cid = $visit['cid'];
        $name = $visit['name'];
        $visits = $visit['visits'];
        $isCheckedIn = getCustomerCheckedInPayment($cid, $eventID);
        $checkinID = getCheckinIDForCustomerAndEvent($cid, $eventID);
        $usedFreeEntrance = $checkinID ? hasCustomerUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getCustomerNumberOfFreeEntrances($cid);
        array_push($customerArray, 
            array(
            "cid" => $cid,
            "email" => $visit['email'],
            "payment" => ($isCheckedIn == null ? "" : $isCheckedIn),
            "name" => $name,
            "visits" => $visits,
            "isCheckedIn" => ($isCheckedIn != null),
            "usedFreeEntrance" => $usedFreeEntrance,
            "numberOfFreeEntrances" => $numberOfFreeEntrances,
            )
        );
        $timingArray[3] = (microtime(TRUE) - $st);
    }
    $end_time = microtime(TRUE);
    $timingArray[2] = ($end_time - $start_time);
    $returnJSON = array();
    $returnJSON['customers'] = $customerArray;
    $debugArray = array();
    $debugArray['timing'] = $timingArray;
    $debugArray['counts'] = $countArray;
    $returnJSON['debuging'] = $debugArray;
    if($numInSystemNumber > $limit + 1){
        $returnJSON['numberOfExtra'] = $numInSystemNumber - $limit;
    } else {
        $returnJSON['numberOfExtra'] = 0;
    }
    return json_encode($returnJSON);
}

function benchmarkSearchCustomers($name, $limit){
    mysql_query("RESET QUERY CACHE") or die ("<br/>" . mysql_error());
    echo "</br>Benchmarking searchCustomersFastest with param: " . $name . "</br>";
    $eventID = "1";
    $args = array("name"=>$name, "limit"=>$limit, "eventID"=>$eventID);
    $start_time = microtime(TRUE);
    $search = searchCustomersFastest($args);
    $end_time = microtime(TRUE);
    $search = json_decode($search, true);
    $debugArray = $search['debuging'];
    $countArray = $debugArray['counts'];
    $timingArray = $debugArray['timing'];
    unset($search['debuging']);
    echo "Time to perform: " .  ($end_time - $start_time) . " ... <b>Number of Results:</b> " . count($search['customers']);
    echo "<br/>Time to perform ";
    for($i = 0; $i < count($timingArray); $i++){
        echo " Statements $i: ".$timingArray[$i] . " ";
    }
        echo "<br/>Counts : ";
    for($i = 0; $i < count($countArray); $i++){
        echo "Count $i: " . $countArray[$i];
    }
    $search = json_encode($search);
    return array("time"=> ($end_time - $start_time), "search"=>$search, "debugArray"=>$debugArray);
}

function benchmarkSearchCustomers2($name, $limit){
    mysql_query("RESET QUERY CACHE") or die ("<br/>" . mysql_error());
    echo "</br>Benchmarking searchCustomersFastestRedux with param: " . $name . "</br>";
    $eventID = "1";
    $args = array("name"=>$name, "limit"=>$limit, "eventID"=>$eventID);
    $start_time = microtime(TRUE);
    $search = searchCustomersFastestRedux($args);
    $end_time = microtime(TRUE);
    $search = json_decode($search, true);
    $debugArray = $search['debuging'];
    $countArray = $debugArray['counts'];
    $timingArray = $debugArray['timing'];
    unset($search['debuging']);
    echo "Time to perform: " .  ($end_time - $start_time) . " ... <b>Number of Results:</b> " . count($search['customers']);
    echo "<br/>Time to perform ";
    for($i = 0; $i < count($timingArray); $i++){
        echo " Statements $i: ".$timingArray[$i] . " ";
    }
        echo "<br/>Counts : ";
    for($i = 0; $i < count($countArray); $i++){
        echo "Count $i: " . $countArray[$i];
    }
    $search = json_encode($search);
    return array("time"=> ($end_time - $start_time), "search"=>$search, "debugArray"=>$debugArray);
}

function benchmarkSearchCustomers3($name, $limit){
    mysql_query("RESET QUERY CACHE") or die ("<br/>" . mysql_error());
    echo "</br>Benchmarking searchCustomersFastestReduxCombo with param: " . $name . "</br>";
    $eventID = "1";
    $args = array("name"=>$name, "limit"=>$limit, "eventID"=>$eventID);
    $start_time = microtime(TRUE);
    $search = searchCustomersFastestReduxCombo($args);
    $end_time = microtime(TRUE);
    $search = json_decode($search, true);
    $debugArray = $search['debuging'];
    $countArray = $debugArray['counts'];
    $timingArray = $debugArray['timing'];
    unset($search['debuging']);
    echo "Time to perform: " .  ($end_time - $start_time) . " ... <b>Number of Results:</b> " . count($search['customers']);
    echo "<br/>Time to perform ";
    for($i = 0; $i < count($timingArray); $i++){
        echo " Statements $i: ".$timingArray[$i] . " ";
    }
        echo "<br/>Counts : ";
    for($i = 0; $i < count($countArray); $i++){
        echo "Count $i: " . $countArray[$i];
    }
    $search = json_encode($search);
    return array("time"=> ($end_time - $start_time), "search"=>$search, "debugArray"=>$debugArray);
}

function benchmarkSearchCustomers4($name, $limit){
    mysql_query("RESET QUERY CACHE") or die ("<br/>" . mysql_error());
    echo "</br>Benchmarking searchCustomersFinalFrontier with param: " . $name . "</br>";
    $eventID = "1";
    $args = array("name"=>$name, "limit"=>$limit, "eventID"=>$eventID);
    $start_time = microtime(TRUE);
    $search = searchCustomersFinalFrontier($args);
    $end_time = microtime(TRUE);
    $search = json_decode($search, true);
    $debugArray = $search['debuging'];
    $countArray = $debugArray['counts'];
    $timingArray = $debugArray['timing'];
    unset($search['debuging']);
    echo "Time to perform: " .  ($end_time - $start_time) . " ... <b>Number of Results:</b> " . count($search['customers']);
    echo "<br/>Time to perform ";
    for($i = 0; $i < count($timingArray); $i++){
        echo " Statements $i: ".$timingArray[$i] . " ";
    }
        echo "<br/>Counts : ";
    for($i = 0; $i < count($countArray); $i++){
        echo "Count $i: " . $countArray[$i];
    }
    $search = json_encode($search);
    return array("time"=> ($end_time - $start_time), "search"=>$search, "debugArray"=>$debugArray);
}

function benchmarkSearchCustomers5($name, $limit){
    mysql_query("RESET QUERY CACHE") or die ("<br/>" . mysql_error());
    echo "</br>Benchmarking searchCustomersCerb with param: " . $name . "</br>";
    $eventID = "1";
    $args = array("name"=>$name, "limit"=>$limit, "eventID"=>$eventID);
    $start_time = microtime(TRUE);
    $search = searchCustomersCerb($args);
    $end_time = microtime(TRUE);
    $search = json_decode($search, true);
    $debugArray = $search['debuging'];
    $countArray = $debugArray['counts'];
    $timingArray = $debugArray['timing'];
    unset($search['debuging']);
    echo "Time to perform: " .  ($end_time - $start_time) . " ... <b>Number of Results:</b> " . count($search['customers']);
    echo "<br/>Time to perform ";
    for($i = 0; $i < count($timingArray); $i++){
        echo " Statements $i: ".$timingArray[$i] . " ";
    }
        echo "<br/>Counts : ";
    for($i = 0; $i < count($countArray); $i++){
        echo "Count $i: " . $countArray[$i];
    }
    $search = json_encode($search);
    return array("time"=> ($end_time - $start_time), "search"=>$search, "debugArray"=>$debugArray);
}

$runs = 0;
$times1 = array();
$times2 = array();
$times3 = array();
$times4 = array();
$timingArray1 = array();
$timingArray2 = array();
$timingArray3 = array();
$timingArray4 = array();
$debugArray1 = array();
$debugArray2 = array();
$debugArray3 = array();
$debugArray4 = array();
$countArray1 = array();
$countArray2 = array();
$countArray3 = array();
$countArray4 = array();
$names = array();
$limit = 36;
$borderlineTimesArray = array();
$space = true;
echo "Search results limited to: <b>$limit</b><br/>";
for($i = 0; $i < 27; $i++){
for($start = 0; $start < 26; $start++){
    if($i == 0){
        $name = chr(97 + $start);
    } else {
        $name = chr(97 + $i - 1) . chr(97 + $start);
    }
    if($space){
        $space = false;
        $name = "";
        $start--;
    }
    $names[$start] = $name;
    echo "<br/><b>NAME: $name, LENGTH: ". strlen($name) . "</b><br/>";
    echo "<br/><b>Begin result $start</b>";
    $result1 = benchmarkSearchCustomers5($name, $limit);
    //echo "<br/>" . print_r($result1['search'], true);
    $result2 = benchmarkSearchCustomers2($name, $limit);
    //echo "<br/>" . print_r($result2['search'], true);
    $result3 = benchmarkSearchCustomers3($name, $limit);
    //echo "<br/>" . print_r($result3['search'], true);
    $result4 = benchmarkSearchCustomers4($name, $limit);
    $times1[] = $result1['time'];
    $times2[] = $result2['time'];
    $times3[] = $result3['time'];
    $times4[] = $result4['time'];
    $timingArray1[] = $result1['debugArray']['timing'];
    $timingArray2[] = $result2['debugArray']['timing'];
    $timingArray3[] = $result3['debugArray']['timing'];
    $timingArray4[] = $result3['debugArray']['timing'];
    $countArray1[] = $result1['debugArray']['counts'];
    $countArray2[] = $result2['debugArray']['counts'];
    $countArray3[] = $result3['debugArray']['counts'];
    $countArray4[] = $result3['debugArray']['counts'];
    $runs++;
    
    
    
    if(abs($result1['time'] - $result2['time']) < 0.005){
        echo "<br/><br/><b>Result 1 time and result 2 time are very close</b><br/>";
    }
    if(abs($result1['time'] - $result3['time']) < 0.005){
        echo "<br/><br/><b>Result 1 time and result 3 time are very close</b><br/>";
    }
    if(abs($result2['time'] - $result3['time']) < 0.005){
        echo "<br/><br/><b>Result 2 time and result 3 time are very close</b><br/>";
    }
    if(abs($result2['debugArray']['timing'][1] - $result3['debugArray']['timing'][1]) < 0.005){
        $borderlineTimesArray[] = array("name"=>$name, "count"=>$result2['debugArray']['counts'][0], "result2Time" => $result2['debugArray']['timing'][1], "result3Time" => $result3['debugArray']['timing'][1]);
    }
    
    if($result1['search'] != $result2['search']){
        $result1text = print_r($result1['search'],true);
        $result2text = print_r($result2['search'],true);
        echo "<br/><br/><b>RESULT 1 DOES NOT EQUAL RESULT 2</b><br/>";
        echo "<br/><br/> <b>RESULT 1:</b> " . print_r($result1['search'],true) . "<br/><br/> <b>RESULT 2:</b> " . print_r($result2['search'],true);
    }
    if($result1['search'] != $result3['search']){
        $result1text = print_r($result1['search'],true);
        $result3text = print_r($result3['search'],true);
        
        echo "<br/><br/><b>RESULT 1 DOES NOT EQUAL RESULT 3</b><br/>";
        echo "<br/><br/> <b>RESULT 1:</b> " . print_r($result1['search'],true) . "<br/><br/> <b>RESULT 3:</b> " . print_r($result3['search'],true);
    }
    if($result2['search'] != $result3['search']){
        $result2text = print_r($result2['search'],true);
        $result3text = print_r($result3['search'],true);
        
        echo "<br/><br/><b>RESULT 2 DOES NOT EQUAL RESULT 3</b><br/>";
        echo "<br/><br/> <b>RESULT 2:</b> " . print_r($result2['search'],true) . "<br/><br/> <b>RESULT 3:</b> " . print_r($result3['search'],true);
    }
    if($result1['search'] != $result4['search'] || $result2['search'] != $result4['search'] || $result3['search'] != $result4['search']){
        echo "<br/><br/><b>RESULT 4 DOES NOT EQUAL RESULT 1 or 2 or 3</b><br/>";
        echo "<br/><br/> <b>RESULT 1:</b> " . print_r($result1['search'],true) . "<br/><br/> <b>RESULT 3:</b> " . print_r($result4['search'],true);
    }
    echo "<br/><b>End result $start</b><br/>";
}
}

echo "<br/><br/>TOTAL RUNS: <b>$runs</b>";
echo "<br/><br/>Total run time for benchmarkSearchCustomers5 (Cerb): " .array_sum($times1). ", average run time: " . (array_sum($times1)/$runs) . ", median time: " . median($times1) . ", longest time: " . max($times1) . ", standard deviation: " . stats_standard_deviation($times1);
echo "<br/><br/>Total run time for benchmarkSearchCustomers2 (FastestRedux): " .array_sum($times2). ", average run time: " . (array_sum($times2)/$runs) . ", median time: " . median($times2) . ", longest time: " . max($times2) . ", standard deviation: " . stats_standard_deviation($times2);
echo "<br/><br/>Total run time for benchmarkSearchCustomers3 (FastestReduxCombo): " .array_sum($times3). ", average run time: " . (array_sum($times3)/$runs) . ", median time: " . median($times3) . ", longest time: " . max($times3) . ", standard deviation: " . stats_standard_deviation($times3);
echo "<br/><br/>Total run time for benchmarkSearchCustomers4 (FinalFrontier): " .array_sum($times4). ", average run time: " . (array_sum($times4)/$runs) . ", median time: " . median($times4) . ", longest time: " . max($times4) . ", standard deviation: " . stats_standard_deviation($times4);
echo "<br/><br/><b>Names used:</b> " . print_r($names, true);

echo "<br/><br/><b>Borderline Cases:</b><br/>";
for($i = 0; $i < count($borderlineTimesArray); $i++){
    echo "<br/>Name: " . $borderlineTimesArray[$i]['name'] . ", Count: " . $borderlineTimesArray[$i]['count'] . ", Result 2 Time: " . $borderlineTimesArray[$i]['result2Time'] . ", Result 3 Time: " . $borderlineTimesArray[$i]['result3Time'];
}