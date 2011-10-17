<?php
require_once('includes/settings.inc.php');
require_once('includes/lb_log_parser.php');
require_once('includes/adodb5/adodb.inc.php');

error_reporting(E_ALL); 
ini_set("display_errors", 1);


$DB = NewADOConnection('mysql');
$DB->Connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
//$DB->debug=1;
//$rsLog = $DB->Execute('SELECT * FROM rawlogs WHERE 0=1');

echo "path: ".LOG_PATH."\n";

//Path checking
if (!is_dir(LOG_PATH)) mkdir (LOG_PATH);
if (!is_dir(LOG_PATH."archive/")) mkdir (LOG_PATH."archive/");

$logDir = opendir(LOG_PATH);
$logArray = array();
$zipArray = array();

//Scan the directory for files and put the zip files in one array and the no extension files (logs) in another
while($entryName = readdir($logDir)) {
    if ($entryName != "." && $entryName != ".." && $entryName != "archive") {
        switch (pathinfo(LOG_PATH.$entryName, PATHINFO_EXTENSION)) {
            case "zip":
                $zipArray[] = $entryName;
                break;
            case "":
                $logArray[] = $entryName;
                break;
        }
    }
}

closedir($logDir);

//Are there any zips to extract?
$zipCount = count($zipArray);
if ($zipCount > 0) {
    echo "extracting ". $zipCount . " zip files\n";
    for($z=0; $z < $zipCount; $z++) {
        $zip = new ZipArchive;
        $contents = "";
        echo $zipArray[$z];
        if ($zip->open(LOG_PATH.$zipArray[$z]) === TRUE) {
            //Rackspace saves the file with a : in the filename
            //Windows hates this, but PHP allows it in some cases, leaving the file
            //sort of readable in some cases but not always
            //So grab a stream of the file and save it to a good filename
            $originalName = $zip->getNameIndex(0);
            $fixedName = str_replace(":", "_", $originalName);
            $fp = $zip->getStream($originalName);
            
            while (!feof($fp)) {
                $contents .= fread($fp, 2);
            }

            fclose($fp);
            file_put_contents(LOG_PATH.$fixedName, $contents);            
            
            $zip->close();
            unlink(LOG_PATH.$zipArray[$z]);
            $logArray[] = $fixedName;
            echo " ok\n";
        } else {
            echo " failed\n";
        }
    }
}

$indexCount = count($logArray);

echo $indexCount. " logs found\n";

for($i=0; $i < $indexCount; $i++) {
    //See if the file has already been indexed
    $val = $DB->GetOne("SELECT ID FROM processedfiles WHERE FileName='". $logArray[$i] ."'");
    if (!$val) {
        //File has not been processed
        echo "Processing " .$logArray[$i]. "\n";
        //Parse the file through the log reader
        $data = new lb_log_parser(LOG_PATH.$logArray[$i]);    
        if($data->file) {
            $output = $data->getData();

            //If the file is good, add it to our processed list and store the file ID
            //TODO: catch if processing fails part way and rollback changes
            $sql = "insert into processedfiles (FileName,DateTime) ";
            $sql .= "values ('$logArray[$i]', NOW())";

            if ($DB->Execute($sql) === false) {
                echo 'error inserting: '.$DB->ErrorMsg()."\n";
            }
            
            $fileRowID = $DB->Insert_ID();
            
            $rsLog = $DB->Execute('SELECT * FROM rawlogs WHERE 0=1');
            
            //Loop through the log records and insert into DB
            foreach($output as $logRow) {
                $record = $logRow;
                $record["FileID"] = (int)$fileRowID;
                
                $sql = "INSERT INTO rawlogs (FileID, balancerid, host, ip, identity, user, date, time, timezone, method, path, protocol, status, bytes, referrer, agent) ".
                        "VALUES (" .
                        $record["FileID"] .", " .
                        "'".$record["balancerid"]."', ".
                        "'".$record["host"]."', ".
                        "'".$record["ip"]."', ".
                        "'".$record["identity"]."', ".
                        "'".$record["user"]."', ".
                        "'".date_format($record["date"], 'Y-m-d')."', ".
                        "'".$record["time"]."', ".
                        "'".$record["timezone"]."', ".
                        "'".$record["method"]."', ".
                        "'".$record["path"]."', ".
                        "'".$record["protocol"]."', ".
                        $record["status"] .", " .
                        $record["bytes"] .", " .
                        "'".$record["referrer"]."', ".
                        "'".$record["agent"]."');";
                
                $DB->Execute($sql);
                //$insertSQL = $DB->AutoExecute("rawlogs", $record, 'INSERT');
                //echo "sql=".$insertSQL."\n";
            }
        } else {
            echo "ERROR: Unable to read file\n";
        }        
    }
    else {
        echo "$logArray[$i] was found\n";
        rename(LOG_PATH.$logArray[$i], LOG_PATH."archive/".$logArray[$i]);
    }
}

?>
