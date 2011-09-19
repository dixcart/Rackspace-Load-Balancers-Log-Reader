<?php
require_once 'includes/lb_log_parser.php';

$filename = (isset($_GET['file']))? $_GET['file']:null;

if ($filename) {
    
    $data = new lb_log_parser($filename);    
    if($data->file) {
        $output = $data->getData();
    } else {
        $output = array("error" => "Unable to read file");
    }
} else {
    $output = array("error" => "No filename specified");
}

echo json_encode($output);

?>
