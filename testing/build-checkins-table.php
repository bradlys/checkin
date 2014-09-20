<?php

require_once '../backend/search.php';

/**
for($i = 0; $i < 100000; $i++){
    $payment = rand(1, 10);
    $cid = rand(1, 76076);
    $eventid = rand(1, 52);
    mysql_query("INSERT INTO checkins VALUES(NULL, '$cid', '$eventid', '$payment', '1', CURRENT_TIMESTAMP)") or die(mysql_error());
    echo "<br/>" . mysql_insert_id();
}