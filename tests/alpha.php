<?php
declare(strict_types=1);
// Dedicated disposable test database; never loads production configuration.
$base=getenv('TEST_DB_NAME')?:'webshop_test';
if(!preg_match('/^webshop_test(?:_[a-z0-9]+)?$/D',$base))exit("Refusing non-test database.\n");
$name=$base.'_alpha';
$db=new PDO('mysql:host='.(getenv('TEST_DB_HOST')?:'127.0.0.1').';port='.(getenv('TEST_DB_PORT')?:'33317').';charset=utf8mb4',getenv('TEST_DB_USER')?:'root',getenv('TEST_DB_PASSWORD')?:'local_test_only',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
$db->exec('CREATE DATABASE IF NOT EXISTS '.$name.' CHARACTER SET utf8mb4');
$db->exec('USE '.$name);
if($db->query('SHOW TABLES')->fetchColumn())exit("Alpha test database must be empty.\n");
function alphaRunSql(string $path):void {global $db;foreach(explode(';',file_get_contents($path)) as $sql)if(trim($sql)!=='')$db->exec($sql);}
function alphaExpect(bool $condition,string $label):void {if(!$condition)throw new RuntimeException('FAIL: '.$label);echo "PASS: $label\n";}
alphaRunSql(dirname(__DIR__).'/database/install-alpha-demo.sql');
require dirname(__DIR__).'/app/services.php';
require dirname(__DIR__).'/app/catalog.php';
function e(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function money(float $value):string{return number_format($value,2,',','.').' KM';}
$_SESSION=[];
$settings=query('SELECT setting_key,setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
alphaExpect(storeTheme()==='alpha-hybrid','new installation selects Alpha Hybrid');
alphaExpect((int)query('SELECT COUNT(*) FROM products')->fetchColumn()===12,'complete installer includes 12 demo articles');
alphaExpect((int)query('SELECT COUNT(*) FROM stock_movements')->fetchColumn()===12,'initial stock movements are recorded');
alphaExpect((int)query("SELECT COUNT(*) FROM products WHERE stock=0")->fetchColumn()===1,'sold-out demo available');
alphaExpect(count(alphaCategories())===6,'six populated Alpha categories');
query("UPDATE products SET price=321.12,stock=2 WHERE sku='ALPHA-DEMO-001'");
alphaRunSql(dirname(__DIR__).'/database/demo-alpha.sql');
alphaExpect((int)query('SELECT COUNT(*) FROM products')->fetchColumn()===12,'repeat demo import does not duplicate products');
alphaExpect((int)query('SELECT COUNT(*) FROM stock_movements')->fetchColumn()===12,'repeat demo import does not duplicate movements');
$edited=query("SELECT price,stock FROM products WHERE sku='ALPHA-DEMO-001'")->fetch();
alphaExpect($edited['price']==='321.12'&&(int)$edited['stock']===2,'repeat import preserves manually edited prices and stock');
foreach(['alpha-pro'=>'Sve za vašu radionicu.','alpha-premium'=>'Perfekcija u svakom sloju.','alpha-hybrid'=>'Od pripreme do savršenog sjaja.'] as $theme=>$headline){
    $settings['store_theme']=$theme;
    alphaExpect(alphaTheme()&&storeTheme()===$theme,'theme allowlist: '.$theme);
    ob_start();alphaHero();$html=ob_get_clean();
    alphaExpect(str_contains($html,$headline)&&str_contains($html,'321,12 KM'),'theme renders data and distinct headline: '.$theme);
}
$settings['store_theme']='untrusted';alphaExpect(storeTheme()==='classic'&&!alphaTheme(),'invalid themes fall back safely');
query("UPDATE settings SET setting_value='alpha-pro' WHERE setting_key='store_theme'");
alphaRunSql(dirname(__DIR__).'/database/migrations/010_alpha_themes.sql');
alphaExpect(query("SELECT setting_value FROM settings WHERE setting_key='store_theme'")->fetchColumn()==='alpha-pro','repeat migration preserves selected theme');
alphaExpect(imageSource('assets/alpha/disc.svg')==='assets/alpha/disc.svg','local demo images accepted');
alphaExpect(imageSource('assets/alpha/../../config/local.php')==='assets/product-placeholder.svg','demo image allowlist prevents traversal');
require dirname(__DIR__).'/app/uploads.php';
alphaExpect(productImage('assets/alpha/maxxt-polisher.png')[0]==='assets/alpha/maxxt-polisher.png','bundled product photo survives price-only editing');
alphaExpect(imageSource('assets/alpha/polisher.svg','ALPHA-DEMO-001')==='assets/alpha/maxxt-polisher.png','existing demo photograph resolved without destructive data changes');
alphaRunSql(dirname(__DIR__).'/database/migrations/011_alpha_photos.sql');
alphaExpect(query("SELECT price FROM products WHERE sku='ALPHA-DEMO-001'")->fetchColumn()==='321.12','photo migration preserves edited price');
echo "PASS: Alpha installer, repeat import, preserved data, three layouts and safe images.\n";
