<?php

require_once 'backend/html.php';
require_once 'backend/organization.php';
require_once 'backend/checkin.php';
require_once 'backend/settings.php';
require_once 'backend/event.php';

printHeader();

if(isset($_GET['id'])){
    $id = $_GET['id'];
    if(!isInteger($id) || $id < 1){
        echo "Need a positive integer for id";
        exit;
    }
}

$organizationID = $id;

$isFreeEntranceEnabled = isFreeEntranceEnabled($organizationID);

?>
<style type="text/css">

svg {
    font-family: "Helvetica Neue", Helvetica;
}

.line {
    fill: none;
    stroke: #000;
    stroke-width: 2px;
}

</style>
<div class="container">
    <div class="starter-template">
        <h1><span id="organizationName"></span></h1>
        <span id="organizationID"><?=$organizationID?></span>
        <span id="organizationStatistics"></span>
        
        
        
        
        
        
        
        
    </div>
</div>
<?php

printFooter();