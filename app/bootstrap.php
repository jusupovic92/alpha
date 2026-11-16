<?php
declare(strict_types=1);
session_start(['cookie_httponly'=>true,'cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','cookie_samesite'=>'Lax','use_strict_mode'=>true]);
if(!empty($_SESSION['user_id']) && time()-(int)($_SESSION['last_activity']??time())>1800){unset($_SESSION['user_id']);session_regenerate_id(true);}
$_SESSION['last_activity']=time();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
$configFile=dirname(__DIR__).'/config/local.php';
if (!is_file($configFile)) { http_response_code(503); exit('Konfiguracija baze nije postavljena.'); }
$config=require $configFile;
try {
  $db=new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
} catch (PDOException $e) { error_log($e->getMessage()); http_response_code(503); exit('Baza trenutno nije dostupna.'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
function e(string $s):string { return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function money(float $n):string { return number_format($n,2,',','.').' KM'; }
function csrf():string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function checkCsrf():void { if (!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??''))) {http_response_code(403);exit('Neispravan zahtjev.');} }
function redirect(string $to):never {header('Location: '.$to, true,303);exit;}
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; img-src 'self' https:; style-src 'self' 'unsafe-inline'; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
date_default_timezone_set('Europe/Sarajevo');
require_once __DIR__.'/services.php';
require_once __DIR__.'/catalog.php';
if(is_file(dirname(__DIR__).'/storage/maintenance.flag')){if(backupRecoverInterrupted()){$_SESSION=[];session_regenerate_id(true);http_response_code(503);exit('Postupak oporavka je završen. Osvježite stranicu i prijavite se ponovo.');}http_response_code(503);exit('U toku je vraćanje podataka. Pokušajte ponovo za nekoliko trenutaka.');}
$epochFile=dirname(__DIR__).'/storage/session-epoch';$authEpoch=is_file($epochFile)?trim((string)file_get_contents($epochFile)):'initial';if(!empty($_SESSION['user_id'])&&($_SESSION['auth_epoch']??'initial')!==$authEpoch){$_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));}
try { $settings=query('SELECT setting_key,setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR); }
catch(PDOException $e){http_response_code(503);exit('Potrebno je primijeniti SQL migraciju 002 prije pokretanja ove verzije.');}
if(!query("SELECT version FROM schema_migrations WHERE version='003_storefront'")->fetchColumn()){http_response_code(503);exit('Za verziju 0.3 prvo uvezite database/migrations/003_storefront.sql kroz phpMyAdmin. Prethodno napravite backup baze.');}


if(!query("SELECT version FROM schema_migrations WHERE version='004_fulfillment'")->fetchColumn()){http_response_code(503);exit('Za verziju 0.4 prvo uvezite database/migrations/004_fulfillment.sql kroz phpMyAdmin, nakon backupa baze.');}

if(!query("SELECT version FROM schema_migrations WHERE version='005_hero_banner'")->fetchColumn()){http_response_code(503);exit('Za verziju 0.5 prvo uvezite database/migrations/005_hero_banner.sql kroz phpMyAdmin, nakon backupa baze.');}

if(!query("SELECT version FROM schema_migrations WHERE version='006_delivery'")->fetchColumn()){http_response_code(503);exit('Prvo uvezite database/migrations/006_delivery.sql kroz phpMyAdmin nakon backupa baze.');}

if(!query("SELECT version FROM schema_migrations WHERE version='007_mail_discounts'")->fetchColumn()){http_response_code(503);exit('Prvo uvezite database/migrations/007_mail_discounts.sql kroz phpMyAdmin nakon backupa baze.');}

if(!query("SELECT version FROM schema_migrations WHERE version='008_readability_numbering'")->fetchColumn()){http_response_code(503);exit('Prvo uvezite database/migrations/008_readability_numbering.sql kroz phpMyAdmin nakon backupa baze.');}

if(!query("SELECT version FROM schema_migrations WHERE version='009_admin_backups'")->fetchColumn()){http_response_code(503);exit('Prvo uvezite database/migrations/009_admin_backups.sql kroz phpMyAdmin nakon backupa baze.');}
