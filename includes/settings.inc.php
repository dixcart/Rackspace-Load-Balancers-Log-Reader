<?php
//Enables more verbose messages
DEFINE('DEBUG', false);

//DB Settings
DEFINE('DB_SERVER', 'localhost');
DEFINE('DB_USER', 'lb-logreader');
DEFINE('DB_PASS', 'wibble');
DEFINE('DB_NAME', 'lb-logreader');

//Cloud Files authentication
DEFINE('CF_USER', 'whatever');
DEFINE('CF_KEY', 'yourapikeynotmine');

//Path to store and analyse log files, if Windows use \\ or /
DEFINE('LOG_PATH', "/the/path/to/wherever");

//Set to true to remove from CF when downloaded
DEFINE('DELETE_WHEN_DL', true);
DEFINE('DELETE_EMPTY_CONT', true);

//If set to true, will delete the file, if false will archive in subfolder of logs folder
DEFINE('DELETE_WHEN_PROCESSED', false);
?>