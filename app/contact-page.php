<?php
declare(strict_types=1);

function businessContact(): array {
    $configured=setting('contact_configured','0')==='1';
    $fallback=static function(string $key,string $old,string $default)use($configured):string{return $configured?setting($key,$default):(setting($key)!==''?setting($key):(setting($old)!==''?setting($old):$default));};
    return [
        'company'=>setting('contact_company','ALPHA refinish d.o.o.'),
        'email'=>$fallback('contact_email','contact_email','info@alpha-refinish.com'),
        'phone'=>$fallback('sender_phone','sender_phone','+387 61 401 860'),
        'address'=>$fallback('contact_address','sender_address',"Ul. Melente Bošnjaka broj 9\n72240 Kakanj\nBosna i Hercegovina"),
        'hours'=>setting('contact_hours','Ponedjeljak – Petak 08–16h'),
        'support'=>setting('contact_support_hours','Ponedjeljak – Petak 08–16h'),
        'id'=>setting('contact_company_id','4219071620006'),
        'vat'=>setting('contact_company_vat','2190716200006'),
        'mbs'=>setting('contact_company_mbs','43-01-0047-20'),
        'topbar'=>setting('contact_topbar_enabled','1')==='1',
        'tagline'=>setting('contact_topbar_text','Dostupno za radionice i partnere'),
        'title'=>setting('contact_title','Kontaktirajte nas'),
        'intro'=>setting('contact_intro','Za informacije o proizvodima, pomoć pri izboru ili upit o narudžbi kontaktirajte nas telefonom, e-mailom ili putem obrasca.'),
        'footer_text'=>setting('contact_footer_text','Materijali i oprema za profesionalnu autoreparaturu, poliranje i detailing.'),
        'online_note'=>setting('contact_online_note','Naručujte online 24/7'),
        'whatsapp'=>setting('contact_whatsapp','1')==='1',
        'viber'=>setting('contact_viber','1')==='1',
        'map_enabled'=>setting('contact_map_enabled','1')==='1',
        'map_query'=>setting('contact_map_query',''),
        'map_embed'=>setting('contact_map_embed'),
        'map_link'=>setting('contact_map_link')
    ];
}
function contactIcon(string $name): string {
    $paths=[
        'phone'=>'<path d="m5 3 4 1 1 5-3 2c2 4 3 5 7 7l2-3 5 1 1 4c-1 4-6 2-10 0C7 17 2 11 2 7c0-2 1-3 3-4z"/>',
        'mail'=>'<rect x="2" y="4" width="20" height="16" rx="3"/><path d="m3 6 9 7 9-7"/>',
        'pin'=>'<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="2.5"/>',
        'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/>'
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['pin']).'</svg>';
}
function normalizeMapEmbed(string $value): string {
    $value=trim($value);if($value==='')return '';
    if(str_starts_with($value,'<')){if(!preg_match('~<iframe\b[^>]*\bsrc\s*=\s*([\x22\x27])(.*?)\1~is',$value,$match))throw new RuntimeException('Zalijepite Google Maps iframe ili njegovu src adresu.');$value=html_entity_decode($match[2],ENT_QUOTES|ENT_HTML5,'UTF-8');}
    $parts=parse_url($value);$host=strtolower((string)($parts['host']??''));$path=(string)($parts['path']??'');
    if(!filter_var($value,FILTER_VALIDATE_URL)||($parts['scheme']??'')!=='https'||isset($parts['user'])||isset($parts['pass'])||isset($parts['port'])||!in_array($host,['www.google.com','maps.google.com','www.google.ba','maps.google.ba'],true)||!(str_starts_with($path,'/maps/embed')||($path==='/maps'&&str_starts_with($host,'maps.'))))throw new RuntimeException('Ugrađena mapa mora biti HTTPS Google Maps embed adresa.');
    return $value;
}
function validateMapLink(string $url): string {
    if($url==='')return '';$parts=parse_url($url);$host=strtolower((string)($parts['host']??''));
    if(!filter_var($url,FILTER_VALIDATE_URL)||($parts['scheme']??'')!=='https'||isset($parts['user'])||isset($parts['pass'])||isset($parts['port'])||!in_array($host,['www.google.com','google.com','maps.google.com','www.google.ba','maps.google.ba','maps.app.goo.gl','goo.gl'],true))throw new RuntimeException('Link lokacije mora biti Google Maps HTTPS adresa.');
    return $url;
}
function contactMapUrls(array $contact): array {
    $query=trim($contact['map_query']?:($contact['address']!==''?$contact['company'].', '.str_replace("\n",', ',$contact['address']):''));
    try{$embed=normalizeMapEmbed($contact['map_embed']);}catch(Throwable){$embed='';}
    if($embed===''&&$query!=='')$embed='https://maps.google.com/maps?q='.rawurlencode($query).'&z=14&output=embed';
    try{$link=validateMapLink($contact['map_link']);}catch(Throwable){$link='';}
    if($link===''&&$query!=='')$link='https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);
    return [$embed,$link];
}
function contactDeliveryConfig(): array {
    $config=mailConfig();$recipients=trim(setting('contact_recipients'));
    if($recipients!=='')$config['mail_recipients']=$recipients;
    return $config;
}
function contactTransportReady(array $config): bool {
    try{$recipients=mailRecipients($config['mail_recipients']??'');}catch(Throwable){return false;}
    return contactMailEnabled($config)&&$recipients&&filter_var($config['mail_from']??'',FILTER_VALIDATE_EMAIL)&&in_array($config['mail_transport']??'',['smtp','mail'],true)&&(($config['mail_transport']??'')==='mail'||(!empty($config['mail_host'])&&(int)($config['mail_port']??0)>0));
}
function saveContactSettings(): void {
    global $db;requirePermission('settings');
    $keys=['contact_company'=>180,'contact_email'=>254,'sender_phone'=>60,'contact_address'=>1000,'contact_hours'=>500,'contact_support_hours'=>500,'contact_company_id'=>60,'contact_company_vat'=>60,'contact_company_mbs'=>100,'contact_topbar_text'=>250,'contact_title'=>150,'contact_intro'=>1500,'contact_footer_text'=>1000,'contact_online_note'=>250,'contact_map_query'=>700,'contact_map_embed'=>5000,'contact_map_link'=>1500,'contact_recipients'=>1500];$values=[];
    foreach($keys as $key=>$limit)$values[$key]=inputText($key,$limit,in_array($key,['contact_company','contact_title'],true));
    if($values['contact_email']!==''&&!filter_var($values['contact_email'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('Unesite ispravan javni e-mail.');
    if($values['sender_phone']!==''&&!preg_match('~^[+0-9 ()/.,-]{5,60}$~D',$values['sender_phone']))throw new RuntimeException('Provjerite format telefonskog broja.');
    $values['contact_recipients']=implode("\n",mailRecipients($values['contact_recipients']));
    $values['contact_map_embed']=normalizeMapEmbed($values['contact_map_embed']);$values['contact_map_link']=validateMapLink($values['contact_map_link']);
    foreach(['contact_topbar_enabled','contact_map_enabled','contact_whatsapp','contact_viber','contact_form_enabled','mail_contact_enabled'] as $key)$values[$key]=isset($_POST[$key])?'1':'0';
    $values['contact_configured']='1';
    try{$db->beginTransaction();foreach($values as $key=>$value)query('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',[$key,$value]);audit('settings.saved',null,'Kontakt, lokacija, radno vrijeme i primaoci upita');$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function renderContactTopbar(): void {
    $c=businessContact();if(!$c['topbar'])return;
    ?><div class="alpha-contact-topbar"><div><?php if($c['phone']!==''):?><a href="tel:<?=e(preg_replace('/[^+0-9]/','',$c['phone'])??'')?>"><?=contactIcon('phone')?><span><?=e($c['phone'])?></span></a><?php endif;if(filter_var($c['email'],FILTER_VALIDATE_EMAIL)):?><a href="mailto:<?=e($c['email'])?>"><?=contactIcon('mail')?><span><?=e($c['email'])?></span></a><?php endif;if($c['tagline']!==''):?><span class="alpha-contact-topnote"><?=e($c['tagline'])?></span><?php endif;?></div></div><?php
}
function contactChatLinks(array $c): void {
    $digits=preg_replace('/\D/','',$c['phone'])??'';if($digits==='')return;
    ?><div class="alpha-chat-links"><?php if($c['whatsapp']):?><a href="https://wa.me/<?=e($digits)?>" target="_blank" rel="noopener noreferrer">WhatsApp ↗</a><?php endif;if($c['viber']):?><a href="viber://chat?number=<?=e(rawurlencode('+'.$digits))?>">Viber ↗</a><?php endif;?></div><?php
}
function contactCompanyDetails(array $c): void {
    if($c['address']!==''):?><p class="alpha-business-address"><?=nl2br(e($c['address']))?></p><?php endif;
    $identifiers=['ID'=>$c['id'],'PDV'=>$c['vat'],'MBS'=>$c['mbs']];?><dl class="alpha-business-ids"><?php foreach($identifiers as $label=>$value):if($value==='')continue;?><div><dt><?=e($label)?></dt><dd><?=e($value)?></dd></div><?php endforeach;?></dl><?php
}
function renderRichContactPage(): void {
    $c=businessContact();[$embed,$mapLink]=contactMapUrls($c);
    ?><section class="alpha-content-page alpha-contact-page"><header class="alpha-contact-hero"><a class="alpha-back" href="./">← Povratak na naslovnicu</a><p class="alpha-section-kicker">TU SMO ZA VAS</p><h1><?=e($c['title'])?></h1><p><?=nl2br(e($c['intro']))?></p></header><div class="alpha-contact-cards">
    <?php if($c['phone']!==''):?><a class="alpha-contact-card" href="tel:<?=e(preg_replace('/[^+0-9]/','',$c['phone'])??'')?>"><span class="alpha-contact-icon"><?=contactIcon('phone')?></span><div><h2>Telefon</h2><p><?=e($c['phone'])?></p></div></a><?php endif;if(filter_var($c['email'],FILTER_VALIDATE_EMAIL)):?><a class="alpha-contact-card" href="mailto:<?=e($c['email'])?>"><span class="alpha-contact-icon"><?=contactIcon('mail')?></span><div><h2>E-mail</h2><p><?=e($c['email'])?></p></div></a><?php endif;if($c['address']!==''):?><div class="alpha-contact-card"><span class="alpha-contact-icon"><?=contactIcon('pin')?></span><div><h2>Adresa</h2><p><?=nl2br(e($c['address']))?></p></div></div><?php endif;if($c['hours']!==''):?><div class="alpha-contact-card"><span class="alpha-contact-icon"><?=contactIcon('clock')?></span><div><h2>Radno vrijeme</h2><p><?=nl2br(e($c['hours']))?></p></div></div><?php endif;?></div>
    <div class="alpha-contact-body <?=setting('contact_form_enabled','1')==='1'?'':'is-form-disabled'?>"><?php if(setting('contact_form_enabled','1')==='1'):?><div class="alpha-contact-form-panel"><?php renderContactForm();?></div><?php endif;?><aside class="alpha-contact-business"><p class="alpha-section-kicker">KONTAKT I PODRŠKA</p><h2><?=e($c['company'])?></h2><?php contactCompanyDetails($c);if($c['online_note']!==''):?><p class="alpha-online-note"><?=e($c['online_note'])?></p><?php endif;if($c['support']!==''):?><h3>Podrška</h3><p><?=nl2br(e($c['support']))?></p><?php endif;contactChatLinks($c);if($mapLink!==''):?><a class="btn secondary" href="<?=e($mapLink)?>" target="_blank" rel="noopener noreferrer">Otvori u Google Maps ↗</a><?php endif;?></aside></div>
    <?php if($c['map_enabled']&&$embed!==''):?><section class="alpha-contact-map" aria-labelledby="contact-map-title"><div class="section-heading"><div><p class="alpha-section-kicker">NAŠA LOKACIJA</p><h2 id="contact-map-title">Pronađite nas na mapi</h2></div><?php if($mapLink!==''):?><a class="alpha-view-all" href="<?=e($mapLink)?>" target="_blank" rel="noopener noreferrer">Google Maps ↗</a><?php endif;?></div><iframe src="<?=e($embed)?>" title="<?=e('Lokacija: '.$c['company'])?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></section><?php endif;?></section><?php
}
function renderBusinessFooter(): void {
    $c=businessContact();?><footer class="store-footer alpha-rich-footer alpha-business-footer"><div class="alpha-footer-brand"><?php renderStoreBrand(true,true);?><p><?=nl2br(e($c['footer_text']))?></p><h3><?=e($c['company'])?></h3><?php contactCompanyDetails($c);?></div><div><h3>Ponuda</h3><?php foreach(array_slice(alphaCategories(),0,6) as $category):?><a href="?category=<?=(int)$category['id']?>#ponuda"><?=e($category['name'])?></a><?php endforeach;?></div><div><h3>Korisni linkovi</h3><?php foreach(['kontakt'=>'Kontakt','dostava'=>'Dostava i plaćanje','reklamacije'=>'Reklamacije i povrat','uslovi'=>'Uslovi kupovine','privatnost'=>'Politika privatnosti'] as $slug=>$title):?><a href="?page=info&amp;section=<?=e($slug)?>"><?=e($title)?></a><?php endforeach;?></div><div><h3>Radno vrijeme</h3><?php if($c['hours']!==''):?><p><?=nl2br(e($c['hours']))?></p><?php endif;if($c['online_note']!==''):?><p class="alpha-online-note"><?=e($c['online_note'])?></p><?php endif;?><h3>Kontakt i podrška</h3><?php alphaContact();if($c['support']!==''):?><p><?=nl2br(e($c['support']))?></p><?php endif;contactChatLinks($c);?></div><div class="footer-bottom"><span>© <?=date('Y')?> <?=e(setting('shop_name'))?></span><a href="?page=info&amp;section=privatnost">Privatnost</a><a href="admin.php">Administracija ↗</a></div></footer><?php
}
