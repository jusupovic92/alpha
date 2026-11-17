<?php
declare(strict_types=1);

function partnersReady():bool{return (bool)query("SELECT version FROM schema_migrations WHERE version='013_business_partners'")->fetchColumn();}
function partnerMoney(int $value):string{return number_format($value/100,2,',','.').' KM';}
function partnerMode(string $mode):string{return $mode==='markup'?'Marža na nabavnu cijenu':'Popust na prodajnu cijenu';}
function partnerRate(string $mode,mixed $value):int {
    if(!in_array($mode,['discount','markup'],true)||!is_string($value)||!preg_match('/^\d{1,4}(?:[.,]\d{1,2})?$/D',$value))throw new RuntimeException('Odaberite obračun i ispravan postotak.');
    $rate=cents(str_replace(',','.',$value));if($rate>($mode==='discount'?10000:100000))throw new RuntimeException('Popust može biti 0–100%, a marža 0–1000%.');return $rate;
}
function partnerQuote(array $product,array $partner,?array $override=null):array {
    $mode=(string)($override['pricing_mode']??$partner['pricing_mode']);$rate=partnerRate($mode,(string)($override['pricing_rate']??$partner['pricing_rate']));
    $base=cents((string)$product[$mode==='markup'?'purchase_price':'price']);
    if($mode==='markup'&&$base===0)throw new RuntimeException('Za ovaj artikal nije podešena nabavna cijena za obračun marže. Obratite se prodavnici.');
    $unit=intdiv($base*($mode==='discount'?10000-$rate:10000+$rate)+5000,10000);
    if($unit>999999999999)throw new RuntimeException('Prevelika obračunata cijena.');
    return ['base'=>$base,'unit'=>$unit,'adjustment'=>abs($unit-$base),'mode'=>$mode,'rate'=>$rate,'override'=>$override!==null];
}
function partnerField(array $input,string $key,int $max,bool $required=true):string {
    $v=$input[$key]??'';if(!is_string($v)||str_contains($v,"\0")||mb_strlen($v)>$max||($required&&trim($v)===''))throw new RuntimeException('Provjerite polje: '.$key);return trim($v);
}
function partnerPassword(string $password):string {if(strlen($password)<12||strlen($password)>72||trim($password)!==$password||preg_match('/[\x00-\x1f\x7f]/',$password))throw new RuntimeException('Lozinka mora sadržavati 12–72 bajta, bez kontrolnih znakova i razmaka na početku ili kraju.');return password_hash($password,PASSWORD_DEFAULT);}
function currentPartner():?array {
    if(empty($_SESSION['partner_id'])||!partnersReady())return null;
    $p=query('SELECT * FROM business_partners WHERE id=? AND active=1',[(int)$_SESSION['partner_id']])->fetch();
    global $authEpoch;
    if(!$p||(int)($_SESSION['partner_version']??0)!==(int)$p['auth_version']||time()-(int)($_SESSION['partner_activity']??0)>1800||($_SESSION['partner_epoch']??'initial')!==($authEpoch??'initial')){unset($_SESSION['partner_id'],$_SESSION['partner_cart'],$_SESSION['partner_checkout_token']);return null;}
    $_SESSION['partner_activity']=time();return $p;
}
function partnerLogin(string $username,string $password,string $ip):void {
    if(!partnersReady())throw new RuntimeException('Modul saradnika još nije postavljen.');
    $username=normalizeUsername($username);requireUsernames();if(strlen($password)>72)throw new RuntimeException('Neispravni podaci za prijavu.');
    $identity=hash('sha256','partner:'.$username.':'.$ip);$ipIdentity=hash('sha256','partner-ip:'.$ip);
    if((int)query('SELECT COUNT(*) FROM login_attempts WHERE identity_hash IN (?,?) AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)',[$identity,$ipIdentity])->fetchColumn()>=20)throw new RuntimeException('Previše pokušaja. Pokušajte ponovo za 15 minuta.');
    query('INSERT INTO login_attempts(identity_hash) VALUES(?),(?)',[$identity,$ipIdentity]);
    $p=query('SELECT * FROM business_partners WHERE username=? AND active=1',[$username])->fetch();
    $hash=$p['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    if(!password_verify($password,$hash)||!$p)throw new RuntimeException('Neispravni podaci za prijavu.');
    query('DELETE FROM login_attempts WHERE identity_hash=?',[$identity]);
    unset($_SESSION['user_id'],$_SESSION['auth_epoch']);session_regenerate_id(true);global $authEpoch;$_SESSION['partner_id']=(int)$p['id'];$_SESSION['partner_version']=(int)$p['auth_version'];$_SESSION['partner_epoch']=$authEpoch??'initial';$_SESSION['partner_activity']=time();$_SESSION['partner_verified_at']=time();$_SESSION['partner_cart']=[];$_SESSION['partner_checkout_token']=bin2hex(random_bytes(32));$_SESSION['csrf']=bin2hex(random_bytes(32));
}
function changePartnerPassword(array $partner,string $old,string $password):void {
    global $db;$hash=partnerPassword($password);$db->beginTransaction();try{
        $p=query('SELECT * FROM business_partners WHERE id=? AND active=1 FOR UPDATE',[$partner['id']])->fetch();
        if(!$p||(int)$p['auth_version']!==(int)($partner['auth_version']??0))throw new RuntimeException('Prijava je izmijenjena ili je račun isključen. Odjavite se i prijavite ponovo.');
        if(!partnerInitialPasswordVerified($p)&&!password_verify($old,$p['password_hash']))throw new RuntimeException('Trenutna lozinka nije ispravna. Unesite lozinku kojom ste se prijavili.');
        query('UPDATE business_partners SET password_hash=?,must_change_password=0,auth_version=auth_version+1 WHERE id=?',[$hash,$p['id']]);$db->commit();$_SESSION['partner_version']=(int)$p['auth_version']+1;unset($_SESSION['partner_verified_at']);session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function partnerInitialPasswordVerified(array $partner):bool {
    $verified=(int)($_SESSION['partner_verified_at']??0);
    return !empty($partner['must_change_password'])&&(int)($_SESSION['partner_id']??0)===(int)$partner['id']&&(int)($_SESSION['partner_version']??0)===(int)$partner['auth_version']&&$verified>0&&$verified<=time()&&time()-$verified<=600;
}
function partnerPasswordInput(array $input,string $key):string {
    $value=$input[$key]??null;if(!is_string($value)||$value===''||strlen($value)>72||str_contains($value,"\0"))throw new RuntimeException('Provjerite unos lozinke.');return $value;
}
function saveBusinessPartner(int $id,array $input):int {
    global $db;requirePermission('partners');requireUsernames();$values=[];foreach(['company'=>180,'contact_name'=>180,'username'=>64,'email'=>254,'phone'=>60,'address'=>255,'city'=>150,'tax_id'=>80] as $k=>$len)$values[$k]=partnerField($input,$k,$len,$k!=='tax_id');
    $values['username']=normalizeUsername($values['username']);$values['email']=mb_strtolower($values['email']);if(!filter_var($values['email'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('Unesite ispravan e-mail saradnika.');
    $mode=partnerField($input,'pricing_mode',20);$rate=partnerRate($mode,$input['pricing_rate']??null);$password=$input['password']??'';if(!is_string($password))throw new RuntimeException('Neispravna lozinka.');$hash=$password!==''?partnerPassword($password):null;if(!$id&&!$hash)throw new RuntimeException('Unesite početnu lozinku saradnika.');
    try{$db->beginTransaction();if($id&&!query('SELECT id FROM business_partners WHERE id=? FOR UPDATE',[$id])->fetchColumn())throw new RuntimeException('Saradnik ne postoji.');
    $v=array_values($values);array_push($v,isset($input['active'])?1:0,$mode,decimal($rate));
    if($id){$v[]=$id;query('UPDATE business_partners SET company=?,contact_name=?,username=?,email=?,phone=?,address=?,city=?,tax_id=?,active=?,pricing_mode=?,pricing_rate=?,auth_version=auth_version+1 WHERE id=?',$v);if($hash)query('UPDATE business_partners SET password_hash=?,auth_version=auth_version+1,must_change_password=1 WHERE id=?',[$hash,$id]);}
    else{array_push($v,$hash);query('INSERT INTO business_partners(company,contact_name,username,email,phone,address,city,tax_id,active,pricing_mode,pricing_rate,password_hash) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',$v);$id=(int)$db->lastInsertId();}audit('partner.saved',$id,'Podaci i uslovi saradnje');$db->commit();return $id;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();if($e instanceof PDOException&&$e->getCode()==='23000')throw new RuntimeException('Korisničko ime ili e-mail se već koristi, ili podaci nisu ispravni.');throw $e;}
}
function savePartnerPrice(int $partner,int $product,array $input):void {
    global $db;requirePermission('partners');try{$db->beginTransaction();if(!query('SELECT id FROM business_partners WHERE id=? FOR UPDATE',[$partner])->fetchColumn()||!query('SELECT id FROM products WHERE id=?',[$product])->fetchColumn())throw new RuntimeException('Saradnik ili artikal ne postoji.');
    if(isset($input['inherit']))query('DELETE FROM partner_prices WHERE partner_id=? AND product_id=?',[$partner,$product]);
    else{$mode=partnerField($input,'pricing_mode',20);$rate=partnerRate($mode,$input['pricing_rate']??null);query('INSERT INTO partner_prices(partner_id,product_id,pricing_mode,pricing_rate) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE pricing_mode=VALUES(pricing_mode),pricing_rate=VALUES(pricing_rate)',[$partner,$product,$mode,decimal($rate)]);}
    audit('partner.price',$partner,'Artikal #'.$product.' - individualni uslovi');$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function partnerStatus(string $s):string{return ['new'=>'Zaprimljena','preparing'=>'U pripremi','partial'=>'Djelimično realizovana','completed'=>'U potpunosti realizovana','closed_partial'=>'Djelimično realizovana / ostatak otkazan','cancelled'=>'Otkazana','partly_returned'=>'Djelimičan povrat','returned'=>'Vraćena'][$s]??$s;}
function partnerItemOpen(array $i):int{return (int)$i['quantity']-(int)$i['fulfilled_qty']-(int)$i['cancelled_qty'];}
function partnerTotals(array $o,array $items):array {
    $t=['ordered'=>0,'open'=>0,'fulfilled'=>0,'cancelled'=>0,'returned'=>0,'due'=>0,'remaining'=>0];foreach($items as $i){$t['ordered']+=(int)$i['quantity'];$t['open']+=partnerItemOpen($i);$t['fulfilled']+=(int)$i['fulfilled_qty'];$t['cancelled']+=(int)$i['cancelled_qty'];$t['returned']+=(int)$i['returned_qty'];$t['due']+=((int)$i['fulfilled_qty']-(int)$i['returned_qty'])*cents((string)$i['unit_price']);$t['remaining']+=partnerItemOpen($i)*cents((string)$i['unit_price']);}$t['due']+=$t['fulfilled']>0?cents((string)$o['shipping_cost']):0;return $t;
}
function partnerDerivedStatus(array $t,string $previous):string {
    if($t['returned']>0)return $t['open']===0&&$t['returned']===$t['fulfilled']?'returned':'partly_returned';
    if($t['open']===0)return $t['fulfilled']===0?'cancelled':($t['cancelled']>0?'closed_partial':'completed');
    return $t['fulfilled']>0?'partial':($previous==='preparing'?'preparing':'new');
}
function partnerOrderFor(int $id,?int $partnerId=null):?array {$sql='SELECT * FROM partner_orders WHERE id=?';$args=[$id];if($partnerId!==null){$sql.=' AND partner_id=?';$args[]=$partnerId;}return query($sql,$args)->fetch()?:null;}
function partnerOrderItems(int $id):array{return query('SELECT * FROM partner_order_items WHERE order_id=? ORDER BY product_id,id',[$id])->fetchAll();}

function createPartnerOrder(int $partnerId,array $cart,array $input,string $token):int {
    global $db;if(!$cart||count($cart)>100||!preg_match('/^[a-f0-9]{64}$/D',$token))throw new RuntimeException('Korpa nije ispravna.');
    $deliveryFields=[];foreach(['contact_name'=>180,'phone'=>60,'address'=>255,'city'=>150,'note'=>2000] as $k=>$len)$deliveryFields[$k]=partnerField($input,$k,$len,$k!=='note');
    try{$db->beginTransaction();$p=query('SELECT * FROM business_partners WHERE id=? AND active=1 FOR UPDATE',[$partnerId])->fetch();if(!$p||$p['must_change_password'])throw new RuntimeException('Prijavite se i promijenite početnu lozinku.');
    if(isset($input['_auth_version'])&&(int)$input['_auth_version']!==(int)$p['auth_version'])throw new RuntimeException('Prijava je promijenjena. Prijavite se ponovo.');
    $existing=query('SELECT id,partner_id FROM partner_orders WHERE checkout_token=?',[$token])->fetch();if($existing){if((int)$existing['partner_id']!==$partnerId)throw new RuntimeException('Neispravna oznaka narudžbe.');$db->commit();return (int)$existing['id'];}
    $tracked=query("SELECT setting_value FROM settings WHERE setting_key='inventory_enabled' FOR UPDATE")->fetchColumn();$tracked=$tracked===false||$tracked==='1';
    $shipping=filter_var($input['shipping_id']??null,FILTER_VALIDATE_INT);$delivery=$shipping?query('SELECT * FROM shipping_methods WHERE id=? AND active=1 AND deleted_at IS NULL FOR UPDATE',[$shipping])->fetch():false;if(!$delivery)throw new RuntimeException('Odaberite način dostave.');
    ksort($cart,SORT_NUMERIC);$items=[];$total=deliveryCharge($delivery);$base=0;
    foreach($cart as $id=>$qty){if(!ctype_digit((string)$id)||(int)$id<1||filter_var($qty,FILTER_VALIDATE_INT)===false||(int)$qty<1||(int)$qty>100)throw new RuntimeException('Količina mora biti 1–100 komada po stavci.');$product=query('SELECT * FROM products WHERE id=? AND active=1 FOR UPDATE',[(int)$id])->fetch();if(!$product||($tracked&&(int)$product['stock']<(int)$qty))throw new RuntimeException('Artikal nije dostupan u traženoj količini.');$override=query('SELECT * FROM partner_prices WHERE partner_id=? AND product_id=?',[$partnerId,$id])->fetch()?:null;$quote=partnerQuote($product,$p,$override);$total+=$quote['unit']*(int)$qty;$base+=$quote['base']*(int)$qty;$items[]=[$product,(int)$qty,$quote];}
    if(isset($input['quote_hash'])&&(!is_string($input['quote_hash'])||!hash_equals(partnerQuoteHash($items,deliveryCharge($delivery)),$input['quote_hash'])))throw new RuntimeException('Cjenovnik ili dostava su promijenjeni. Osvježite korpu i ponovo potvrdite iznos.');
    if($total>999999999999||$base>999999999999)throw new RuntimeException('Prevelik iznos narudžbe.');
    query('INSERT INTO partner_orders(partner_id,company,contact_name,email,phone,address,city,note,total,base_total,shipping_method,shipping_cost,shipping_terms,shipping_payer,inventory_tracked,checkout_token) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$partnerId,$p['company'],$deliveryFields['contact_name'],$p['email'],$deliveryFields['phone'],$deliveryFields['address'],$deliveryFields['city'],$deliveryFields['note'],decimal($total),decimal($base),$delivery['name'],decimal(deliveryCharge($delivery)),$delivery['terms'],$delivery['payer'],$tracked?1:0,$token]);$order=(int)$db->lastInsertId();if(deliveryTypesReady())query('UPDATE partner_orders SET shipping_type=? WHERE id=?',[deliveryType($delivery),$order]);
    foreach($items as [$product,$qty,$q]){query('INSERT INTO partner_order_items(order_id,product_id,sku,product_name,quantity,base_price,unit_price,pricing_mode,pricing_rate) VALUES(?,?,?,?,?,?,?,?,?)',[$order,$product['id'],$product['sku'],$product['name'],$qty,decimal($q['base']),decimal($q['unit']),$q['mode'],decimal($q['rate'])]);if($tracked){query('UPDATE products SET stock=stock-?,reserved=reserved+? WHERE id=?',[$qty,$qty,$product['id']]);movement((int)$product['id'],-$qty,'reserve','B2B #'.$order.' - rezervacija');}}
    partnerQueueMail($order,'Nova narudžba');audit('partner.order_created',$order,'Saradnik #'.$partnerId);$db->commit();partnerScheduleMail();return $order;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function fulfillPartnerOrder(int $id,string $kind,array $quantities,string $note,string $courier,string $tracking,int $version,string $token):void {
    global $db;requirePermission('orders');if(in_array($kind,['dispatch','return'],true))requirePermission('shipping');if(!in_array($kind,['prepare','dispatch','cancel','return'],true)||!preg_match('/^[a-f0-9]{64}$/D',$token)||trim($note)===''||mb_strlen($note)>1000||mb_strlen($courier)>150||mb_strlen($tracking)>150)throw new RuntimeException('Unesite ispravnu promjenu i obrazloženje.');
    try{$db->beginTransaction();$o=query('SELECT * FROM partner_orders WHERE id=? FOR UPDATE',[$id])->fetch();if(!$o)throw new RuntimeException('Narudžba ne postoji.');$done=query('SELECT order_id FROM partner_order_events WHERE event_token=?',[$token])->fetchColumn();if($done){if((int)$done!==$id)throw new RuntimeException('Neispravna oznaka promjene.');$db->commit();return;}
    if((int)$o['version']!==$version)throw new RuntimeException('Narudžba je izmijenjena. Osvježite stranicu i provjerite količine.');
    $items=partnerOrderItems($id);if(deliveryType($o)==='pickup'){$courier='';$tracking='';}$byId=array_column($items,null,'id');$updates=[];
    if($kind==='prepare'){if(!in_array($o['status'],['new','preparing'],true))throw new RuntimeException('Narudžba više nije u početnoj pripremi.');}
    else{foreach($quantities as $itemId=>$qty){if(!isset($byId[$itemId])||filter_var($qty,FILTER_VALIDATE_INT)===false||(int)$qty<0)throw new RuntimeException('Neispravna stavka ili količina.');$qty=(int)$qty;if(!$qty)continue;$item=$byId[$itemId];$max=$kind==='return'?(int)$item['fulfilled_qty']-(int)$item['returned_qty']:partnerItemOpen($item);if($qty>$max)throw new RuntimeException('Količina prelazi preostalu količinu stavke: '.$item['product_name']);$updates[$itemId]=$qty;}if(!$updates)throw new RuntimeException('Unesite količinu za najmanje jednu stavku.');}
    query('INSERT INTO partner_order_events(order_id,kind,note,courier,tracking_number,actor_id,event_token) VALUES(?,?,?,?,?,?,?)',[$id,$kind,$note,$courier,$tracking,currentUser()['id'],$token]);$event=(int)$db->lastInsertId();
    foreach($items as $item){$qty=$updates[$item['id']]??0;if(!$qty)continue;if($o['inventory_tracked']){if(!$item['product_id'])throw new RuntimeException('Nedostaje artikal za lager evidenciju.');$product=query('SELECT * FROM products WHERE id=? FOR UPDATE',[$item['product_id']])->fetch();if(!$product||($kind!=='return'&&(int)$product['reserved']<$qty))throw new RuntimeException('Neslaganje rezervacija artikla.');if($kind==='return')query('UPDATE products SET stock=stock+? WHERE id=?',[$qty,$item['product_id']]);else query('UPDATE products SET reserved=reserved-?,stock=stock+? WHERE id=?',[$qty,$kind==='cancel'?$qty:0,$item['product_id']]);movement((int)$item['product_id'],$kind==='dispatch'?0:$qty,$kind==='dispatch'?'dispatch':($kind==='return'?'return':'release'),'B2B #'.$id.' / promjena #'.$event.' - '.mb_substr($note,0,300));}
    $column=['dispatch'=>'fulfilled_qty','cancel'=>'cancelled_qty','return'=>'returned_qty'][$kind];query('UPDATE partner_order_items SET '.$column.'='.$column.'+? WHERE id=?',[$qty,$item['id']]);query('INSERT INTO partner_event_items(event_id,item_id,quantity) VALUES(?,?,?)',[$event,$item['id'],$qty]);}
    $status=$kind==='prepare'?'preparing':partnerDerivedStatus(partnerTotals($o,partnerOrderItems($id)),$o['status']);query('UPDATE partner_orders SET status=?,version=version+1,updated_at=NOW() WHERE id=?',[$status,$id]);partnerQueueMail($id,'Promjena narudžbe', $event);audit('partner.fulfillment',$id,$kind.' / promjena #'.$event.' - '.$note);$db->commit();partnerScheduleMail();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function partnerCartData(array $partner,array $cart,int $shipping=0):array {
    $rows=[];$total=0;$base=0;ksort($cart,SORT_NUMERIC);foreach($cart as $id=>$qty){$p=query('SELECT * FROM products WHERE id=? AND active=1',[(int)$id])->fetch();if(!$p)throw new RuntimeException('Artikal iz korpe više nije dostupan. Uklonite ga iz korpe.');$override=query('SELECT * FROM partner_prices WHERE partner_id=? AND product_id=?',[$partner['id'],$id])->fetch()?:null;$q=partnerQuote($p,$partner,$override);$rows[]=[$p,(int)$qty,$q];$total+=$q['unit']*(int)$qty;$base+=$q['base']*(int)$qty;}
    $delivery=$shipping?query('SELECT * FROM shipping_methods WHERE id=? AND active=1 AND deleted_at IS NULL',[$shipping])->fetch():false;$charge=$delivery?deliveryCharge($delivery):0;return ['rows'=>$rows,'subtotal'=>$total,'base'=>$base,'shipping'=>$charge,'total'=>$total+$charge,'hash'=>partnerQuoteHash($rows,$charge)];
}
function partnerQuoteHash(array $rows,int $shipping):string {return hash('sha256',json_encode([array_map(static fn($r)=>[$r[0]['id'],$r[1],$r[2]['base'],$r[2]['unit'],$r[2]['mode'],$r[2]['rate']],$rows),$shipping],JSON_THROW_ON_ERROR));}
function partnerMailEnabled(array $config):bool{return ($config['mail_partner_enabled']??'0')==='1';}
function partnerMailReady(array $config):bool{return filter_var($config['mail_from']??'',FILTER_VALIDATE_EMAIL)&&in_array($config['mail_transport']??'',['mail','smtp'],true)&&(($config['mail_transport']??'')==='mail'||!empty($config['mail_host']));}
function partnerQueueMail(int $id,string $title,?int $event=null):void {
    $config=mailConfig();if(!partnerMailEnabled($config))return;$o=partnerOrderFor($id);$items=partnerOrderItems($id);$t=partnerTotals($o,$items);$lines=[$title.' B2B #'.$id,'Saradnik: '.$o['company'],'Kontakt: '.$o['contact_name'],'E-mail: '.$o['email'],'Telefon: '.$o['phone'],'Isporuka: '.$o['address'].', '.$o['city'],'Status: '.partnerStatus($o['status']),'Napomena: '.$o['note'],'','STAVKE'];
    foreach($items as $i)$lines[]=$i['sku'].' | '.$i['product_name'].' | naručeno '.$i['quantity'].' | isporučeno '.$i['fulfilled_qty'].' | otkazano '.$i['cancelled_qty'].' | vraćeno '.$i['returned_qty'].' | '.partnerMoney(cents($i['unit_price'])).'/kom | '.partnerMode($i['pricing_mode']).' '.$i['pricing_rate'].'%';
    array_push($lines,'','Vrijednost narudžbe: '.partnerMoney(cents($o['total'])),'Dostava: '.$o['shipping_method'].' / '.partnerMoney(cents($o['shipping_cost'])),'Uslovi: '.$o['shipping_terms'],'Realizovano za plaćanje: '.partnerMoney($t['due']),'Preostali artikli: '.partnerMoney($t['remaining']));
    if($event){$e=query('SELECT * FROM partner_order_events WHERE id=?',[$event])->fetch();$lines[]='';$lines[]='PROMJENA: '.partnerEventLabel($e['kind']).' - '.$e['note'];$lines[]='Kurir: '.$e['courier'].' / broj pošiljke: '.$e['tracking_number'];foreach(query('SELECT i.product_name,e.quantity FROM partner_event_items e JOIN partner_order_items i ON i.id=e.item_id WHERE event_id=?',[$event])->fetchAll() as $i)$lines[]=$i['product_name'].': '.$i['quantity'].' kom.';}
    $base=rtrim($config['mail_base_url']??'','/');$adminRecipients=mailRecipients(setting('partner_recipients')?:($config['mail_recipients']??''));$recipientRoles=[];foreach($adminRecipients as $email)$recipientRoles[$email]='admin';$recipientRoles[mb_strtolower($o['email'])]='partner';
    foreach($recipientRoles as $recipient=>$role){$body=implode("\n",$lines);if($base!=='')$body.="\n\n".$base.($role==='partner'?'/partner.php?page=order&id=':'/admin.php?page=partner_order&id=').$id;query('INSERT INTO mail_jobs(recipient,subject,body) VALUES(?,?,?)',[$recipient,'WEBSHOP: B2B #'.$id.' - '.$title,$body]);}
}
function partnerEventLabel(string $kind):string{return ['prepare'=>'Priprema','dispatch'=>'Isporuka','cancel'=>'Otkazivanje ostatka','return'=>'Povrat'][$kind]??$kind;}
function partnerScheduleMail():void {if(PHP_SAPI==='cli'||!function_exists('fastcgi_finish_request'))return;register_shutdown_function(static function(){if(session_status()===PHP_SESSION_ACTIVE)session_write_close();fastcgi_finish_request();try{processMailQueue(20);}catch(Throwable){error_log('B2B obavijesti čekaju cron.');}});}
