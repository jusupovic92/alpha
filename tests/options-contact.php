<?php
declare(strict_types=1);
$base=getenv('TEST_DB_NAME')?:'webshop_test';if(!preg_match('/^webshop_test(?:_[a-z0-9]+)?$/D',$base))exit('Only disposable databases');
$db=new PDO('mysql:host='.(getenv('TEST_DB_HOST')?:'127.0.0.1').';port='.(getenv('TEST_DB_PORT')?:'33317').';charset=utf8mb4',getenv('TEST_DB_USER')?:'root',getenv('TEST_DB_PASSWORD')?:'local_test_only',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE DATABASE '.$base.'_options CHARACTER SET utf8mb4');$db->exec('USE '.$base.'_options');
foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/install-alpha-demo.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
require dirname(__DIR__).'/app/services.php';require dirname(__DIR__).'/app/catalog.php';
function e(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}function money(float $n):string{return number_format($n,2,',','.').' KM';}
function verify(bool $ok,string $label):void{if(!$ok)throw new RuntimeException($label);echo "PASS: $label\n";}
function option(string $key,string $value):void{global $settings;query('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',[$key,$value]);$settings[$key]=$value;}
$_SESSION=[];$settings=query('SELECT setting_key,setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
query("INSERT INTO users(role_id,name,email,password_hash) VALUES(1,'Test Admin','admin@example.test','unused')");$_SESSION['user_id']=(int)$db->lastInsertId();
query("INSERT INTO products(sku,name,price,stock) VALUES('NO-STOCK','Bez stanja',12.50,0)");$id=(int)$db->lastInsertId();
$fields=['customer_name'=>'Test kupac','email'=>'buyer@example.test','phone'=>'123','address'=>'Test','city'=>'Test','note'=>''];
option('inventory_enabled','0');$before=query('SELECT COUNT(*) FROM stock_movements')->fetchColumn();
$order=checkout([$id=>3],$fields,1,'cod',bin2hex(random_bytes(32)));
verify(query('SELECT inventory_state FROM orders WHERE id=?',[$order])->fetchColumn()==='untracked','zero stock checkout without inventory');
verify((int)query('SELECT stock+reserved FROM products WHERE id=?',[$id])->fetchColumn()===0,'untracked checkout leaves quantities unchanged');
option('inventory_enabled','1');changeOrder($order,'handed_over','','','Test predaja');changeOrder($order,'returned','','','Test povrat');
verify(query('SELECT COUNT(*) FROM stock_movements')->fetchColumn()===$before,'switching on does not add stock on untracked return');
verify(query('SELECT inventory_state FROM orders WHERE id=?',[$order])->fetchColumn()==='untracked','untracked order retains its original accounting mode');
query('UPDATE products SET stock=5 WHERE id=?',[$id]);$tracked=checkout([$id=>2],$fields,1,'cod',bin2hex(random_bytes(32)));
option('inventory_enabled','0');changeOrder($tracked,'cancelled','','','Test otkazivanje');
verify((int)query('SELECT stock FROM products WHERE id=?',[$id])->fetchColumn()===5,'tracked reservation released after inventory switched off');
verify((int)query('SELECT reserved FROM products WHERE id=?',[$id])->fetchColumn()===0,'tracked reserved quantity restored');
option('inventory_enabled','1');query('UPDATE products SET stock=0 WHERE id=?',[$id]);
try{checkout([$id=>1],$fields,1,'cod',bin2hex(random_bytes(32)));verify(false,'tracked stock check');}catch(RuntimeException){verify(true,'tracked mode rejects zero stock');}
option('mail_enabled','0');option('mail_contact_enabled','1');option('mail_from','shop@example.test');option('mail_recipients',"first@example.test\nsecond@example.test");
$_SESSION['contact_token']=bin2hex(random_bytes(32));$input=['contact_token'=>$_SESSION['contact_token'],'contact_name'=>'Čedo Šarić','contact_email'=>'cedo@example.test','contact_phone'=>'+387 61 123 456','contact_message'=>'Upit o poliranju.','contact_consent'=>'1'];
$sent=[];$sink=static function(array $job,array $config)use(&$sent){$sent[]=$job;};
$jobs=submitContact($input,'127.0.0.1',$sink);verify(count($jobs)===2,'contact queued to every configured notification recipient');
processMailQueue(20,null,$sink);verify(count($sent)===2,'contact mail processes while order notifications disabled');
verify(str_contains($sent[0]['body'],'Čedo Šarić')&&str_contains($sent[0]['body'],'cedo@example.test')&&str_contains($sent[0]['body'],'+387 61'),'contact mail contains UTF-8 contact details');
try{submitContact($input,'127.0.0.1',$sink);verify(false,'duplicate contact token');}catch(InvalidArgumentException){verify(true,'duplicate contact submit rejected');}
$input['contact_token']=$_SESSION['contact_token'];$input['contact_email']="cedo@example.test\r\nBcc: other@example.test";
try{submitContact($input,'127.0.0.1',$sink);verify(false,'header injection');}catch(InvalidArgumentException){verify(true,'contact header injection rejected');}
$input['contact_email']='cedo@example.test';$input['contact_consent']='';
try{submitContact($input,'127.0.0.1',$sink);verify(false,'consent required');}catch(InvalidArgumentException){verify(true,'contact consent required');}
$input['contact_consent']='1';$fail=static function(){throw new RuntimeException('local simulated outage');};
$jobs=submitContact($input,'127.0.0.1',$fail);verify(query('SELECT status FROM mail_jobs WHERE id=?',[$jobs[0]])->fetchColumn()==='pending','transport failure retained for retry');
echo "PASS: stock mode switching, historical order invariants, contact queue and validation.\n";
