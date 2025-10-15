<?php
$valadez_api='https://cs4743.professorvaladez.com/api/';

function create_session($data) {
    $ch=curl_init($valadez_api.'create_session');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //expecting data back. write exceptions for null data
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'content-type: application/x-www-form-urlencoded',
        'content-length: '.strlen($data))); //expecting data back. write exceptions for null data
}

function request_file($data) {
    $ch=curl_init($valadez_api.'request_file');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'content-type: application/x-www-form-urlencoded',
        'content-length: '.strlen($data)));
}

function close_session($data) {
    $ch=curl_init($valadez_api.'close_session');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'content-type: application/x-www-form-urlencoded',
        'content-length: '.strlen($data)));
}

function query_files($data) {
    $ch=curl_init($valadez_api.'query_files');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'content-type: application/x-www-form-urlencoded',
        'content-length: '.strlen($data)));
}

function get_response_time() {
    $start_time=microtime(true);
    $result=curl_exec($ch); 
    $end_time=microtime(true);
    $exe_time=($end_time-$start_time)/60;
    curl_close($ch);

    $info=json_decode($result);
    echo '<pre>';
    print_r($info);
    echo '</pre>';
    echo '<h2>Execution time: '.$exe_time.'</h2>';
}

function write_file($file){
    $fp=fopen("/var/www/html/files/$file", "wb");
    fwrite($fp, $result);
    fclose($fp);
    echo "<h2>$file written to file system</h2>";
}
?>
