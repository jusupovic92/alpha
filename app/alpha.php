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


function alphaIcon(string $name): string {
    $paths=[
      'boje-i-lakovi'=>'<ellipse cx="24" cy="12" rx="13" ry="5"/><path d="M11 12v24c0 7 26 7 26 0V12M11 19c0 7 26 7 26 0M17 28h14M17 33h9"/>',
      'priprema-povrsine'=>'<path d="M12 8h14v11H12zM15 19v5h15l5 6-5 6H18l-5-6H7v-6h8M20 36l-3 8h8l3-8M35 27l6-3M37 31h7M35 35l6 3M8 27H4"/>',
      'brusenje'=>'<circle cx="24" cy="24" r="17"/><circle cx="24" cy="24" r="3"/><circle cx="24" cy="13" r="1.5"/><circle cx="34" cy="19" r="1.5"/><circle cx="34" cy="30" r="1.5"/><circle cx="24" cy="36" r="1.5"/><circle cx="14" cy="30" r="1.5"/><circle cx="14" cy="19" r="1.5"/>',
      'poliranje'=>'<ellipse cx="23" cy="37" rx="17" ry="5"/><path d="M12 35v-7h22v7M16 28V17c0-7 13-7 13 0v11M29 18h10v7H29M39 22h4v13M19 12V7h8v5M17 22h7"/>',
      'detailing'=>'<path d="M6 34v-9l5-4 4-10h18l4 10 5 4v9H6zM11 21h26M14 27h5M29 27h5M11 34v5h6v-5M31 34v5h6v-5M39 4v8M35 8h8M6 6v6M3 9h6"/>',
      'oprema'=>'<path d="m11 7 9 9-4 4-9-9a11 11 0 0 0 13 15l15 15a4 4 0 0 0 6-6L26 20A11 11 0 0 0 11 7zM9 39l10-10M29 19l9-9 5-1-1 5-9 9"/>',
      'bag'=>'<path d="M12 16h24l3 26H9l3-26zM18 16V11a6 6 0 0 1 12 0v5"/>',
      'eye'=>'<path d="M3 24s8-13 21-13 21 13 21 13-8 13-21 13S3 24 3 24z"/><circle cx="24" cy="24" r="6"/>'
    ];
    return '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['oprema']).'</svg>';
}

function alphaHero(): void {
    $categories=alphaCategories();$premium=storeTheme()==='alpha-premium';$pro=storeTheme()==='alpha-pro';
    $photo='assets/alpha/'.($premium?'premium-studio.webp':($pro?'spray-guns.jpg':'workshop-spray.jpg'));
    $slides=heroProducts();$spotlight=$slides[0]??null;
    ?>
    <section class="alpha-hero alpha-photo-hero" aria-labelledby="alpha-title">
      <div class="alpha-hero-image"><img src="<?=e($photo)?>" alt="<?=$premium?'Profesionalno poliranje vozila':($pro?'Profesionalni pištolji za lakiranje':'Rad u lakirerskoj radionici')?>" fetchpriority="high" width="1200" height="720"></div>
      <div class="alpha-hero-copy"><p class="alpha-kicker">ZA PROMJENU, PROBAJTE KVALITET.</p>
        <h1 id="alpha-title"><?=$pro?'Sve za vašu radionicu.':($premium?'Perfekcija u svakom <em>sloju.</em>':'Od pripreme do savršenog sjaja.')?></h1>
        <p>Profesionalni materijali i oprema za autoreparaturu, poliranje i detailing.</p>
        <div class="alpha-actions"><a class="btn" href="<?=e(alphaCategoryLink($categories,'boje-i-lakovi'))?>"><?=$premium?'Istraži ponudu':'Autoreparatura'?> <span aria-hidden="true">→</span></a><a class="btn secondary" href="<?=e(alphaCategoryLink($categories,'detailing'))?>"><?=$premium?'Za profesionalce':'Detailing'?> <span aria-hidden="true">→</span></a></div>
        <?php if($spotlight):?><a class="alpha-hero-product" href="<?=e(productLink((int)$spotlight['id']))?>">Izdvojeno: <?=e($spotlight['name'])?> · <?=money((float)$spotlight['price'])?> →</a><?php endif;?>
      </div>
    </section>
    <?php if($categories):?><section class="alpha-categories" aria-label="Kategorije proizvoda"><?php foreach($categories as $category):?><a class="alpha-category-<?=e($category['slug'])?>" href="?category=<?=(int)$category['id']?>#ponuda"><?php if($premium):?><div class="alpha-category-icon"><?=alphaIcon((string)$category['slug'])?></div><?php else:?><div class="alpha-category-photo"><img src="<?=e(alphaCategoryPhoto($category))?>" alt="" loading="lazy" width="320" height="200"></div><?php endif;?><span class="alpha-category-label"><strong><?=e($premium&&$category['slug']==='priprema-povrsine'?'Priprema':$category['name'])?></strong><span aria-hidden="true">→</span></span></a><?php endforeach;?></section><?php endif;?>
    <?php
}
function alphaPromotion(): void { ?>
    <section class="alpha-promotion"><img src="assets/alpha/polishing-close.jpg" alt="Završna obrada i poliranje vozila" loading="lazy" width="768" height="960"><div><p>OD PRIPREME DO ZAVRŠNE OBRADE</p><h2>Materijali za<br>vrhunski rezultat.</h2><a href="?all=1#ponuda">Istražite ponudu →</a></div></section>
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
    '010'=>['polish.webp','P&S Therapy Final Polish 236 ml — testna ponuda'],
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
