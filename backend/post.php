<?php

require_once 'checkin.php';
require_once 'customer.php';
require_once 'event.php';
require_once 'organization.php';
require_once 'settings.php';

$method = $_SERVER['REQUEST_METHOD'];
if( strtolower($method) != 'post'){
    return;
}
$functions = array(
    "checkinCustomer" => array("birthday", "checkinID", "cid", "email", 
        "eventID", "name", "numberOfFreeEntrances", "payment",
        "useFreeEntrance"),
    "checkoutCustomer" => array("checkinID", "cid", "eventID"),
    "createCheckin" => array("cid", "eventID", "payment", "useFreeEntrance"),
    "createCustomer" => array("birthday", "email", "name", 
        "numberOfFreeEntrances"),
    "createCustomerAndCheckinCustomer" => array("birthday", "checkinID", "cid",
        "email", "eventID", "name", "numberOfFreeEntrances", "payment",
        "useFreeEntrance"),
    "deleteCheckin" => array("checkinID"),
    "editCheckin" => array("checkinID", "cid", "eventID", "payment",
        "useFreeEntrance"),
    "editCustomerCheckin" => array("birthday", "checkinID", "cid", "email", 
        "eventID", "name", "numberOfFreeEntrances", "payment",
        "useFreeEntrance"),
    "editEvent" => array("costs", "date", "eventID", "name", "organizationID"),
    "editOrganization" => array("name", "organizationID"),
    "getCustomerByCheckinID" => array("checkinID"),
    "getEventCosts" => array("eventID"),
    "searchCustomers" => array("eventID", "limit", "name"),
    "searchEvents" => array("name", "organizationID"),
    "searchOrganizations" => array("name")
    );
$argumentTypes = array(
    "birthday" => "isValidDateFormat",
    "checkinID" => "isPositiveInteger",
    "cid" => "isPositiveInteger",
    "costs" => "isItemCostFormat",
    "date" => "isValidDateFormat",
    "eventID" => "isPositiveInteger",
    "limit" => "isPositiveInteger",
    "numberOfFreeEntrances" => "isNonNegativeInteger",
    "organizationID" => "isPositiveInteger",
    "payment" => "isNonNegativeInteger",
    "useFreeEntrance" => "isBoolean"
);

/**
 * Breakdown:
 * View (Client-side JS) sends a POST request to this file.
 * The POST request is in JSON format. Specifically, it includes
 * a key for "purpose" (e.g. {purpose:checkoutCustomer} ) and
 * the other necessary keys for that specific purpose. (In case of
 * checkoutCustomer, it needs a customer ID(cid) and a event ID(eventID))
 * 
 * The code below pulls the purpose out of the JSON, formats the rest
 * of the JSON into a PHP Array, and calls the function the key, purpose,
 * had for a value (assuming it is in the $functions array), while passing
 * each of the original JSON values in the order they were provided.
 * 
 * e.g. I do a POST request to post.php with this in JSON format:
 * {checkinID:0,
 * cid:38352,
 * date:,
 * email:Adria@gmail.com,
 * eventID:1,
 * name:Adria,
 * numberOfFreeEntrances:7,
 * purpose:checkinCustomer,
 * payment:6,
 * useFreeEntrance:false}
 * 
 * The code below will make a function call of
 * checkinCustomer(0, 38352, "", "Adira@gmail.com", 1, "Adira", 7, 6, false).
 * 
 * Upon completion of the method, if anything is returned or thrown then it 
 * will be echo'd in JSON format. That echo'd JSON encoded string is then
 * returned to the View (Client-side JS) for processing.
 */
$toEcho = array("error"=>"Unable to achieve anything. Total system failure.");
try {
    if(isset($_POST['purpose'])){
        $purpose = $_POST['purpose'];
        $args = parse_post_arguments();
        if(in_array($purpose, $functions)){
            $toEcho = call_user_func_array($purpose, $args);
        } else {
            $toEcho = array("error"=>"Unable to achieve purpose: $purpose");
        }
    } else {
        $toEcho = array("error"=>"Purpose is not set.");
    }
} catch (Exception $e) {
    $toEcho = array("error"=>$e->getMessage());
}
header("HTTP/1.1 200 OK");
header("Content-type: text/html");
echo json_encode($toEcho);