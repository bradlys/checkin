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
              </div>
            </div>
            <div class="panel-body" id="result">
                <p class="lead">Head on over to <a href="events.php?id=1">events.php?id=1</a></p>
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