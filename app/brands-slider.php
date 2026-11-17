<?php
declare(strict_types=1);

function brandsReady(): bool {return (bool)query("SELECT version FROM schema_migrations WHERE version='012_brands_slider'")->fetchColumn();}
function sliderConfig(): array {
    $effect=setting('slider_effect','fade');
    return ['count'=>max(1,min(12,(int)setting('slider_count','3'))),'interval'=>max(2,min(30,(int)setting('slider_interval','6'))),'effect'=>in_array($effect,['fade','slide','none'],true)?$effect:'fade','duration'=>max(150,min(1200,(int)setting('slider_duration','500'))),'autoplay'=>setting('slider_autoplay','1')==='1','hover'=>setting('slider_hover_pause','1')==='1'];
}
function saveSliderSettings(): void {
    global $db;requirePermission('settings');
    $values=['slider_count'=>(string)inputInt('slider_count',1,12),'slider_interval'=>(string)inputInt('slider_interval',2,30),'slider_duration'=>(string)inputInt('slider_duration',150,1200),'slider_effect'=>inputText('slider_effect',10),'slider_autoplay'=>isset($_POST['slider_autoplay'])?'1':'0','slider_hover_pause'=>isset($_POST['slider_hover_pause'])?'1':'0'];
    if(!in_array($values['slider_effect'],['fade','slide','none'],true))throw new RuntimeException('Odaberite način promjene objava.');
    try{$db->beginTransaction();foreach($values as $key=>$value)query('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',[$key,$value]);audit('settings.saved',null,'Postavke naslovnog slidera');$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function alphaBrandStrip(): void {
    if(!brandsReady())return;
    $brands=query('SELECT b.* FROM brands b WHERE b.active=1 AND b.show_on_home=1 AND EXISTS(SELECT 1 FROM products p WHERE p.brand_id=b.id AND p.active=1) ORDER BY b.sort_order,b.name')->fetchAll();
    if(!$brands)return;
    ?><section class="alpha-brand-section" aria-labelledby="alpha-brand-title"><div class="section-heading"><div><p class="eyebrow">BRENDOVI</p><h2 id="alpha-brand-title">Brendovi za vašu radionicu</h2></div><a class="alpha-view-all" href="?all=1#ponuda">Pogledaj ponudu →</a></div><div class="alpha-brand-grid alpha-logo-grid"><?php foreach($brands as $brand):?><a class="<?=!empty($brand['logo_dark'])?'is-dark-logo':''?>" href="?brand=<?=(int)$brand['id']?>#ponuda" aria-label="<?=e('Proizvodi brenda '.$brand['name'])?>"><?php if($brand['logo_url']!==''):?><img src="<?=e(imageSource($brand['logo_url']))?>" alt="<?=e($brand['name'])?>" loading="lazy" width="220" height="90"><?php else:?><strong><?=e($brand['name'])?></strong><?php endif;?><span aria-hidden="true">↗</span></a><?php endforeach;?></div></section><?php
}
function alphaProductCarousel(array $products,string $background): void {
    $options=sliderConfig();
    ?><section class="alpha-hero alpha-photo-hero alpha-product-carousel" aria-label="Naslovne objave proizvoda" aria-roledescription="rotirajući prikaz" data-carousel data-interval="<?=$options['interval']*1000?>" data-duration="<?=$options['duration']?>" data-effect="<?=e($options['effect'])?>" data-autoplay="<?=$options['autoplay']?'1':'0'?>" data-hover-pause="<?=$options['hover']?'1':'0'?>">
    <?php foreach($products as $index=>$product):$product=alphaPresentProduct($product);?><article class="alpha-product-slide" data-slide <?=$index?'hidden':''?> aria-label="<?=e(($index+1).' od '.count($products))?>"><div class="alpha-slide-backdrop"><img src="<?=e($background)?>" alt="" width="1600" height="800"></div><div class="alpha-slide-copy"><p class="alpha-kicker">IZDVAJAMO ZA VAŠU RADIONICU</p><h1 title="<?=e($product['name'])?>"><?=e($product['name'])?></h1><p class="alpha-slide-description"><?=e(excerptText((string)$product['description'],160))?></p><div class="alpha-slide-price"><?php renderPrice($product);?></div><a class="btn" href="<?=e(productLink((int)$product['id']))?>">Pogledaj proizvod <span aria-hidden="true">→</span></a></div><a class="alpha-slide-visual <?=!empty($product['hero_banner_url'])?'has-banner':''?>" href="<?=e(productLink((int)$product['id']))?>" aria-label="<?=e('Pogledaj '.$product['name'])?>"><img src="<?=e(imageSource(!empty($product['hero_banner_url'])?$product['hero_banner_url']:$product['image_url'],(string)$product['sku']))?>" alt="<?=e($product['name'])?>" width="700" height="600" <?=$index?'loading="lazy"':'fetchpriority="high"'?>></a></article><?php endforeach;?>
    <?php if(count($products)>1):?><div class="alpha-slider-controls" data-carousel-controls hidden><button type="button" data-carousel-prev aria-label="Prethodna objava">←</button><div class="alpha-slider-dots"><?php foreach($products as $index=>$product):?><button type="button" data-slide-index="<?=$index?>" aria-label="<?=e('Prikaži '.$product['name'])?>" aria-pressed="<?=$index?'false':'true'?>"><?=$index+1?></button><?php endforeach;?></div><button type="button" data-carousel-next aria-label="Sljedeća objava">→</button><button type="button" data-carousel-pause aria-label="Zaustavi automatsku izmjenu">Ⅱ</button><span class="sr-only" data-carousel-status aria-live="polite"></span></div><?php endif;?></section><?php
}
