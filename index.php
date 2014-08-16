<?php

require_once 'backend/html.php';

printHeader();

?>

<div class="container">

      <div class="starter-template">
        <h1>Welcome!</h1>
        <div class="panel panel-default">
            <div class="panel-heading">
              <div class="row">
                <div class="col-lg-6">
                    <input type="text" class="form-control" id="organizationSearch" placeholder="Enter Name" autocomplete="off">
                </div>
              </div>
            </div>
            <div class="panel-body" id="organizationResult">
            </div>
        </div>
      </div>
    </div>

    <div class="modal fade bs-modal-lg" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" id ="modalCloseTop" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title" id="modalTitle"></h4>
            </div>
            <div class="modal-body">
            <span id="organizationID"></span>
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
                </div>
              </div>
            </div>
            <div class="panel-body" id="result">
            </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-info" id="save">Save</button>
              <a href="#" class="btn btn-primary" id="gotoOrganization">Go!</a>
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