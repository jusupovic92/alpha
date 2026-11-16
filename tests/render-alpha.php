<?php
declare(strict_types=1);
// Render actual PHP storefront against the disposable Alpha test fixture.
$base=getenv('TEST_DB_NAME')?:'webshop_test';
if(!preg_match('/^webshop_test(?:_[a-z0-9]+)?$/D',$base))exit("Refusing non-test database.\n");
$root=dirname(__DIR__);$config=$root.'/config/local.php';
if(is_file($config))exit("Preview refuses existing configuration.\n");
$values=['db_host'=>(getenv('TEST_DB_HOST')?:'127.0.0.1').';port='.(getenv('TEST_DB_PORT')?:'33317'),'db_name'=>$base.'_alpha','db_user'=>getenv('TEST_DB_USER')?:'root','db_pass'=>getenv('TEST_DB_PASSWORD')?:'local_test_only'];
$db=new PDO('mysql:host='.$values['db_host'].';dbname='.$values['db_name'].';charset=utf8mb4',$values['db_user'],$values['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec("UPDATE products SET price=300 WHERE sku='ALPHA-DEMO-001'");
try{
    file_put_contents($config,'<?php return '.var_export($values,true).';');
    foreach(['alpha-pro','alpha-premium','alpha-hybrid'] as $theme){
        $update=$db->prepare("UPDATE settings SET setting_value=? WHERE setting_key='store_theme'");$update->execute([$theme]);
        $process=proc_open([PHP_BINARY,'-r','$_SERVER["REQUEST_METHOD"]="GET"; $_SERVER["HTTPS"]="on"; require "public/index.php";'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);
        fclose($pipes[0]);$html=stream_get_contents($pipes[1]);$errors=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        if(proc_close($process)!==0||$errors!==''||!str_contains($html,'alpha-photo-hero'))throw new RuntimeException($errors?:'Preview did not render.');
        echo 'ALPHA_PREVIEW '.$theme.' '.base64_encode($html)."\n";
    }
}finally{if(is_file($config))unlink($config);}
