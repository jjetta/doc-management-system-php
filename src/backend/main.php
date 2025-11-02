<?php
require_once __DIR__ . '/helpers/api_helpers.php';

$username = getenv('API_USER');
$password = getenv('API_PASS');

$data = "username=$username&password=$password";
// Create Session Endpoint
$info = api_call('create_session', $data);

/*if ($info[0] == "Status: OK") { // This contains the status response from the API*/
/*    $sid = $info[2]; //contains current active session id*/
/*    $data = "uid=$username&sid=$sid";*/
/**/
/*    //Query Files Endpoint*/
/*    $info = api_call('query_files', $data);*/
/**/
/*    $files = generate_files($info);*/
/**/
/*    foreach ($files as $key=>$value) {*/
/*        $data = "sid=$sid&uid=$username&fid=$value";*/
/**/
/*        // File Request Endpoint*/
/*        api_call('request_file', $data);*/
/**/
/*        $start_time = microtime(true);*/
/*        $result = curl_exec($ch);*/
/*        $end_time = microtime($ch);*/
/*        $exec_time = ($end_time-$start_time)/60;*/
/**/
/*        echo '<pre>';*/
/*        echo $result;*/
/*        echo '</pre>';*/
/**/
/*        //write file to filesystm*/
/*        write_file($file);*/
/*    }*/
/**/
/*    $data = "sid=$sid";*/
/**/
/*    // Close Session Endpoint*/
/*    $info = api_call('close_session', $data);*/
/*}*/
