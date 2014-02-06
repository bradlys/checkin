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
        $cid = mysql_real_escape_string($_POST['cid']);
        $money = mysql_real_escape_string($_POST['money']);
        $email = mysql_real_escape_string($_POST['email']);
        $name = mysql_real_escape_string($_POST['name']);
        $eventid = mysql_real_escape_string($_POST['eventid']);
        $checkout = $_POST['checkout'];
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
}

$getstuff = mysql_real_escape_string($_POST['name']);
$id = mysql_real_escape_string($_POST['id']);

$highestVisitsAndLikeName = <<<SQL
CREATE TEMPORARY TABLE highestVisitsAndLikeName
SELECT COUNT(*) as visits, cu.id as cid, cu.name as name, cu.email as email
FROM checkins as ch
JOIN customers AS cu ON ch.customer_id = cu.id
WHERE cu.name LIKE '%$getstuff%'
GROUP BY cu.id
ORDER BY visits DESC
LIMIT 11
SQL;

mysql_query($highestVisitsAndLikeName) or die ("We didn't start the fire, but something went wrong with $highestVisitsAndLikeName");

$visitsql = "SELECT * FROM highestVisitsAndLikeName";
$visitquery = mysql_query($visitsql) or die ("We didn't start the fire, but something went wrong with $visitsql");

$alreadycheckedinsql = "SELECT customers.name AS cname, checkins.customer_id AS customerid, checkins.payment AS payment FROM checkins JOIN customers ON checkins.customer_id = customers.id WHERE event_id = '$id' AND customer_id IN (SELECT customer_id FROM highestVisitsAndLikeName)";
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
            '</a></div>';
}
echo '<div class="customer col-xs-3" id="newuser"><div id="username">Add New User</div></div>';
?>