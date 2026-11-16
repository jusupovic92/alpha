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
    $premium=storeTheme()==='alpha-premium';
    $pro=storeTheme()==='alpha-pro';
    $slides=heroProducts();
    $spotlight=$slides[0]??null;
    $heroImage=$spotlight ? imageSource($spotlight['hero_banner_url'] ?: $spotlight['image_url']) : 'assets/alpha/workshop.svg';
    ?>
    <section class="alpha-hero <?=!empty($spotlight['hero_banner_url'])?'alpha-hero-custom':''?>" aria-labelledby="alpha-title">
      <div class="alpha-hero-copy"><p class="alpha-kicker">ALPHA REFINISH / PROFESIONALNA AUTOREPARATURA</p>
        <h1 id="alpha-title"><?=$pro?'Sve za vašu radionicu.':($premium?'Perfekcija u svakom sloju.':'Od pripreme do savršenog sjaja.')?></h1>
        <p>Materijali i oprema za pripremu, lakiranje, poliranje i njegu vozila.</p>
        <div class="alpha-actions"><a class="btn" href="<?=e(alphaCategoryLink($categories,'boje-i-lakovi'))?>">Autoreparatura <span aria-hidden="true">→</span></a><a class="btn secondary" href="<?=e(alphaCategoryLink($categories,'detailing'))?>">Detailing <span aria-hidden="true">→</span></a></div>
        <a class="alpha-all" href="?all=1#ponuda">Pregledajte cijelu ponudu →</a>
      </div>
      <div class="alpha-hero-image"><img src="<?=e($heroImage)?>" alt="<?=e($spotlight?$spotlight['name']:'Priprema vozila u radionici – ilustracija')?>" fetchpriority="high"><?php if($spotlight):?><a class="alpha-spotlight" href="<?=e(productLink((int)$spotlight['id']))?>"><span>Izdvojeni artikal</span><strong><?=e($spotlight['name'])?></strong><span><?=money((float)$spotlight['price'])?> · Pogledaj →</span></a><?php endif;?></div>
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
