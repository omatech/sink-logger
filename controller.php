<?php
ini_set("session.gc_maxlifetime","7200");
ini_set("session.cookie_lifetime","7200");
session_start();
// start of buffer, gzipped
ob_start('ob_gzhandler');

header('Content-Type: application/json; charset=UTF-8', true);
setlocale(LC_TIME, "es_ES.UTF-8");

$autoload_location = '/vendor/autoload.php';
$tries=0;
while (!is_file(__DIR__.$autoload_location))
{
 $autoload_location='/..'.$autoload_location;
 $tries++;
 if ($tries>10) die("Error trying to find autoload file\n");
}
require_once __DIR__.$autoload_location;

require_once('conf/config.php');

ini_set("memory_limit", "9000M");
set_time_limit(0);
$start_microtime=microtime(true);

use Omatech\Editora\Utils\Sanitizer;
use Omatech\Editora\Utils\Ips;


//require_once (__DIR__.'/utils/sanitize.php');

//prevencion de sqli + xss
$sanitized=0;
foreach($_REQUEST as $key => $value) {
	$initial_value=$_REQUEST[$key];
    $_REQUEST[$key] = Sanitizer::sanitize($_REQUEST[$key]);
	$_REQUEST[$key] = htmlspecialchars($_REQUEST[$key]);
	if ($_REQUEST[$key]!=$initial_value) $sanitized=1;
}

$response_code=200;
$result_code='ok';
$path=$_SERVER['REQUEST_URI'];
$host=$_SERVER['HTTP_HOST'];
$method=$_SERVER['REQUEST_METHOD'];
$user_agent=$_SERVER['HTTP_USER_AGENT'];

if ($path=='/favicon.ico')
{
	$result_code='ignored';
	echo json_encode($result);
	die;
}

$result=[];
$result['result']=$result_code;
$result['code']=$response_code;
$result['host']=$host;
$result['path']=$path;
$result['method']=$method;
$result['user_agent']=$user_agent;

$result['sanitized']=$sanitized;
$ip=Ips::get_real_ip();
$result['ip']=$ip;
$input=$_REQUEST;
$result['input']=$input;

// timings
$end_microtime=microtime(true);
$seconds_taken=round(($end_microtime-$start_microtime), 4);
$result['seconds_taken']=$seconds_taken;

$model->log($result_code, $response_code, $host, $path, $method, $sanitized, $ip, json_encode($input), $seconds_taken, $user_agent);

http_response_code ($response_code);
echo json_encode($result);

$output=ob_get_contents();
ob_end_clean();
ob_start('ob_gzhandler');

echo $output;
ob_end_flush();
