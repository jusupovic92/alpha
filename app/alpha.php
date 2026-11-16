<?php
declare(strict_types=1);

function alphaTheme(): bool {
    return in_array(storeTheme(), ['alpha-pro','alpha-premium','alpha-hybrid'], true);
}

function alphaCategories(): array {
    return query('SELECT c.*, (SELECT image_url FROM products p WHERE p.category_id=c.id AND p.active=1 ORDER BY p.id LIMIT 1) image_url FROM categories c WHERE c.show_on_home=1 AND EXISTS(SELECT 1 FROM products p WHERE p.category_id=c.id AND p.active=1) ORDER BY c.sort_order,c.name LIMIT 8')->fetchAll();
}

function alphaCategoryLink(array $categories, string $slug): string {
    foreach ($categories as $category) {
        if ($category['slug'] === $slug) return '?category='.(int)$category['id'].'#ponuda';
    }
    return '?all=1#ponuda';
}

function alphaHero(): void {
    $categories=alphaCategories();$premium=storeTheme()==='alpha-premium';$pro=storeTheme()==='alpha-pro';
    $photo='assets/alpha/'.($premium?'polishing-wide.jpg':($pro?'spray-guns.jpg':'workshop-spray.jpg'));
    $slides=heroProducts();$spotlight=$slides[0]??null;
    ?>
    <section class="alpha-hero alpha-photo-hero" aria-labelledby="alpha-title">
      <div class="alpha-hero-image"><img src="<?=e($photo)?>" alt="<?=$premium?'Profesionalno poliranje vozila':($pro?'Profesionalni pištolji za lakiranje':'Rad u lakirerskoj radionici')?>" fetchpriority="high" width="1200" height="720"></div>
      <div class="alpha-hero-copy"><p class="alpha-kicker">ZA PROMJENU, PROBAJTE KVALITET.</p>
        <h1 id="alpha-title"><?=$pro?'Sve za vašu radionicu.':($premium?'Perfekcija u svakom sloju.':'Od pripreme do savršenog sjaja.')?></h1>
        <p>Profesionalni materijali i oprema za autoreparaturu, poliranje i detailing.</p>
        <div class="alpha-actions"><a class="btn" href="<?=e(alphaCategoryLink($categories,'boje-i-lakovi'))?>">Autoreparatura <span aria-hidden="true">→</span></a><a class="btn secondary" href="<?=e(alphaCategoryLink($categories,'detailing'))?>">Detailing <span aria-hidden="true">→</span></a></div>
        <?php if($spotlight):?><a class="alpha-hero-product" href="<?=e(productLink((int)$spotlight['id']))?>">Izdvojeno: <?=e($spotlight['name'])?> · <?=money((float)$spotlight['price'])?> →</a><?php endif;?>
      </div>
    </section>
    <?php if($categories):?><section class="alpha-categories" aria-label="Kategorije proizvoda"><?php foreach($categories as $category):?><a class="alpha-category-<?=e($category['slug'])?>" href="?category=<?=(int)$category['id']?>#ponuda"><div class="alpha-category-photo"><img src="<?=e(alphaCategoryPhoto($category))?>" alt="" loading="lazy" width="320" height="200"></div><span class="alpha-category-label"><strong><?=e($category['name'])?></strong><span aria-hidden="true">↗</span></span></a><?php endforeach;?></section><?php endif;?>
    <?php
}
function alphaPromotion(): void { ?>
    <section class="alpha-promotion"><img src="assets/alpha/polishing-close.jpg" alt="Završna obrada i poliranje vozila" loading="lazy" width="768" height="960"><div><p>OD PRIPREME DO ZAVRŠNE OBRADE</p><h2>Materijali i oprema<br>za profesionalce.</h2><a href="?all=1#ponuda">Istražite ponudu →</a></div></section>
    <?php
}
function alphaDemoMedia(): array {return [
    '001'=>['maxxt-polisher.png','MAXXT ekscentrična polirka 15 mm'],
    '002'=>['maxxt-rotary.png','MAXXT rotaciona polirka'],
    '003'=>['maxxt-sander.png','MAXXT ekscentrična šlajfarica'],
    '004'=>['maxxt-sander.png','MAXXT ekscentrična šlajfarica 5,0 mm'],
    '005'=>['maxxt-sander.png','MAXXT ekscentrična rašpa 198 × 70 mm, 3,0 mm'],
    '006'=>['maxxt-vacuum.png','MAXXT usisivač'],
    '007'=>['clearcoat.jpg','Mipa C UV lak 1 kg — testna ponuda'],
    '008'=>['cleaner.jpg','APC Typhoon 0,5 L — testna ponuda'],
    '009'=>['pads.jpg','ChemicalWorkz Pad Box 75 mm, 6/1 — testna ponuda'],
    '010'=>['polish.jpg','P&S Therapy Final Polish 236 ml — testna ponuda'],
    '011'=>['microfiber.jpg','EWOCAR mikrofiber krpa 400 GSM — testna ponuda'],
    '012'=>['shampoo.jpg','3D Wash N Wax 3,8 L — testna ponuda']
];}
function alphaImage(string $url,string $sku=''): string {
    if(preg_match('~^ALPHA-DEMO-(\d{3})$~D',$sku,$match)&&preg_match('~^assets/alpha/[a-z0-9-]+\.svg$~D',$url)){
        $media=alphaDemoMedia()[$match[1]]??null;
        if($media&&is_file(dirname(__DIR__).'/public/assets/alpha/'.$media[0]))return 'assets/alpha/'.$media[0];
    }
    return $url;
}
function alphaPresentProduct(array $product): array {
    if(preg_match('~^ALPHA-DEMO-(\d{3})$~D',(string)($product['sku']??''),$match)&&preg_match('~^assets/alpha/[a-z0-9-]+\.svg$~D',(string)($product['image_url']??''))){
        $media=alphaDemoMedia()[$match[1]]??null;
        if($media&&(int)$match[1]>=7&&str_contains((string)$product['name'],'testni artikal'))$product['name']=$media[1];
    }
    return $product;
}
function alphaCategoryPhoto(array $category): string {return 'assets/alpha/'.(['boje-i-lakovi'=>'clearcoat.jpg','priprema-povrsine'=>'paint-system.jpg','brusenje'=>'maxxt-sander.png','poliranje'=>'maxxt-polisher.png','detailing'=>'microfiber.jpg','oprema'=>'spray-guns.jpg'][$category['slug']]??'paint-system.jpg');}
function alphaDemoSeed(): int {
    global $db;
    requirePermission('settings');
    requirePermission('products');
    requirePermission('inventory');
    $sql=file_get_contents(dirname(__DIR__).'/database/demo-alpha.sql');
    $photoSql=file_get_contents(dirname(__DIR__).'/database/migrations/011_alpha_photos.sql');
    if($sql!==false&&$photoSql!==false)$sql.="\n".$photoSql;
    if($sql===false) throw new RuntimeException('Nedostaje datoteka testnih artikala.');
    $before=(int)query("SELECT COUNT(*) FROM products WHERE sku LIKE 'ALPHA-DEMO-%'")->fetchColumn();
    try {
        $db->beginTransaction();
        foreach(explode(';',$sql) as $statement) if(trim($statement)!=='') $db->exec($statement);
        $after=(int)query("SELECT COUNT(*) FROM products WHERE sku LIKE 'ALPHA-DEMO-%'")->fetchColumn();
        audit('alpha.demo_loaded',null,'Dodano testnih artikala: '.($after-$before));
        $db->commit();
        return $after-$before;
    } catch(Throwable $error) {
        if($db->inTransaction()) $db->rollBack();
        throw $error;
    }
}
