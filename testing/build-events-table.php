<?php

require_once '../backend/event.php';
require_once '../backend/misc.php';
PRODUCTION_SERVER ? die() : "";

$id = isInteger($_GET['id']) ? $_GET['id'] : 0;
$date = time()-31536000;
if($_GET['ignite'] == 'true' && $id > 0){
    for($i = 1; $i < 53; $i++){
        $dateString = date('Y-m-d H:i:s', $date);
        $eventName = "Event $i";
        editEvent("", $dateString, "", $eventName, $id);
        echo "<br/>inserted event ID: $i; event name: $eventName; date: $dateString;";
        $date += 604800;
    }
}