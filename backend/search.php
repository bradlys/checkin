<?php
/**
 * This is where all POST requests are handled and all the back-end work is done.
 * Security is very minimal at this point.
 * POST requests are submitted to this page with a JSON format.
 * When submitted, the JSON is combed through and actions are made based upon the purpose declared
 * in the JSON string submitted. Once purpose is properly determined, it will check for the proper
 * data that should follow and will then do work based upon the purpose and data. Once the work is done,
 * this page will return a string containing the information desired. (Or an error)
 * @author Bradly Schlenker
 */

require_once "settings.php";

$method = $_SERVER['REQUEST_METHOD'];
if( strtolower($method) != 'post'){
    return 'OUT-OUT-OUT-OUT-OUT!';
}

//For putting the name of the event at the top
if(isset($_POST['purpose'])){
    $jsonarray = array();
    $purpose = $_POST['purpose'];
    if($purpose == 'getEvent'){
        $eventid = mysql_real_escape_string($_POST['eventid']);
        $sql = "SELECT name FROM events WHERE id = '$eventid'";
        $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
        $result = mysql_fetch_array($query);
        echo $result['name'];
        return '';
    }
    else if($purpose == 'getOrganization'){
        $organizationid = mysql_real_escape_string($_POST['organizationid']);
        $sql = "SELECT name FROM organizations WHERE id = '$organizationid'";
        $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
        $result = mysql_fetch_array($query);
        echo $result['name'];
        return '';
    }
    else if($purpose == 'getEmail'){
        $cid = mysql_real_escape_string($_POST['cid']);
        $sql = "SELECT email FROM customers WHERE id = '$cid'";
        $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
        $result = mysql_fetch_array($query);
        echo $result['email'];
        return '';
    }
    else if($purpose == 'findUser'){
        $name = mysql_real_escape_string($_POST['name']);
        $sql = "SELECT id FROM customers WHERE name = '$name'";
        $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
        $result = mysql_fetch_array($query);
        echo $result['id'];
        return '';
    }
    else if($purpose == 'getCID'){
        $name = mysql_real_escape_string($_POST['name']);
        $sql = "SELECT id FROM customers WHERE name = '$name'";
        $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
        $result = mysql_fetch_array($query);
        echo $result['id'];
        return '';
    }
    else if($purpose == 'checkin'){
        $money = mysql_real_escape_string($_POST['money']);
        if(empty($money) && $money != "0"){
            echo 'Please input payment';
            return '';
        }
        $email = mysql_real_escape_string($_POST['email']);
        $name = mysql_real_escape_string($_POST['name']);
        if(empty($name)){
            echo 'Please input a name';
            return '';
        }
        $cid = mysql_real_escape_string($_POST['cid']);
        if(empty($cid)){
            $sql = "INSERT INTO customers VALUES ('', '$name', '$email', CURRENT_TIMESTAMP)";
            $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
            $cid = mysql_insert_id();
        }
        $eventid = mysql_real_escape_string($_POST['eventid']);
        $sql = "SELECT * FROM checkins AS ch JOIN customers AS cu ON ch.customer_id = cu.id WHERE ch.customer_id = '$cid' AND ch.event_id = '$eventid'";
        $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
        $result = mysql_fetch_array($query);
        if(!$result){
            $sql = "INSERT INTO checkins VALUES ('', '$cid', '$eventid', '$money', CURRENT_TIMESTAMP)";
            //$sql = "SELECT * FROM checkins WHERE cid = '$cid' AND event_id = '$eventid'";
            $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
            return '';
        }
        $sql = "UPDATE checkins
                SET payment = $money
                WHERE customer_id = '$cid' AND event_id = '$eventid'";
        $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
        $sql = "UPDATE customers
                SET name = '$name', email = '$email'
                WHERE id = '$cid'";
        $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
        return '';
    }
    else if($purpose == 'checkout'){
        
    }
    else if($purpose == 'editEvent'){
        $eventID = mysql_real_escape_string($_POST['eventid']);
        $name = mysql_real_escape_string($_POST['name']);
        $organizationID = mysql_real_escape_string($_POST['organizationid']);
        $checkout = mysql_real_escape_string($_POST['checkout']);
        if(empty($name)){
            echo 'No name was entered for the event.';
            return '';
        }
        if(empty($organizationID)){
            echo 'No organization ID. WHAT DID YOU DO!?';
            return '';
        }
        if(empty($eventID)){
            //new event time
            $sql = "INSERT INTO events VALUES('', '$organizationID', '$name', CURRENT_TIMESTAMP)";
            $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
            return '';
        }
        $sql = "UPDATE events
                SET name = '$name'
                WHERE organization_id = '$organizationID' AND id = '$eventID'";
        $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
        
        return '';
    }
    else if($purpose == 'searchEvents'){
        $name = mysql_real_escape_string($_POST['name']);
        $organizationID = mysql_real_escape_string($_POST['organizationID']);
        $sql = "SELECT * FROM events WHERE organization_id = '$organizationID' AND name LIKE '%$name%'";
        $query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
        while($event = mysql_fetch_array($query)){
            echo '<div class="eventResultItem col-xs-3">' . 
                '<span class="eventResultID">' . $event['id'] . '</span>' .
                '<div id="eventResultName">' . $event['name'] . '</div>' . 
                '</div>';
        }
        echo '<div class="eventResultItem col-xs-3" id="newEvent"><div id="eventResultName">Add New Event</div></div>';
    }
    else if($purpose == 'searchCustomers'){
        $name = mysql_real_escape_string($_POST['name']);
        $limit = mysql_real_escape_string($_POST['limit']);
        $eventID = mysql_real_escape_string($_POST['eventID']);
        $highestVisitsAndLikeName =
        "CREATE TEMPORARY TABLE highestVisitsAndLikeName
        SELECT COUNT(*) as visits, cu.id as cid, cu.name as name, cu.email as email
        FROM checkins as ch
        JOIN customers AS cu ON ch.customer_id = cu.id
        WHERE cu.name LIKE '%$name%'
        GROUP BY cu.id
        ORDER BY visits DESC
        ";
        mysql_query($highestVisitsAndLikeName) or die ("We didn't start the fire, but something went wrong with $highestVisitsAndLikeName");
        $numInSystemSQL = "SELECT COUNT(*) as K FROM highestVisitsAndLikeName";
        $numInSystemQuery = mysql_query($numInSystemSQL) or die ("We didn't start the fire, but something went wrong with $numInSystemSQL");
        $numInSystemNumber = mysql_fetch_array($numInSystemQuery);
        $numInSystemNumber = $numInSystemNumber['K'];
        $visitsql = "SELECT * FROM highestVisitsAndLikeName " . ($numInSystemNumber > ($limit + 1) ? "LIMIT " .  ($limit) : "");
        $visitquery = mysql_query($visitsql) or die ("We didn't start the fire, but something went wrong with $visitsql");
        $alreadycheckedinsql = "SELECT customers.name AS cname, checkins.customer_id AS customerid, checkins.payment AS payment FROM checkins JOIN customers ON checkins.customer_id = customers.id WHERE event_id = '$eventID' AND customer_id IN (SELECT customer_id FROM highestVisitsAndLikeName)";
        $alreadycheckedinquery = mysql_query($alreadycheckedinsql) or die ("We didn't start the fire, but something went wrong with $alreadycheckedinsql");
        $alreadycheckedin = array();
        while($tmp = mysql_fetch_array($alreadycheckedinquery)){
            $alreadycheckedin[$tmp['cname']] = $tmp['payment'];
        }
        $keysAlreadyCheckedIn = array_keys($alreadycheckedin);
        while($visit = mysql_fetch_array($visitquery)){
            $name = $visit['name'];
            $visits = $visit['visits'];
            $isCheckedIn = in_array($name, $keysAlreadyCheckedIn);
            echo '<div class="customer col-xs-3">' . 
                '<span class="cid">' . $visit['cid'] . '</span>' . 
                '<span class="email">' . $visit['email'] . '</span>' . 
                '<span class="payment">' . ($isCheckedIn ? $alreadycheckedin[$name] : '') . '</span>' .
                '<div id="username">' . $name . '</div>' . 
                '<div id="visits">' . $visits . ' visits</div>'. 
                ($isCheckedIn ? '<small>Already Checked In</small>' : '') .
                '</div>';
        }
        if($numInSystemNumber > ($limit + 1) ){
            echo '<div class="customer col-xs-3" id="seemore"><div id="username">' . ($numInSystemNumber - $limit) . ' more...</div></div>';
        }
        echo '<div class="customer col-xs-3" id="newuser"><div id="username">Add New User</div></div>';
    }
    else if($purpose == 'searchOrganizations'){
        $name = mysql_real_escape_string($_POST['name']);
        $sql = "SELECT * FROM organizations WHERE name LIKE '%$name%'";
        $query = mysql_query($sql) or die (json_encode(array("error"=>"We didn't start the fire, but something went wrong with $sql")));
        while($oganization = mysql_fetch_array($query)){
            echo '<div class="organizationResultItem col-xs-3">' . 
                '<span class="organizationResultID">' . $oganization['id'] . '</span>' .
                '<div id="organizationResultName">' . $oganization['name'] . '</div>' . 
                '</div>';
        }
        echo '<div class="organizationResultItem col-xs-3" id="newEvent"><div id="organizationResultName">Add New Organization</div></div>';
    }
    else if($purpose == 'editOrganization'){
        $name = mysql_real_escape_string($_POST['name']);
        $email = mysql_real_escape_string($_POST['email']);
        $jsonarray['organizationid'] = $organizationID;
        $jsonarray['neworganization'] = false;
        if(!$name){
            $jsonarray['error'] = 'Please enter a name';
            echo json_encode($jsonarray);
            return '';
        }
        $organizationID = mysql_real_escape_string($_POST['organizationid']);
        if(!$organizationID){
            //create new organization
            $sql = "INSERT INTO organizations VALUES('', '$name', '$email', CURRENT_TIMESTAMP)";
            $query = mysql_query($sql) or die (json_encode(array("error"=>"We didn't start the fire, but something went wrong with $sql")));
            $jsonarray['success'] = "You created a new organization!";
            $jsonarray['organizationid'] = mysql_insert_id();
            $jsonarray['neworganization'] = true;
            echo json_encode($jsonarray);
            return '';
        }
        $sql = "SELECT * FROM organizations WHERE id = '$organizationID'";
        $query = mysql_query($sql) or die (json_encode(array("error"=>"We didn't start the fire, but something went wrong with $sql")));
        if(!mysql_fetch_array($query)){
            $jsonarray['error'] = "No organization exists under id = $organizationID";
            echo json_encode($jsonarray);
            return '';
        }
        $sql = "UPDATE organizations
                SET name = '$name', email = '$email'
                WHERE id = '$organizationID'";
        $query = mysql_query($sql) or die (json_encode(array("error"=>"We didn't start the fire, but something went wrong with $sql")));
        $jsonarray['success'] = "Successfully saved changes.";
        echo json_encode($jsonarray);
        return '';
    }
    
}
?>