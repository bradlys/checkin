<?php

require_once '../backend/event.php';
require_once '../backend/misc.php';

$id = isInteger($_GET['id']) ? $_GET['id'] : 0;
$date = time()-31536000;
if($_GET['ignite'] == 'true' && $id > 0){
    for($i = 1; $i < 53; $i++){
        editEvent("", date('Y-m-d H:i:s', $date), "", "Event " . $i, $id);
        echo "<br/>inserted event $i";
        $date += 604800;
    }
}