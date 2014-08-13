<?php

define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'somedatabase');
$mysql_access = mysql_connect('a_host', DB_USER, DB_PASS, true) or die ('Unable to connect to the Database');
mysql_select_db(DB_NAME, $mysql_access) or die ('Error: unable to select database:' . mysql_error());
mysql_query('SET CHARACTER SET utf8');

?>