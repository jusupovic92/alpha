<?php
declare(strict_types=1);
function mailRecipients(string $value):array {
    $addresses=array_values(array_unique(array_filter(array_map(static fn($s)=>strtolower(trim($s)),preg_split('/[\s,;]+/',$value)?:[]))));
    if(count($addresses)>20)throw new RuntimeException('Najviše 20 primalaca.');
    foreach($addresses as $address)if(strlen($address)>254||!filter_var($address,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Neispravna adresa primaoca.');return $addresses;
}
function mailSecret(string $value,bool $decrypt=false):string {
    $path=dirname(__DIR__).'/config/mail.key';
    if(!is_file($path)&&!$decrypt){$handle=@fopen($path,'x');if($handle){$key=random_bytes(32);fwrite($handle,$key);fclose($handle);@chmod($path,0600);}}
    $key=@file_get_contents($path);if($key===false||strlen($key)!==32)throw new RuntimeException('Nedostaje ključ config/mail.key ili nije moguće pisati u config direktorij.');
    if($decrypt){$bytes=base64_decode($value,true);if($bytes===false||strlen($bytes)<28)throw new RuntimeException('Neispravna sačuvana SMTP lozinka.');$plain=openssl_decrypt(substr($bytes,28),'aes-256-gcm',$key,OPENSSL_RAW_DATA,substr($bytes,0,12),substr($bytes,12,16));if($plain===false)throw new RuntimeException('SMTP lozinka se ne može otključati.');return $plain;}
    $iv=random_bytes(12);$cipher=openssl_encrypt($value,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);if($cipher===false)throw new RuntimeException('Lozinka nije spremljena.');return base64_encode($iv.$tag.$cipher);
}
function mailConfig():array{return query("SELECT setting_key,setting_value FROM settings WHERE setting_key LIKE 'mail_%'")->fetchAll(PDO::FETCH_KEY_PAIR);}
function saveMailSettings(array $values,string $password,bool $clearPassword):void {
    global $db;requirePermission('*');$keys=['mail_contact_enabled','mail_enabled','mail_transport','mail_host','mail_port','mail_security','mail_username','mail_from','mail_from_name','mail_recipients','mail_base_url','mail_immediate'];$clean=[];
    foreach($keys as $key){$clean[$key]=trim((string)($values[$key]??($key==='mail_contact_enabled'?'0':'')));if(strlen($clean[$key])>5000||str_contains($clean[$key],"\0"))throw new RuntimeException('Preduga postavka maila.');}
    if(!in_array($clean['mail_contact_enabled'],['0','1'],true)||!in_array($clean['mail_transport'],['smtp','mail'],true)||!in_array($clean['mail_security'],['tls','ssl'],true)||!in_array($clean['mail_enabled'],['0','1'],true)||!in_array($clean['mail_immediate'],['0','1'],true))throw new RuntimeException('Provjerite način slanja i zaštitu veze.');
    if(!ctype_digit($clean['mail_port'])||(int)$clean['mail_port']<1||(int)$clean['mail_port']>65535)throw new RuntimeException('Neispravan SMTP port.');
    $recipients=mailRecipients($clean['mail_recipients']);$clean['mail_recipients']=implode("\n",$recipients);
    foreach(['mail_from_name','mail_host','mail_username','mail_from'] as $key)if(preg_match('/[\r\n]/',$clean[$key])||mb_strlen($clean[$key])>254)throw new RuntimeException('Neispravno polje pošiljaoca ili servera.');
    $url=parse_url($clean['mail_base_url']);
    if($clean['mail_base_url']!==''&&(!filter_var($clean['mail_base_url'],FILTER_VALIDATE_URL)||($url['scheme']??'')!=='https'||isset($url['user'])||isset($url['pass'])||isset($url['query'])||isset($url['fragment'])))throw new RuntimeException('Unesite HTTPS adresu prodavnice bez parametara.');
    if($clean['mail_from']!==''&&!filter_var($clean['mail_from'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('Neispravna adresa pošiljaoca.');
    if($clean['mail_host']!==''&&!preg_match('/^[a-zA-Z0-9.-]+$/D',$clean['mail_host']))throw new RuntimeException('Unesite naziv jednog SMTP servera, bez protokola.');
    if(($clean['mail_enabled']==='1'||$clean['mail_contact_enabled']==='1')&&(!$recipients||$clean['mail_from']===''||$clean['mail_base_url']===''||($clean['mail_transport']==='smtp'&&$clean['mail_host']==='')))throw new RuntimeException('Za uključivanje unesite pošiljaoca, primaoce, adresu prodavnice i SMTP server.');
    if(strlen($password)>1000)throw new RuntimeException('Preduga SMTP lozinka.');
    if($password!=='')$clean['mail_password']=mailSecret($password);elseif($clearPassword)$clean['mail_password']='';
    try{$db->beginTransaction();foreach($clean as $key=>$value)query('INSERT INTO settings(setting_value,setting_key) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',[$value,$key]);query("UPDATE settings SET setting_value='1' WHERE setting_key='mail_configured'");audit('mail.settings_saved',null,'Postavke obavijesti ažurirane; lozinka se ne zapisuje u dnevnik.');$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function enqueueOrderMail(int $order):void {
    $config=mailConfig();if(($config['mail_enabled']??'0')!=='1')return;
    $o=query('SELECT * FROM orders WHERE id=?',[$order])->fetch();
    $format=static fn($c)=>number_format($c/100,2,',','.').' KM';
    $lines=['Nova narudžba #'.$order.' je u pripremi za slanje.','Datum: '.$o['created_at'],'','KUPAC / PRIMALAC','Ime i prezime: '.$o['customer_name'],'E-mail: '.$o['email'],'Telefon: '.$o['phone'],'Adresa: '.$o['address'],'Grad: '.$o['city'],'Napomena kupca: '.($o['note']!==''?$o['note']:'Nije unesena'),'','ARTIKLI'];
    $subtotal=0;foreach(query('SELECT product_name,quantity,unit_price FROM order_items WHERE order_id=? ORDER BY id',[$order])->fetchAll() as $item){$amount=cents((string)$item['unit_price'])*(int)$item['quantity'];$subtotal+=$amount;$lines[]=$item['product_name'].' — '.$item['quantity'].' kom. × '.$format(cents((string)$item['unit_price'])).' = '.$format($amount);}
    $lines[]='';$lines[]='Proizvodi: '.$format($subtotal);$lines[]='Način dostave: '.$o['shipping_method'];$lines[]='Dostava: '.$format(cents((string)$o['shipping_cost']));$lines[]='Uslovi dostave: '.($o['shipping_terms']?:'Nisu navedeni');$lines[]='Plaćanje dostave: '.(deliveryPayers()[$o['shipping_payer']]??'Nije navedeno');$lines[]='Način plaćanja: '.($o['payment_method']==='cod'?'Pouzećem':'Uplata na račun');$lines[]='UKUPNO: '.$format(cents((string)$o['total']));
    $lines[]='';$lines[]='Otvorite administraciju za pripremu pošiljke:';$lines[]=rtrim($config['mail_base_url'],'/').'/admin.php?page=order&id='.$order;
    foreach(mailRecipients($config['mail_recipients']) as $recipient)query('INSERT IGNORE INTO mail_jobs(order_id,recipient,subject,body) VALUES(?,?,?,?)',[$order,$recipient,'WEBSHOP: priprema narudžbe #'.$order,implode("\n",$lines)]);
}
function sendConfiguredMail(array $job,array $config):void {
    foreach(['Exception','PHPMailer','SMTP'] as $file)require_once __DIR__.'/vendor/PHPMailer/'.$file.'.php';
    $mail=new \PHPMailer\PHPMailer\PHPMailer(true);$mail->CharSet='UTF-8';$mail->Timeout=8;$mail->Timelimit=12;
    if($config['mail_transport']==='smtp'){$mail->isSMTP();$mail->Host=$config['mail_host'];$mail->Port=(int)$config['mail_port'];$mail->SMTPSecure=$config['mail_security']==='ssl'?'ssl':'tls';$mail->SMTPAuth=$config['mail_username']!=='';$mail->Username=$config['mail_username'];$mail->Password=$config['mail_password']!==''?mailSecret($config['mail_password'],true):'';}
    else $mail->isMail();
    $mail->setFrom($config['mail_from'],$config['mail_from_name']);$mail->addAddress($job['recipient']);if(str_starts_with($job['subject'],'WEBSHOP: kontakt - ')&&preg_match('/^E-mail pošiljaoca: ([^\r\n]+)$/m',$job['body'],$reply)&&filter_var($reply[1],FILTER_VALIDATE_EMAIL))$mail->addReplyTo($reply[1]);$mail->Subject=$job['subject'];$mail->Body=$job['body'];$mail->send();
}
function processMailQueue(int $limit=20,?int $order=null,?callable $sender=null,?array $jobIds=null):int {
    global $db;if(is_file(dirname(__DIR__).'/storage/maintenance.flag'))return 0;$config=mailConfig();if(($config['mail_enabled']??'0')!=='1'&&!contactMailEnabled($config))return 0;
    if((int)query("SELECT GET_LOCK('webshop_mail_jobs',0)")->fetchColumn()!==1)return 0;$count=0;
    try{$args=[];$filter=$order!==null?' AND order_id=?':'';if($order!==null)$args[]=$order;
        if(($config['mail_enabled']??'0')!=='1')$filter.=' AND order_id IS NULL';
        if(!contactMailEnabled($config))$filter.=" AND subject NOT LIKE 'WEBSHOP: kontakt - %'";
        if($jobIds!==null){$ids=array_values(array_filter(array_map('intval',$jobIds),static fn($id)=>$id>0));if(!$ids)return 0;$filter.=' AND id IN ('.implode(',',$ids).')';}
        $jobs=query("SELECT * FROM mail_jobs WHERE status='pending' AND attempts<5 AND (next_attempt_at IS NULL OR next_attempt_at<=NOW()) $filter ORDER BY id LIMIT ".max(1,min(20,$limit)),$args)->fetchAll();
        foreach($jobs as $job){if($job['order_id']&&!in_array(query('SELECT status FROM orders WHERE id=?',[$job['order_id']])->fetchColumn(),['new','preparing'],true)){query("UPDATE mail_jobs SET status='cancelled' WHERE id=?",[$job['id']]);continue;}
            query('UPDATE mail_jobs SET attempts=attempts+1,next_attempt_at=DATE_ADD(NOW(),INTERVAL 5 MINUTE) WHERE id=?',[$job['id']]);
            try{if($sender)$sender($job,$config);else sendConfiguredMail($job,$config);query("UPDATE mail_jobs SET status='sent',sent_at=NOW(),last_error=NULL WHERE id=?",[$job['id']]);$count++;}
            catch(Throwable){query("UPDATE mail_jobs SET status=?,last_error='Slanje nije potvrđeno. Provjerite SMTP, mrežu, pošiljaoca i podatke za prijavu.' WHERE id=?",[(int)$job['attempts']+1>=5?'failed':'pending',$job['id']]);}
        }return $count;
    }finally{query("SELECT RELEASE_LOCK('webshop_mail_jobs')");}
}
function scheduleOrderMail(int $order):void {
    if(PHP_SAPI==='cli'||!function_exists('fastcgi_finish_request')||(mailConfig()['mail_immediate']??'0')!=='1')return;
    register_shutdown_function(static function()use($order){if(session_status()===PHP_SESSION_ACTIVE)session_write_close();fastcgi_finish_request();try{processMailQueue(20,$order);}catch(Throwable){error_log('WEBSHOP: red mail obavijesti čeka ponovni pokušaj.');}});
}
