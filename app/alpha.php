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
    $categories=alphaCategories();
    $slides=[];
    $defaultImages=['assets/alpha/hero-nijansa.jpg','assets/alpha/hero-lakirnica.jpg','assets/alpha/hero-finis.jpg'];
    $defaults=[
        ['title'=>'Pronađite savršenu nijansu','text'=>'Sve za profesionalno lakiranje i vrhunsku završnu obradu.','button'=>'Pogledaj ponudu'],
        ['title'=>'Profesionalno lakiranje','text'=>'Kvalitetni materijali i oprema za radionice koje traže pouzdan rezultat.','button'=>'Istraži proizvode'],
        ['title'=>'Od pripreme do savršenog finiša','text'=>'Pronađite sve što vam treba za svaki korak autoreparature.','button'=>'Otkrij proizvode'],
    ];
    for($i=1;$i<=3;$i++){
        $slides[]=[
            'title'=>setting('hero_slide_'.$i.'_title',$defaults[$i-1]['title']),
            'text'=>setting('hero_slide_'.$i.'_text',$defaults[$i-1]['text']),
            'button'=>setting('hero_slide_'.$i.'_button',$defaults[$i-1]['button']),
            'url'=>setting('hero_slide_'.$i.'_url','?all=1#ponuda'),
            'image'=>setting('hero_slide_'.$i.'_image',$defaultImages[$i-1]),
        ];
    }
    ?>
    <section class="alpha-showcase" data-carousel aria-label="Promotivni slajder">
      <?php foreach($slides as $i=>$slide):$image=$slide['image']!==''?$slide['image']:'assets/alpha/workshop.svg';?>
      <article class="alpha-showcase-slide" data-slide <?=$i?'hidden':''?> aria-label="<?=($i+1).' od '.count($slides)?>">
        <div class="alpha-showcase-copy">
          <p class="alpha-kicker">ALPHA REFINISH / PROFESIONALNA AUTOREPARATURA</p>
          <h1><?=e($slide['title'])?></h1>
          <p><?=e($slide['text'])?></p>
          <a class="btn" href="<?=e($slide['url'])?>"><?=e($slide['button'])?> <span aria-hidden="true">→</span></a>
        </div>
        <div class="alpha-showcase-image"><img src="<?=e(imageSource($image))?>" alt="<?=e($slide['title'])?>" <?=$i?'loading="lazy"':'fetchpriority="high"'?>></div>
      </article>
      <?php endforeach;?>
      <div class="carousel-controls alpha-showcase-controls" data-carousel-controls hidden>
        <div class="carousel-dots"><?php foreach($slides as $i=>$slide):?><button class="carousel-dot" data-slide-index="<?=$i?>" aria-label="<?=e('Prikaži slajd '.($i+1))?>" aria-pressed="<?=$i?'false':'true'?>"><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></button><?php endforeach;?></div>
        <div><button class="icon-button" data-carousel-pause aria-label="Zaustavi automatsku izmjenu">Ⅱ</button><button class="icon-button" data-carousel-prev aria-label="Prethodni slajd">←</button><button class="icon-button" data-carousel-next aria-label="Sljedeći slajd">→</button></div>
        <span class="sr-only" data-carousel-status aria-live="polite"></span>
      </div>
    </section>
    <?php if($categories):?><section class="alpha-categories" aria-label="Kategorije proizvoda"><?php foreach($categories as $category):?><a href="?category=<?=(int)$category['id']?>#ponuda"><img src="<?=e(imageSource($category['image_url']))?>" alt="" loading="lazy"><strong><?=e($category['name'])?></strong><span aria-hidden="true">→</span></a><?php endforeach;?></section><?php endif;?>
    <div class="alpha-benefits"><span>Od pripreme do završne obrade</span><span>Materijali i alati na jednom mjestu</span><span>Jasna dostupnost artikala</span></div>
    <?php
}

function alphaDemoSeed(): int {
    global $db;
    requirePermission('settings');
    requirePermission('products');
    requirePermission('inventory');
    $sql=file_get_contents(dirname(__DIR__).'/database/demo-alpha.sql');
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
