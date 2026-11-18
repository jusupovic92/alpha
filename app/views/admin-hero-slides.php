<?php
requirePermission('settings');
$defaultImages=['assets/alpha/hero-nijansa.jpg','assets/alpha/hero-lakirnica.jpg','assets/alpha/hero-finis.jpg'];
$defaults=[["Pronađite savršenu nijansu","Sve za profesionalno lakiranje i vrhunsku završnu obradu.","Pogledaj ponudu"],["Profesionalno lakiranje","Kvalitetni materijali i oprema za radionice koje traže pouzdan rezultat.","Istraži proizvode"],["Od pripreme do savršenog finiša","Pronađite sve što vam treba za svaki korak autoreparature.","Otkrij proizvode"]];
?>
<div class="page-title"><div><p class="eyebrow">SADRŽAJ NASLOVNICE</p><h1>Promotivni slajder</h1><p>Uredite tekst i fotografiju za sva tri slajda. Izmjene se prikazuju na naslovnici.</p></div><a class="btn secondary" href="index.php" target="_blank" rel="noopener">Pregled prodavnice ↗</a></div>
<section class="panel">
<?php startForm('hero_slides');?>
<div class="hero-slide-admin-grid">
<?php for($i=1;$i<=3;$i++):?>
<fieldset class="panel hero-slide-admin"><legend>Slajd 0<?=$i?></legend>
<?php field('slide_title_'.$i,'Naslov',setting('hero_slide_'.$i.'_title',$defaults[$i-1][0]));textField('slide_text_'.$i,'Opis',setting('hero_slide_'.$i.'_text',$defaults[$i-1][1]));field('slide_button_'.$i,'Tekst dugmeta',setting('hero_slide_'.$i.'_button',$defaults[$i-1][2]));field('slide_url_'.$i,'Odredište dugmeta',setting('hero_slide_'.$i.'_url','?all=1#ponuda'));field('slide_image_url_'.$i,'Adresa slike (opcionalno)',setting('hero_slide_'.$i.'_image',$defaultImages[$i-1]),'text',false);?>
<label for="slide_image_<?=$i?>">Učitaj novu sliku</label><input id="slide_image_<?=$i?>" name="slide_image_<?=$i?>" type="file" accept="image/jpeg,image/png,image/webp"><p class="field-help">JPG, PNG ili WebP, najviše 5 MB. Učitana slika zamjenjuje postojeću.</p>
<?php $image=setting('hero_slide_'.$i.'_image');if($image!==''):?><img class="hero-slide-preview" src="<?=e(imageSource($image))?>" alt="Trenutna slika slajda <?=$i?>"><?php endif;?>
</fieldset>
<?php endfor;?>
</div><div class="form-footer"><button>Sačuvaj slajdove</button></div></form>
</section>
<style>.hero-slide-admin-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.hero-slide-admin{min-width:0}.hero-slide-admin legend{font-size:18px;font-weight:700;padding:0 8px}.hero-slide-preview{display:block;width:100%;max-height:180px;object-fit:cover;margin-top:12px;border-radius:8px}@media(max-width:900px){.hero-slide-admin-grid{grid-template-columns:1fr}}</style>
