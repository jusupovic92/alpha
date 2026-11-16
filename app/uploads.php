<?php
declare(strict_types=1);
function productImage(string $url,string $field='image'):array {
    $upload=$_FILES[$field]??null;
    if(!$upload||($upload['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE){
        if($url!==''&&!preg_match('~^uploads/[a-f0-9]{32}\.(jpg|png|webp)$~D',$url)&&(!filter_var($url,FILTER_VALIDATE_URL)||!str_starts_with($url,'https://')))throw new RuntimeException('Slika mora imati HTTPS adresu ili biti postavljena kroz obrazac.');
        return [$url,null];
    }
    if(($upload['error']??1)!==UPLOAD_ERR_OK||!is_uploaded_file($upload['tmp_name'])||(int)$upload['size']>5*1024*1024)throw new RuntimeException('Slika mora biti manja od 5 MB.');
    $info=@getimagesize($upload['tmp_name']);$extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!$info||!isset($extensions[$info['mime']])||$info[0]>6000||$info[1]>6000)throw new RuntimeException('Koristite JPG, PNG ili WebP do 6000 × 6000 piksela.');
    $name=bin2hex(random_bytes(16)).'.'.$extensions[$info['mime']];$relative='uploads/'.$name;$absolute=dirname(__DIR__).'/public/'.$relative;
    if(!move_uploaded_file($upload['tmp_name'],$absolute))throw new RuntimeException('Nije moguće spremiti sliku. Provjerite dozvolu pisanja za public/uploads.');
    return [$relative,$absolute];
}
function productGallery(string $text):array {
    $urls=array_values(array_filter(array_map('trim',preg_split('/\R/',$text)?:[]),fn($s)=>$s!==''));
    $files=$_FILES['gallery']??null;$count=0;
    if($files&&is_array($files['name']??null))foreach($files['error'] as $error)if($error!==UPLOAD_ERR_NO_FILE)$count++;
    if(count($urls)+$count>8)throw new RuntimeException('Galerija može imati najviše osam dodatnih slika.');
    foreach($urls as $url)if(strlen($url)>500||(!preg_match('~^uploads/[a-f0-9]{32}\.(jpg|png|webp)$~D',$url)&&(!filter_var($url,FILTER_VALIDATE_URL)||!str_starts_with($url,'https://'))))throw new RuntimeException('Neispravna adresa slike u galeriji.');
    $created=[];$original=$_FILES['image']??null;
    try{
        if($count)foreach($files['name'] as $i=>$name){
            if($files['error'][$i]===UPLOAD_ERR_NO_FILE)continue;
            $_FILES['image']=['name'=>$name,'tmp_name'=>$files['tmp_name'][$i],'error'=>$files['error'][$i],'size'=>$files['size'][$i],'type'=>$files['type'][$i]];
            [$url,$path]=productImage('');$urls[]=$url;if($path)$created[]=$path;
        }
        return [array_values(array_unique($urls)),$created];
    }catch(Throwable $e){foreach($created as $path)if(is_file($path))unlink($path);throw $e;}
    finally{if($original===null)unset($_FILES['image']);else $_FILES['image']=$original;}
}
