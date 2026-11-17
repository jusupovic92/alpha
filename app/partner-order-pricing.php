<?php
declare(strict_types=1);
function orderPricingReady():bool{return (bool)query("SELECT version FROM schema_migrations WHERE version='016_order_prices'")->fetchColumn();}
function initializePartnerOrderPriceSnapshot(int $id):void {
    if(!orderPricingReady())return;query('UPDATE partner_orders SET initial_total=total WHERE id=? AND initial_total IS NULL',[$id]);query('UPDATE partner_order_items SET original_base_price=COALESCE(original_base_price,base_price),original_unit_price=COALESCE(original_unit_price,unit_price),original_pricing_mode=COALESCE(original_pricing_mode,pricing_mode),original_pricing_rate=COALESCE(original_pricing_rate,pricing_rate),price_before_extra=COALESCE(price_before_extra,unit_price) WHERE order_id=?',[$id]);
}
function orderPriceMoney(mixed $value):int {if(!is_string($value))throw new RuntimeException('Unesite ispravan iznos cijene.');try{return cents(str_replace(',','.',trim($value)));}catch(Throwable){throw new RuntimeException('Cijena mora biti nenegativan iznos sa najviše dvije decimale.');}}
function partnerPriceState(array $item):array{return ['base_price'=>(string)$item['base_price'],'unit_price'=>(string)$item['unit_price'],'pricing_mode'=>(string)$item['pricing_mode'],'pricing_rate'=>(string)$item['pricing_rate'],'price_before_extra'=>(string)($item['price_before_extra']??$item['unit_price']),'extra_discount_rate'=>(string)($item['extra_discount_rate']??'0.00')];}
function partnerRepricedState(array $item,array $input):array {
    $mode=$input['mode']??null;if(!is_string($mode)||!in_array($mode,['discount','markup','fixed','original'],true))throw new RuntimeException('Odaberite način obračuna stavke.');
    if($mode==='original')return ['base_price'=>(string)($item['original_base_price']??$item['base_price']),'unit_price'=>(string)($item['original_unit_price']??$item['unit_price']),'pricing_mode'=>(string)($item['original_pricing_mode']??$item['pricing_mode']),'pricing_rate'=>(string)($item['original_pricing_rate']??$item['pricing_rate']),'price_before_extra'=>(string)($item['original_unit_price']??$item['unit_price']),'extra_discount_rate'=>'0.00'];
    $extra=partnerRate('discount',$input['extra_discount_rate']??null);
    if($mode==='fixed'){$base=cents((string)$item['base_price']);$rate=0;$before=orderPriceMoney($input['fixed_price']??null);}
    else{$base=orderPriceMoney($input['base_price']??null);$rate=partnerRate($mode,$input['rate']??null);$before=intdiv($base*($mode==='discount'?10000-$rate:10000+$rate)+5000,10000);}
    $unit=intdiv($before*(10000-$extra)+5000,10000);if($before>99999999999||$unit>99999999999)throw new RuntimeException('Prevelika obračunata cijena.');
    return ['base_price'=>decimal($base),'unit_price'=>decimal($unit),'pricing_mode'=>$mode,'pricing_rate'=>decimal($rate),'price_before_extra'=>decimal($before),'extra_discount_rate'=>decimal($extra)];
}
function revisePartnerOrderPrices(int $id,array $lines,string $note,int $version,string $token):void {
    global $db;requirePermission('orders');requirePermission('order_prices');if(!orderPricingReady())throw new RuntimeException('Prvo primijenite database/migrations/016_order_prices.sql nakon backupa.');
    if(!$lines||count($lines)>100||trim($note)===''||mb_strlen($note)>1000||str_contains($note,"\0")||!preg_match('/^[a-f0-9]{64}$/D',$token))throw new RuntimeException('Unesite stavke i obrazloženje promjene cijena.');
    try{$db->beginTransaction();$o=query('SELECT * FROM partner_orders WHERE id=? FOR UPDATE',[$id])->fetch();if(!$o)throw new RuntimeException('Narudžba nije pronađena.');
        $done=query('SELECT order_id,kind FROM partner_order_events WHERE event_token=?',[$token])->fetch();if($done){if((int)$done['order_id']!==$id||$done['kind']!=='pricing')throw new RuntimeException('Neispravna oznaka promjene.');$db->commit();return;}
        if((int)$o['version']!==$version)throw new RuntimeException('Narudžba je izmijenjena. Osvježite stranicu prije izmjene cijena.');
        $items=partnerOrderItems($id);$byId=array_column($items,null,'id');$changes=[];
        foreach($lines as $itemId=>$input){if(!isset($byId[$itemId])||!is_array($input))throw new RuntimeException('Neispravna stavka narudžbe.');$item=$byId[$itemId];$open=partnerItemOpen($item);if($open<1)throw new RuntimeException('Cijena isporučene ili otkazane stavke se ne mijenja.');$before=partnerPriceState($item);$after=partnerRepricedState($item,$input);if($before===$after)continue;
            $original=['original_base_price'=>(string)($item['original_base_price']??$item['base_price']),'original_unit_price'=>(string)($item['original_unit_price']??$item['unit_price']),'original_pricing_mode'=>(string)($item['original_pricing_mode']??$item['pricing_mode']),'original_pricing_rate'=>(string)($item['original_pricing_rate']??$item['pricing_rate'])];
            if($open!==(int)$item['quantity']){
                query('UPDATE partner_order_items SET quantity=quantity-? WHERE id=?',[$open,$itemId]);
                query('INSERT INTO partner_order_items(order_id,product_id,sku,product_name,quantity,base_price,unit_price,pricing_mode,pricing_rate,price_before_extra,extra_discount_rate,original_base_price,original_unit_price,original_pricing_mode,original_pricing_rate) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$id,$item['product_id'],$item['sku'],$item['product_name'],$open,$after['base_price'],$after['unit_price'],$after['pricing_mode'],$after['pricing_rate'],$after['price_before_extra'],$after['extra_discount_rate'],$original['original_base_price'],$original['original_unit_price'],$original['original_pricing_mode'],$original['original_pricing_rate']]);$result=(int)$db->lastInsertId();
            }else{$result=(int)$itemId;query('UPDATE partner_order_items SET base_price=?,unit_price=?,pricing_mode=?,pricing_rate=?,price_before_extra=?,extra_discount_rate=? WHERE id=?',[$after['base_price'],$after['unit_price'],$after['pricing_mode'],$after['pricing_rate'],$after['price_before_extra'],$after['extra_discount_rate'],$result]);}
            $changes[]=['source_item'=>(int)$itemId,'result_item'=>$result,'sku'=>$item['sku'],'product_name'=>$item['product_name'],'quantity'=>$open,'before'=>$before,'after'=>$after];
        }
        if(!$changes)throw new RuntimeException('Nema izmjena cijena za spremanje.');
        $total=cents((string)$o['shipping_cost']);$base=0;foreach(partnerOrderItems($id) as $item){$total+=(int)$item['quantity']*cents((string)$item['unit_price']);$base+=(int)$item['quantity']*cents((string)$item['base_price']);}if($total>99999999999||$base>99999999999)throw new RuntimeException('Prevelik iznos narudžbe.');
        query('UPDATE partner_orders SET total=?,base_total=?,version=version+1,updated_at=NOW() WHERE id=?',[decimal($total),decimal($base),$id]);
        query('INSERT INTO partner_order_events(order_id,kind,note,actor_id,event_token,pricing_details) VALUES(?,?,?,?,?,?)',[$id,'pricing',$note,currentUser()['id'],$token,json_encode($changes,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);$event=(int)$db->lastInsertId();partnerQueueMail($id,'Izmjena cijena narudžbe',$event);audit('partner.order_priced',$id,'Promjena #'.$event.' / '.$note);$db->commit();partnerScheduleMail();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function partnerPriceEventRows(array $event):array {$data=json_decode((string)($event['pricing_details']??''),true);return is_array($data)?array_values(array_filter($data,'is_array')):[];}
function partnerPriceChangeText(array $change):string {
    try{$before=$change['before'];$after=$change['after'];return (string)$change['sku'].' · '.(string)$change['product_name'].' · '.(int)$change['quantity'].' kom: '.partnerMoney(cents((string)$before['unit_price'])).' → '.partnerMoney(cents((string)$after['unit_price'])).' / kom. '.partnerMode((string)$after['pricing_mode']).' '.(string)$after['pricing_rate'].'%; dodatni popust '.(string)$after['extra_discount_rate'].'%.';}catch(Throwable){return 'Evidentirana izmjena cijene stavke.';}
}
function renderPartnerItemPricing(array $item):void {
    $s=partnerPriceState($item);$fixed=$s['pricing_mode']==='fixed';$before=cents($s['price_before_extra']);$extra=cents($s['extra_discount_rate']);
    ?><small><?=e(partnerMode($s['pricing_mode']))?> <?=$fixed?'':e($s['pricing_rate']).'%'?><br><?php if(!$fixed):?>Osnovica: <?=partnerMoney(cents($s['base_price']))?><br>Popust / dodatak: <?=partnerMoney(abs(cents($s['base_price'])-$before))?><?php else:?>Ručna cijena: <?=partnerMoney($before)?><?php endif;if($extra>0):?><br>Dodatni popust: <?=e($s['extra_discount_rate'])?>% (<?=partnerMoney($before-cents($s['unit_price']))?>)<?php endif;if(isset($item['original_unit_price'])&&cents((string)$item['original_unit_price'])!==cents($s['unit_price'])):?><br>Pri kreiranju: <?=partnerMoney(cents((string)$item['original_unit_price']))?><?php endif;?></small><?php
}
