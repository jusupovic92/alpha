<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$config=require dirname(__DIR__).'/config/local.php';
foreach(['notify_email','mail_from'] as $key)if(!filter_var($config[$key]??'',FILTER_VALIDATE_EMAIL))exit("Postavite notify_email i mail_from u config/local.php.\n");
$url=rtrim((string)($config['base_url']??''),'/');
if(!filter_var($url,FILTER_VALIDATE_URL)||!str_starts_with($url,'https://'))exit("Postavite HTTPS base_url.\n");
$db=new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if((int)$db->query("SELECT GET_LOCK('webshop_notifications',0)")->fetchColumn()!==1)exit;
try{
    $messages=$db->query('SELECT * FROM notifications WHERE emailed_at IS NULL AND email_attempts<5 AND (last_email_attempt_at IS NULL OR last_email_attempt_at<DATE_SUB(NOW(),INTERVAL 10 MINUTE)) ORDER BY id LIMIT 20')->fetchAll();
    foreach($messages as $n){
        $s=$db->prepare('UPDATE notifications SET email_attempts=email_attempts+1,last_email_attempt_at=NOW() WHERE id=?');$s->execute([$n['id']]);
        $sent=mail($config['notify_email'],'WEBSHOP: nova narudzba #'.$n['order_id'],"Nova narudzba je zaprimljena.\n".$url.'/admin.php?page=order&id='.$n['order_id'],['From'=>$config['mail_from'],'Content-Type'=>'text/plain; charset=UTF-8']);
        if($sent){$s=$db->prepare('UPDATE notifications SET emailed_at=NOW() WHERE id=?');$s->execute([$n['id']]);}else fwrite(STDERR,'Slanje nije uspjelo: '.$n['id']."\n");
    }
}finally{$db->query("SELECT RELEASE_LOCK('webshop_notifications')");}
