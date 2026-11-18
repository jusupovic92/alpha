<?php
declare(strict_types=1);
function deliveryPayers():array{return ['customer'=>'Dostavu plaća kupac','sender'=>'Dostavu plaća prodavac'];}
function deliveryTypesReady():bool{return (bool)query("SELECT version FROM schema_migrations WHERE version='015_personal_pickup'")->fetchColumn();}
function deliveryType(array $method):string{return ($method['delivery_type']??$method['shipping_type']??'courier')==='pickup'?'pickup':'courier';}
function deliveryPayerLabel(array $method):string{return deliveryType($method)==='pickup'?'Lično preuzimanje — bez troška dostave':(deliveryPayers()[$method['payer']??$method['shipping_payer']??'']??'Nije navedeno');}
function deliveryPaymentLabel(array $order):string{return ($order['payment_method']??'cod')==='cod'?(deliveryType($order)==='pickup'?'Plaćanje pri ličnom preuzimanju':'Pouzećem'):'Uplata na račun';}
function deliveryCost(string $value):string {try{return decimal(cents(str_replace(',','.',trim($value))));}catch(InvalidArgumentException){throw new RuntimeException('Unesite trošak dostave kao 0,00 ili 10,00 KM, bez oznake valute.');}}
function deliveryCharge(array $method):int{return deliveryType($method)==='pickup'||($method['payer']??'customer')==='sender'?0:cents((string)$method['cost']);}
function saveDelivery(int $id,string $name,string $cost,string $courier,string $terms,string $payer,bool $active,string $type='courier'):void {
    global $db;requirePermission('settings');
    if(!in_array($type,['courier','pickup'],true))throw new RuntimeException('Odaberite vrstu isporuke.');
    $typed=deliveryTypesReady();if($type==='pickup'&&!$typed)throw new RuntimeException('Prvo primijenite database/migrations/015_personal_pickup.sql nakon backupa.');
    if($type==='pickup'){$cost='0.00';$courier='';$payer='customer';}
    if(trim($name)===''||mb_strlen($name)>150||mb_strlen($courier)>150||mb_strlen($terms)>1000||!isset(deliveryPayers()[$payer]))throw new RuntimeException('Provjerite naziv, kurira i uslove dostave.');
    $amount=deliveryCost($cost);
    try{$db->beginTransaction();
        if($id){$old=query('SELECT * FROM shipping_methods WHERE id=? FOR UPDATE',[$id])->fetch();if(!$old||$old['deleted_at'])throw new RuntimeException('Način dostave ne postoji ili je obrisan.');query('UPDATE shipping_methods SET name=?,cost=?,courier_name=?,terms=?,payer=?,active=? WHERE id=?',[$name,$amount,$courier,$terms,$payer,(int)$active,$id]);}
        else{query('INSERT INTO shipping_methods(name,cost,courier_name,terms,payer,active) VALUES(?,?,?,?,?,?)',[$name,$amount,$courier,$terms,$payer,(int)$active]);$id=(int)$db->lastInsertId();}
        if($typed)query('UPDATE shipping_methods SET delivery_type=? WHERE id=?',[$type,$id]);
        audit('delivery.saved',$id,json_encode(['name'=>$name,'cost'=>$amount,'courier'=>$courier,'terms'=>$terms,'payer'=>$payer,'type'=>$type,'active'=>$active],JSON_UNESCAPED_UNICODE));$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function deleteDelivery(int $id,string $note):void {
    global $db;requirePermission('settings');if(trim($note)===''||mb_strlen($note)>1000)throw new RuntimeException('Unesite razlog brisanja dostave.');
    try{$db->beginTransaction();$d=query('SELECT * FROM shipping_methods WHERE id=? FOR UPDATE',[$id])->fetch();if(!$d||$d['deleted_at'])throw new RuntimeException('Dostava je već obrisana ili ne postoji.');query('UPDATE shipping_methods SET active=0,deleted_at=NOW() WHERE id=?',[$id]);audit('delivery.deleted',$id,$d['name'].' · '.$note);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function prepareShipment(int $id,int $method,string $courier,string $cost,string $terms,string $payer,string $tracking,string $note):void {
    global $db;requirePermission('orders');requirePermission('shipping');
    if(trim($courier)===''||mb_strlen($courier)>150||mb_strlen($terms)>1000||!isset(deliveryPayers()[$payer])||mb_strlen($tracking)>150||trim($note)===''||mb_strlen($note)>1000)throw new RuntimeException('Odaberite dostavu, kurira, uslove i obrazloženje pripreme.');
    $amount=deliveryCost($cost);
    try{$db->beginTransaction();$o=query('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id])->fetch();if(!$o||!in_array($o['status'],['new','preparing'],true))throw new RuntimeException('Dostavu možete pripremati samo prije otpreme.');
        $d=query('SELECT * FROM shipping_methods WHERE id=? AND deleted_at IS NULL FOR UPDATE',[$method])->fetch();if(!$d)throw new RuntimeException('Odaberite postojeći način dostave.');
        if(deliveryType($d)==='pickup'||deliveryType($o)==='pickup')throw new RuntimeException('Lično preuzimanje potvrdite kao ličnu predaju, bez pripreme kurirske pošiljke.');
        query('UPDATE orders SET planned_delivery_id=?,planned_delivery_name=?,planned_delivery_cost=?,planned_delivery_terms=?,planned_delivery_payer=?,courier_name=?,tracking_number=?,updated_at=NOW() WHERE id=?',[$method,$d['name'],$amount,$terms,$payer,$courier,$tracking,$id]);
        audit('order.delivery_prepared',$id,json_encode(['method'=>$d['name'],'courier'=>$courier,'cost'=>$amount,'payer'=>$payer,'terms'=>$terms,'tracking'=>$tracking,'note'=>$note],JSON_UNESCAPED_UNICODE));$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
