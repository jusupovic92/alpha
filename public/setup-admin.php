<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');

// The key is a separate private file uploaded through cPanel File Manager.
// No default key or password is included in the source repository.
$keyPath=dirname(__DIR__).'/config/setup.key';
$key=is_file($keyPath)?trim((string)file_get_contents($keyPath)):'';
if(strlen($key)<32||strlen($key)>256){http_response_code(404);exit('Postavljanje nije omogućeno.');}
if(empty($_SERVER['HTTPS'])||$_SERVER['HTTPS']==='off'){http_response_code(403);exit('Otvorite ovu stranicu putem HTTPS veze.');}
if(query('SELECT id FROM users LIMIT 1')->fetchColumn()||query("SELECT setting_value FROM settings WHERE setting_key='admin_setup_completed'")->fetchColumn()){
    http_response_code(403);exit('Prvi korisnik već postoji. Postavljanje je zaključano. Prijavite se na admin.php.');
}
$error='';$success=false;$email='';$name='';$username='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    checkCsrf();
    try{
        $ip=hash('sha256','admin-setup:'.(string)($_SERVER['REMOTE_ADDR']??''));
        if((int)query('SELECT COUNT(*) FROM login_attempts WHERE identity_hash=? AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)',[$ip])->fetchColumn()>=10){http_response_code(429);throw new RuntimeException('Previše pokušaja. Pokušajte za 15 minuta.');}
        query('INSERT INTO login_attempts(identity_hash) VALUES(?)',[$ip]);
        if(!is_string($_POST['setup_key']??null)||!hash_equals($key,$_POST['setup_key'])){http_response_code(403);throw new RuntimeException('Ključ za postavljanje nije ispravan.');}
        $username=normalizeUsername($_POST['username']??null);requireUsernames();
        $email=is_string($_POST['email']??null)?trim($_POST['email']):'';
        $name=is_string($_POST['name']??null)?trim($_POST['name']):'';
        $password=is_string($_POST['password']??null)?$_POST['password']:'';
        $confirm=is_string($_POST['password_confirm']??null)?$_POST['password_confirm']:'';
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254||$name===''||mb_strlen($name)>150||strlen($password)<12||strlen($password)>72){http_response_code(422);throw new RuntimeException('Unesite ime, ispravan e-mail i lozinku od 12 do 72 bajta.');}
        if(!hash_equals($password,$confirm)){http_response_code(422);throw new RuntimeException('Lozinke se ne podudaraju.');}
        $hash=password_hash($password,PASSWORD_DEFAULT);
        $db->beginTransaction();
        // Serialize first-user creation even when two browsers submit at once.
        if(!query("SELECT setting_key FROM settings WHERE setting_key='shop_name' FOR UPDATE")->fetchColumn())throw new RuntimeException('Nedostaju početne postavke baze.');
        if(query('SELECT id FROM users ORDER BY id LIMIT 1 FOR UPDATE')->fetchColumn()||query("SELECT setting_value FROM settings WHERE setting_key='admin_setup_completed' FOR UPDATE")->fetchColumn())throw new RuntimeException('Postavljanje je već završeno.');
        $role=null;
        foreach(query('SELECT id,permissions FROM roles ORDER BY id FOR UPDATE')->fetchAll() as $r){if(in_array('*',json_decode($r['permissions'],true)??[],true)){$role=(int)$r['id'];break;}}
        if(!$role)throw new RuntimeException('Nedostaje super administratorska uloga. Provjerite SQL instalaciju.');
        query('INSERT INTO users(role_id,name,username,email,password_hash) VALUES(?,?,?,?,?)',[$role,$name,$username,$email,$hash]);
        $id=(int)$db->lastInsertId();
        query("INSERT INTO settings(setting_key,setting_value) VALUES('admin_setup_completed','1')");
        query('INSERT INTO audit_log(user_id,action,entity_id,details) VALUES(?,?,?,?)',[$id,'admin.initial_setup',$id,'Prvi administrator kreiran kroz zaštićeni obrazac.']);
        $db->commit();
        // Database lockout remains effective even if cPanel file permissions prevent removal.
        if(is_file($keyPath))@unlink($keyPath);
        session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));$success=true;
    }catch(Throwable $ex){if($db->inTransaction())$db->rollBack();error_log('Admin setup: '.$ex->getMessage());$error=$ex instanceof PDOException?'Kreiranje nije uspjelo. Provjerite bazu i pokušajte ponovo.':$ex->getMessage();}
}
?><!doctype html><html lang="bs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Prvi administrator</title><link rel="stylesheet" href="assets/style.css"></head><body><main><section class="panel login">
<?php if($success):?><h1>Administrator je kreiran</h1><p>Postavljanje je zaključano. Možete se prijaviti svojim korisničkim imenom i lozinkom.</p><p><a class="btn" href="admin.php">Otvori administraciju</a></p><p>U cPanel File Manageru obrišite public/setup-admin.php i, ako je preostao, config/setup.key.</p>
<?php else:?><h1>Kreiranje prvog administratora</h1><p>Ključ preuzmite iz privatnog fajla config/setup.key. Podaci za postojeću bazu se ne mijenjaju.</p><?php if($error):?><p class="error" role="alert"><?=e($error)?></p><?php endif;?>
<form method="post" autocomplete="off"><?=csrf()?>
<label for="setup_key">Ključ za postavljanje</label><input id="setup_key" name="setup_key" type="password" required autocomplete="off">
<label for="name">Ime</label><input id="name" name="name" value="<?=e($name)?>" maxlength="150" required autocomplete="name">
<label for="username">Korisničko ime za prijavu</label><input id="username" name="username" type="text" value="<?=e($username)?>" minlength="3" maxlength="64" required autocomplete="username" autocapitalize="none" spellcheck="false">
<label for="email">Kontakt e-mail</label><input id="email" name="email" type="email" value="<?=e($email)?>" maxlength="254" required autocomplete="email">
<label for="password">Lozinka (12–72 bajta)</label><input id="password" name="password" type="password" minlength="12" maxlength="72" required autocomplete="new-password">
<label for="password_confirm">Ponovite lozinku</label><input id="password_confirm" name="password_confirm" type="password" minlength="12" maxlength="72" required autocomplete="new-password">
<button>Kreiraj administratora</button></form><?php endif;?></section></main></body></html>
