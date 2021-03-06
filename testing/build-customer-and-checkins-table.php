<?php

require_once '../backend/checkin.php';
PRODUCTION_SERVER ? die() : "";
$ignite = isset($_GET['ignite']);
$buildCustomers = isset($_GET['buildCustomers']) && isInteger($_GET['buildCustomers']);
$buildCheckins = isset($_GET['buildCheckins']) && isInteger($_GET['buildCheckins']);
error_reporting(E_ALL);
ini_set('display_errors', '1');

if(!$ignite){
    echo "No go!";
    return;
}
$possiblePayments = array(5, 7, 10);
if($buildCustomers){
    $buildCustomersLimit = intval($_GET['buildCustomers']);
}
if($buildCheckins){
    $buildCheckinsLimit = intval($_GET['buildCheckins']);
}
if($buildCustomers){
    $firstNames = fopen("yob2013.txt", "r");
    $firstNamesArray = fgetcsv($firstNames);
    fclose($firstNames);
    $lastNames = fopen("mostcommonlastnames.txt", "r");
    $lastNamesArray = fgetcsv($lastNames);
    fclose($lastNames);
    $firstArrayLen = count($firstNamesArray) - 1;
    $lastArrayLen = count($lastNamesArray) - 1;
    if($buildCustomersLimit < 5000){
        echo "<table><tr><td>Name</td><td>Email</td><td>Birthday</td><td>Payment</td><td>Event</td></tr>";
    }
    $allEventsSQL = "SELECT id FROM events WHERE events.status = '1'";
    $allEventsQuery = mysql_query($allEventsSQL) or die(mysql_error());
    $allEventsArray = array();
    while($event = mysql_fetch_array($allEventsQuery)){
        $allEventsArray[] = $event['id'];
    }
    for($i = 0; $i < $buildCustomersLimit; $i++){
        $randomFirst = mt_rand(0, $firstArrayLen);
        $randomLast = mt_rand(0, $lastArrayLen);
        $randomDate = mt_rand(0, 850022416);
        $randomPay = mt_rand(0, 2);
        $name = $firstNamesArray[$randomFirst] . " " . $lastNamesArray[$randomLast];
        $email = str_replace(" ", "", $name) . "@gmail.com";
        $birthday = date('Y-m-d H:i:s', $randomDate);
        $randomPay = $possiblePayments[$randomPay];
        $randomEvent = $allEventsArray[mt_rand(0, $eventsCount)];
        if($buildCustomersLimit < 5000){
            echo "<tr><td>".$name."</td><td>".$email."</td><td>".$birthday."</td><td>".$randomPay."</td><td>".$randomEvent."</td></tr>";
        }
        checkinCustomer($birthday, 0, 0, $email, $randomEvent, $name, 0, $randomPay, false);
    }
    if($buildCustomersLimit < 5000){
        echo "</table>";
    }
}
if($buildCheckins){
    $allCustomersSQL = "SELECT name, id, birthday, email FROM customers WHERE status = '1'";
    $allCustomersQuery = mysql_query($allCustomersSQL) or die(mysql_error());
    $customerArray = array();
    while($customer = mysql_fetch_array($allCustomersQuery)){
        $customerArray[] = $customer;
    }
    $allEventsSQL = "SELECT id FROM events WHERE events.status = '1'";
    $allEventsQuery = mysql_query($allEventsSQL) or die(mysql_error());
    $allEventsArray = array();
    while($event = mysql_fetch_array($allEventsQuery)){
        $allEventsArray[] = $event['id'];
    }
    $eventsCount = count($allEventsArray) - 1;
    $customerCount = count($customerArray) - 1;
    if($buildCheckinsLimit < 5000){
        echo "<table><tr><td>#</td><td>ID</td><td>Name</td><td>Email</td><td>Birthday</td><td>Payment</td><td>Event</td></tr>";
    }
    for($i = 0; $i < $buildCheckinsLimit; $i++){
        $randomCustomer = mt_rand(0,$customerCount);
        $randomEvent = $allEventsArray[mt_rand(0, $eventsCount)];
        $randomCustomer = $customerArray[$randomCustomer];
        if(getCheckinIDForCustomerAndEvent($randomCustomer['id'], $randomEvent) == 0){
            $randomPay = mt_rand(0, 2);
            $randomPay = $possiblePayments[$randomPay];
            if($buildCheckinsLimit < 5000){
                echo "<tr><td>".$i."</td><td>".$randomCustomer['id']."</td><td>".$randomCustomer['name']."</td><td>".$randomCustomer['email']."</td><td>".$randomCustomer['birthday']."</td><td>".$randomPay."</td><td>".$randomEvent."</td></tr>";
            }
            checkinCustomer($randomCustomer['birthday'], 0, $randomCustomer['id'], $randomCustomer['email'], $randomEvent, $randomCustomer['name'], 0, $randomPay, false);
        }
    }
    if($buildCheckinsLimit < 5000){
        echo "</table>";
    }
}
echo "Finished";