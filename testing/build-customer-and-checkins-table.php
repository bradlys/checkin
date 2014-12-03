<?php

require_once '../backend/checkin.php';
PRODUCTION_SERVER ? die() : "";

$ignite = isset($_GET['ignite']);
$buildCustomers = isset($_GET['buildCustomers']) && isInteger($_GET['buildCustomers']);
$buildCheckins = isset($_GET['buildCheckins']) && isInteger($_GET['buildCheckins']);

if(!$ignite){
    echo "No go!";
    return;
}
$possiblePayments = array(5, 7, 10);
$buildCustomersLimit = intval($_GET['buildCustomers']);
$buildCheckinsLimit = intval($_GET['buildCheckins']);
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
    for($i = 0; $i < $buildCustomersLimit; $i++){
        $randomFirst = mt_rand(0, $firstArrayLen);
        $randomLast = mt_rand(0, $lastArrayLen);
        $randomDate = mt_rand(0, 850022416);
        $randomPay = mt_rand(0, 2);
        $name = $firstNamesArray[$randomFirst] . " " . $lastNamesArray[$randomLast];
        $email = str_replace(" ", "", $name) . "@gmail.com";
        $birthday = date('Y-m-d H:i:s', $randomDate);
        $randomPay = $possiblePayments[$randomPay];
        $randomEvent = mt_rand(1, 52);
        if($buildCustomersLimit < 5000){
            echo "<tr><td>".$name."</td><td>".$email."</td><td>".$birthday."</td><td>".$randomPay."</td><td>".$randomEvent."</td></tr>";
        }
        checkinCustomer(0, 0, $birthday, $email, $randomEvent, $name, 0, $randomPay, false);
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
    $customerCount = count($customerArray) - 1;
    if($buildCheckinsLimit < 5000){
        echo "<table><tr><td>#</td><td>ID</td><td>Name</td><td>Email</td><td>Birthday</td><td>Payment</td><td>Event</td></tr>";
    }
    for($i = 0; $i < $buildCheckinsLimit; $i++){
        $randomCustomer = mt_rand(0,$customerCount);
        $randomEvent = mt_rand(1, 52);
        $randomCustomer = $customerArray[$randomCustomer];
        if(getCheckinIDForCustomerAndEvent($randomCustomer['id'], $randomEvent) == 0){
            $randomPay = mt_rand(0, 2);
            $randomPay = $possiblePayments[$randomPay];
            if($buildCheckinsLimit < 5000){
                echo "<tr><td>".$i."</td><td>".$randomCustomer['id']."</td><td>".$randomCustomer['name']."</td><td>".$randomCustomer['email']."</td><td>".$randomCustomer['birthday']."</td><td>".$randomPay."</td><td>".$randomEvent."</td></tr>";
            }
            checkinCustomer(0, $randomCustomer['id'], $randomCustomer['birthday'], $randomCustomer['email'], $randomEvent, $randomCustomer['name'], 0, $randomPay, false);
        }
    }
    if($buildCheckinsLimit < 5000){
        echo "</table>";
    }
}
echo "Finished";