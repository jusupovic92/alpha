<?php
declare(strict_types=1);
// Dedicated EMPTY disposable database only. No production configuration is loaded.
$name=getenv('TEST_DB_NAME')?:'webshop_test';
if(!preg_match('/^webshop_test(?:_[a-z0-9]+)?$/D',$name))exit("Refusing non-test database.\n");
$db=new PDO('mysql:host='.(getenv('TEST_DB_HOST')?:'127.0.0.1').';port='.(getenv('TEST_DB_PORT')?:'33317').';dbname='.$name.';charset=utf8mb4',getenv('TEST_DB_USER')?:'root',getenv('TEST_DB_PASSWORD')?:'local_test_only',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
if($db->query('SHOW TABLES')->fetchColumn())exit("Test database must be empty.\n");
function runSql(string $path):void {global $db;foreach(explode(';',file_get_contents($path)) as $sql)if(trim($sql)!=='')$db->exec($sql);}
function expect(bool $yes,string $label):void {if(!$yes)throw new RuntimeException('FAIL: '.$label);echo "PASS: $label\n";}
require dirname(__DIR__).'/app/services.php';
$_SESSION=[];$settings=[];
runSql(dirname(__DIR__).'/database/schema.sql');
$db->exec("INSERT INTO products(sku,name,price,stock) VALUES('LEGACY','Stari proizvod',12.50,3)");
$db->exec("INSERT INTO orders(customer_name,email,phone,address,city,total) VALUES('Stari kupac','legacy@example.test','123','Adresa','Grad',12.50)");
$db->exec("INSERT INTO order_items(order_id,product_id,product_name,quantity,unit_price) VALUES(1,1,'Stari proizvod',1,12.50)");
runSql(dirname(__DIR__).'/database/migrations/002_management.sql');
expect((int)query('SELECT stock FROM products WHERE id=1')->fetchColumn()===3,'upgrade preserves legacy stock');
expect(query('SELECT inventory_state FROM orders WHERE id=1')->fetchColumn()==='legacy','legacy inventory is classified without re-deduction');
runSql(dirname(__DIR__).'/database/migrations/003_storefront.sql');
expect((int)query('SELECT stock FROM products WHERE id=1')->fetchColumn()===3,'storefront migration preserves stock');
expect((int)query('SELECT hero_selected FROM products WHERE id=1')->fetchColumn()===0,'hero selection is opt-in');
query("INSERT INTO orders(customer_name,email,phone,address,city,total,status,payment_status,inventory_state) VALUES('Stari plaćeni zapis','oldpaid@example.test','123','Adresa','Grad',20.00,'preparing','paid','reserved')");
runSql(dirname(__DIR__).'/database/migrations/004_fulfillment.sql');
expect(query('SELECT status FROM orders WHERE id=2')->fetchColumn()==='preparing','migration does not guess dispatch of old paid preparing order');
expect((int)query('SELECT stock FROM products WHERE id=1')->fetchColumn()===3,'fulfillment migration preserves stock');
runSql(dirname(__DIR__).'/database/migrations/005_hero_banner.sql');
expect(query('SELECT hero_banner_url FROM products WHERE id=1')->fetchColumn()==='','banner migration keeps existing products in automatic layout');
expect((int)query('SELECT stock FROM products WHERE id=1')->fetchColumn()===3,'banner migration preserves stock');
runSql(dirname(__DIR__).'/database/migrations/006_delivery.sql');
runSql(dirname(__DIR__).'/database/migrations/007_mail_discounts.sql');
expect(query('SELECT compare_price FROM products WHERE id=1')->fetchColumn()===null,'discount migration preserves prices without inventing savings');
runSql(dirname(__DIR__).'/database/migrations/008_readability_numbering.sql');
runSql(dirname(__DIR__).'/database/migrations/009_admin_backups.sql');
function testOrderChange(int $id,string $next,string $tracking='',string $courier='Test kurir',string $note='Testni dokument promjene'):void {if($next==='shipped'&&in_array(query('SELECT status FROM orders WHERE id=?',[$id])->fetchColumn(),['new','preparing'],true))prepareShipment($id,1,$courier,'10.00','Testni uslovi','customer',$tracking,'Priprema');changeOrder($id,$next,$tracking,$courier,$note);}
query('INSERT INTO users(role_id,name,email,password_hash) VALUES(1,?,?,?)',['Test Admin','admin@example.test',password_hash('test-password-123',PASSWORD_DEFAULT)]);$_SESSION['user_id']=1;
$settings=query('SELECT setting_key,setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
expect(can('payments')&&can('*'),'super administrator permissions');
for($i=0;$i<10;$i++)checkoutRateLimit('test-only-identity');
try{checkoutRateLimit('test-only-identity');expect(false,'checkout rate limit');}catch(RuntimeException){expect(true,'checkout rate limit enforced');}
expect(cents('0.01')===1&&cents('19.90')*3===5970,'integer monetary calculations');
try{cents('-1');expect(false,'negative money rejected');}catch(InvalidArgumentException){expect(true,'negative money rejected');}
testOrderChange(1,'cancelled','');expect((int)query('SELECT stock FROM products WHERE id=1')->fetchColumn()===4,'legacy cancellation restocks once');
try{testOrderChange(1,'cancelled','');expect(false,'duplicate cancellation rejected');}catch(RuntimeException){expect((int)query('SELECT stock FROM products WHERE id=1')->fetchColumn()===4,'duplicate cancellation cannot restock twice');}
query("INSERT INTO products(sku,name,price,stock) VALUES('TEST','Novi proizvod',19.90,5)");$product=(int)$db->lastInsertId();
query('UPDATE products SET purchase_price=8.50 WHERE id=?',[$product]);
$fields=['customer_name'=>'Test kupac','email'=>'buyer@example.test','phone'=>'061123456','address'=>'Test 1','city'=>'Kakanj','note'=>'Test'];
$token=bin2hex(random_bytes(32));$id=checkout([$product=>2],$fields,1,'cod',$token);
$p=query('SELECT * FROM products WHERE id=?',[$product])->fetch();expect((int)$p['stock']===3&&(int)$p['reserved']===2,'checkout reserves stock');
$snapshot=query('SELECT * FROM stock_movements WHERE product_id=? ORDER BY id DESC LIMIT 1',[$product])->fetch();
expect((int)$snapshot['available_after']===3&&(int)$snapshot['reserved_after']===2&&$snapshot['unit_cost']==='8.50','stock ledger records resulting balances and historical unit cost');
expect(query('SELECT total FROM orders WHERE id=?',[$id])->fetchColumn()==='49.80','shipping included in exact total');
expect(checkout([$product=>2],$fields,1,'cod',$token)===$id,'checkout idempotency');
$before=(int)query('SELECT COUNT(*) FROM orders')->fetchColumn();
try{checkout([$product=>4],$fields,1,'cod',bin2hex(random_bytes(32)));expect(false,'overselling rejected');}catch(RuntimeException){expect((int)query('SELECT COUNT(*) FROM orders')->fetchColumn()===$before,'failed order rolls back');}
try{testOrderChange($id,'delivered','');expect(false,'invalid transition rejected');}catch(RuntimeException){expect(true,'invalid transition rejected');}
expect(query('SELECT status FROM orders WHERE id=?',[$id])->fetchColumn()==='preparing','new checkout starts in preparation for dispatch');
try{changeOrder($id,'shipped','','Neki kurir','Predaja');expect(false,'delivery plan required');}catch(RuntimeException){expect(true,'delivery plan required');}
try{changeOrder($id,'shipped','','','Predaja');expect(false,'courier mandatory');}catch(RuntimeException){expect(true,'courier mandatory');}
testOrderChange($id,'shipped','TRACK-123');$p=query('SELECT * FROM products WHERE id=?',[$product])->fetch();expect((int)$p['stock']===3&&(int)$p['reserved']===0,'shipment closes reservation without double deduction');
testOrderChange($id,'delivered','TRACK-123');recordPayment($id,'paid','Bankovni izvod 123');expect(query('SELECT status FROM orders WHERE id=?',[$id])->fetchColumn()==='delivered','payment keeps delivery status');expect(isOrderSettled(query('SELECT * FROM orders WHERE id=?',[$id])->fetch()),'dispatch and payment together settle the order');
try{testOrderChange($id,'returned','TRACK-123');expect(false,'paid return requires refund');}catch(RuntimeException){expect(true,'paid return requires refund');}
recordPayment($id,'refunded','Povrat dokument 124');testOrderChange($id,'returned','TRACK-123');expect((int)query('SELECT stock FROM products WHERE id=?',[$product])->fetchColumn()===5,'return restocks shipped goods');
$cancel=checkout([$product=>1],$fields,1,'cod',bin2hex(random_bytes(32)));testOrderChange($cancel,'cancelled','');$p=query('SELECT * FROM products WHERE id=?',[$product])->fetch();expect((int)$p['stock']===5&&(int)$p['reserved']===0,'cancellation releases reservation');
$paidPending=checkout([$product=>1],$fields,1,'cod',bin2hex(random_bytes(32)));
recordPayment($paidPending,'paid','Uplata unaprijed');
$pending=query('SELECT * FROM orders WHERE id=?',[$paidPending])->fetch();$p=query('SELECT * FROM products WHERE id=?',[$product])->fetch();
expect($pending['status']==='preparing'&&!isOrderSettled($pending)&&(int)$p['reserved']===1&&(int)$p['stock']===4,'payment in preparation does not dispatch goods');
testOrderChange($paidPending,'shipped','','Kurir bez broja još','Predano kuriru, broj naknadno');
$sent=query('SELECT * FROM orders WHERE id=?',[$paidPending])->fetch();$p=query('SELECT * FROM products WHERE id=?',[$product])->fetch();
expect($sent['status']==='shipped'&&$sent['payment_status']==='paid'&&isOrderSettled($sent)&&(int)$p['stock']===4&&(int)$p['reserved']===0,'paid preparing order becomes shipped and paid after explicit dispatch');
expect($sent['courier_name']==='Kurir bez broja još'&&$sent['dispatched_at']!==null,'dispatch preserves actual courier and timestamp');
updateShipment($paidPending,'Test ekspres','LATER-123','Broj dostavljen kasnije');
expect(query('SELECT tracking_number FROM orders WHERE id=?',[$paidPending])->fetchColumn()==='LATER-123','tracking can be added without redispatch');
try{testOrderChange($paidPending,'shipped','DUP');expect(false,'duplicate dispatch blocked');}catch(RuntimeException){expect((int)query('SELECT reserved FROM products WHERE id=?',[$product])->fetchColumn()===0,'duplicate dispatch does not deduct twice');}
recordPayment($paidPending,'refunded','Povrat avansa');testOrderChange($paidPending,'returned','');
$personal=checkout([$product=>1],$fields,1,'cod',bin2hex(random_bytes(32)));testOrderChange($personal,'handed_over','','','Predano lično kupcu');recordPayment($personal,'paid','Gotovina potvrda');
expect(query('SELECT status FROM orders WHERE id=?',[$personal])->fetchColumn()==='handed_over','personal order handover remains distinct after payment');
recordPayment($personal,'refunded','Povrat lične predaje');testOrderChange($personal,'returned','');
$reservation=checkout([$product=>1],$fields,1,'cod',bin2hex(random_bytes(32)));
try{createStockExit($product,5,'write_off','0','not_applicable','','','Otpis',bin2hex(random_bytes(32)));expect(false,'reserved stock protected from other exits');}catch(RuntimeException){expect(true,'reserved stock protected from other exits');}
$exitToken=bin2hex(random_bytes(32));$exit=createStockExit($product,1,'write_off','0','not_applicable','','OTP-1','Oštećen artikal',$exitToken);
expect(createStockExit($product,1,'write_off','0','not_applicable','','OTP-1','Oštećen artikal',$exitToken)===$exit,'stock exit idempotency');
$p=query('SELECT * FROM products WHERE id=?',[$product])->fetch();expect((int)$p['stock']===3&&(int)$p['reserved']===1,'write-off removes free quantity only');
$m=query('SELECT * FROM stock_movements WHERE stock_exit_id=? ORDER BY id DESC LIMIT 1',[$exit])->fetch();expect($m['kind']==='write_off'&&(int)$m['quantity']===-1&&(int)$m['reserved_after']===1,'typed exit linked to stock ledger');
reverseStockExit($exit,'Pogrešna evidencija, artikal provjeren i vraćen');
try{reverseStockExit($exit,'Drugi pokušaj');expect(false,'duplicate reversal blocked');}catch(RuntimeException){expect((int)query('SELECT stock FROM products WHERE id=?',[$product])->fetchColumn()===4,'reversal restores stock once');}
testOrderChange($reservation,'cancelled','');
$sale=createStockExit($product,2,'personal_sale','25.10','paid','Test primalac','SALE-1','Lična predaja i naplata',bin2hex(random_bytes(32)));
expect(query('SELECT total FROM stock_exits WHERE id=?',[$sale])->fetchColumn()==='50.20','personal sale uses exact monetary total');
try{reverseStockExit($sale,'Povrat robe');expect(false,'paid sale requires refund before reversal');}catch(RuntimeException){expect(true,'paid sale requires refund before reversal');}
recordExitPayment($sale,'refunded','Novac vraćen kupcu');reverseStockExit($sale,'Roba vraćena na lager');
expect((int)query('SELECT stock FROM products WHERE id=?',[$product])->fetchColumn()===5,'personal sale refund and reversal restore stock without double deductions');
[$sql,$args]=financialEventsSql('1970-01-01','2100-01-01');$events=query("SELECT * FROM ($sql) events WHERE source='stock_exit'",$args)->fetchAll();expect(count($events)===2,'personal sale financial events are separate from order events');
try{createStockExit($product,1,'withdrawal','0','not_applicable','','','',bin2hex(random_bytes(32)));expect(false,'exit explanation mandatory');}catch(RuntimeException){expect(true,'exit explanation mandatory');}
query("INSERT INTO products(sku,name,price,stock) VALUES('LAST','Posljednji artikal',1.00,1)");$last=(int)$db->lastInsertId();
$processes=[];$deadline=microtime(true)+1;
for($i=0;$i<2;$i++){
    $pipes=[];$proc=proc_open([PHP_BINARY,__DIR__.'/race-worker.php',(string)$last,(string)$deadline],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$processes[]=[$proc,$pipes];
}
$results=[];foreach($processes as [$proc,$pipes]){$results[]=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);expect(proc_close($proc)===0,'race worker completed '.$err);}
sort($results);expect($results===['rejected','success'],'two simultaneous checkouts cannot oversell final item');
$p=query('SELECT stock,reserved FROM products WHERE id=?',[$last])->fetch();expect((int)$p['stock']===0&&(int)$p['reserved']===1,'concurrent checkout inventory invariant');

saveDelivery(0,'Test besplatna','12.00','Test Express','Rok 2 dana','sender',true);
$method=(int)query("SELECT id FROM shipping_methods WHERE name='Test besplatna'")->fetchColumn();
$freeOrder=checkout([$product=>1],$fields,$method,'cod',bin2hex(random_bytes(32)));
$before=query('SELECT * FROM orders WHERE id=?',[$freeOrder])->fetch();
expect($before['shipping_cost']==='0.00'&&$before['shipping_terms']==='Rok 2 dana'&&$before['shipping_payer']==='sender','seller-paid delivery has zero customer charge and stores checkout terms');
$inventoryBefore=query('SELECT stock,reserved FROM products WHERE id=?',[$product])->fetch();
prepareShipment($freeOrder,$method,'Posebni kurir','15.50','Predaja do 14 sati','sender','','Test priprema');
$planned=query('SELECT * FROM orders WHERE id=?',[$freeOrder])->fetch();
expect($planned['status']==='preparing'&&$planned['total']===$before['total']&&$planned['planned_delivery_cost']==='15.50'&&query('SELECT stock,reserved FROM products WHERE id=?',[$product])->fetch()===$inventoryBefore,'preparation snapshots conditions without changing totals or inventory');
saveDelivery($method,'Novi naziv','99.00','Drugi kurir','Novi uslovi','customer',false);
deleteDelivery($method,'Ukinuta usluga');
$preserved=query('SELECT * FROM orders WHERE id=?',[$freeOrder])->fetch();expect($preserved['planned_delivery_name']==='Test besplatna'&&$preserved['planned_delivery_terms']==='Predaja do 14 sati'&&$preserved['shipping_terms']==='Rok 2 dana','editing and deleting method preserves customer and dispatch snapshots');
try{prepareShipment($freeOrder,$method,'Kurir','1.00','','customer','','Test');expect(false,'deleted method rejected');}catch(RuntimeException){expect(true,'deleted method rejected');}
try{checkout([$product=>1],$fields,$method,'cod',bin2hex(random_bytes(32)));expect(false,'deleted checkout method rejected');}catch(RuntimeException){expect(true,'deleted checkout method rejected');}
changeOrder($freeOrder,'shipped','','Ignorisani novi kurir','Predaja');expect(query('SELECT courier_name FROM orders WHERE id=?',[$freeOrder])->fetchColumn()==='Posebni kurir','dispatch uses saved plan even after method deletion');
try{prepareShipment($freeOrder,1,'Kurir','1.00','','customer','','Test');expect(false,'sent plan cannot change');}catch(RuntimeException){expect(true,'sent plan cannot change');}

expect(count(mailRecipients("Pack@example.test, pack@example.test; ship@example.test"))===2,'mail recipients validated and deduplicated');
try{mailRecipients('bad-address');expect(false,'invalid recipient');}catch(RuntimeException){expect(true,'invalid recipient rejected');}
$keyPath=dirname(__DIR__).'/config/mail.key';if(file_exists($keyPath))throw new RuntimeException('Refusing existing mail.key during test');
try{$secret=mailSecret('local-only-secret');expect($secret!=='local-only-secret'&&mailSecret($secret,true)==='local-only-secret','SMTP password encrypted with separate local key');}finally{if(file_exists($keyPath))unlink($keyPath);}
foreach(['mail_enabled'=>'1','mail_recipients'=>"pack@example.test\nship@example.test",'mail_from'=>'shop@example.test','mail_base_url'=>'https://shop.example.test'] as $k=>$v)query('UPDATE settings SET setting_value=? WHERE setting_key=?',[$v,$k]);
$mailToken=bin2hex(random_bytes(32));$mailOrder=checkout([$product=>1],$fields,1,'cod',$mailToken);checkout([$product=>1],$fields,1,'cod',$mailToken);
$mailBody=query('SELECT body FROM mail_jobs WHERE order_id=? LIMIT 1',[$mailOrder])->fetchColumn();foreach(['customer_name','email','phone','address','city','note'] as $field)expect(str_contains($mailBody,$fields[$field]),'order email includes '.$field);expect(str_contains($mailBody,'UKUPNO:')&&str_contains($mailBody,'Način dostave:')&&str_contains($mailBody,'Način plaćanja:'),'order email includes financial and delivery details');
expect((int)query('SELECT COUNT(*) FROM mail_jobs WHERE order_id=?',[$mailOrder])->fetchColumn()===2,'one notification per order and recipient despite checkout resubmission');
$sent=processMailQueue(20,$mailOrder,static function($job){if($job['recipient']==='pack@example.test')throw new RuntimeException('Test transport down');});expect($sent===1&&query("SELECT status FROM mail_jobs WHERE order_id=? AND recipient='pack@example.test'",[$mailOrder])->fetchColumn()==='pending','failed recipient stays queued without rolling back order or other recipient');
query('UPDATE mail_jobs SET next_attempt_at=NULL WHERE order_id=?',[$mailOrder]);$sent=processMailQueue(20,$mailOrder,static function($job){});expect($sent===1&&(int)query("SELECT COUNT(*) FROM mail_jobs WHERE order_id=? AND status='sent'",[$mailOrder])->fetchColumn()===2,'retry sends only unfinished recipient');
expect(processMailQueue(20,$mailOrder,static function($job){throw new RuntimeException('must not resend');})===0,'accepted messages not sent twice');
$cancelMailOrder=checkout([$product=>1],$fields,1,'cod',bin2hex(random_bytes(32)));testOrderChange($cancelMailOrder,'cancelled');expect(processMailQueue(20,$cancelMailOrder,static function($job){throw new RuntimeException('must not send cancelled order');})===0&&(int)query("SELECT COUNT(*) FROM mail_jobs WHERE order_id=? AND status='cancelled'",[$cancelMailOrder])->fetchColumn()===2,'outdated cancelled preparation notifications suppressed');
query("INSERT INTO mail_jobs(recipient,subject,body) VALUES('fail@example.test','Test failure','Test')");$failedJob=(int)$db->lastInsertId();for($attempt=0;$attempt<5;$attempt++){query('UPDATE mail_jobs SET next_attempt_at=NULL WHERE id=?',[$failedJob]);processMailQueue(20,null,static function($job){throw new RuntimeException('local failure');});}expect(query('SELECT status FROM mail_jobs WHERE id=?',[$failedJob])->fetchColumn()==='failed'&&(int)query('SELECT attempts FROM mail_jobs WHERE id=?',[$failedJob])->fetchColumn()===5,'retry limit records failed message after five attempts');
query("UPDATE settings SET setting_value='0' WHERE setting_key='mail_enabled'");

$historic=query('SELECT unit_price FROM order_items WHERE order_id=1')->fetchColumn();query("INSERT INTO products(sku,name,price,compare_price,stock) VALUES('SALE-TEST','Test sale',89.00,139.00,2)");$saleProduct=(int)$db->lastInsertId();$saleOrder=checkout([$saleProduct=>1],$fields,1,'cod',bin2hex(random_bytes(32)));
expect(query('SELECT unit_price FROM order_items WHERE order_id=?',[$saleOrder])->fetchColumn()==='89.00'&&query('SELECT total FROM orders WHERE id=?',[$saleOrder])->fetchColumn()==='99.00','checkout charges current discounted price plus shipping');
expect(query('SELECT unit_price FROM order_items WHERE order_id=1')->fetchColumn()===$historic,'price changes do not reprice historical order items');

$db->beginTransaction();$firstSku=nextProductSku();query('INSERT INTO products(sku,name,price) VALUES(?,?,1.00)',[$firstSku,'Auto 1']);$secondSku=nextProductSku();query('INSERT INTO products(sku,name,price) VALUES(?,?,1.00)',[$secondSku,'Auto 2']);$db->commit();expect($firstSku==='1'&&$secondSku==='2','new product numbers start at one independently of database IDs');
query("INSERT INTO products(sku,name,price) VALUES('3','Existing numeric SKU',1.00)");$db->beginTransaction();$skipSku=nextProductSku();$db->commit();expect($skipSku==='4','automatic numbers skip occupied historical SKUs');
$db->beginTransaction();$rolledBack=nextProductSku();$db->rollBack();$db->beginTransaction();$retrySku=nextProductSku();$db->rollBack();expect($rolledBack===$retrySku,'failed product save does not consume automatic number');

$workers=[];$deadline=microtime(true)+1;for($i=0;$i<2;$i++){$pipes=[];$proc=proc_open([PHP_BINARY,__DIR__.'/race-sku-worker.php',(string)$deadline],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$workers[]=[$proc,$pipes];}$numbers=[];foreach($workers as [$proc,$pipes]){$numbers[]=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);expect(proc_close($proc)===0,'parallel numbering worker '.$err);}sort($numbers);expect($numbers===['5','6'],'simultaneous product creation gets distinct consecutive numbers');

expect(activityLabel('order.created')==='Narudžba je zaprimljena','audit action translated without rewriting stored code');$human=activityDetails(['action'=>'order.status','entity_id'=>4,'details'=>'preparing -> cancelled · Kupac odustao']);expect(str_contains($human,'U pripremi za slanje')&&str_contains($human,'Otkazano')&&!str_contains($human,'cancelled'),'old status details translated into Bosnian');$human=activityDetails(['action'=>'order.delivery_prepared','entity_id'=>3,'details'=>json_encode(['method'=>'Brza pošta','cost'=>'10.00','payer'=>'customer','note'=>'Spremno'])]);expect(str_contains($human,'Način dostave: Brza pošta')&&str_contains($human,'kupac')&&!str_contains($human,'{"'),'structured delivery details shown as human readable text');
echo "Integration suite complete. Test fixtures retained for HTTP tests.\n";
