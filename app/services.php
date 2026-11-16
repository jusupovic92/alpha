<?php
declare(strict_types=1);
require_once __DIR__.'/domain.php';
function query(string $sql, array $args=[]): PDOStatement { global $db; $s=$db->prepare($sql); $s->execute($args); return $s; }
function setting(string $key, string $default=''): string { global $settings; return (string)($settings[$key]??$default); }
function checkoutRateLimit(string $ip):void {
    global $db;$key=hash('sha256','checkout:'.$ip);$now=time();
    try{
        $db->beginTransaction();query('INSERT IGNORE INTO request_limits(identity_hash,window_started) VALUES(?,?)',[$key,$now]);
        $r=query('SELECT * FROM request_limits WHERE identity_hash=? FOR UPDATE',[$key])->fetch();
        $count=$now-(int)$r['window_started']>=900?0:(int)$r['attempts'];
        if($count>=10){$db->commit();throw new RuntimeException('Previše pokušaja naručivanja. Pokušajte za 15 minuta.');}
        query('UPDATE request_limits SET attempts=?,window_started=? WHERE identity_hash=?',[$count+1,$count===0?$now:$r['window_started'],$key]);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function audit(string $action, ?int $id=null, string $details=''): void { query('INSERT INTO audit_log(user_id,action,entity_id,details) VALUES(?,?,?,?)',[currentUser()['id']??null,$action,$id,$details]); }
function currentUser(): ?array {
    static $user=false;
    if ($user===false) $user=empty($_SESSION['user_id']) ? null : (query('SELECT u.*,r.name role_name,r.permissions FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.active=1',[$_SESSION['user_id']])->fetch()?:null);
    return $user;
}
function can(string $permission): bool { $u=currentUser(); if(!$u)return false; $p=json_decode($u['permissions'],true)??[]; return in_array('*',$p,true)||in_array($permission,$p,true); }
function requirePermission(string $permission): void { if(!can($permission)){http_response_code(403);exit('Nemate dozvolu za ovu radnju.');} }
function movement(int $product, int $quantity, string $kind, string $note, ?int $order=null, ?int $exit=null): void {
    $p=query('SELECT stock,reserved,purchase_price FROM products WHERE id=?',[$product])->fetch();
    query('INSERT INTO stock_movements(product_id,order_id,user_id,quantity,kind,note,available_after,reserved_after,unit_cost,stock_exit_id) VALUES(?,?,?,?,?,?,?,?,?,?)',[$product,$order,currentUser()['id']??null,$quantity,$kind,$note,$p['stock'],$p['reserved'],$p['purchase_price'],$exit]);
}
require_once __DIR__.'/delivery.php';
require_once __DIR__.'/mailer.php';
function checkout(array $cart, array $fields, int $shipping, string $payment, string $token): int {
    global $db;
    if(!$cart || count($cart)>100 || !preg_match('/^[a-f0-9]{64}$/D',$token))throw new RuntimeException('Korpa nije ispravna.');
    if(!in_array($payment,['cod','bank'],true) || ($payment==='bank' && setting('bank_details')===''))throw new RuntimeException('Način plaćanja nije dostupan.');
    try {
        $db->beginTransaction();
        $existing=query('SELECT id FROM orders WHERE checkout_token=?',[$token])->fetchColumn();
        if($existing){$db->commit();return (int)$existing;}
        $delivery=query('SELECT * FROM shipping_methods WHERE id=? AND active=1 FOR UPDATE',[$shipping])->fetch();
        if(!$delivery)throw new RuntimeException('Odaberite dostupnu dostavu.');
        ksort($cart,SORT_NUMERIC); $items=[]; $total=deliveryCharge($delivery);
        foreach($cart as $id=>$qty){
            $qty=filter_var($qty,FILTER_VALIDATE_INT);
            if(!$qty||$qty<1||$qty>100)throw new RuntimeException('Neispravna količina.');
            $p=query('SELECT * FROM products WHERE id=? AND active=1 FOR UPDATE',[(int)$id])->fetch();
            if(!$p||(int)$p['stock']<$qty)throw new RuntimeException('Proizvod više nije dostupan u traženoj količini.');
            $total+=cents((string)$p['price'])*$qty; $items[]=[$p,$qty];
        }
        if($total>999999999999)throw new RuntimeException('Prevelik iznos narudžbe.');
        query("INSERT INTO orders(customer_name,email,phone,address,city,note,total,shipping_method,shipping_cost,payment_method,inventory_state,status,checkout_token) VALUES(?,?,?,?,?,?,?,?,?,?,'reserved','preparing',?)",[$fields['customer_name'],$fields['email'],$fields['phone'],$fields['address'],$fields['city'],$fields['note'],decimal($total),$delivery['name'],decimal(deliveryCharge($delivery)),$payment,$token]);
        $order=(int)$db->lastInsertId();query('UPDATE orders SET shipping_terms=?,shipping_payer=? WHERE id=?',[$delivery['terms'],$delivery['payer'],$order]);
        foreach($items as [$p,$qty]){
            query('INSERT INTO order_items(order_id,product_id,product_name,quantity,unit_price) VALUES(?,?,?,?,?)',[$order,$p['id'],$p['name'],$qty,$p['price']]);
            query('UPDATE products SET stock=stock-?,reserved=reserved+? WHERE id=?',[$qty,$qty,$p['id']]);
            movement((int)$p['id'],-$qty,'reserve','Rezervacija narudžbe',$order);
        }
        query('INSERT INTO notifications(order_id,message) VALUES(?,?)',[$order,'Nova narudžba #'.$order]);
        enqueueOrderMail($order);audit('order.created',$order); $db->commit();try{scheduleOrderMail($order);}catch(Throwable){error_log('WEBSHOP: obavijest čeka cron obradu.');}return $order;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}finally{query("SELECT RELEASE_LOCK('webshop_mutations')");}
}
function changeOrder(int $id,string $next,string $tracking,string $courier='',string $note=''): void {
    global $db; requirePermission('orders');
    if(in_array($next,['shipped','handed_over','delivered','returned'],true))requirePermission('shipping');
    if(trim($note)===''||mb_strlen($note)>1000||mb_strlen($tracking)>150||mb_strlen($courier)>150)throw new RuntimeException('Obrazloženje / dokument promjene je obavezan. Provjerite dužinu podataka.');
    if($next==='shipped'&&trim($courier)==='')throw new RuntimeException('Odaberite ili unesite kurirsku službu. Broj pošiljke možete dopuniti kasnije.');
    try{
        $db->beginTransaction();$o=query('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id])->fetch();
        if(!$o||!in_array($next,transitions($o['status']),true))throw new RuntimeException('Nedozvoljena promjena statusa.');
        if($next==='shipped'){if(!$o['planned_delivery_id'])throw new RuntimeException('Prvo sačuvajte način i uslove dostave u pripremi.');$courier=(string)$o['courier_name'];}
        if($next==='returned' && $o['payment_status']==='paid')throw new RuntimeException('Prvo evidentirajte povrat novca.');
        if($next==='cancelled' && $o['payment_status']==='paid')throw new RuntimeException('Prvo evidentirajte povrat novca.');
        if($tracking==='')$tracking=(string)($o['tracking_number']??'');
        $state=$o['inventory_state'];
        if(in_array($next,['shipped','handed_over','cancelled','returned'],true)){
            $items=query('SELECT * FROM order_items WHERE order_id=? ORDER BY product_id',[$id])->fetchAll();
            foreach($items as $item){
                if(!$item['product_id'])continue;
                $p=query('SELECT * FROM products WHERE id=? FOR UPDATE',[$item['product_id']])->fetch();$q=(int)$item['quantity'];
                if($state==='reserved'){
                    if((int)$p['reserved']<$q)throw new RuntimeException('Neslaganje rezervacija.');
                    query('UPDATE products SET reserved=reserved-?,stock=stock+? WHERE id=?',[$q,$next==='cancelled'?$q:0,$p['id']]);
                    movement((int)$p['id'],$next==='cancelled'?$q:0,$next==='cancelled'?'release':($next==='handed_over'?'personal_handover':'dispatch'),'Narudžba #'.$id.' · '.$note,$id);
                }elseif(in_array($state,['deducted','legacy'],true)&&in_array($next,['cancelled','returned'],true)){
                    query('UPDATE products SET stock=stock+? WHERE id=?',[$q,$p['id']]);movement((int)$p['id'],$q,'return','Povrat na lager · '.$note,$id);
                }
            }
            $state=in_array($next,['shipped','handed_over'],true)?'deducted':'released';
        }
        query('UPDATE orders SET status=?,inventory_state=?,tracking_number=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$next,$state,$tracking,$id]);
        if(in_array($next,['shipped','handed_over'],true))query('UPDATE orders SET courier_name=?,dispatch_method=?,dispatched_at=NOW(),dispatch_note=? WHERE id=?',[$next==='shipped'?$courier:null,$next==='shipped'?'courier':'personal',$note,$id]);
        audit('order.status',$id,$o['status'].' -> '.$next.' · '.$courier.' · '.$tracking.' · '.$note);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function recordPayment(int $id,string $next,string $note):void {
    global $db;requirePermission('orders');requirePermission('payments');
    try{
        $db->beginTransaction();$o=query('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id])->fetch();
        $allowed=$o?match($o['payment_status']){'unpaid'=>['paid'],'paid'=>['refunded'],'refunded'=>['paid'],default=>[]}:[];
        if(!$o||!in_array($next,$allowed,true)||(in_array($o['status'],['cancelled','returned'],true)&&$next==='paid'))throw new RuntimeException('Nedozvoljena promjena naplate.');
        if(trim($note)===''||mb_strlen($note)>500)throw new RuntimeException('Unesite dokument ili napomenu naplate.');
        query('UPDATE orders SET payment_status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$next,$id]);audit('payment.'.$next,$id,$note);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function updateShipment(int $id,string $courier,string $tracking,string $note):void {
    global $db;requirePermission('orders');requirePermission('shipping');
    if(trim($courier)===''||trim($note)===''||mb_strlen($courier)>150||mb_strlen($tracking)>150||mb_strlen($note)>1000)throw new RuntimeException('Unesite kurira i obrazloženje dopune podataka.');
    try{
        $db->beginTransaction();$o=query('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id])->fetch();
        if(!$o||!in_array($o['status'],['shipped','delivered','completed'],true)||$o['dispatch_method']==='personal')throw new RuntimeException('Narudžba nije kurirska pošiljka.');
        query('UPDATE orders SET courier_name=?,tracking_number=?,updated_at=NOW() WHERE id=?',[$courier,$tracking,$id]);
        audit('order.shipment_updated',$id,'Kurir: '.($o['courier_name']??'—').' -> '.$courier.' · broj: '.($o['tracking_number']??'—').' -> '.$tracking.' · '.$note);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
require_once __DIR__.'/stock-exits.php';
function financialEventsSql(string $from,string $until):array {
    $sql="SELECT a.id,a.created_at,a.entity_id record_id,a.action,a.details,o.customer_name recipient,o.total,u.name operator_name,'order' source FROM audit_log a JOIN orders o ON o.id=a.entity_id LEFT JOIN users u ON u.id=a.user_id WHERE a.action IN ('payment.paid','payment.refunded') AND a.created_at>=? AND a.created_at<? UNION ALL SELECT a.id,a.created_at,a.entity_id record_id,a.action,a.details,e.recipient,e.total,u.name operator_name,'stock_exit' source FROM audit_log a JOIN stock_exits e ON e.id=a.entity_id LEFT JOIN users u ON u.id=a.user_id WHERE a.action IN ('stock_exit.paid','stock_exit.refunded') AND a.created_at>=? AND a.created_at<?";
    return [$sql,[$from,$until,$from,$until]];
}

function nextProductSku():string {
    global $db;requirePermission('products');if(!$db->inTransaction())throw new RuntimeException('Numeracija zahtijeva transakciju.');
    $next=(int)query("SELECT next_value FROM app_counters WHERE counter_key='product_sku' FOR UPDATE")->fetchColumn();if($next<1)throw new RuntimeException('Brojač artikala nije postavljen.');
    while(query('SELECT id FROM products WHERE sku=?',[(string)$next])->fetchColumn()){if($next>=999999999999)throw new RuntimeException('Brojač artikala je popunjen.');$next++;}
    if($next>=999999999999)throw new RuntimeException('Brojač artikala je popunjen.');query("UPDATE app_counters SET next_value=? WHERE counter_key='product_sku'",[$next+1]);return (string)$next;
}

require_once __DIR__.'/activity.php';

require_once __DIR__.'/backups.php';

require_once __DIR__.'/branding.php';

require_once __DIR__.'/alpha.php';

require_once __DIR__.'/alpha-content.php';
