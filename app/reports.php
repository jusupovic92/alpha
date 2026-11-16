<?php
declare(strict_types=1);
if(!canReports()){http_response_code(403);exit('Nemate dozvolu za izvještaje.');}
$from=(string)($_GET['from']??date('Y-m-01'));$to=(string)($_GET['to']??date('Y-m-d'));$product=(int)($_GET['product']??0);
foreach([$from,$to] as $date){$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$d||$d->format('Y-m-d')!==$date){http_response_code(422);exit('Neispravan datum izvještaja.');}}
if($from>$to){http_response_code(422);exit('Početni datum mora biti prije završnog.');}
$until=(new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d');
$export=(string)($_GET['export']??'');
if($export!==''){
    if(in_array($export,['stock','movements'],true))requirePermission('inventory');elseif($export==='payments'){requirePermission('orders');requirePermission('payments');}else{http_response_code(400);exit('Nepoznat izvještaj.');}
    header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="webshop-'.$export.'-'.date('Ymd').'.csv"');header('Cache-Control: no-store');
    $out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
    $write=function(array $row)use($out):void{foreach($row as &$v)if(is_string($v)&&preg_match('/^[\s]*[=+@\-]/u',$v))$v="'".$v;unset($v);fputcsv($out,$row,';','"','');};
    if($export==='stock'){
        $write(['SKU','Artikal','Slobodno','Rezervisano','Fizicko stanje','Nabavna cijena KM','Nabavna vrijednost KM - trenutna cijena']);
        $s=query('SELECT *,((stock+reserved)*purchase_price) value FROM products ORDER BY sku');while($r=$s->fetch())$write([$r['sku'],$r['name'],(int)$r['stock'],(int)$r['reserved'],(int)$r['stock']+(int)$r['reserved'],str_replace('.',',',$r['purchase_price']),str_replace('.',',',$r['value'])]);
    }elseif($export==='movements'){
        $write(['ID','Datum','SKU','Artikal','Vrsta','Promjena slobodnog','Slobodno poslije','Rezervisano poslije','Nabavna cijena tada KM','Narudzba','Drugi izlaz','Dokument / razlog','Operater']);
        $s=query('SELECT m.*,p.sku,p.name,u.name operator_name FROM stock_movements m JOIN products p ON p.id=m.product_id LEFT JOIN users u ON u.id=m.user_id WHERE m.created_at>=? AND m.created_at<? AND (?=0 OR m.product_id=?) ORDER BY m.id',[$from,$until,$product,$product]);
        while($r=$s->fetch())$write([(int)$r['id'],$r['created_at'],$r['sku'],$r['name'],movementLabel($r['kind']),(int)$r['quantity'],$r['available_after']===null?'':(int)$r['available_after'],$r['reserved_after']===null?'':(int)$r['reserved_after'],$r['unit_cost']===null?'':str_replace('.',',',$r['unit_cost']),$r['order_id']===null?'':(int)$r['order_id'],$r['stock_exit_id']===null?'':(int)$r['stock_exit_id'],$r['note'],$r['operator_name']??'Kupac / sistem']);
    }else{
        $write(['Evidencija','Datum radnje','Izvor','Dokument','Primalac','Radnja','Iznos KM','Obrazlozenje','Operater']);
        [$eventSql,$eventArgs]=financialEventsSql($from,$until);$s=query("SELECT * FROM ($eventSql) events ORDER BY id",$eventArgs);
        while($r=$s->fetch())$write([(int)$r['id'],$r['created_at'],$r['source']==='order'?'Web narudzba':'Licna prodaja',(int)$r['record_id'],$r['recipient'],in_array($r['action'],['payment.paid','stock_exit.paid'],true)?'Naplata':'Povrat novca',str_replace('.',',',$r['total']),$r['details'],$r['operator_name']??'Sistem']);
    }
    fclose($out);exit;
}
