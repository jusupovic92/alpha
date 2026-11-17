<?php
declare(strict_types=1);

function contactMailEnabled(array $config): bool {return ($config['mail_contact_enabled']??$config['mail_enabled']??'0')==='1';}

function submitContact(array $input,string $ip,?callable $sender=null): array {
    global $db;
    $config=mailConfig();
    if(!contactMailEnabled($config)||empty($config['mail_from'])||!mailRecipients($config['mail_recipients']??''))throw new RuntimeException('Kontakt mail nije podešen.');
    $token=$input['contact_token']??'';
    if(!is_string($token)||!preg_match('/^[a-f0-9]{64}$/D',$token)||!hash_equals($_SESSION['contact_token']??'',$token))throw new InvalidArgumentException('Obrazac je istekao ili već poslan. Osvježite stranicu.');
    if(!empty($input['website']))return [];
    $values=[];
    foreach(['contact_name'=>180,'contact_email'=>254,'contact_phone'=>60,'contact_message'=>5000] as $key=>$max){
        $value=$input[$key]??'';
        if(!is_string($value)||str_contains($value,"\0")||mb_strlen($value)>$max)throw new InvalidArgumentException('Provjerite dužinu unesenih podataka.');
        $values[$key]=trim($value);
    }
    if($values['contact_name']===''||$values['contact_message']===''||!filter_var($values['contact_email'],FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$values['contact_email'].$values['contact_name'].$values['contact_phone']))throw new InvalidArgumentException('Unesite ime, ispravan e-mail i poruku.');
    if(($input['contact_consent']??'')!=='1')throw new InvalidArgumentException('Potvrdite saglasnost za obradu podataka radi odgovora na upit.');
    checkoutRateLimit('contact:'.$ip);
    $body="Novi kontakt upit sa web prodavnice\nVrijeme: ".date('d.m.Y. H:i')."\n\nIme i prezime: ".$values['contact_name']."\nE-mail pošiljaoca: ".$values['contact_email']."\nTelefon: ".($values['contact_phone']?:'Nije unesen')."\n\nPORUKA\n".$values['contact_message'];
    $jobs=[];
    try{
        $db->beginTransaction();
        foreach(mailRecipients($config['mail_recipients']) as $recipient){query('INSERT INTO mail_jobs(recipient,subject,body) VALUES(?,?,?)',[$recipient,'WEBSHOP: kontakt - '.$values['contact_name'],$body]);$jobs[]=(int)$db->lastInsertId();}
        $db->commit();
        $_SESSION['contact_token']=bin2hex(random_bytes(32));
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    if(function_exists('fastcgi_finish_request')&&PHP_SAPI!=='cli')register_shutdown_function(static function()use($jobs,$sender){if(session_status()===PHP_SESSION_ACTIVE)session_write_close();fastcgi_finish_request();try{processMailQueue(20,null,$sender,$jobs);}catch(Throwable){error_log('ALPHA: kontakt mail čeka cron obradu.');}});
    else try{processMailQueue(1,null,$sender,$jobs);}catch(Throwable){error_log('ALPHA: kontakt mail čeka cron obradu.');}
    return $jobs;
}

function renderContactForm(): void {
    if(empty($_SESSION['contact_token']))$_SESSION['contact_token']=bin2hex(random_bytes(32));
    $config=mailConfig();$enabled=contactMailEnabled($config)&&!empty($config['mail_from'])&&!empty($config['mail_recipients']);
    $old=$_SESSION['contact_old']??[];$error=$_SESSION['contact_error']??'';$sent=!empty($_SESSION['contact_sent']);
    unset($_SESSION['contact_old'],$_SESSION['contact_error'],$_SESSION['contact_sent']);
    ?><section class="alpha-contact-form-wrap"><h2>Pošaljite nam upit</h2><?php if($sent):?><p class="alpha-form-success" role="status">Vaš upit je zaprimljen. Hvala što ste nas kontaktirali.</p><?php endif;if($error):?><p class="alpha-form-error" role="alert"><?=e((string)$error)?></p><?php endif;if(!$enabled):?><p>Kontakt forma trenutno nije aktivna. Koristite kontakt prodavnice naveden iznad.</p><?php else:?><form class="alpha-contact-form" method="post" action="?page=contact"><?=csrf()?><input type="hidden" name="contact_token" value="<?=e($_SESSION['contact_token'])?>"><div class="alpha-form-fields"><?php foreach(['contact_name'=>['Ime i prezime','text',180,true],'contact_email'=>['E-mail','email',254,true],'contact_phone'=>['Telefon (opcionalno)','tel',60,false]] as $key=>[$label,$type,$max,$required]):?><div><label for="<?=e($key)?>"><?=e($label)?></label><input id="<?=e($key)?>" name="<?=e($key)?>" type="<?=e($type)?>" maxlength="<?=$max?>" value="<?=e(is_string($old[$key]??null)?$old[$key]:'')?>" <?=$required?'required':''?> autocomplete="<?=['contact_name'=>'name','contact_email'=>'email','contact_phone'=>'tel'][$key]?>"></div><?php endforeach;?></div><label for="contact_message">Vaša poruka</label><textarea id="contact_message" name="contact_message" maxlength="5000" rows="6" required><?=e(is_string($old['contact_message']??null)?$old['contact_message']:'')?></textarea><div class="alpha-honeypot" aria-hidden="true"><label for="website">Ostavite prazno</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div><label class="alpha-consent"><input type="checkbox" name="contact_consent" value="1" required><span>Saglasan/a sam da se moji podaci koriste radi odgovora na ovaj upit. <a href="?page=info&amp;section=privatnost">Privatnost</a></span></label><button class="btn" type="submit">Pošalji upit →</button></form><?php endif;?></section><?php
}
