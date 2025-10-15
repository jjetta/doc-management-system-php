<?php
$username=API_USER;
$password=API_PASS;
$data="username=$username&password=$password";

$ch=curl_init($valadez_api.'create_session');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //expecting data back. write exceptions for null data
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'content-type: application/x-www-form-urlencoded',
    'content-length: '.strlen($data))); //expecting data back. write exceptions for null data

//get time stamp for the current response time
$start_time=microtime(true);
$result=curl_exec($ch); //executs PHP curl command
$end_time=microtime(true);
$exe_time=($end_time-$start_time)/60;
curl_close($ch);

$info=json_decode($result);
echo '<pre>';
print_r($info);
echo '</pre>';
echo '<h2>Execution time: '.$exe_time.'</h2>';
if ($info[0]=="Status: OK") // This contains the status response from the API
{
    $sid=$info[2]; //contains current active session id
    $data="uid=$username&sid=$sid";
    $ch=curl_init($valadez_api.'query_files');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'content-type: application/x-www-form-urlencoded',
        'content-length: '.strlen($data)));

    $start_time=microtime(true);
    $result=curl_exec($ch); //executs PHP curl command
    $end_time=microtime(true);
    $exe_time=($end_time-$start_time)/60;
    curl_close($ch);

    $info=json_decode($result);
    echo '<pre>';
    print_r($info);
    echo '</pre>';
    echo '<h2>Execution time: '.$exe_time.'</h2>';

    $tmp=explode(":", $info[1]);
    $files=json_decode($tmp[1]);
    $files=$info[1]; //contains files generated

    echo '<h2>Number of files received: '.count($files).'</h2>';
    echo '<pre>';
    print_r($files);
    echo '<pre>';
    echo '<hr>';
    echo '<h2>Downloading Files</h2>';
    foreach ($files as $key=>$value)
    {
        $data="sid=$sid&uid=$username&fid=$value";
        $ch=curl_init($valadez_api.'request_file');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'content-type: application/x-www-form-urlencoded',
            'content-length: '.strlen($data)));
        $start_time=microtime(true);
        $result=curl_exec($ch);
        $end_time=microtime($ch);
        $exec_time=($end_time-$start_time)/60;
        echo '<pre>';
        echo $result;
        echo '</pre>';
        //write file to filesystm
        $fp=fopen("/var/www/html/files/$file", "wb");
        fwrite($fp, $result);
        fclose($fp);
        echo "<h2>$file written to file system</h2>";
    }

    $data="sid=$sid";
    $ch=curl_init($valadez_api.'close_session');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'content-type: application/x-www-form-urlencoded',
        'content-length: '.strlen($data)));

    $start_time=microtime(true);
    $result=curl_exec($ch); //executs PHP curl command
    $end_time=microtime(true);
    $exe_time=($end_time-$start_time)/60;
    curl_close($ch);

    $info=json_decode($result);
    echo '<pre>';
    print_r($info);
    echo '</pre>';
    echo '<h2>Execution time: '.$exe_time.'</h2>';
}
else 
{
    
}
?>
