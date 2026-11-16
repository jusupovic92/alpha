# WEBSHOP 0.5.1 — velika fotografija i detalji artikla

Naslovnica prati prihvaćeni prijedlog: svijetla lijeva strana s kratkim tekstom, cijenom i zelenim dugmetom; velika fotografija desno i do dvije manje fotografije detalja. Maslinasto zaglavlje, tri rotirajuća artikla, kategorije, pretraga i korpa ostaju dostupni. Ispod prikaza su lager, dostava i pouzeće.

## Nadogradnja bez Terminala

1. Napravite backup fajlova u cPanel File Manageru i izvoz baze u phpMyAdminu. Postupak prvo provjerite na kopiji instalacije.
2. **Ako već koristite verziju 0.5 s migracijom 005, nema nove SQL migracije.** Ne uvozite ništa ponovo. Ako koristite 0.4, nakon backupa prvo uvezite samo priloženu database/migrations/005_hero_banner.sql, jednom. Ne uvozite install.sql preko postojeće baze. Za starije verzije prvo dovršite nedostajuće ranije nadogradnje redom.
3. ZIP nadogradnje raspakujte u **shop.sdpkakanj.ba**, iznad direktorija public. Zamijenite fajlove iz paketa; ne brišite config/local.php ili public/uploads. Document root ostaje **shop.sdpkakanj.ba/public**.
4. Osvježite uz Ctrl+F5 i provjerite naslovnicu na računaru i mobitelu, otvaranje artikla i galeriju. Uklonite ZIP s hostinga. Za povrat izgleda vratite backup prethodnih fajlova; ova verzija ne mijenja podatke baze.

## Fotografije i tekst

U **Artikli i naslovnica → Uredi** glavna slika postaje velika fotografija. Prve dvije različite dodatne slike galerije postaju mali prikazi detalja. Bez dodatnih slika nema tih malih prikaza; jedna dodatna slika daje jedan prikaz. Fotografije se ne izmišljaju i nisu automatski napravljeni različiti pogledi proizvoda. Klik na veliku ili malu fotografiju vodi na artikal, gdje ostaje galerija u prozorčiću.

Za izgled najbliži prijedlogu postavite kvalitetnu glavnu fotografiju proizvoda u prostoru, preporučeno najmanje 1200 px, te dvije stvarne fotografije detalja. Cijeli proizvod na glavnoj slici ostaje vidljiv, bez rezanja. Manji prikazi koriste izrez kadra, a na stranici artikla se vidi puna fotografija. Na mobitelu je fotografija iznad teksta.

Naziv, cijena i prve približno 110 jedinica širine opisa automatski dolaze iz artikla. Cijena se ažurira kroz administraciju; nije dio slike. Sažet naziv i kratka prva rečenica opisa daju rezultat najbliži prijedlogu. I dalje se prikazuju tri najnovija aktivna artikla označena kao Velika naslovna objava.

**Ranije postavljen zaseban široki baner ostaje podržan.** Ako želite novi raspored velike fotografije i detalja umjesto tog banera, u uređivanju artikla obrišite vrijednost polja Široki baner za naslovnicu, sačuvajte bez novog baner fajla i koristite glavnu sliku i galeriju. Brisanje adrese ne briše fotografiju s hostinga.

Slikovni koncept iz razgovora nije ugrađen kao fotografija artikla. Prikaz koristi vaše stvarne fotografije i podatke. Testne slike i testni artikli nisu u paketu.

## Provjere

Prošli su PHP lint, integracijski, HTTP i setup testovi: narudžbe, rezervacije, lager, naplata, izlazi robe, dozvole, baneri i galerija. Novi test provjerava najviše dva prikaza detalja. Preglednik je provjeren na širinama 390, 768, 1280 i 1600 px, s rotacijom, otvaranjem artikala, galerijom i postavkom smanjenog kretanja.
