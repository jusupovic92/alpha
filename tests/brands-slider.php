<?php
declare(strict_types=1);
$base=getenv('TEST_DB_NAME')?:'webshop_test';if(!preg_match('/^webshop_test(?:_[a-z0-9]+)?$/D',$base))exit('Only test databases');
$db=new PDO('mysql:host='.(getenv('TEST_DB_HOST')?:'127.0.0.1').';port='.(getenv('TEST_DB_PORT')?:'33317').';charset=utf8mb4',getenv('TEST_DB_USER')?:'root',getenv('TEST_DB_PASSWORD')?:'local_test_only',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE DATABASE '.$base.'_brands CHARACTER SET utf8mb4');$db->exec('USE '.$base.'_brands');
foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/install-alpha-demo.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
require dirname(__DIR__).'/app/services.php';require dirname(__DIR__).'/app/catalog.php';
function e(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}function money(float $n):string{return number_format($n,2,',','.').' KM';}
function checkBrand(bool $ok,string $label):void{if(!$ok)throw new RuntimeException($label);echo "PASS: $label\n";}
$_SESSION=[];$settings=query('SELECT setting_key,setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
checkBrand(brandsReady(),'new installer has brand schema');
checkBrand((int)query('SELECT COUNT(*) FROM brands')->fetchColumn()===6,'six real brand records seeded');
checkBrand(query("SELECT b.slug FROM products p JOIN brands b ON b.id=p.brand_id WHERE p.sku='ALPHA-DEMO-001'")->fetchColumn()==='maxxt','existing products assigned to real brand');
checkBrand(in_array('brands',backupTables(),true),'brand data participates in backups');
query("UPDATE brands SET name='Custom Mipa',logo_url='https://example.test/custom.png' WHERE slug='mipa'");query("UPDATE products SET brand_id=NULL WHERE sku='ALPHA-DEMO-001'");query("UPDATE settings SET setting_value='5' WHERE setting_key='slider_count'");
foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/012_brands_slider.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
checkBrand(query("SELECT name FROM brands WHERE slug='mipa'")->fetchColumn()==='Custom Mipa','repeat migration preserves custom brand');
checkBrand(query("SELECT brand_id FROM products WHERE sku='ALPHA-DEMO-001'")->fetchColumn()===null,'repeat migration preserves manual brand removal');
checkBrand(query("SELECT setting_value FROM settings WHERE setting_key='slider_count'")->fetchColumn()==='5','repeat migration preserves slider preferences');
foreach(['brand-maxxt.png','brand-mipa.jpg','brand-chemicalworkz.png','brand-ps.png','brand-ewocar.png','brand-3d.png'] as $file)checkBrand(is_file(dirname(__DIR__).'/public/assets/alpha/'.$file)&&filesize(dirname(__DIR__).'/public/assets/alpha/'.$file)>100,'local manufacturer logo '.$file);
query('UPDATE products SET hero_selected=0');
query("INSERT INTO products(sku,name,price,active,hero_selected,created_at,hero_selected_at) VALUES('OLD-HERO','Stariji proizvod, novo odabran',5,1,1,'2000-01-01','2035-01-01'),('NEW-HERO','Noviji proizvod, ranije odabran',6,1,1,'2030-01-01','2034-01-01'),('HIDDEN-HERO','Skriven artikal',7,0,1,'2030-01-01','2035-06-01')");
$settings['slider_count']='1';$slides=heroProducts();checkBrand(count($slides)===1&&$slides[0]['sku']==='OLD-HERO','most recently selected old product leads the configured slider');
$settings['slider_count']='2';$slides=heroProducts();checkBrand(count($slides)===2&&$slides[1]['sku']==='NEW-HERO','slider limit and hidden product exclusion');
$settings['slider_count']='999';$settings['slider_interval']='0';$settings['slider_effect']='invalid';checkBrand(sliderConfig()['count']===12&&sliderConfig()['interval']===2&&sliderConfig()['effect']==='fade','invalid stored slider options fall back safely');
echo "PASS: repeatable migration, manufacturer logos, assignments, backups and announcement ordering.\n";
