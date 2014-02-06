<?php


function printHeader(){
    if(basename($_SERVER['PHP_SELF']) == 'checkin.php'){
        $active = true;
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
          <a class="navbar-brand" href="index.php">Check-in App</a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="<?=$active ? 'active' : '' ?>"><a href="checkin.php?id=<?=$id?>">Check-in</a></li>
            <li><a href="about.php?id=<?= $id ?>">About</a></li>
            <li><a href="contact.php?id=<?= $id ?>">Contact</a></li>
          </ul>
        </div>
      </div>
    </div>
<?    
    
}

function printFooter() {
    
}

?>