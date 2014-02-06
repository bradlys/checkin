<?php
/**
 * 
 * @author Bradly Schlenker
 */

require_once "backend/settings.php";

$method = $_SERVER['REQUEST_METHOD'];
if( strtolower($method) != 'post'){
    return 'OUT-OUT-OUT-OUT-OUT!';
}

//For putting the name of the event at the top
if(isset($_POST['purpose'])){
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
        if(empty($money)){
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
        $eventID = mysql_real_escape_string($_POST['eventID']);
        $highestVisitsAndLikeName =
        "CREATE TEMPORARY TABLE highestVisitsAndLikeName
        SELECT COUNT(*) as visits, cu.id as cid, cu.name as name, cu.email as email
        FROM checkins as ch
        JOIN customers AS cu ON ch.customer_id = cu.id
        WHERE cu.name LIKE '%$name%'
        GROUP BY cu.id
        ORDER BY visits DESC
        LIMIT 11";
        mysql_query($highestVisitsAndLikeName) or die ("We didn't start the fire, but something went wrong with $highestVisitsAndLikeName");
        $visitsql = "SELECT * FROM highestVisitsAndLikeName";
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
        echo '<div class="customer col-xs-3" id="newuser"><div id="username">Add New User</div></div>';
    }
}
?>