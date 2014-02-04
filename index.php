<?php
/**
 * 
 * @author Bradly Schlenker
 */
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="/checkin/ico/favicon.ico">

    <title>Check-in App</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/starter-template.css" rel="stylesheet">
  </head>

  <body>

    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">Check-in App</a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="#">Check-in</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#contact">Contact</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="container">

      <div class="starter-template">
        <h1>Check-in App</h1>
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                <div class="col-lg-6">
                  <div class="input-group">
                    <input type="text" class="form-control" id="search" placeholder="Enter Name" autocomplete="off">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button" data-toggle="modal" data-target="#myModal">Go!</button>
                    </span>
                  </div>
                </div>
              </div>

            </div>
            <div class="panel-body" id="result">
                <p class="lead" id="beforefound">Go ahead and try searching for a user!</p>
                <p class="lead" id="nonefound">No users were found!</p>
            </div>
        </div>
      </div>
      
      <div class="modal fade bs-modal-lg" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title" id="modaltitle"></h4>
            </div>
            <div class="modal-body">
            <div class="panel panel-default">
            <div class="panel-heading">
              <div class="row">
                <div class="col-sm-6">
                  <div class="input-group-sm">
                    <div class="panel-heading modalNameEmail">Name</div>
                    <input type="text" class="form-control modalNameEmailInput" id="modalName" placeholder="Enter Name" autocomplete="off">
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="input-group-sm">
                      <div class="panel-heading modalNameEmail">Email</div>
                    <input type="text" class="form-control" id="modalEmail" placeholder="Enter Email" autocomplete="off">
                  </div>
                </div>
              </div>
            </div>
            <div class="panel-body" id="result">
                <div class="panel panel-default col-sm-3 paymentbox">
                    <div class="panel-heading">Payment</div>
                    <div class="panel-footer">
                    <form class="form-horizontal" role="form">
                    <div class="form-group payment">
                    <div class="btn-group">
                        <div class="col-sm-2 customMoney">
                        <input type="text" class="form-control" id="modalMoney" placeholder="$XX" autocomplete="off">
                        </div>
                        <button type="button" class="btn btn-default modalMoneyClearer">$0</button>
                        <button type="button" class="btn btn-default modalMoneyClearer">$3</button>
                    </div>
                    <br/>
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
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary">Save & Checkin</button>
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