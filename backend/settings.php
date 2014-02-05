<?php

define('DB_USER', 'root');
define('DB_PASS', 'pickels');
define('DB_NAME', 'checkinapp');
$mysql_access = mysql_connect('vergil.u.washington.edu:3555', DB_USER, DB_PASS, true) or die ('Unable to connect to the Database');
mysql_select_db(DB_NAME, $mysql_access) or die ('Error: unable to select database:' . mysql_error());
mysql_query('SET CHARACTER SET utf8');

?>
