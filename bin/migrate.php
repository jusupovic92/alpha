<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$config=require dirname(__DIR__).'/config/local.php';
$db=new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if((int)$db->query("SELECT GET_LOCK('webshop_schema_upgrade',10)")->fetchColumn()!==1)exit("Migraciju već pokreće drugi proces.\n");
try{
    $tables=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $done002=in_array('schema_migrations',$tables,true)&&$db->query("SELECT COUNT(*) FROM schema_migrations WHERE version='002_management'")->fetchColumn();
    if(!$done002){
    if(in_array('roles',$tables,true))throw new RuntimeException('Otkrivena djelimična migracija. Vratite backup prije ponovnog pokušaja.');
    if(!in_array('products',$tables,true))throw new RuntimeException('Prvo uvezite database/schema.sql ili za novu instalaciju koristite database/install.sql.');
    $columns=$db->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_COLUMN);
    if(in_array('reserved',$columns,true))throw new RuntimeException('Otkrivena djelimična migracija. Vratite backup.');
    foreach(['products','orders','order_items','categories'] as $table){
        $s=$db->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$s->execute([$table]);
        if($s->fetchColumn()!=='InnoDB')throw new RuntimeException('Sve postojeće tabele moraju koristiti InnoDB.');
    }
    foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/002_management.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
    echo "Migracija 002 je primijenjena.\n";
    }
    if($db->query("SELECT COUNT(*) FROM schema_migrations WHERE version='003_storefront'")->fetchColumn())echo "Migracija 003 je već primijenjena.\n";
    else{
        $columns=$db->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_COLUMN);
        if(in_array('hero_selected',$columns,true))throw new RuntimeException('Djelimična migracija 003: vratite backup prije ponavljanja.');
        foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/003_storefront.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
        echo "Migracija 003 je primijenjena.\n";
    }
    if($db->query("SELECT COUNT(*) FROM schema_migrations WHERE version='004_fulfillment'")->fetchColumn())echo "Migracija 004 je već primijenjena.\n";
    else{
        $columns=$db->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
        if(in_array('courier_name',$columns,true))throw new RuntimeException('Djelimična migracija 004: vratite backup prije ponavljanja.');
        foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/004_fulfillment.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
        echo "Migracija 004 je primijenjena.\n";
    }
    if($db->query("SELECT COUNT(*) FROM schema_migrations WHERE version='005_hero_banner'")->fetchColumn())echo "Migracija 005 je već primijenjena.\n";
    else{
        $columns=$db->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_COLUMN);
        if(in_array('hero_banner_url',$columns,true))throw new RuntimeException('Djelimična migracija 005: vratite backup prije ponavljanja.');
        foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/005_hero_banner.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
        echo "Migracija 005 je primijenjena.\n";
    }
    if(!$db->query("SELECT COUNT(*) FROM schema_migrations WHERE version='006_delivery'")->fetchColumn()){
        $columns=$db->query('SHOW COLUMNS FROM shipping_methods')->fetchAll(PDO::FETCH_COLUMN);
        if(in_array('deleted_at',$columns,true))throw new RuntimeException('Djelimična migracija 006: vratite backup prije ponavljanja.');
        foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/006_delivery.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
        echo "Migracija 006 je primijenjena.\n";
    }else echo "Migracija 006 je već primijenjena.\n";
    if(!$db->query("SELECT COUNT(*) FROM schema_migrations WHERE version='007_mail_discounts'")->fetchColumn()){
        $columns=$db->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_COLUMN);
        if(in_array('compare_price',$columns,true))throw new RuntimeException('Djelimična migracija 007: vratite backup prije ponavljanja.');
        foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/007_mail_discounts.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
        echo "Migracija 007 je primijenjena.\n";
    }else echo "Migracija 007 je već primijenjena.\n";
    if(!$db->query("SELECT COUNT(*) FROM schema_migrations WHERE version='008_readability_numbering'")->fetchColumn()){
        $tables=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if(in_array('app_counters',$tables,true))throw new RuntimeException('Djelimična migracija 008: vratite backup prije ponavljanja.');
        foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/008_readability_numbering.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
        echo "Migracija 008 je primijenjena.\n";
    }else echo "Migracija 008 je već primijenjena.\n";
    if(!$db->query("SELECT COUNT(*) FROM schema_migrations WHERE version='009_admin_backups'")->fetchColumn()){
        if($db->query("SELECT COUNT(*) FROM settings WHERE setting_key='admin_theme'")->fetchColumn())throw new RuntimeException('Djelimična migracija 009: vratite backup prije ponavljanja.');
        foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/009_admin_backups.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
        echo "Migracija 009 je primijenjena.\n";
    }else echo "Migracija 009 je već primijenjena.\n";
    if(!$db->query("SELECT COUNT(*) FROM schema_migrations WHERE version='010_alpha_themes'")->fetchColumn()) {
        foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/010_alpha_themes.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
        echo "Migracija 010 Alpha je primijenjena.\n";
    }
    if(!$db->query("SELECT COUNT(*) FROM schema_migrations WHERE version='011_alpha_photos'")->fetchColumn()) {
        foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/011_alpha_photos.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
        echo "Migracija 011 Alpha fotografije je primijenjena.\n";
    }
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\nMigracija može biti djelimično primijenjena jer MySQL DDL nije transakcijski. Vratite backup prije novog pokušaja.\n");exit(1);}
finally{$db->query("SELECT RELEASE_LOCK('webshop_schema_upgrade')");}
