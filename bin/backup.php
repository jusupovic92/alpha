<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(is_file(dirname(__DIR__).'/storage/maintenance.flag'))exit;
date_default_timezone_set('Europe/Sarajevo');$config=require dirname(__DIR__).'/config/local.php';
$db=new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);require dirname(__DIR__).'/app/services.php';$_SESSION=[];$settings=query('SELECT setting_key,setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
if(setting('backup_enabled')!=='1'||date('H:i')<setting('backup_hour','03:00'))exit;
try{backupLock();}catch(Throwable){exit;}
try{foreach(backupList() as $copy)if($copy['reason']==='scheduled'&&substr($copy['created_at'],0,10)===date('Y-m-d'))exit;$id=backupCreate('scheduled',true);backupInspect($id);$copies=array_values(array_filter(backupList(),static fn($copy)=>$copy['reason']==='scheduled'));$keep=max(7,min(30,(int)setting('backup_keep','14')));foreach(array_slice($copies,$keep) as $copy){$old=backupId($copy['id']);@unlink(backupRoot().'/'.$old.'.wsb');@unlink(backupRoot().'/'.$old.'.json');}echo "Sigurnosna kopija napravljena i provjerena: $id\n";}catch(Throwable $e){fwrite(STDERR,'Backup nije završen: '.$e->getMessage()."\n");exit(1);}finally{backupUnlock();}
