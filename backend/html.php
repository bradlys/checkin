<?php

include_once 'settings.php';

/**
 * Prints the HTML Header (And, at this point, some body stuff too)
 */
function printHeader(){
    $checkinactive = false;
    $firstliActive = false;
    $secondliActive = false;
    $thirdliActive = false;
    $indexPage = false;
    $eventsPage = false;
    $basename = basename($_SERVER['PHP_SELF']);
    if($basename == 'checkin.php'){
        $checkinactive = true;
        $firstliActive = true;
    }
    if($basename == 'checkin-about.php'){
        $checkinactive = true;
        $secondliActive = true;
    }
    if($basename == 'checkin-contact.php'){
        $checkinactive = true;
        $thirdliActive = true;
    }
    if($basename == 'organization-about.php'){
        $secondliActive = true;
        $eventsPage = true;
    }
    if($basename == 'organization-contact.php'){
        $thirdliActive = true;
        $eventsPage = true;
    }
    if($basename == 'index.php'){
        $indexPage = true;
    }
    if($basename == 'events.php'){
        $eventsPage = true;
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
    <link rel="shortcut icon" href="ico/favicon.ico">

    <title>Check-in App</title>

    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet">
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
            <?php
            if(!$indexPage){
                if(!$eventsPage){
                ?>
            <li class="<?= $firstliActive ? 'active' : '' ?>"><a href="<?= "checkin.php?id=$id" ?>"><?= "Check-in" ?></a></li>
            <?php
                }
            ?>
            <li class="<?= $secondliActive ? 'active' : '' ?>"><a href="<?= $indexPage ? 'about.php' : ($checkinactive ? "checkin-about.php?id=$id" : "organization-about.php?id=$id") ?>">About<?= $checkinactive ? " Event" : " Organization" ?></a></li>
            <li class="<?= $thirdliActive ? 'active' : '' ?>"><a href="<?= $indexPage ?  'contact.php' : ($checkinactive ? "checkin-contact.php?id=$id" : "organization-contact.php?id=$id") ?>">Contact</a></li>
            <?php
            }
            ?>
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
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery<?= PRODUCTION_SERVER ? ".min" : "" ?>.js"></script>
    <script type="text/javascript" src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.7.0/underscore<?= PRODUCTION_SERVER ? "-min" : "" ?>.js"></script>
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.3/moment-with-locales<?= PRODUCTION_SERVER ? ".min" : "" ?>.js"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.min.js"></script>
    <script type="text/javascript" src="js/checkinapp.js"></script>
  </body>
</html>
<?php
}
?>