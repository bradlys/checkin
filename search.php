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

$sql = "SELECT * FROM customers WHERE name LIKE '%$getstuff%'";
$query = mysql_query($sql) or die ("We didn't start the fire, but something went wrong with $sql");
$visitsql = "SELECT COUNT(*) as visits, id, name FROM checkins WHERE name LIKE '%$getstuff%' GROUP BY name ORDER BY visits DESC";
$visitquery = mysql_query($visitsql) or die ("We didn't start the fire, but something went wrong with $visitsql");

$customers = array();
while($tmp = mysql_fetch_array($query)){
    $customers[$tmp['id']] = $tmp;
}

while($tmp2 = mysql_fetch_array($visitquery)){
    $name = isset($customers[$tmp2['id']]['name']) ? $customers[$tmp2['id']]['name'] : $tmp2['name'];
    echo '<div id="customer"><div id="name">' . $name . '</div><div id="visits">' . $tmp2['visits'] . ' visits</div></div>';
}

?>