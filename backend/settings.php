<?php

include_once "database.php";
/**
 * This file contains various settings but also includes
 * the database settings (A requirement to get do any SQL queries). 
 * The reason for the files being separate is that the settings.php 
 * file will be changed frequently upon system enhancements. However, 
 * the database.php file will always remain the same.
 */

//Is this a production server? This controls some debugging options for dev.
define('PRODUCTION_SERVER', false);
