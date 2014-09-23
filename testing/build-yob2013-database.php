<?php
/**
 * No longer correct in functionality.
 * It used to build a lage database using the names of every child born in the year 2013
 * with an occurrence of the name at least 5 times.
 */
/**
require_once '../backend/settings.php';
$charactersFile = fopen("yob2013.txt", "r");
echo "here we are <br/>";
while(! feof($charactersFile)){
    $curline = fgets($charactersFile);
    $name = mysql_real_escape_string(trim($curline));
    echo "inserting $name <br/>";
    $email = str_replace(" ", "", $name) . "@gmail.com";
    $sql = "INSERT INTO customers VALUES ('', '$name', '$email', NULL, '1', CURRENT_TIMESTAMP)";
    $query = mysql_query($sql) or die("problems with $sql");
    $insert_id = mysql_insert_id();
    $randomint = mt_rand(0, 1411022416);
    $date = mysql_real_escape_string(date("m/d/Y", $randomint));
    $sql = "INSERT INTO customerAttributes VALUES (NULL, '$insert_id', 'Customer Birthday', '$date', '1', CURRENT_TIMESTAMP)";
    $query = mysql_query($sql) or die("problems with $sql");
    $sql = "INSERT INTO customerAttributes VALUES (NULL, '$insert_id', 'Free Entrances', '0', '1', CURRENT_TIMESTAMP)";
    $query = mysql_query($sql) or die("problems with $sql");
}
fclose($charactersFile);

