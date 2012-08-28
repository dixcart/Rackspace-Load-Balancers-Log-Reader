<?php
require_once('../includes/settings.inc.php');
require_once('../includes/adodb5/adodb.inc.php');

$DB = NewADOConnection('mysqli');
$DB->Connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

$rawlogs = $DB->GetOne("SELECT COUNT(LogID) FROM rawlogs");
$downloads = $DB->GetOne("SELECT COUNT(FileID) FROM downloadedfiles");
$processed = $DB->GetOne("SELECT COUNT(FileID) FROM processedfiles");
$lastfile = $DB->GetOne("SELECT FileName FROM processedfiles ORDER BY DateTime DESC LIMIT 1");

echo "{ \"rawlogs\": $rawlogs, \"downloads\": $downloads, \"processed\": $processed, \"lastfile\": \"$lastfile\" }";

?>