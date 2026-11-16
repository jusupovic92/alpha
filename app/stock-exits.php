<?php
declare(strict_types=1);

function createStockExit(int $product,int $qty,string $reason,string $price,string $payment,string $recipient,string $reference,string $note,string $token):int {
    global $db;requirePermission('inventory');
    if(!isset(exitReasons()[$reason])||$qty<1||$qty>1000000||trim($note)===''||mb_strlen($note)>1000||mb_strlen($reference)>150||mb_strlen($recipient)>180||!preg_match('/^[a-f0-9]{64}$/D',$token))throw new RuntimeException('Provjerite artikal, količinu, vrstu izlaza i obavezno obrazloženje.');
    $sale=$reason==='personal_sale';
    if($sale){if(trim($recipient)==='')throw new RuntimeException('Za ličnu prodaju unesite primaoca.');if(!in_array($payment,['unpaid','paid'],true))throw new RuntimeException('Neispravan status naplate.');if($payment==='paid')requirePermission('payments');$unit=cents($price);if($unit<1)throw new RuntimeException('Unesite prodajnu cijenu.');}
    else{if($payment!=='not_applicable')throw new RuntimeException('Naplata je dostupna samo za ličnu prodaju.');$unit=0;}
    if($unit*$qty>999999999999)throw new RuntimeException('Prevelik iznos izlaza.');
    try{
        $db->beginTransaction();
        $p=query('SELECT * FROM products WHERE id=? FOR UPDATE',[$product])->fetch();
        // The product lock also serializes repeat submissions with the same key.
        $existing=query('SELECT id FROM stock_exits WHERE idempotency_key=? FOR UPDATE',[$token])->fetchColumn();
        if($existing){$db->commit();return (int)$existing;}
        if(!$p||(int)$p['stock']<$qty)throw new RuntimeException('Nema dovoljno slobodnog lagera. Rezervisana roba ne može se izdati ovim putem.');
        query('INSERT INTO stock_exits(product_id,product_name,sku,quantity,reason,unit_price,total,payment_status,recipient,document_ref,note,user_id,idempotency_key) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)',[$product,$p['name'],$p['sku'],$qty,$reason,decimal($unit),decimal($unit*$qty),$sale?$payment:'not_applicable',$recipient,$reference,$note,currentUser()['id'],$token]);
        $id=(int)$db->lastInsertId();query('UPDATE products SET stock=stock-? WHERE id=?',[$qty,$product]);
        movement($product,-$qty,$reason,$note,null,$id);audit('stock_exit.created',$id,$reason.' · '.$reference.' · '.$note);
        if($sale&&$payment==='paid')audit('stock_exit.paid',$id,$reference.' · '.$note);
        $db->commit();return $id;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function recordExitPayment(int $id,string $next,string $note):void {
    global $db;requirePermission('payments');
    if(trim($note)===''||mb_strlen($note)>1000)throw new RuntimeException('Unesite dokument / obrazloženje naplate ili povrata.');
    try{
        $db->beginTransaction();$e=query('SELECT * FROM stock_exits WHERE id=? FOR UPDATE',[$id])->fetch();
        if(!$e||$e['reason']!=='personal_sale'||$e['reversed_at'])throw new RuntimeException('Ovaj izlaz nema otvorenu naplatu.');
        $allowed=match($e['payment_status']){'unpaid','refunded'=>['paid'],'paid'=>['refunded'],default=>[]};
        if(!in_array($next,$allowed,true))throw new RuntimeException('Nedozvoljena promjena naplate.');
        query('UPDATE stock_exits SET payment_status=? WHERE id=?',[$next,$id]);audit('stock_exit.'.$next,$id,$note);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function reverseStockExit(int $id,string $note):void {
    global $db;requirePermission('inventory');
    if(trim($note)===''||mb_strlen($note)>1000)throw new RuntimeException('Obrazloženje storna je obavezno.');
    try{
        $db->beginTransaction();$product=query('SELECT product_id FROM stock_exits WHERE id=?',[$id])->fetchColumn();
        if(!$product)throw new RuntimeException('Izlaz ne postoji.');
        $p=query('SELECT * FROM products WHERE id=? FOR UPDATE',[$product])->fetch();
        $e=query('SELECT * FROM stock_exits WHERE id=? FOR UPDATE',[$id])->fetch();
        if($e['reversed_at'])throw new RuntimeException('Izlaz je već storniran ili ne postoji.');
        if($e['payment_status']==='paid')throw new RuntimeException('Prvo evidentirajte povrat novca pa stornirajte izlaz.');
        if((int)$p['stock']+(int)$e['quantity']>2147483647)throw new RuntimeException('Preveliko stanje lagera.');
        query('UPDATE products SET stock=stock+? WHERE id=?',[$e['quantity'],$e['product_id']]);
        query('UPDATE stock_exits SET reversed_at=NOW(),reversal_note=?,reversal_user_id=? WHERE id=?',[$note,currentUser()['id'],$id]);
        movement((int)$e['product_id'],(int)$e['quantity'],'exit_reversal',$note,null,$id);audit('stock_exit.reversed',$id,$note);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function stockExitFilters(string $from,string $to):array {
    $where='1=1';$args=[];
    if(!can('inventory')){$where.=" AND e.reason='personal_sale'";}
    $q=mb_substr(trim((string)($_GET['q']??'')),0,100);
    if($q!==''){$where.=' AND (CAST(e.id AS CHAR) LIKE ? OR e.product_name LIKE ? OR e.sku LIKE ? OR e.recipient LIKE ? OR e.document_ref LIKE ? OR e.note LIKE ?)';array_push($args,...array_fill(0,6,'%'.$q.'%'));}
    foreach(['reason'=>'reason','payment'=>'payment_status','operator'=>'user_id'] as $key=>$column)if(($_GET[$key]??'')!==''){$where.=' AND e.'.$column.'=?';$args[]=(string)$_GET[$key];}
    if(($_GET['state']??'')==='active')$where.=' AND e.reversed_at IS NULL';if(($_GET['state']??'')==='reversed')$where.=' AND e.reversed_at IS NOT NULL';
    if($from!==''){$where.=' AND e.created_at>=?';$args[]=$from;}if($to!==''){$where.=' AND e.created_at<?';$args[]=(new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d');}
    return [$where,$args];
}
function exportStockExits(string $from,string $to):never {
    [$where,$args]=stockExitFilters($from,$to);
    header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="webshop-izlazi-'.date('Ymd').'.csv"');header('Cache-Control: no-store');
    $out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
    fputcsv($out,['Izlaz','Datum','SKU','Artikal','Vrsta','Kolicina','Primalac','Dokument','Obrazlozenje','Prodajni iznos KM','Naplata','Operater','Stornirano','Razlog storna'],';','"','');
    $s=query("SELECT e.*,u.name operator_name FROM stock_exits e JOIN users u ON u.id=e.user_id WHERE $where ORDER BY e.id DESC",$args);
    while($e=$s->fetch()){$row=[(int)$e['id'],$e['created_at'],$e['sku'],$e['product_name'],exitReasons()[$e['reason']],(int)$e['quantity'],$e['recipient'],$e['document_ref'],$e['note'],$e['reason']==='personal_sale'?str_replace('.',',',$e['total']):'',paymentLabel($e['payment_status']),$e['operator_name'],$e['reversed_at']??'',$e['reversal_note']??''];foreach($row as &$v)if(is_string($v)&&preg_match('/^[\s]*[=+@\-]/u',$v))$v="'".$v;unset($v);fputcsv($out,$row,';','"','');}
    fclose($out);exit;
}
