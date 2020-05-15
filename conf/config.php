<?php
//à

use \Doctrine\DBAL\Configuration;
use \Doctrine\DBAL\DriverManager;
use \Omatech\Models\SinkModel;

ini_set('display_errors',1);
define("ERROR_LEVEL", E_ALL & ~E_NOTICE);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();
	 
$config = new Configuration();
//..
$connectionParams = array(
    'dbname' => $_ENV['DBNAME'],
    'user' => $_ENV['DBUSER'],
    'password' => $_ENV['DBPASS'],
    'host' => $_ENV['DBHOST'],
    'driver' => 'pdo_mysql',
		'charset' => 'utf8'
);

$params=['show_inmediate_debug'=>true, 'debug'=>true];

$conn = DriverManager::getConnection($connectionParams, $config);
$model=new SinkModel($conn, $params, false);
$model->conn->executeQuery('SET SESSION group_concat_max_len = 1000000;');


