<?php

require_once 'backend/html.php';
require_once 'backend/organization.php';
require_once 'backend/checkin.php';
require_once 'backend/settings.php';
require_once 'backend/event.php';

printHeader();

if(isset($_GET['id']) && isInteger($_GET['id']) && $_GET['id'] > 0){
    $eventID = mysql_real_escape_string($_GET['id']);
} else {
    die;
}

$isFreeEntranceEnabled = isFreeEntranceEnabled(inferOrganizationID($eventID));
$eventName = getEventName($eventID);
$eventCheckins = getEventCheckins($eventID);
$eventCheckinsCount = count($eventCheckins)

?>
<div class="container">
    <h1><span id="eventName"><?=$eventName?>, Check-ins: <?=$eventCheckinsCount?></span></h1>
    <span id="eventID"><?=$eventID?></span>
        <?php
if($eventCheckins){
    echo "<div class='table-responsive'><table class='table table-striped table-hover' id='eventCheckinsTable'>";
    echo "<thead><tr><th>Checkin ID</th><th>Name</th><th>Payment</th><th>Timestamp</th><th>Customer ID</th></tr></thead>";
    echo "<tbody>";
    for($i = 0; $i < $eventCheckinsCount && $i < 500; $i++){
        echo "<tr class='eventCheckinsTableCustomerRow"
        . ($eventCheckins[$i]['status'] == "1" ? "" : " danger") . "' id='"
        . $eventCheckins[$i]['id'] . "'><td class='checkinID'>"
        . $eventCheckins[$i]['id'] . "</td><td class='customerName'>"
        . $eventCheckins[$i]['name'] . "</td><td class='customerPayment'>"
        . $eventCheckins[$i]['payment'] . "</td><td class='customerTimestamp'>"
        . $eventCheckins[$i]['timestamp'] . "</td><td class='customerID'>"
        . $eventCheckins[$i]['cid'] . "</td></tr>";
    }
    echo "</tbody></table></div>";
} else {
    echo "Nothing to display";
}
    ?>
</div>

<div class="modal fade bs-modal-lg" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" id ="modalCloseTop" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title" id="modalTitle"></h4>
        </div>
        <div class="modal-body">
            <span class="cid"></span><span id="modalCheckinID"></span>
        <div class="panel panel-default">
        <div class="panel-heading">
          <div class="row">
            <div class="col-sm-6">
              <div class="input-group-sm">
                <div class="panel-heading modalNameEmail">Name</div>
                <input type="text" class="form-control modalNameEmailInput" id="modalName" autofocus="" required="" placeholder="Enter Name" autocomplete="off" />
              </div>
            </div>
            <div class="col-sm-6">
              <div class="input-group-sm">
                  <div class="panel-heading modalNameEmail">Email</div>
                <input type="email" class="form-control modalNameEmailInput" id="modalEmail" autofocus="" required="" placeholder="Enter Email" autocomplete="off" />
              </div>
            </div>
          </div>
        </div>
        <div class="panel-body" id="result">
            <div class="panel panel-default col-sm-3 paymentBox">
                <div class="panel-heading">Payment</div>
                <div class="panel-footer">
                <form class="form-horizontal" role="form">
                <div class="form-group paymentArea">
                <div class="btn-group">
                    <div class="col-sm-2 customMoney">
                    <input type="text" class="form-control" id="modalMoney" placeholder="$XX" autocomplete="off" />
                    </div>
                    <button type="button" class="btn btn-default modalMoneyClearer">$0</button>
                    <button type="button" class="btn btn-default modalMoneyClearer">$3</button>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-default modalMoneyClearer">$5</button>
                    <button type="button" class="btn btn-default modalMoneyClearer">$6</button>
                    <button type="button" class="btn btn-default modalMoneyClearer">$7</button>
                    <button type="button" class="btn btn-default modalMoneyClearer">$8</button>
                </div>
                </div>
                </form>
                </div>
            </div>
            <div class="col-sm-3">
              <label for="modalDate">Birthday</label>
              <div class='input-group date' id="modalDate">
                <input type='text' class="form-control" id="modalDateForm" data-date-pickTime="false" />
                <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
              </div>
            </div>
            <?php
            if($isFreeEntranceEnabled){ ?>
            <div class="panel panel-default col-sm-3 paymentOptionsBox">
                <div class="form-group paymentOptionsArea">
                    <div class="checkbox useFreeEntrance">
                        <label>
                            <input type="checkbox" id="useFreeEntrance" /> Use Free Entrance
                        </label>
                    </div>
                    <div class="numberOfFreeEntrances">
                        <label for="numberOfFreeEntrances">Number Of Free Entrances</label>
                        <input type="text" class="form-control" id="numberOfFreeEntrances" placeholder="0" />
                    </div>
                </div>
            </div>
            <?php
            }
            ?>
        </div>
        </div>
        <div class="modal-footer">
          <button type="button" id="modalCheckout" class="btn btn-danger">Checkout</button>
          <button type="button" id ="modalCloseBot" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="save">Save & Checkin</button>
        </div>
      </div>
    </div>
    </div>

</div>
<?php

printFooter();