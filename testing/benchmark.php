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


function stats_standard_deviation($aValues, $bSample = false) {
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

function benchmarkSearchCustomers($name, $limit, $eventID = "1"){
    mysql_query("RESET QUERY CACHE") or die ("<br/>" . mysql_error());
    echo "<br/>Benchmarking searchCustomers($name, $limit, $eventID)<br/>";
    $start_time = microtime(TRUE);
    $search = searchCustomers($eventID, $limit, $name);
    $end_time = microtime(TRUE);
    echo "Time to perform: " .  ($end_time - $start_time) . " ... <b>Number of Results:</b> " . count($search['customers']);
    return $end_time - $start_time;
}

$runs = 0;
$times1 = array();
$limit = 36;
echo "Number of search results limited to: <b>$limit</b><br/>";
echo "<br/><b>NAME: , LENGTH: 0</b><br/>";
echo "<br/><b>Begin result -1</b>";
$times1[] = benchmarkSearchCustomers("", $limit);
echo "<br/><b>End result -1</b><br/>";
$runs++;
for($i = 0; $i < 27; $i++){
    for($start = 0; $start < 26; $start++){
        if($i == 0){
            $name = chr(97 + $start);
        } else {
            $name = chr(97 + $i - 1) . chr(97 + $start);
        }
        echo "<br/><b>NAME: $name, LENGTH: ". strlen($name) . "</b><br/>";
        echo "<br/><b>Begin result $i, $start</b>";
        $result1 = benchmarkSearchCustomers($name, $limit);
        $times1[] = $result1;
        $runs++;
        echo "<br/><b>End result $i, $start</b><br/>";
    }
}

echo "<br/><br/>TOTAL RUNS: <b>$runs</b>";
echo "<br/><br/>Total run time for benchmarkSearchCustomers: " .array_sum($times1). "
    <br/>average run time: " . (array_sum($times1)/$runs) . "
    <br/>median time: " . median($times1) . "
    <br/>longest time: " . max($times1) . "
    <br/>shortest time " . min($times1) . "
    <br/>standard deviation: " . stats_standard_deviation($times1);