<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/uploads.php';
$uploadedFile=null;$uploadedFiles=[];
header('Cache-Control: no-store');
$page=(string)($_GET['page']??'dashboard');$error='';
if(empty($_SESSION['stock_exit_token']))$_SESSION['stock_exit_token']=bin2hex(random_bytes(32));
function field(string $name,string $label,mixed $value='',string $type='text',bool $required=true):void {
    echo '<label for="'.e($name).'">'.e($label).'</label><input id="'.e($name).'" name="'.e($name).'" type="'.e($type).'" value="'.e((string)$value).'" '.($required?'required':'').'>';
}
function selectField(string $name,string $label,array $options,mixed $selected=''):void {
    echo '<label for="'.e($name).'">'.e($label).'</label><select id="'.e($name).'" name="'.e($name).'">';
    foreach($options as $value=>$text)echo '<option value="'.e((string)$value).'" '.((string)$selected===(string)$value?'selected':'').'>'.e((string)$text).'</option>';
    echo '</select>';
}
function textField(string $name,string $label,string $value=''):void {echo '<label for="'.e($name).'">'.e($label).'</label><textarea id="'.e($name).'" name="'.e($name).'">'.e($value).'</textarea>';}
function inputText(string $key,int $length,bool $required=true):string {
    $s=trim((string)($_POST[$key]??''));if(($required&&$s==='')||mb_strlen($s)>$length)throw new RuntimeException('Provjerite polje: '.$key);return $s;
}
function inputInt(string $key,int $min=0,int $max=2147483647):int {
    $n=filter_var($_POST[$key]??null,FILTER_VALIDATE_INT);if($n===false||$n===null||$n<$min||$n>$max)throw new RuntimeException('Neispravan broj: '.$key);return $n;
}
function superOnly():void {if(!can('*')){http_response_code(403);exit('Pristup ima samo super administrator.');}}
function startForm(string $action,int $id=0):void {echo '<form method="post" enctype="multipart/form-data">'.csrf().'<input type="hidden" name="action" value="'.e($action).'"><input type="hidden" name="id" value="'.$id.'">';}

if($_SERVER['REQUEST_METHOD']==='POST'){
    checkCsrf();$action=(string)($_POST['action']??'');
    try{
        if($action==='login'){
            $email=inputText('email',254);$ip=hash('sha256',(string)($_SERVER['REMOTE_ADDR']??''));
            $identity=hash('sha256',mb_strtolower($email));
            if((int)query('SELECT COUNT(*) FROM login_attempts WHERE identity_hash IN (?,?) AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)',[$ip,$identity])->fetchColumn()>=10)throw new RuntimeException('Previše pokušaja. Pokušajte za 15 minuta.');
            query('INSERT INTO login_attempts(identity_hash) VALUES(?),(?)',[$ip,$identity]);
            $u=query('SELECT * FROM users WHERE email=? AND active=1',[$email])->fetch();
            if(!$u||!password_verify((string)($_POST['password']??''),$u['password_hash']))throw new RuntimeException('Neispravni podaci za prijavu.');
            session_regenerate_id(true);$_SESSION['user_id']=(int)$u['id'];$_SESSION['auth_epoch']=$authEpoch;$_SESSION['csrf']=bin2hex(random_bytes(32));
            redirect('admin.php');
        }
        if(!currentUser()){http_response_code(401);exit('Prijavite se.');}
        if($action==='logout'){$_SESSION=[];session_regenerate_id(true);redirect('admin.php');}
        if((int)query("SELECT GET_LOCK('webshop_mutations',2)")->fetchColumn()!==1)throw new RuntimeException('Sistem trenutno priprema podatke. Pokušajte ponovo.');
        register_shutdown_function(static function(){try{query("SELECT RELEASE_LOCK('webshop_mutations')");}catch(Throwable){}});
        $id=(int)($_POST['id']??0);
        if($action==='alpha_demo'){alphaDemoSeed();redirect('admin.php?page=themes&demo_loaded=1#store-themes');}
        if($action==='store_theme'){requirePermission('settings');$theme=inputText('theme',20);if(!isset(storeThemes()[$theme]))throw new RuntimeException('Odaberite postojeću temu prodavnice.');query("INSERT INTO settings(setting_key,setting_value) VALUES('store_theme',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)",[$theme]);audit('store_theme.changed',null,storeThemes()[$theme][0]);redirect('admin.php?page=themes&saved=1#store-themes');}
        if($action==='theme'){requirePermission('settings');$theme=inputText('theme',20);if(!isset(adminThemes()[$theme]))throw new RuntimeException('Odaberite postojeću temu.');query("UPDATE settings SET setting_value=? WHERE setting_key='admin_theme'",[$theme]);audit('theme.changed',null,adminThemes()[$theme][0]);redirect('admin.php?page=themes&saved=1');}
        if(str_starts_with($action,'backup_')){
            superOnly();$backupId=(string)($_POST['backup_id']??'');
            if(in_array($action,['backup_key','backup_restore'],true)&&!password_verify((string)($_POST['password']??''),currentUser()['password_hash']))throw new RuntimeException('Administratorska lozinka nije ispravna.');
            if($action==='backup_create'){$backupId=backupCreate();backupInspect($backupId);redirect('admin.php?page=backups&backup='.$backupId.'&saved=1');}
            if($action==='backup_import'){$upload=$_FILES['backup_file']??[];if(($upload['error']??1)!==UPLOAD_ERR_OK||!is_uploaded_file($upload['tmp_name']??''))throw new RuntimeException('Kopija nije učitana. Provjerite veličinu fajla i ograničenje hostinga.');$backupId=backupImport($upload['tmp_name']);redirect('admin.php?page=backups&backup='.$backupId.'&saved=1');}
            if($action==='backup_restore'){if(inputText('confirmation',10)!=='VRATI')throw new RuntimeException('Za potvrdu upišite VRATI.');backupRestore($backupId);$_SESSION=[];session_regenerate_id(true);redirect('admin.php?restored=1');}
            if($action==='backup_download'){$path=backupRoot().'/'.backupId($backupId).'.wsb';if(!is_file($path))throw new RuntimeException('Kopija ne postoji.');audit('backup.downloaded',null,$backupId);header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="WEBSHOP-backup-'.$backupId.'.wsb"');header('Content-Length: '.filesize($path));readfile($path);exit;}
            if($action==='backup_key'){$key=backupKey();audit('backup.key_downloaded');header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="WEBSHOP-kljuc-oporavka.key"');echo $key;exit;}
            if($action==='backup_settings'){$hour=inputText('hour',5);$keep=inputInt('keep',7,30);if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D',$hour)||!in_array($keep,[7,14,30],true))throw new RuntimeException('Provjerite vrijeme i broj kopija.');foreach(['backup_enabled'=>isset($_POST['enabled'])?'1':'0','backup_hour'=>$hour,'backup_keep'=>(string)$keep] as $key=>$value)query('UPDATE settings SET setting_value=? WHERE setting_key=?',[$value,$key]);audit('backup.settings_saved',null,$hour.' · Zadrži '.$keep.' automatskih kopija');redirect('admin.php?page=backups&saved=1');}
            throw new RuntimeException('Nepoznata radnja sigurnosne kopije.');
        }
        if($action==='product'){
            requirePermission('products');$name=inputText('name',200);$sku=inputText('sku',80,false);if($id&&$sku==='')throw new RuntimeException('Broj postojećeg artikla ne smije biti prazan.');$price=decimal(cents(inputText('price',12)));$old=inputText('compare_price',12,false);$compare=$old===''?null:decimal(cents($old));if($compare!==null&&cents($compare)<=cents($price))throw new RuntimeException('Stara cijena mora biti veća od trenutne cijene.');$category=inputInt('category_id');$url=inputText('image_url',500,false);
            [$url,$uploadedFile]=productImage($url);
            [$gallery,$uploadedFiles]=productGallery(inputText('gallery_urls',5000,false));
            [$banner,$bannerFile]=productImage(inputText('hero_banner_url',500,false),'hero_banner');if($bannerFile)$uploadedFiles[]=$bannerFile;
            if($url===''&&$gallery)$url=array_shift($gallery);
            $values=[$category?:null,$sku,$name,inputText('description',20000,false),$price,$url,isset($_POST['active'])?1:0,isset($_POST['featured'])?1:0,isset($_POST['hero_selected'])?1:0,$banner];
            $db->beginTransaction();if(!$id&&$sku===''){$sku=nextProductSku();$values[1]=$sku;}
            if($id){$values[]=$id;query('UPDATE products SET category_id=?,sku=?,name=?,description=?,price=?,image_url=?,active=?,featured=?,hero_selected=?,hero_banner_url=? WHERE id=?',$values);}
            else{query('INSERT INTO products(category_id,sku,name,description,price,image_url,active,featured,hero_selected,hero_banner_url) VALUES(?,?,?,?,?,?,?,?,?,?)',$values);$id=(int)$db->lastInsertId();}
            query('UPDATE products SET compare_price=? WHERE id=?',[$compare,$id]);
            if(can('inventory')&&array_key_exists('purchase_price',$_POST))query('UPDATE products SET purchase_price=?,low_stock_threshold=? WHERE id=?',[decimal(cents(inputText('purchase_price',12))),inputInt('low_stock_threshold',0,1000000),$id]);
            query('DELETE FROM product_images WHERE product_id=?',[$id]);foreach($gallery as $position=>$image)query('INSERT INTO product_images(product_id,image_url,sort_order) VALUES(?,?,?)',[$id,$image,$position]);
            audit('product.saved',$id,'Objava i postavke artikla');$db->commit();redirect('admin.php?page=products&saved=1');
        }
        if($action==='category'){
            requirePermission('categories');$name=inputText('name',150);$slug=inputText('slug',170);
            if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug))throw new RuntimeException('Slug smije sadržati mala slova, brojeve i crtice.');
            $db->beginTransaction();
            $show=isset($_POST['show_on_home'])?1:0;$sort=isset($_POST['sort_order'])?inputInt('sort_order',-100000,100000):0;
            if($id)query('UPDATE categories SET name=?,slug=?,show_on_home=?,sort_order=? WHERE id=?',[$name,$slug,$show,$sort,$id]);else{query('INSERT INTO categories(name,slug,show_on_home,sort_order) VALUES(?,?,?,?)',[$name,$slug,$show,$sort]);$id=(int)$db->lastInsertId();}
            audit('category.saved',$id);$db->commit();redirect('admin.php?page=categories&saved=1');
        }
        if($action==='stock'){
            requirePermission('inventory');$delta=inputInt('quantity',-1000000,1000000);$note=inputText('note',500);
            if(!$delta)throw new RuntimeException('Količina ne smije biti nula.');
            if($delta<0)throw new RuntimeException('Za izlaz robe koristite Drugi izlazi robe i odaberite vrstu i obrazloženje.');
            $db->beginTransaction();$p=query('SELECT * FROM products WHERE id=? FOR UPDATE',[$id])->fetch();
            if(!$p||(int)$p['stock']+$delta<0||(int)$p['stock']+$delta>2147483647)throw new RuntimeException('Nedovoljno slobodnog lagera ili prevelika količina.');
            query('UPDATE products SET stock=stock+? WHERE id=?',[$delta,$id]);movement($id,$delta,'adjustment',$note);audit('stock.adjusted',$id,(string)$delta.' '.$note);$db->commit();redirect('admin.php?page=inventory&saved=1');
        }
        if($action==='status'){changeOrder($id,inputText('status',30),inputText('tracking_number',150,false),inputText('courier_name',150,false),inputText('dispatch_note',1000));redirect('admin.php?page=order&id='.$id.'&saved=1');}
        if($action==='shipment'){updateShipment($id,inputText('courier_name',150),inputText('tracking_number',150,false),inputText('dispatch_note',1000));redirect('admin.php?page=order&id='.$id.'&saved=1');}
        if($action==='stock_exit'){
            requirePermission('inventory');$token=inputText('exit_token',64);if(!hash_equals($_SESSION['stock_exit_token'],$token))throw new RuntimeException('Obrazac već iskorišten ili istekao. Osvježite stranicu.');
            $reason=inputText('reason',30);$exit=createStockExit(inputInt('product_id',1),inputInt('quantity',1,1000000),$reason,inputText('unit_price',12,false),$reason==='personal_sale'?inputText('exit_payment',30):'not_applicable',inputText('recipient',180,false),inputText('document_ref',150,false),inputText('exit_note',1000),$token);
            $_SESSION['stock_exit_token']=bin2hex(random_bytes(32));redirect('admin.php?page=exits&id='.$exit.'&saved=1');
        }
        if($action==='exit_payment'){recordExitPayment($id,inputText('payment_status',30),inputText('exit_note',1000));redirect('admin.php?page=exits&id='.$id.'&saved=1');}
        if($action==='exit_reverse'){reverseStockExit($id,inputText('exit_note',1000));redirect('admin.php?page=exits&id='.$id.'&saved=1');}
        if($action==='payment'){
            recordPayment($id,inputText('payment_status',30),inputText('note',500));redirect('admin.php?page=order&id='.$id.'&saved=1');
        }
        if($action==='mail_settings'){superOnly();$v=$_POST;$v['mail_enabled']=isset($_POST['mail_enabled'])?'1':'0';$v['mail_immediate']=isset($_POST['mail_immediate'])?'1':'0';saveMailSettings($v,(string)($_POST['mail_password']??''),isset($_POST['clear_password']));redirect('admin.php?page=mail&saved=1');}
        if($action==='mail_test'){superOnly();$m=mailConfig();if(($m['mail_enabled']??'0')!=='1')throw new RuntimeException('Prvo uključite i sačuvajte mail postavke.');foreach(mailRecipients($m['mail_recipients']) as $to)query('INSERT INTO mail_jobs(recipient,subject,body) VALUES(?,?,?)',[$to,'WEBSHOP: probna obavijest','Probna poruka za tim za pakovanje i slanje.']);audit('mail.test_queued');processMailQueue();redirect('admin.php?page=mail&saved=1');}
        if($action==='mail_retry'){superOnly();query("UPDATE mail_jobs SET status='pending',attempts=0,next_attempt_at=NULL,last_error=NULL WHERE id=? AND status IN ('pending','failed')",[$id]);audit('mail.retry',$id);redirect('admin.php?page=mail&saved=1');}
        if($action==='mail_cancel'){superOnly();query("UPDATE mail_jobs SET status='cancelled' WHERE id=? AND status IN ('pending','failed')",[$id]);audit('mail.cancelled',$id);redirect('admin.php?page=mail&saved=1');}
        if($action==='branding'){saveBranding();redirect('admin.php?page=branding&saved=1');}
        if($action==='settings'){
            requirePermission('settings');$keys=['shop_name'=>100,'hero_title'=>200,'hero_text'=>1000,'contact_email'=>254,'sender_name'=>180,'sender_address'=>500,'sender_phone'=>60,'bank_details'=>2000,'privacy_text'=>5000,'accent_color'=>7,'store_font_scale'=>3,'admin_font_scale'=>3];$values=[];
            if(isset($_POST['alpha_demo_notice'])){if(!in_array($_POST['alpha_demo_notice'],['0','1'],true))throw new RuntimeException('Odaberite ispravan status testne ponude.');$values['alpha_demo_notice']=$_POST['alpha_demo_notice'];}
            foreach($keys as $k=>$len)$values[$k]=inputText($k,$len,in_array($k,['shop_name','hero_title','accent_color'],true));
            if(!preg_match('/^#[0-9a-fA-F]{6}$/D',$values['accent_color']))throw new RuntimeException('Boja mora biti u formatu #167655.');
            foreach(['store_font_scale','admin_font_scale'] as $key)if(!ctype_digit($values[$key])||(int)$values[$key]<100||(int)$values[$key]>140)throw new RuntimeException('Veličina teksta mora biti između 100 i 140%.');
            if($values['contact_email']!==''&&!filter_var($values['contact_email'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('Neispravan e-mail.');
            $db->beginTransaction();foreach($values as $k=>$v)query('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',[$k,$v]);audit('settings.saved');$db->commit();redirect('admin.php?page=settings&saved=1');
        }
        if($action==='delivery'){
            saveDelivery($id,inputText('name',150),inputText('cost',12),inputText('courier_name',150,false),inputText('terms',1000,false),inputText('payer',20),isset($_POST['active']));redirect('admin.php?page=delivery&saved=1');
        }
        if($action==='delivery_delete'){deleteDelivery($id,inputText('note',1000));redirect('admin.php?page=delivery&saved=1');}
        if($action==='delivery_prepare'){prepareShipment($id,inputInt('delivery_id',1),inputText('courier_name',150),inputText('delivery_cost',12),inputText('delivery_terms',1000,false),inputText('delivery_payer',20),inputText('tracking_number',150,false),inputText('note',1000));redirect('admin.php?page=order&id='.$id.'&saved=1');}
        if($action==='role'){
            superOnly();$name=inputText('name',100);$permissions=array_values(array_intersect((array)($_POST['permissions']??[]),['products','categories','inventory','orders','shipping','payments','print','settings']));
            $db->beginTransaction();if($id){$r=query('SELECT * FROM roles WHERE id=? FOR UPDATE',[$id])->fetch();if(!$r||in_array('*',json_decode($r['permissions'],true),true))throw new RuntimeException('Sistemska super administratorska uloga se ne mijenja.');query('UPDATE roles SET name=?,permissions=? WHERE id=?',[$name,json_encode($permissions),$id]);}
            else query('INSERT INTO roles(name,permissions) VALUES(?,?)',[$name,json_encode($permissions)]);
            audit('role.saved',$id);$db->commit();redirect('admin.php?page=roles&saved=1');
        }
        if($action==='user'){
            superOnly();$name=inputText('name',150);$email=inputText('email',254);$role=inputInt('role_id',1);$active=isset($_POST['active'])?1:0;$password=(string)($_POST['password']??'');
            if(!filter_var($email,FILTER_VALIDATE_EMAIL)||(($password!==''||!$id)&&strlen($password)<12)||strlen($password)>72)throw new RuntimeException('Provjerite e-mail i lozinku (12–72 znakova).');
            $db->beginTransaction();query('SELECT id FROM roles ORDER BY id FOR UPDATE')->fetchAll();
            if(!query('SELECT id FROM roles WHERE id=?',[$role])->fetchColumn())throw new RuntimeException('Uloga ne postoji.');
            if($id===(int)currentUser()['id']&&(!$active||$role!==(int)currentUser()['role_id']))throw new RuntimeException('Vlastitu aktivnu super administratorsku ulogu ne možete ukloniti.');
            if($id){query('UPDATE users SET name=?,email=?,role_id=?,active=? WHERE id=?',[$name,$email,$role,$active,$id]);if($password!=='')query('UPDATE users SET password_hash=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$id]);}
            else{query('INSERT INTO users(name,email,role_id,active,password_hash) VALUES(?,?,?,?,?)',[$name,$email,$role,$active,password_hash($password,PASSWORD_DEFAULT)]);$id=(int)$db->lastInsertId();}
            audit('user.saved',$id);$db->commit();redirect('admin.php?page=users&saved=1');
        }
        throw new RuntimeException('Nepoznata radnja.');
    }catch(Throwable $ex){if($db->inTransaction())$db->rollBack();if($uploadedFile&&is_file($uploadedFile))unlink($uploadedFile);foreach($uploadedFiles as $path)if(is_file($path))unlink($path);error_log($ex->getMessage());$error=$ex instanceof PDOException?'Promjena nije spremljena. Provjerite jedinstveni SKU, e-mail, slug i povezana polja.':$ex->getMessage();}
}
$u=currentUser();
if($u){
    $guard=['branding'=>'settings','themes'=>'settings','backups'=>'*','mail'=>'*','products'=>'products','categories'=>'categories','inventory'=>'inventory','orders'=>'orders','order'=>'orders','print'=>'print','settings'=>'settings','delivery'=>'settings'];
    if(isset($guard[$page]))requirePermission($guard[$page]);
    if(in_array($page,['users','roles','audit'],true))superOnly();
    if(in_array($page,['orders','exits'],true))[$rangeFrom,$rangeTo]=listDateRange();
    if($page==='exits'){
        if(!can('inventory')&&!can('payments')){http_response_code(403);exit('Nemate dozvolu za druge izlaze.');}
        if(!empty($_GET['id'])&&!can('inventory')&&query('SELECT reason FROM stock_exits WHERE id=?',[(int)$_GET['id']])->fetchColumn()!=='personal_sale'){http_response_code(403);exit('Nemate pristup ovom izlazu.');}
        if(($_GET['export']??'')==='csv')exportStockExits($rangeFrom,$rangeTo);
    }
    if($page==='reports')require dirname(__DIR__).'/app/reports.php';
    if($page==='print'){require __DIR__.'/../app/print.php';exit;}
}
?><!doctype html><html lang="bs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administracija · <?=e(setting('shop_name'))?></title><link rel="stylesheet" href="assets/style.css?v=090"><link rel="stylesheet" href="assets/admin.css?v=090"><link rel="stylesheet" href="assets/admin-ui.css?v=090"><link rel="stylesheet" href="assets/branding.css?v=092"><script src="assets/admin.js?v=090" defer></script></head><body data-theme="<?=e(adminTheme())?>" style="--font-scale:<?=e(fontScale('admin'))?>" class="admin-app <?=$u?'is-authenticated':'is-login'?>">
<?php if($u):?><aside class="admin-sidebar"><a class="admin-brand" href="admin.php"><span>w.</span><strong><?=e(setting('shop_name'))?><small>POSLOVNI SISTEM</small></strong></a><p class="nav-caption">RADNI PROSTOR</p><nav class="admin-nav"><a href="admin.php" class="<?=$page==='dashboard'?'active':''?>"><span>◫</span> Pregled poslovanja</a><?php foreach(['orders'=>['orders','Narudžbe','▤'],'inventory'=>['inventory','Lager i rezervacije','▧'],'products'=>['products','Artikli i naslovnica','◇'],'categories'=>['categories','Kategorije','▦']] as $target=>[$permission,$label,$icon]):if(can($permission)):?><a href="?page=<?=$target?>" class="<?=$page===$target||($page==='order'&&$target==='orders')?'active':''?>"><span><?=$icon?></span> <?=e($label)?></a><?php endif;endforeach;if(can('inventory')||can('payments')):?><a href="?page=exits" class="<?=$page==='exits'?'active':''?>"><span>↗</span> Drugi izlazi robe</a><?php endif;if(canReports()):?><a href="?page=reports" class="<?=$page==='reports'?'active':''?>"><span>▥</span> Izvještaji i izvoz</a><?php endif;?></nav><p class="nav-caption">UPRAVLJANJE</p><nav class="admin-nav"><?php foreach(['branding'=>['settings','Logo i brend','◉'],'themes'=>['settings','Teme','◈'],'backups'=>['*','Backup i vraćanje','▣'],'mail'=>['*','Mail obavijesti','✉'],'delivery'=>['settings','Dostava','↗'],'users'=>['*','Korisnici','♙'],'roles'=>['*','Uloge i dozvole','◎'],'settings'=>['settings','Izgled i postavke','⚙'],'audit'=>['*','Dnevnik aktivnosti','≡']] as $target=>[$permission,$label,$icon]):if(can($permission)):?><a href="?page=<?=$target?>" class="<?=$page===$target?'active':''?>"><span><?=$icon?></span> <?=e($label)?></a><?php endif;endforeach;?></nav><div class="sidebar-bottom"><a href="index.php">Otvori prodavnicu ↗</a><small>WEBSHOP · 0.9.2</small></div></aside><?php endif;?><div class="admin-workspace"><header class="admin-topbar"><div><span class="workspace-label">POSLOVANJE</span><span class="workspace-divider">/</span><strong><?=e(['dashboard'=>'Pregled','orders'=>'Narudžbe','order'=>'Detalji narudžbe','inventory'=>'Skladište','products'=>'Katalog','categories'=>'Kategorije','exits'=>'Drugi izlazi robe','reports'=>'Izvještaji','settings'=>'Postavke','users'=>'Korisnici','roles'=>'Dozvole','audit'=>'Dnevnik','delivery'=>'Dostava','mail'=>'Mail obavijesti','branding'=>'Logo i brend','themes'=>'Teme','backups'=>'Backup i vraćanje'][$page]??'Administracija')?></strong></div><?php if($u):?><?php if(can('orders')||can('products')):?><form class="workspace-search" method="get"><input type="hidden" name="page" value="<?=can('orders')?'orders':'products'?>"><label class="sr-only" for="workspace-search">Pretraga</label><input id="workspace-search" name="q" placeholder="<?=can('orders')?'Pretraga narudžbi, kupaca, artikala…':'Pretraga artikala…'?>"><button aria-label="Pretraži">⌕</button></form><?php endif;?><div class="topbar-user"><span class="avatar"><?=e(mb_strtoupper(mb_substr($u['name'],0,1)))?></span><span><strong><?=e($u['name'])?></strong><small><?=e($u['role_name'])?></small></span><?php startForm('logout');?><button class="logout-button">Odjava</button></form></div><?php else:?><a href="index.php">Prodavnica ↗</a><?php endif;?></header><main class="admin-main"><?php if(!$u):?><section class="panel login"><h1>Prijava</h1><?php if(isset($_GET['restored'])):?><p class="success">Vraćanje podataka je završeno. Prijavite se računom iz vraćene kopije.</p><?php endif;?><?php if($error):?><p class="error" role="alert"><?=e($error)?></p><?php endif;startForm('login');field('email','E-mail','','email');field('password','Lozinka','','password');?><button>Prijavi se</button></form><p>Prijavite se dodijeljenim korisničkim računom.</p></section>
<?php else:?>
<?php if($error):?><p class="error panel" role="alert"><?=e($error)?></p><?php endif;if(isset($_GET['saved'])):?><p class="success">Promjena je spremljena.</p><?php endif;?>
<?php if($page==='dashboard'):require dirname(__DIR__).'/app/views/admin-dashboard.php';elseif($page==='products'):require dirname(__DIR__).'/app/views/admin-products.php';elseif($page==='categories'):require dirname(__DIR__).'/app/views/admin-categories.php';elseif($page==='inventory'):require dirname(__DIR__).'/app/views/admin-inventory.php';elseif($page==='reports'):require dirname(__DIR__).'/app/views/admin-reports.php';?>
<?php elseif($page==='orders'):require dirname(__DIR__).'/app/views/admin-orders.php';elseif($page==='order'):require dirname(__DIR__).'/app/views/admin-order.php';elseif($page==='exits'):require dirname(__DIR__).'/app/views/admin-exits.php';?>
<?php elseif($page==='themes'):require dirname(__DIR__).'/app/views/admin-themes.php';elseif($page==='backups'):require dirname(__DIR__).'/app/views/admin-backups.php';?>
<?php elseif($page==='mail'):superOnly();require dirname(__DIR__).'/app/views/admin-mail.php';?>
<?php elseif($page==='branding'):require dirname(__DIR__).'/app/views/admin-branding.php';?>
<?php elseif($page==='settings'):?><h1>Izgled i postavke</h1><section class="panel"><?php startForm('settings');selectField('alpha_demo_notice','Oznaka testne ponude',['1'=>'Prikaži — testna ponuda','0'=>'Sakrij — stvarna prodavnica'],setting('alpha_demo_notice','0'));foreach(['shop_name'=>'Naziv prodavnice','hero_title'=>'Naslov naslovnice','hero_text'=>'Tekst naslovnice','accent_color'=>'Boja (#167655)','contact_email'=>'Kontakt e-mail','sender_name'=>'Pošiljalac','sender_address'=>'Adresa pošiljaoca','sender_phone'=>'Telefon pošiljaoca'] as $k=>$label)field($k,$label,setting($k),'text',false);selectField('store_font_scale','Veličina teksta u prodavnici',[100=>'100%',110=>'110%',115=>'115%',120=>'120%',130=>'130%',140=>'140%'],setting('store_font_scale','115'));selectField('admin_font_scale','Veličina teksta u administraciji',[100=>'100%',110=>'110%',115=>'115%',120=>'120%',130=>'130%',140=>'140%'],setting('admin_font_scale','120'));textField('bank_details','Podaci za uplatu (prazno = onemogućeno)',setting('bank_details'));textField('privacy_text','Obavijest o obradi podataka',setting('privacy_text'));?><button>Spremi postavke</button></form></section>
<?php elseif($page==='delivery'):require dirname(__DIR__).'/app/views/admin-delivery.php';?>
<?php elseif($page==='users'):$edit=query('SELECT id,name,email,role_id,active FROM users WHERE id=?',[(int)($_GET['id']??0)])->fetch()?:[];?><h1>Korisnici</h1><section class="panel"><?php startForm('user',(int)($edit['id']??0));field('name','Ime',$edit['name']??'');field('email','E-mail',$edit['email']??'','email');field('password','Lozinka (12–72 znakova; prazno zadržava postojeću)','','password',!$edit);$options=[];foreach(query('SELECT * FROM roles ORDER BY id')->fetchAll() as $r)$options[$r['id']]=$r['name'];selectField('role_id','Uloga',$options,$edit['role_id']??1);?><label><input type="checkbox" name="active" <?=!$edit||$edit['active']?'checked':''?>> Aktivan</label><button>Spremi korisnika</button></form></section><div class="panel"><?php foreach(query('SELECT u.*,r.name role_name FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.name')->fetchAll() as $usr):?><p><a href="?page=users&id=<?=$usr['id']?>"><?=e($usr['name'])?></a> · <?=e($usr['email'])?> · <?=e($usr['role_name'])?> · <?=$usr['active']?'Aktivan':'Isključen'?></p><?php endforeach;?></div>
<?php elseif($page==='roles'):$edit=query('SELECT * FROM roles WHERE id=?',[(int)($_GET['id']??0)])->fetch()?:[];?><h1>Uloge i dozvole</h1><p>Upravljanje korisnicima, ulogama i dnevnikom pripada samo super administratoru.</p><section class="panel"><?php startForm('role',(int)($edit['id']??0));field('name','Naziv uloge',$edit['name']??'');foreach(['products'=>'Proizvodi','categories'=>'Kategorije','inventory'=>'Lager','orders'=>'Pregled i priprema narudžbi','shipping'=>'Slanje, dostava i povrati (uz narudžbe)','payments'=>'Naplata (uz narudžbe)','print'=>'Štampa (uz pristup podacima)','settings'=>'Izgled i dostava'] as $key=>$label):?><label><input type="checkbox" name="permissions[]" value="<?=$key?>" <?=in_array($key,json_decode($edit['permissions']??'[]',true),true)?'checked':''?>> <?=e($label)?></label><?php endforeach;?><button>Spremi ulogu</button></form></section><div class="panel"><?php foreach(query('SELECT * FROM roles ORDER BY id')->fetchAll() as $r):?><p><a href="?page=roles&id=<?=$r['id']?>"><?=e($r['name'])?></a> · <?=e($r['permissions'])?></p><?php endforeach;?></div>
<?php elseif($page==='audit'):require dirname(__DIR__).'/app/views/admin-audit.php';?>
<?php else:http_response_code(404);?><h1>Stranica nije pronađena.</h1><?php endif;endif;?></main><footer class="admin-footer">WEBSHOP 0.9.2 · Evidencija prodaje i skladišta</footer></div></body></html>
