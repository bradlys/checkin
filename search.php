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

$getstuff = mysql_real_escape_string($_POST['name']);

//$sql = "SELECT * FROM customers WHERE name LIKE '%$getstuff%'";
//$query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
$visitsql = "SELECT COUNT(*) as visits, customer_id, name FROM checkins WHERE UPPER ( name ) LIKE UPPER ( '%$getstuff%' ) GROUP BY name ORDER BY visits DESC";
$visitquery = mysql_query($visitsql) or die ("We didn't start the fire, but something went wrong with $visitsql");

//$customers = array();
//while($tmp = mysql_fetch_array($query)){
//    $customers[$tmp['id']] = $tmp;
//}
while($visit = mysql_fetch_array($visitquery)){
    $name = $visit['name'];
    $visits = $visit['visits'];
    echo '<div class="customer col-xs-3"><a href="#" class="customer thumbnail"><div id="name">' . $name . '</div><div id="visits">' . $visits . ' visits</div></a></div>';
}
echo '<div class="customer col-xs-3"><a href="#" class="customer thumbnail"><div id="name"> Add New User</div></a></div>';
?>