<?php
require_once('includes/settings.inc.php');
require_once('includes/lb_log_parser.php');
require_once('includes/adodb5/adodb.inc.php');

$DB = NewADOConnection('mysql');
$DB->Connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
//$DB->debug=1;
//$rsLog = $DB->Execute('SELECT * FROM rawlogs WHERE 0=1');

echo "path: ".LOG_PATH."\n";

$logDir = opendir(LOG_PATH);

while($entryName = readdir($logDir)) {
    if ($entryName != "." && $entryName != "..") $logArray[] = $entryName;
}

closedir($logDir);

$indexCount = count($logArray);

echo $indexCount. " logs found\n";

for($i=0; $i < $indexCount; $i++) {
    //See if the file has already been indexed
    $val = $DB->GetOne("SELECT ID FROM processedfiles WHERE FileName='". $logArray[$i] ."'");
    if (!$val) {
        //File has not been processed
        echo "Processing " .$logArray[$i]. "\n";
        $data = new lb_log_parser(LOG_PATH.$logArray[$i]);    
        if($data->file) {
            $output = $data->getData();

            $sql = "insert into processedfiles (FileName,DateTime) ";
            $sql .= "values ('$logArray[$i]', NOW())";

            if ($DB->Execute($sql) === false) {
                echo 'error inserting: '.$DB->ErrorMsg()."\n";
            }
            
            $fileRowID = $DB->Insert_ID();
            
            $rsLog = $DB->Execute('SELECT * FROM rawlogs WHERE 0=1');
            
            foreach($output as $logRow) {
                $record = $logRow;
                $record["FileID"] = (int)$fileRowID;
                $insertSQL = $DB->AutoExecute("rawlogs", $record, 'INSERT');
                //echo "sql=".$insertSQL."\n";
            }
        } else {
            echo "ERROR: Unable to read file\n";
        }        
    }
    else {
        echo "$logArray[$i] was found\n";
    }
}

?>
