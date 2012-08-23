<?php
require_once('includes/settings.inc.php');
require_once('includes/lb_log_parser.php');
require_once('includes/adodb5/adodb.inc.php');
require_once('includes/cloudfiles/cloudfiles.php');

error_reporting(E_ALL); 
ini_set("display_errors", 1);

ini_set('memory_limit', '768M');
printDebugLine("Memory Limit: ".ini_get('memory_limit'));

$DB = NewADOConnection('mysqli');
$DB->Connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
//$DB->debug=1;
//$rsLog = $DB->Execute('SELECT * FROM rawlogs WHERE 0=1');

printDebugLine("path: ".LOG_PATH);

//Path checking
if (!is_dir(LOG_PATH)) mkdir (LOG_PATH);
if (!is_dir(LOG_PATH."archive/")) mkdir (LOG_PATH."archive/");

$logDir = opendir(LOG_PATH);
$logArray = array();
$zipArray = array();

//Download latest from CF
$auth = new CF_Authentication(CF_USER, CF_KEY);
$auth->authenticate();
$conn = new CF_Connection($auth);
$conn->ssl_use_cabundle();
$clist = $conn->get_containers();

printMemoryUsage();

foreach ($clist as $cont) {
    if (preg_match('/^lb_([0-9]*)_([\w-]*)_(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_([0-9]{4})$/', $cont->name)) {
        printDebugLine("Log container name: " . $cont->name);
        $zippedLogs = $cont->get_objects();
        foreach ($zippedLogs as $log) {
            printMemoryUsage();
            //Check if we already have this file
            $val = $DB->GetOne("SELECT FileID FROM downloadedfiles WHERE FileName='". $log->name ."'");
            if ($val) {
                echo date('Y-m-d H:i:s') . " " . $log->name ." already downloaded.\n";
            } else {
                echo date('Y-m-d H:i:s') . " " . "Downloading: " . $log->name."...";
                $result = false;
                $result = $log->save_to_filename(LOG_PATH.str_replace(":", "_", $log->name));
                if ($result) {
                    echo "done.\n";
                    if ($DB->Execute("INSERT INTO downloadedfiles (FileName,DateTime) VALUES ('".$log->name."', NOW());") === false) {
                        echo 'error inserting: '.$DB->ErrorMsg()."\n";
                    }
                } 
                else echo "FAILED!!\n";

            }
            if (DELETE_WHEN_DL) $cont->delete_object($log);
            printMemoryUsage();
        }
        //Deletes the container if it is now empty, only runs on the second check so it doesn't remove the current container, which
        //will have files in on the next check.
        if (DELETE_EMPTY_CONT && $cont->object_count == 0) $conn->delete_container($cont);
    }
}

unset($clist);
unset($conn);
unset($auth);

printMemoryUsage();

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

printMemoryUsage();

//Are there any zips to extract?
$zipCount = count($zipArray);
if ($zipCount > 0) {
    printDebugLine("extracting ". $zipCount . " zip files");
    for($z=0; $z < $zipCount; $z++) {
        $zip = new ZipArchive;
        $contents = "";
        echo date('Y-m-d H:i:s') . " " . $zipArray[$z];
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
            unset($zip);
        } else {
            echo(" failed\n");
        }
        printMemoryUsage();
    }
}

$indexCount = count($logArray);

printDebugLine($indexCount. " logs found");

for($i=0; $i < $indexCount; $i++) {
    //See if the file has already been indexed
    $val = $DB->GetOne("SELECT FileID FROM processedfiles WHERE FileName='". $logArray[$i] ."'");
    if (!$val) {
        //File has not been processed
        printDebugLine("Processing " .$logArray[$i]);
        //Parse the file through the log reader
        $data = new lb_log_parser(LOG_PATH.$logArray[$i]);    
        if($data->file) {
            $output = $data->getData();

            //If the file is good, add it to our processed list and store the file ID
            //TODO: catch if processing fails part way and rollback changes
            $sql = "insert into processedfiles (FileName,DateTime) ";
            $sql .= "values ('$logArray[$i]', NOW())";

            if ($DB->Execute($sql) === false) {
                printDebugLine('error inserting: '.$DB->ErrorMsg());
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
            if (DELETE_WHEN_PROCESSED) {
                unlink(LOG_PATH.$logArray[$i]);
            } else {
                rename(LOG_PATH.$logArray[$i], LOG_PATH."archive/".$logArray[$i]);
            }
        } else {
            printDebugLine( "ERROR: Unable to read file");
        }        
    }
    else {
        printDebugLine( "$logArray[$i] was previously processed");
    }
    printMemoryUsage();
}

function formatBytes($bytes, $precision = 2)
{
    $units = array(
        'B',
        'KB',
        'MB',
        'GB',
        'TB'
    );

    $bytes = max($bytes, 0);
    $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow   = min($pow, count($units) - 1);

    // Uncomment one of the following alternatives
    // $bytes /= pow(1024, $pow);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

function printMemoryUsage()
{
    if(DEBUG) printDebugLine("Memory Usage: " . formatBytes(memory_get_usage(false)) . "/" . formatBytes(memory_get_usage(true)));
}

function printDebugLine($message) {
    echo date('Y-m-d H:i:s') . " " . $message . "\n";
}

?>
