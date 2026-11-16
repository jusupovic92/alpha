<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(is_file(dirname(__DIR__).'/storage/maintenance.flag'))exit;
$config=require dirname(__DIR__).'/config/local.php';
$db=new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
require dirname(__DIR__).'/app/services.php';
if((mailConfig()['mail_configured']??'0')!=='1'&&filter_var($config['notify_email']??'',FILTER_VALIDATE_EMAIL)){require __DIR__.'/send-notifications-legacy.php';exit;}
echo 'Potvrđeno slanje: '.processMailQueue()."\n";
