# WEBSHOP 0.5 — velika naslovnica i široki baneri

Ova nadogradnja je za postojeću verziju 0.4, s primijenjenim migracijama do 004. Naslovnica dobija širi prikaz, maslinasto zaglavlje, zaobljen baner i dugme za otvaranje artikla. Ostaju pretraga, korpa, kategorije i rotacija posljednja tri aktivna označena artikla.

## Postavljanje bez Terminala

1. Sačuvajte postojeće fajlove u cPanel File Manageru i izvezite cijelu bazu kroz phpMyAdmin. Prvo provjerite na staging kopiji; za vrijeme nadogradnje kratko zaustavite kupovinu na hostingu.
2. U postojećoj WEBSHOP bazi uvezite **samo database/migrations/005_hero_banner.sql**, jednom. Ona dodaje prazno polje za baner; ne prepravlja artikle, količine, narudžbe ili fotografije. Ne uvozite install.sql ili ranije migracije ponovo.
3. ZIP nadogradnje raspakujte u **shop.sdpkakanj.ba**, iznad direktorija public. Potvrdite zamjenu fajlova iz paketa. Document root ostaje **shop.sdpkakanj.ba/public**.
4. Sačuvajte config/local.php i public/uploads. Paket nadogradnje ne sadrži lozinke, korisničke fotografije ili demonstracijske banere. Ne kreirajte administratora ponovo.
5. Osvježite uz Ctrl+F5, provjerite naslovnicu i administraciju, uklonite ZIP s hostinga i ponovo omogućite kupovinu.

Za stariju instalaciju prvo primijenite nedostajuće ranije nadogradnje redom. Ako migracija bude prekinuta, provjerite djelimično primijenjenu strukturu prije ponavljanja; MySQL DDL nije transakcijski povratan. Povrat traži usklađen backup baze i prethodnih fajlova, uz čuvanje novih narudžbi.

## Dodavanje banera

Otvorite **Artikli i naslovnica → Uredi artikal**. U dijelu sa slikama postoje dva nova polja: HTTPS adresa širokog banera ili postavljanje fajla preko **Postavi široki naslovni baner**. Sačuvajte artikal i uključite **Velika naslovna objava**. Ostaje pravilo tri najnovija aktivna označena artikla; trenutni izbor i filter su vidljivi u administraciji.

Preporučena dimenzija je **1920 × 680 px**, JPG, PNG ili WebP, do 5 MB. Sistem prikazuje cijelu sliku bez rezanja i dodaje funkcionalno dugme **Pogledaj ponudu**, koje vodi na taj artikal. Na desktopu je dugme u sredini banera, pa taj dio grafike ostavite slobodan. Na mobitelu je dugme ispod slike. Izbjegavajte sitan tekst u grafici jer se cijeli baner smanjuje na uskom ekranu.

Baner pripremite s vlastitim nazivom, artiklima i iznosima u KM. Tekst i cijene koji su dio same slike ne mijenjaju se automatski s cijenom artikla: pri promjeni ponude zamijenite i grafiku. Dugme ne morate crtati u slici. Primjer tuđeg banera nije uključen u paket.

Bez zasebnog banera prikazuje se automatski dizajn s nazivom, opisom, trenutnom cijenom i glavnom slikom artikla. Za uklanjanje banera obrišite njegovu adresu i sačuvajte bez novog fajla. Glavna slika artikla i galerija ostaju nezavisne. Stari fajlovi se ne brišu automatski.

Rotacija, ručne strelice, pauza, tastatura i poštivanje smanjenog kretanja ostaju dostupni. Nula označenih artikala prikazuje postojeći uvod prodavnice, a jedan artikal se ne rotira.

## Provjere

Provjereni su migracija bez promjene lagera, upload/uklanjanje banera, odbijanje neispravnih fajlova i nesigurnih adresa, automatski prikaz bez banera, izbor tri artikla i postojeći procesi narudžbi, lagera, naplate i dozvola. Browser provjera obuhvata desktop, tablet i mobitel, cijeli omjer slike, rotaciju miješanih slajdova i otvaranje tačnog artikla. Pregledi koriste demonstracijsku bazu i grafiku, bez produkcijskih podataka.
