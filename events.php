<?php
/**
 * 
 * @author Bradly Schlenker
 */

require_once 'backend/html.php';

if(isset($_GET['id'])){
    $id = $_GET['id'];
}

printHeader();
?>

    <div class="container">

      <div class="starter-template">
        <h1>Events for <span id="organizationName"></span></h1>
        <div class="panel panel-default">
            <div class="panel-heading">
              <div class="row">
                <div class="col-lg-6">
                    <input type="text" class="form-control" id="eventSearch" placeholder="Enter Name" autocomplete="off">
                </div>
              </div>
            </div>
            <?
            if($id){
            ?>
            <div class="panel-body" id="eventResult">
                <p class="lead" id="nonefound">No events were found!</p>
                <input type='hidden' id='eventID' value='<?=$id?>'>
            </div>
        </div>
      </div>
      <?
            }
            else{
                ?>
                <div class="panel-body">
                    <p class="lead has-warning">I don't know how you got here, but you did it wrong!</p>
                </div>
                <?
            }
      ?>
      <div class="modal fade bs-modal-lg" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" id ="modalCloseTop" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title" id="modalTitle"></h4>
            </div>
            <div class="modal-body">
            <span class="cid"></span>
            <div class="panel panel-default">
            <div class="panel-heading">
              <div class="row">
                <div class="col-sm-6">
                  <div class="input-group-sm">
                    <div class="panel-heading modalNameEmail">Name</div>
                    <input type="text" class="form-control modalNameEmailInput" id="modalName" autofocus="" required="" placeholder="Enter Name" autocomplete="off">
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="input-group-sm">
                      <div class="panel-heading modalNameEmail">Email</div>
                    <input type="email" class="form-control modalNameEmailInput" id="modalEmail" autofocus="" required="" placeholder="Enter Email" autocomplete="off">
                  </div>
                </div>
              </div>
            </div>
            <div class="panel-body" id="result">
                <div class="panel panel-default col-sm-3 paymentbox">
                    <span id="paymentAmount"></span>
                    <div class="panel-heading">Payment</div>
                    <div class="panel-footer">
                    <form class="form-horizontal" role="form">
                    <div class="form-group paymentArea">
                    <div class="btn-group">
                        <div class="col-sm-2 customMoney">
                        <input type="text" class="form-control" id="modalMoney" placeholder="$XX" autocomplete="off">
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
            </div>
            </div>
            <div class="modal-footer">
              <button type="button" id ="modalCloseBot" class="btn btn-default" data-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary" id="save">Save & Checkin</button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <script type="text/javascript" src="js/jquery-2.1.0.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/script.js"></script>
  </body>
</html>
<?
printFooter();
?>