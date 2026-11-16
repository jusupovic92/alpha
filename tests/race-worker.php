<?php
declare(strict_types=1);
$name=getenv('TEST_DB_NAME')?:'webshop_test';if(!preg_match('/^webshop_test(?:_[a-z0-9]+)?$/D',$name))exit(2);
$db=new PDO('mysql:host='.(getenv('TEST_DB_HOST')?:'127.0.0.1').';port='.(getenv('TEST_DB_PORT')?:'33317').';dbname='.$name.';charset=utf8mb4',getenv('TEST_DB_USER')?:'root',getenv('TEST_DB_PASSWORD')?:'local_test_only',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
require dirname(__DIR__).'/app/services.php';$_SESSION=[];$settings=[];
while(microtime(true)<(float)$argv[2])usleep(1000);
try{checkout([(int)$argv[1]=>1],['customer_name'=>'Race','email'=>'race@example.test','phone'=>'123','address'=>'Test','city'=>'Test','note'=>''],1,'cod',bin2hex(random_bytes(32)));echo 'success';}catch(RuntimeException $e){echo 'rejected';}
