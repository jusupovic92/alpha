<?php
declare(strict_types=1);

function fontScale(string $surface):string {$value=(int)setting($surface==='admin'?'admin_font_scale':'store_font_scale',$surface==='admin'?'120':'115');return (string)(max(100,min(140,$value))/100);}
function heroProducts():array {
    return query('SELECT p.*,c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.active=1 AND p.hero_selected=1 ORDER BY p.created_at DESC,p.id DESC LIMIT 3')->fetchAll();
}
function imageSource(?string $url,string $sku=''):string {
    $url=alphaImage((string)$url,$sku);
    if(preg_match('~^assets/alpha/[a-z0-9-]+\.(?:svg|jpg|png|webp)$~D',$url))return $url;
    if(preg_match('~^uploads/[a-f0-9]{32}\.(jpg|png|webp)$~D',$url))return $url;
    if(str_starts_with($url,'https://')&&filter_var($url,FILTER_VALIDATE_URL))return $url;
    return 'assets/product-placeholder.svg';
}
function productLink(int $id):string{return '?page=product&id='.$id;}
function excerptText(string $text,int $width=180):string{return mb_strimwidth(trim(preg_replace('/\s+/u',' ',$text)??$text),0,$width,'…','UTF-8');}
function discountCents(array $p):int {return !empty($p['compare_price'])?max(0,cents((string)$p['compare_price'])-cents((string)$p['price'])):0;}
function renderPrice(array $p):void {
    $saving=discountCents($p);?><span class="price-block <?=$saving?'has-discount':''?>"><strong class="price"><?=money((float)$p['price'])?></strong><?php if($saving):?><del><?=money((float)$p['compare_price'])?></del><small class="price-saving">Ušteda <?=money($saving/100)?> (<?= (int)round($saving/cents((string)$p['compare_price'])*100)?>%)</small><?php endif;?></span><?php
}
function productCard(array $p):void {
    $p=alphaPresentProduct($p);$link=productLink((int)$p['id']);$premium=storeTheme()==='alpha-premium';
    ?><article class="product-card"><a class="product-visual" href="<?=e($link)?>"><img src="<?=e(imageSource($p['image_url'],(string)($p['sku']??'')))?>" alt="<?=e($p['name'])?>" loading="lazy"><?php if(discountCents($p)):?><span class="badge sale-badge">Ušteda <?=money(discountCents($p)/100)?></span><?php endif;if((int)$p['stock']===0):?><span class="badge stock-empty">Rasprodano</span><?php else:?><span class="badge stock-ready">Na lageru</span><?php endif;?></a><?php if($premium):?><a class="alpha-product-view" href="<?=e($link)?>" aria-label="<?=e('Pogledaj proizvod: '.$p['name'])?>"><?=alphaIcon('eye')?></a><?php endif;?><div class="product-copy"><?php if($premium):?><p class="alpha-stock <?=(int)$p['stock']===0?'is-empty':''?>"><i aria-hidden="true"></i><?=(int)$p['stock']===0?'Trenutno nedostupno':'Na stanju'?></p><?php endif;?><p class="eyebrow"><?=e($p['category_name']??'Naša ponuda')?></p><h3><a href="<?=e($link)?>"><?=e($p['name'])?></a></h3><p class="product-excerpt"><?=e(mb_strimwidth((string)$p['description'],0,100,'…','UTF-8'))?></p><div class="product-bottom"><?php renderPrice($p);?><a class="round-link" href="<?=e($link)?>" aria-label="<?=e('Pogledaj '.$p['name'])?>">↗</a></div><form method="post" action="?page=add"><?=csrf()?><input type="hidden" name="id" value="<?=(int)$p['id']?>"><input type="hidden" name="qty" value="1"><button class="add-button" <?=(int)$p['stock']===0?'disabled':''?>><?php if($premium)echo alphaIcon('bag');?>Dodaj u korpu <span aria-hidden="true">＋</span></button></form></div></article><?php
}
function canReports():bool{return can('inventory')||(can('orders')&&can('payments'));}
function paymentLabel(string $status):string{return ['unpaid'=>'Nenaplaćeno','paid'=>'Naplaćeno','refunded'=>'Novac vraćen','not_applicable'=>'Bez naplate'][$status]??$status;}
function movementLabel(string $kind):string{return ['adjustment'=>'Ulaz / korekcija','reserve'=>'Rezervacija','release'=>'Oslobađanje','dispatch'=>'Slanje kuriru','personal_handover'=>'Lična predaja narudžbe','return'=>'Povrat','withdrawal'=>'Povučeno','write_off'=>'Otpis','personal_sale'=>'Lična prodaja','other'=>'Drugi izlaz','exit_reversal'=>'Storno izlaza'][$kind]??$kind;}
function listDateRange():array {
    $from=trim((string)($_GET['from']??''));$to=trim((string)($_GET['to']??''));
    foreach([$from,$to] as $date)if($date!==''){$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$d||$d->format('Y-m-d')!==$date||$date<'1970-01-01'||$date>'2100-12-31'){http_response_code(422);exit('Neispravan datum filtera.');}}
    if($from!==''&&$to!==''&&$from>$to){http_response_code(422);exit('Početni datum mora biti prije završnog.');}return [$from,$to];
}
