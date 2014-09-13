<?php

/**
 * Prints the HTML Header (And, at this point, some body stuff too)
 */
function printHeader(){
    $checkinactive = false;
    $checkinappactive = false;
    if(basename($_SERVER['PHP_SELF']) == 'checkin.php'){
        $checkinactive = true;
    }
    if(basename($_SERVER['PHP_SELF']) == 'index.php'){
        $checkinappactive = true;
    }
    if(isset($_GET['id'])){
        $id = $_GET['id'];
    }
    else{
        $id = '';
    }
    
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Dance Check-in App">
    <meta name="author" content="Bradly Schlenker">
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
          <a class="navbar-brand" href="index.php">Check-in App</a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="<?=$checkinactive ? 'active' : '' ?>"><a href="<?= $checkinappactive ? '' : "checkin.php?id=$id"?>">Check-in</a></li>
            <li><a href="<?= $checkinappactive ? '' : "about.php?id=$id"?>">About</a></li>
            <li><a href="<?= $checkinappactive ? '' : "contact.php?id=$id"?>">Contact</a></li>
          </ul>
        </div>
      </div>
    </div>
<?php

}

/**
 * Prints the HTML Footer.
 */
function printFooter() {

?>
    <script type="text/javascript" src="js/jquery-2.1.0.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/underscore.js"></script>
    <script type="text/javascript" src="js/moment-with-locales.min.js"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.min.js"></script>
    <script type="text/javascript" src="js/script.js"></script>
  </body>
</html>
<?php
}
?>