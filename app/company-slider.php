<?php
declare(strict_types=1);

function companySlideDefaults(): array {
    return [
        ['title'=>'Partner profesionalaca u svijetu autolakiranja.','text'=>'ALPHA Refinish povezuje materijale, opremu i podr�ku za svakodnevni rad profesionalnih radionica.','image'=>'assets/alpha/workshop-spray.jpg','alt'=>'Profesionalna priprema i lakiranje vozila u radionici','target'=>'partner','active'=>true],
        ['title'=>'Profesionalna rje�enja za profesionalne rezultate.','text'=>'Od pripreme povr�ine do zavr�nog sloja: istra�ite rje�enja za autolakiranje, poliranje i detailing.','image'=>'assets/alpha/paint-system.jpg','alt'=>'Priprema boja i materijala za autolakiranje','target'=>'catalog','active'=>true],
        ['title'=>'Sve za autolakirersku industriju na jednom mjestu.','text'=>'Boje, lakovi, brusni materijali i profesionalna oprema. Obratite nam se za pomo� pri izboru za va�u radionicu.','image'=>'assets/alpha/spray-guns.jpg','alt'=>'Profesionalna oprema i pi�tolji za lakiranje','target'=>'contact','active'=>true]
    ];
}
function companySlideTargets(): array {
    return ['catalog'=>['?all=1#ponuda','Istra�ite ponudu'],'partner'=>['partner.php','Portal za partnere'],'contact'=>['?page=info&section=kontakt','Kontaktirajte nas']];
}
function companyMetrics(): array {
    $defaults=[['value'=>'2020','label'=>'godina osnivanja'],['value'=>'35+','label'=>'brendova u ponudi'],['value'=>'120+','label'=>'aktivnih partnera']];
    $saved=json_decode(setting('company_metrics',''),true);
    if(!is_array($saved)||count($saved)!==3)return $defaults;
    foreach($saved as $s)if(!is_array($s)||!is_string($s['value']??null)||!is_string($s['label']??null))return $defaults;
    return $saved;
}
function companySlides(bool $all=false): array {
    $defaults=companySlideDefaults();
    $stored=json_decode(setting('company_slides',''),true);
    if(!is_array($stored)||count($stored)!==3)$stored=$defaults;
    $slides=[];
    foreach($defaults as $i=>$default){
        $s=is_array($stored[$i]??null)?$stored[$i]:$default;
        foreach(['title','text','image','alt'] as $key)if(!is_string($s[$key]??null)||trim($s[$key])==='')$s[$key]=$default[$key];
        $s['target']=(is_string($s['target']??null)&&isset(companySlideTargets()[$s['target']]))?$s['target']:$default['target'];
        $s['active']=!empty($s['active']);$slides[]=$s;
    }
    if($all)return $slides;
    $active=array_values(array_filter($slides,fn($s)=>$s['active']));
    return $active?:[$defaults[0]];
}
function saveCompanySlides(): void {
    global $db,$uploadedFiles;
    requirePermission('settings');$slides=[];
    foreach(companySlideDefaults() as $i=>$default){
        $p='company_'.$i.'_';
        $s=['title'=>inputText($p.'title',130),'text'=>inputText($p.'text',320),'alt'=>inputText($p.'alt',180),'target'=>inputText($p.'target',15),'active'=>isset($_POST[$p.'active'])];
        if(!isset(companySlideTargets()[$s['target']]))throw new RuntimeException('Odaberite va�e�e odredi�te dugmeta.');
        [$s['image'],$created]=productImage(inputText($p.'image',500),$p.'upload');
        if($created)$uploadedFiles[]=$created;
        $slides[]=$s;
    }
    if(!array_filter($slides,fn($s)=>$s['active']))throw new RuntimeException('Ostavite uklju�en barem jedan slajd o kompaniji.');
    $metrics=[];for($i=0;$i<3;$i++)$metrics[]=['value'=>inputText('metric_'.$i.'_value',20,false),'label'=>inputText('metric_'.$i.'_label',80,false)];
    $json=json_encode($slides,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    try{$db->beginTransaction();foreach(['company_slides'=>$json,'company_metrics'=>json_encode($metrics,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)] as $key=>$value)query('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',[$key,$value]);audit('settings.saved',null,'Sadr�aj kompanijskog slajdera');$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function alphaCompanyCarousel(): void {
    $slides=companySlides();$options=sliderConfig();
    ?><section class="alpha-company-carousel" aria-label="ALPHA Refinish - na�a kompanija" aria-roledescription="rotiraju�i prikaz" data-carousel data-interval="<?=$options['interval']*1000?>" data-duration="<?=$options['duration']?>" data-effect="<?=e($options['effect'])?>" data-autoplay="<?=$options['autoplay']?'1':'0'?>" data-hover-pause="<?=$options['hover']?'1':'0'?>">
    <?php foreach($slides as $i=>$s):[$href,$label]=companySlideTargets()[$s['target']];?><article class="alpha-company-slide" data-slide <?=$i?'hidden':''?> aria-label="<?=($i+1).' od '.count($slides)?>"><div class="alpha-company-copy"><p class="alpha-company-kicker">ALPHA REFINISH  UZ VA�U RADIONICU</p><?php if($i===0):?><h1><?=e($s['title'])?></h1><?php else:?><h2><?=e($s['title'])?></h2><?php endif;?><p><?=e($s['text'])?></p><div class="alpha-company-actions"><a class="btn" href="<?=e($href)?>"><?=e($label)?> <span aria-hidden="true"></span></a><a href="?page=info&amp;section=kontakt">Razgovarajmo o va�im potrebama </a></div></div><div class="alpha-company-photo"><img src="<?=e(imageSource($s['image']))?>" alt="<?=e($s['alt'])?>" width="1000" height="800" <?=$i?'loading="lazy"':'fetchpriority="high"'?>></div><aside class="alpha-company-metrics" aria-label="ALPHA Refinish u brojkama"><?php foreach(companyMetrics() as $metric):if($metric['value']===''||$metric['label']==='')continue;?><div><strong><?=e($metric['value'])?></strong><span><?=e($metric['label'])?></span></div><?php endforeach;?></aside></article><?php endforeach;?>
    <?php if(count($slides)>1):?><div class="alpha-slider-controls" data-carousel-controls hidden><button type="button" data-carousel-prev aria-label="Prethodni slajd"></button><div class="alpha-slider-dots"><?php foreach($slides as $i=>$s):?><button type="button" data-slide-index="<?=$i?>" aria-label="<?=e('Prika�i: '.$s['title'])?>" aria-pressed="<?=$i?'false':'true'?>"><?=$i+1?></button><?php endforeach;?></div><button type="button" data-carousel-next aria-label="Sljede�i slajd"></button><button type="button" data-carousel-pause aria-label="Zaustavi automatsku izmjenu">?</button><span class="sr-only" data-carousel-status aria-live="polite"></span></div><?php endif;?></section>
    <section class="alpha-company-intro" aria-label="�ta nudimo"><div><span>01 / MATERIJALI</span><h2>Od pripreme do zavr�nog sloja.</h2><p>Boje, lakovi i potro�ni materijal za profesionalnu autoreparaturu.</p></div><div><span>02 / OPREMA</span><h2>Alati za precizan rad.</h2><p>Rje�enja za bru�enje, lakiranje, poliranje i detailing.</p></div><div><span>03 / PARTNERSTVO</span><h2>Tu smo za va�u radionicu.</h2><p>Kontakt, izbor materijala i jednostavno naru�ivanje kroz partnerski portal.</p></div></section><?php
}
