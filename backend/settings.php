<?php

include_once "database.php";
require_once 'misc.php';
/**
 * This file contains various settings but also includes
 * the database settings (A requirement to get do any SQL queries). 
 * The reason for the files being separate is that the settings.php 
 * file will be changed frequently upon system enhancements. However, 
 * the database.php file will always remain the same.
 */

//Is this a production server? This controls some debugging options for dev.
define('PRODUCTION_SERVER', false);

/*
 * Below are some lengths of some database columns
 * such as: the column name in the organizations table
 * which has a max length of 127.
 */
define('TABLE_MAX_ORGANIZATION_NAME_LENGTH', 127);
define('TABLE_MAX_ORGANIZATION_EMAIL_LENGTH', 127);
define('TABLE_MAX_EVENT_NAME_LENGTH', 127);
define('TABLE_MAX_CUSTOMER_NAME_LENGTH', 127);
define('TABLE_MAX_CUSTOMER_EMAIL_LENGTH', 127);