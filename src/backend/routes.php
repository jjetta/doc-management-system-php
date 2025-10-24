<?php
$valadez_api='https://cs4743.professorvaladez.com/api/';

function api_call($endpoint, $data) {
    global $valadez_api;

    $ch = curl_init($valadez_api . $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //expecting data back. write exceptions for null data
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded',
        'Content-Length: '.strlen($data))); //expecting data back. write exceptions for null data

    $start_time = microtime(true);
    $response = curl_exec($ch);
    $end_time = microtime(true);
    $exe_time = ($end_time - $start_time) / 60;

    curl_close($ch);

    $info = json_decode($response);
    echo '<pre>';
    print_r($info);
    echo '</pre>';
    echo '<h2>Execution time: '.$exe_time.'</h2>';

    return $info;
}

function write_file($file){
    $fp = fopen("/var/www/html/files/$file", "wb");
    fwrite($fp, $result);
    fclose($fp);
    echo "<h2>$file written to file system</h2>";
}

function generate_files($info) {
    $tmp = explode(":", $info[1]);
    $files = json_decode($tmp[1]);
    $files = $info[1]; //contains files generated

    echo '<h2>Number of files received: '.count($files).'</h2>';
    echo '<pre>';
    print_r($files);
    echo '<pre>';
    echo '<hr>';
    echo '<h2>Downloading Files</h2>';

    return $files;
}
?>
