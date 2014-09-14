<?php
/**
 * This page is displaying events that the organization has created, creating new events for that organization,
 * editing events that already exist, and going to those events to do check-in's.
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
                    <input type="text" class="form-control" id="eventSearch" placeholder="Search For A Dance" autocomplete="off">
                </div>
              </div>
            </div>
            <?php
            if($id){
            ?>
            <div class="panel-body" id="eventResultArea">
                <p class="lead" id="nonefound">No events were found!</p>
                <input type='hidden' id='organizationID' value='<?=$id?>'>
            </div>
        </div>
      </div>
      <?php
            }
            else{
                ?>
                <div class="panel-body">
                    <p class="lead has-warning">I don't know how you got here, but you did it wrong!</p>
                </div>
                <?php
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
            <span id="eventID"></span>
            <div class="panel panel-default">
            <div class="panel-heading">
              <div class="row">
                <div class="col-sm-6">
                  <label for="modalName">Name</label>
                  <div class="input-group">
                    <input type="text" class="form-control modalNameEmailInput" id="modalName" autofocus="" required="" placeholder="Enter Name" autocomplete="off" />
                    <span class="input-group-addon" />
                  </div>
                </div>
                <div class="col-sm-6">
                  <label for="modalDate">Date</label>
                  <div class='input-group date' id="modalDate">
                    <input type='text' class="form-control" id="modalDateForm" data-date-pickTime="false" />
                    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="panel-body" id="result">
            <div class="control-group" id="fields">
            <div class="controls">
            <legend>Costs</legend>
            <form role="form" autocomplete="off">
                <div class="entry input-group col-sm-6 costFieldGroup">
                    <input class="costField" name="fields[]" type="text" placeholder="" />
                    <input class="costField" name="fields[]" type="text" placeholder="" />
                    <span class="input-group-btn">
                        <button class="btn btn-success btn-add" type="button">
                            <span class="glyphicon glyphicon-plus"></span>
                        </button>
                    </span>
                </div>
            </form>
            </div>
            </div>
            </div>
            </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-info" id="save">Save</button>
              <a href="#" class="btn btn-primary" id="gotoEvent">Go!</a>
            </div>
          </div>
        </div>
      </div>

<?php
printFooter();
?>