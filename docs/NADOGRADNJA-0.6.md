# WEBSHOP 0.6 — načini dostave i priprema pošiljke

## Nadogradnja bez Terminala

Za postojeću verziju 0.5/0.5.1 s primijenjenom migracijom 005:

1. U cPanel File Manageru sačuvajte backup postojećih fajlova, a kroz phpMyAdmin izvezite cijelu bazu. Prvo provjerite nadogradnju na staging kopiji. Za kratko vrijeme nadogradnje zaustavite kupovinu opcijom održavanja na hostingu.
2. U postojećoj WEBSHOP bazi uvezite **samo database/migrations/006_delivery.sql**, jednom. Ne uvozite install.sql ili ranije migracije ponovo. Za starije instalacije prvo dovršite nedostajuće prethodne nadogradnje redom.
3. ZIP nadogradnje raspakujte u **shop.sdpkakanj.ba**, iznad public. Potvrdite zamjenu fajlova i prenesite nove app/delivery.php, app/views/admin-delivery.php i app/views/shipment-prepare.php. Document root ostaje **shop.sdpkakanj.ba/public**.
4. Sačuvajte config/local.php i public/uploads. Paket nadogradnje ne sadrži lozinke, korisničke fotografije, demonstracijske podatke ili instalacijski obrazac. Ne kreirajte administratora ponovo.
5. Osvježite uz Ctrl+F5. Provjerite odjeljak Dostava, probnu pripremu narudžbe i štampu. Uklonite ZIP s hostinga i ponovo omogućite kupovinu.

Migracija dodaje strukturu, bez promjene postojećih iznosa narudžbi, statusa ili lagera. Stare narudžbe nemaju rekonstruisane uslove. Kod prekinute migracije prvo provjerite djelimično primijenjenu strukturu; MySQL DDL nije transakcijski povratan. Povrat zahtijeva usklađen prethodni backup baze i fajlova, uz čuvanje svih novih narudžbi.

## Dodavanje, uređivanje i brisanje dostave

Korisnik s dozvolom **Izgled i dostava / settings** može dodati i uređivati:

- naziv načina dostave;
- kurirsku službu ili izvršioca;
- trošak u KM;
- ko plaća dostavu: kupac ili prodavac;
- tekst uslova, rok isporuke i posebne napomene;
- dostupnost kupcima.

U registru postoje radnje **Uredi** i **Obriši**. Za brisanje unesite razlog i potvrdite. Brisanje uklanja dostavu iz novog izbora, ali zadržava njen historijski zapis i ne dira sačuvane narudžbe ili pripreme. Obrisane načine možete pregledati kroz **Prikaži obrisane**. Isključivanje opcije Dostupno kupcima ostavlja način za internu pripremu, bez ponude na checkoutu.

Ako dostavu u katalogu plaća **prodavac**, pri novoj kupovini kupcu se obračunava **0 KM dostave**. Ako je plaća kupac, dodaje se navedena cijena. Uslovi dostupnih načina vidljivi su kupcu u završnoj formi. Narudžba čuva cijenu i uslove koji su važili pri kupovini.

## Priprema narudžbe

Korisnik s dozvolama **Narudžbe + Slanje** u detalju narudžbe koja je u pripremi:

1. U dijelu **1. Priprema dostave** odabere način dostave. Automatski se popune kurir, trošak, ko plaća i tekst uslova. Može ih prilagoditi stvarnom dogovoru za tu pošiljku. Bez JavaScripta podaci se unose ručno prema prikazanim predlošcima.
2. Po potrebi unese broj pošiljke, obavezno obrazloženje i klikne **Sačuvaj pripremu dostave**.
3. Odštampa list za slanje. List sadrži sačuvani način, kurira, dogovoreni trošak i uslove. Otvaranje štampe ne potvrđuje otpremu.
4. Nakon stvarne predaje paketa unese obrazloženje u dijelu **2. Potvrda predaje kuriru** i potvrdi slanje. Tek tada status postaje Poslano i rezervisana roba izlazi iz fizičkog lagera. Naplata ostaje odvojena.

Dogovoreni trošak kurira u pripremi je **operativna evidencija pošiljke**, a ne izmjena kupčevog računa. Promjena načina, troška ili platioca u pripremi **ne mijenja već ugovoreni ukupni iznos, kupčev trošak dostave ili otkupninu**. Primjer: kupac je potvrdio dostavu 10 KM, a kurir je dogovoren za 9 KM; narudžba ostaje na ugovorenom iznosu, dok priprema pokazuje 9 KM. Ako je potreban drugi iznos za kupca, taj poslovni dogovor treba zasebno riješiti; ova verzija ne dodaje naknadno prepravljanje računa.

Sačuvana priprema može se mijenjati dok narudžba nije otpremljena. Nakon slanja uslovi i trošak su zaključani; postojeća dopuna broja pošiljke/kurira ostaje dostupna i evidentira se u historiji. Lična predaja unutar narudžbe ostaje zasebna radnja, bez obaveze kurirske pripreme.

Naknadno uređivanje ili brisanje načina dostave ne prepisuje kupčeve uslove niti već sačuvanu pripremu. Ako je način obrisan nakon pripreme, već dogovorenu pošiljku i dalje možete otpremiti prema sačuvanim podacima. Za novi izbor obrisani način nije dostupan.

## Provjere

Provjereni su dodavanje, uređivanje, brisanje s historijom, ograničenja dozvola, besplatna dostava za kupca kada plaća prodavac, čuvanje uslova pri kupovini, priprema bez promjene ukupnog iznosa/lagera, čuvanje dogovora nakon izmjene/brisanja načina, odbijanje obrisanih opcija i zaključavanje pripreme nakon slanja. Browser provjera pokriva automatsko popunjavanje, čuvanje, štampu, potvrdu slanja i mobilni prikaz. Ostali testovi pokrivaju postojeće procese kupovine, galerije, naslovnice, rezervacija, izlaza robe i naplate.
