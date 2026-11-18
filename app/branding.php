<?php
declare(strict_types=1);
function brandImage(string $key):string {
    $path=setting($key);return preg_match('~^uploads/[a-f0-9]{32}\.(jpg|png|webp)$~D',$path)&&is_file(dirname(__DIR__).'/public/'.$path)?$path:'';
}
function brandWidth():int{return max(160,min(360,(int)setting('brand_logo_width','280')));}
function renderStoreBrand(bool $dark=false,bool $footer=false):void {
    $logo=($dark?brandImage('brand_logo_dark'):'')?:brandImage('brand_logo');$defaultLogo=false;if(!$logo&&alphaTheme()){$logo='assets/alpha/logo.png';$defaultLogo=true;}$name=setting('shop_name');$slogan=setting('brand_slogan','VAŠA ONLINE PRODAVNICA');
    ?><a class="brand <?=$logo?'brand-with-logo':''?> <?=$defaultLogo?'brand-logo-default':''?>" href="./" aria-label="<?=e($name.' — naslovnica')?>"><?php if($logo):?><img class="store-logo" src="<?=e($logo)?>" alt="<?=e($name)?>" style="--logo-width:<?=brandWidth()?>px" <?=!$footer?'fetchpriority="high"':'loading="lazy"'?>><?php endif;if(!$logo):?><span class="brand-mark" aria-hidden="true">w.</span><?php endif;if(!$logo||setting('brand_show_text','0')==='1'):?><strong><?=e($name)?><?php if($slogan!==''):?><small><?=e($slogan)?></small><?php endif;?></strong><?php endif;?></a><?php
}
function saveBranding():void {
    global $db;requirePermission('settings');$width=inputInt('brand_logo_width',160,360);$slogan=inputText('brand_slogan',160,false);$created=[];$values=['brand_slogan'=>$slogan,'brand_logo_width'=>(string)$width,'brand_show_text'=>isset($_POST['brand_show_text'])?'1':'0'];
    try{
        foreach(['brand_logo','brand_logo_dark','brand_icon'] as $field){
            $hasUpload=(($_FILES[$field]['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE);if($hasUpload&&isset($_POST['remove_'.$field]))throw new RuntimeException('Za istu sliku odaberite učitavanje ili uklanjanje.');
            if($hasUpload){[$url,$absolute]=productImage('',$field);if($absolute)$created[]=$absolute;$values[$field]=$url;}
            elseif(isset($_POST['remove_'.$field]))$values[$field]='';
        }
        $db->beginTransaction();foreach($values as $key=>$value)query('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',[$key,$value]);audit('branding.saved',null,'Ažurirani su logo, slogan i postavke prikaza prodavnice.');$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();foreach($created as $path)if(is_file($path))unlink($path);throw $e;}
}
