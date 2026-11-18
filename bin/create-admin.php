<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$config=require dirname(__DIR__).'/config/local.php';
$db=new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
require_once dirname(__DIR__).'/app/usernames.php';
try{$username=normalizeUsername(trim(readline('Korisničko ime administratora: ')));}catch(Throwable $e){exit($e->getMessage()."\n");}
$email=trim(readline('Kontakt e-mail administratora: '));$name=trim(readline('Ime: '));
// Read from STDIN rather than command arguments / shell history. Terminal may echo.
fwrite(STDOUT,"Lozinka (12–72 znaka; unos može biti vidljiv na terminalu): ");$password=rtrim((string)fgets(STDIN),"\r\n");
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254||$name===''||mb_strlen($name)>150||strlen($password)<12||strlen($password)>72)exit("Neispravni podaci.\n");
$role=$db->query('SELECT id,permissions FROM roles')->fetchAll(PDO::FETCH_ASSOC);$super=null;
foreach($role as $r)if(in_array('*',json_decode($r['permissions'],true),true))$super=$r['id'];
if(!$super)exit("Prvo uvezite migraciju 002.\n");
$s=$db->prepare('INSERT INTO users(role_id,name,username,email,password_hash) VALUES(?,?,?,?,?)');$s->execute([$super,$name,$username,$email,password_hash($password,PASSWORD_DEFAULT)]);
fwrite(STDOUT,"Administrator je kreiran.\n");
