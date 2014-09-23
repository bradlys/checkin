<?php

require_once 'checkin.php';
require_once 'customer.php';
require_once 'event.php';
require_once 'organization.php';
require_once 'search.php';
require_once 'settings.php';

$functions = array("checkinCustomer", "checkoutCustomer", "editEvent", "editOrganization", "getCustomerBirthday", "getCustomerByCheckinID", "getEventCosts", "getEventDate", "searchCustomers", "searchEvents", "searchOrganizations");
$method = $_SERVER['REQUEST_METHOD'];
if( strtolower($method) != 'post'){
    return;
}

if(isset($_POST['purpose'])){
    $jsonarray = array();
    $purpose = $_POST['purpose'];
    $args = parse_post_arguments();
    if(in_array($purpose, $functions)){
        try {
            $toEcho = call_user_func_array($purpose, $args);
        } catch (Exception $e) {
            $toEcho = array("error"=>$e->getMessage());
        }
    } else {
        $toEcho = array("error"=>"Unable to achieve purpose: $purpose");
    }
    echo json_encode($toEcho);
    return;
}

