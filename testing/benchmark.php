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
        $usedFreeEntrance = $checkinID ? hasUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getNumberOfFreeEntrances($cid);
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
        $usedFreeEntrance = $checkinID ? hasUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getNumberOfFreeEntrances($cid);
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
        $usedFreeEntrance = $checkinID ? hasUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getNumberOfFreeEntrances($cid);
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
    $returnJSON['timingArray'] = $timingArray;
    if($numInSystemNumber > $limit + 1){
        $returnJSON['numberOfExtra'] = $numInSystemNumber - $limit;
    } else {
        $returnJSON['numberOfExtra'] = 0;
    }
    return json_encode($returnJSON);
}

function searchCustomersFastestRedux($args){
    $timingArray = array();
    
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
        $usedFreeEntrance = $checkinID ? hasUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getNumberOfFreeEntrances($cid);
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
    $returnJSON['timingArray'] = $timingArray;
    if($numInSystemNumber > $limit + 1){
        $returnJSON['numberOfExtra'] = $numInSystemNumber - $limit;
    } else {
        $returnJSON['numberOfExtra'] = 0;
    }
    return json_encode($returnJSON);
}


function searchCustomersFastestReduxCombo($args){
    $timingArray = array();
    
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
        $usedFreeEntrance = $checkinID ? hasUsedFreeEntrance($cid, $checkinID) : false;
        $numberOfFreeEntrances = getNumberOfFreeEntrances($cid);
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
    $returnJSON['timingArray'] = $timingArray;
    if($numInSystemNumber > $limit + 1){
        $returnJSON['numberOfExtra'] = $numInSystemNumber - $limit;
    } else {
        $returnJSON['numberOfExtra'] = 0;
    }
    return json_encode($returnJSON);
}

function getCustomerCheckedInPayment($cid, $eventID){
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

function benchmarkSearchCustomers($name, $limit){
    mysql_query("RESET QUERY CACHE") or die ("<br/>" . mysql_error());
    echo "</br>Benchmarking searchCustomersFastest with param: " . $name . "</br>";
    $eventID = "1";
    $args = array("name"=>$name, "limit"=>$limit, "eventID"=>$eventID);
    $start_time = microtime(TRUE);
    $search = searchCustomersFastest($args);
    $end_time = microtime(TRUE);
    $search = json_decode($search, true);
    $timingArray = $search['timingArray'];
    unset($search['timingArray']);
    $search = json_encode($search);
    echo "Time to perform: " .  ($end_time - $start_time);
    echo "<br/>Time to perform ";
    for($i = 0; $i < count($timingArray); $i++){
        echo " Statements $i: ".$timingArray[$i] . " ";
    }
    return array("time"=> ($end_time - $start_time), "search"=>$search, "timingArray"=>$timingArray);
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
    $timingArray = $search['timingArray'];
    unset($search['timingArray']);
    $search = json_encode($search);
    echo "Time to perform: " .  ($end_time - $start_time);
    echo "<br/>Time to perform ";
    for($i = 0; $i < count($timingArray); $i++){
        echo " Statements $i: ".$timingArray[$i] . " ";
    }
    return array("time"=> ($end_time - $start_time), "search"=>$search, "timingArray"=>$timingArray);
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
    $timingArray = $search['timingArray'];
    unset($search['timingArray']);
    $search = json_encode($search);
    echo "Time to perform: " . ($end_time - $start_time);
    echo "<br/>Time to perform ";
    for($i = 0; $i < count($timingArray); $i++){
        echo " Statements $i: ".$timingArray[$i] . " ";
    }
    return array("time"=> ($end_time - $start_time), "search"=>$search, "timingArray"=>$timingArray);
}

$runs = 0;
$times = array();
$times2 = array();
$times3 = array();
$timingArray = array();
$timingArray2 = array();
$timingArray3 = array();
$names = array();
$limit = 12;
echo "Search results limited to: <b>$limit</b><br/>";
for($i = 0; $i < 27; $i++){
for($start = 0; $start < 26; $start++){
    if($i == 0){
        $name = chr(97 + $start);
    } else {
        $name = chr(97 + $i - 1) . chr(97 + $start);
    }
    $names[$start] = $name;
    echo "<br/><b>NAME: $name, LENGTH: ". strlen($name) . "</b><br/>";
    echo "<br/><b>Begin result $start</b>";
    $result1 = benchmarkSearchCustomers($name, $limit);
    //echo "<br/>" . print_r($result1['search'], true);
    $result2 = benchmarkSearchCustomers2($name, $limit);
    //echo "<br/>" . print_r($result2['search'], true);
    $result3 = benchmarkSearchCustomers3($name, $limit);
    //echo "<br/>" . print_r($result3['search'], true);
    $times[] = $result1['time'];
    $times2[] = $result2['time'];
    $times3[] = $result3['time'];
    $timingArray[] = $result1['timingArray'];
    $timingArray2[] = $result2['timingArray'];
    $timingArray3[] = $result3['timingArray'];
    $runs++;
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
    echo "<br/><b>End result $start</b><br/>";
}
}

echo "<br/><br/>TOTAL RUNS: <b>$runs</b>";
echo "<br/><br/>Total run time for benchmarkSearchCustomers (Fastest): " .array_sum($times). ", average run time: " . (array_sum($times)/$runs) . ", median time: " . median($times) . ", longest time: " . max($times) . ", standard deviation: " . stats_standard_deviation($times);
echo "<br/><br/>Total run time for benchmarkSearchCustomers2 (FastestRedux): " .array_sum($times2). ", average run time: " . (array_sum($times2)/$runs) . ", median time: " . median($times2) . ", longest time: " . max($times2) . ", standard deviation: " . stats_standard_deviation($times2);
echo "<br/><br/>Total run time for benchmarkSearchCustomers3 (FastestReduxCombo): " .array_sum($times3). ", average run time: " . (array_sum($times3)/$runs) . ", median time: " . median($times3) . ", longest time: " . max($times3) . ", standard deviation: " . stats_standard_deviation($times3);
echo "<br/><br/><b>Names used:</b> " . print_r($names, true);