<?php
declare(strict_types=1);

function alphaAdvice(): array {
    return [
        'odabir-polirke'=>['Kako odabrati polirku?','polishing-close.jpg','Odaberite alat prema površini, iskustvu i vrsti posla.',[
            'Ekscentrična polirka kombinuje rotaciju i oscilaciju. Pri izboru uporedite hod, prečnik podloge, regulaciju brzine, težinu i ergonomiju.',
            'Rotaciona polirka traži pažljivo vođenje i kontrolu temperature površine. Za osjetljive rubove i zahtjevne lakove posebno je važna odgovarajuća tehnika.',
            'Uskladite podlogu, spužvu i pastu s preporukama proizvođača. Krenite od manje agresivne kombinacije i prvo napravite probu na manjoj površini.',
            'Prije rada pročitajte uputstvo uređaja i tehničke podatke proizvoda. Čista podloga i dobar pregled površine olakšavaju kontrolu rezultata.'
        ]],
        'priprema-povrsine'=>['Priprema površine prije lakiranja','workshop-spray.jpg','Dobar završni rezultat počinje pravilnom pripremom.',[
            'Pregledajte podlogu i utvrdite stanje postojećeg premaza. Očistite površinu od nečistoća i koristite sredstvo za odmašćivanje kompatibilno s odabranim sistemom.',
            'Postupak brušenja, granulaciju, temeljni premaz i vrijeme sušenja odredite prema tehničkom listu materijala. Ne postoji isti postupak za sve podloge i sve premaze.',
            'Prije nanošenja uklonite prašinu i zaštitite okolne dijelove. Temperaturu, miješanje i razrjeđivanje provjerite u dokumentaciji proizvođača.',
            'Lakiranje i pripremu obavljajte uz odgovarajuću ventilaciju i zaštitnu opremu prema sigurnosnom listu materijala. Ako niste sigurni u kompatibilnost, zatražite stručnu pomoć.'
        ]],
        'brusni-disk'=>['Koji brusni disk odabrati?','maxxt-sander.png','Podloga, granulacija i način rada trebaju biti usklađeni.',[
            'Provjerite prečnik, način pričvršćivanja i raspored rupa za odsisavanje. Disk treba odgovarati podlozi i uređaju koji koristite.',
            'Granulaciju birajte prema fazi obrade i tehničkom listu premaza. Grublje brušenje ostavlja izraženije tragove; završna obrada traži odgovarajući slijed finijih granulacija.',
            'Pritisak i brzinu prilagodite materijalu i preporuci proizvođača. Redovno pregledajte disk i zamijenite ga kada izgubi učinak ili se zapuni.',
            'Koristite odsisavanje i zaštitu propisanu za materijal koji obrađujete. Prvo provjerite rezultat na manjoj površini.'
        ]]
    ];
}

function alphaContact(): void {
    $contact=businessContact();$email=$contact['email'];$phone=$contact['phone'];
    ?><div class="alpha-contact-links"><?php if(filter_var($email,FILTER_VALIDATE_EMAIL)):?><a href="mailto:<?=e($email)?>"><?=e($email)?></a><?php endif;?><?php if($phone!==''):?><a href="tel:<?=e(preg_replace('/[^+0-9]/','',$phone)??'')?>"><?=e($phone)?></a><?php endif;?><?php if($email===''&&$phone===''):?><p>Kontaktni podaci trenutno nisu uneseni.</p><?php endif;?></div><?php
}

function alphaLowerHome(): void {
    ?>
    <section class="alpha-value-section" aria-labelledby="alpha-value-title"><div class="alpha-value-copy"><p class="alpha-section-kicker">ZA VAŠU RADIONICU</p><h2 id="alpha-value-title">Sve za profesionalan rezultat.</h2><p>Od pripreme površine do završnog poliranja.</p><a class="btn" href="?all=1#ponuda">Istraži ponudu <span aria-hidden="true">→</span></a></div><div class="alpha-value-grid"><?php foreach([
        ['boje-i-lakovi','Pregledna ponuda','Materijali i oprema raspoređeni po kategorijama.'],
        ['bag',inventoryEnabled()?'Dostupnost artikala':'Jednostavno naručivanje',inventoryEnabled()?'Pregledajte stanje uz svaki proizvod.':'Odaberite artikle i pošaljite narudžbu.'],
        ['priprema-povrsine','Pomoć pri odabiru','Kontaktirajte nas za dodatne informacije.'],
        ['oprema','Sve na jednom mjestu','Od pripreme i brušenja do završne obrade.']
    ] as [$icon,$title,$text]):?><article><?=alphaIcon($icon)?><h3><?=e($title)?></h3><p><?=e($text)?></p></article><?php endforeach;?></div></section>
    <?php alphaBrandStrip();?>
    <section class="alpha-advice-section" aria-labelledby="alpha-advice-title"><div class="section-heading"><div><p class="eyebrow">PRAKTIČNO I KORISNO</p><h2 id="alpha-advice-title">Savjeti iz radionice</h2></div></div><div class="alpha-advice-grid"><?php foreach(alphaAdvice() as $slug=>$article):?><a class="alpha-advice-card" href="?page=advice&amp;article=<?=e($slug)?>"><div class="alpha-advice-photo"><img src="assets/alpha/<?=e($article[1])?>" alt="" loading="lazy" width="640" height="400"></div><div class="alpha-advice-copy"><p class="alpha-section-kicker">SAVJETI</p><h3><?=e($article[0])?></h3><span>Pročitaj savjet <b aria-hidden="true">→</b></span></div></a><?php endforeach;?></div></section>
    <section class="alpha-contact-banner"><div><p class="alpha-section-kicker">ZA SVAKI KORAK POSLA</p><h2>Trebate materijal za radionicu?</h2><p>Od pripreme i brušenja, preko lakiranja, do poliranja i završne obrade.</p><div class="alpha-actions"><a class="btn" href="?all=1#ponuda">Pogledaj proizvode →</a><a class="btn secondary" href="?page=info&amp;section=kontakt">Kontaktirajte nas</a></div></div></section>
    <?php
}

function alphaFooter(): void { renderBusinessFooter(); }

function alphaContentPage(string $action): void {
    $articles=alphaAdvice();$slug=(string)($_GET['article']??'');
    $section=(string)($_GET['section']??'kontakt');
    if($action==='info'&&$section==='kontakt'){renderRichContactPage();return;}
    $titles=['kontakt'=>'Kontakt i upiti','dostava'=>'Dostava i plaćanje','reklamacije'=>'Reklamacije i povrat','uslovi'=>'Uslovi kupovine','privatnost'=>'Privatnost'];
    if(($action==='advice'&&!isset($articles[$slug]))||($action==='info'&&!isset($titles[$section]))){http_response_code(404);echo '<h1>Sadržaj nije pronađen.</h1>';return;}
    ?><article class="alpha-content-page"><a class="alpha-back" href="./">← Povratak na naslovnicu</a><?php if($action==='advice'):$article=$articles[$slug];?><p class="alpha-section-kicker">SAVJETI IZ RADIONICE</p><h1><?=e($article[0])?></h1><img class="alpha-article-image" src="assets/alpha/<?=e($article[1])?>" alt="" width="1200" height="600"><p class="alpha-article-intro"><?=e($article[2])?></p><?php foreach($article[3] as $paragraph):?><p><?=e($paragraph)?></p><?php endforeach;?><a class="btn" href="?all=1#ponuda">Pregledaj proizvode →</a><?php else:?><p class="alpha-section-kicker">INFORMACIJE ZA KUPCE</p><h1><?=e($titles[$section])?></h1>
    <?php if($section==='dostava'):?><p>Dostupne načine dostave i konačan trošak birate prije potvrde narudžbe.</p><?php $methods=query('SELECT * FROM shipping_methods WHERE active=1 ORDER BY id')->fetchAll();foreach($methods as $method):?><section><h2><?=e($method['name'])?></h2><p><?=money(deliveryCharge($method)/100)?> · <?=e(deliveryPayers()[$method['payer']]??'')?></p><p><?=nl2br(e((string)$method['terms']))?></p></section><?php endforeach;?><h2>Plaćanje</h2><p>Pouzećem pri preuzimanju.<?php if(setting('bank_details')!==''):?> Dostupna je i uplata na račun prema podacima prikazanim tokom naručivanja.<?php endif;?></p>
    <?php elseif($section==='privatnost'):?><p><?=nl2br(e(setting('privacy_text','Podaci uneseni pri naručivanju koriste se za obradu narudžbe. Za pitanja o obradi podataka kontaktirajte prodavnicu.')))?></p><p>Za rad korpe i prijave koristi se sesija preglednika.</p>
    <?php elseif($section==='reklamacije'):?><p>Za prijavu problema s narudžbom, zahtjev za povrat ili reklamaciju kontaktirajte prodavnicu. Navedite broj narudžbe, proizvod i kratak opis problema. Fotografije možete poslati putem e-maila.</p><p>Prije slanja proizvoda zatražite upute za povrat.</p><?php alphaContact();?>
    <?php elseif($section==='uslovi'):?><p>Uz svaki proizvod prikazana je cijena. U korpi pregledajte količine, zatim unesite podatke za isporuku, odaberite dostavu i način plaćanja. Konačan iznos prikazuje se prije potvrde narudžbe.</p><p>Za dodatne informacije o prodavcu i uslovima kupovine obratite se prodavnici.</p><?php alphaContact();?>
    <?php else:?><p>Za pomoć pri izboru materijala, informacije o proizvodima ili upit o narudžbi koristite sljedeće kontakte.</p><?php alphaContact();renderContactForm();endif;endif;?></article><?php
}
