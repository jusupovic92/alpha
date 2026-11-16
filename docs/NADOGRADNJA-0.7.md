# WEBSHOP 0.7 — mail obavijesti i snižene cijene

## Nadogradnja bez Terminala

Za postojeću verziju 0.6 s migracijom 006:

1. Sačuvajte backup fajlova u cPanel File Manageru i izvezite cijelu bazu u phpMyAdminu. Postupak prvo provjerite na staging kopiji. Za vrijeme nadogradnje kratko zaustavite kupovinu na hostingu.
2. U postojećoj WEBSHOP bazi uvezite **samo database/migrations/007_mail_discounts.sql**, jednom. Ona dodaje polje stare cijene, red mailova i isključene početne mail postavke. Ne mijenja postojeće cijene, narudžbe, iznose ili lager. Ne uvozite install.sql niti ranije migracije ponovo.
3. ZIP nadogradnje raspakujte u **shop.sdpkakanj.ba**, iznad direktorija public. Prenesite sve nove fajlove, uključujući app/mailer.php, app/vendor/PHPMailer, app/views/admin-mail.php i oba bin/send-notifications fajla. Document root ostaje **shop.sdpkakanj.ba/public**.
4. Sačuvajte config/local.php, public/uploads i, nakon što postoji, **config/mail.key**. Ne kreirajte ponovo administratora. Osvježite uz Ctrl+F5 i provjerite administraciju i artikle. Uklonite ZIP s hostinga.

Za starije instalacije prvo dovršite prethodne migracije redom. Ako SQL uvoz bude prekinut, provjerite djelimično primijenjenu strukturu prije ponavljanja; MySQL DDL nije transakcijski povratan. Povrat traži usklađenu prethodnu bazu i fajlove, uz čuvanje novih narudžbi.

## Postavke maila

Super administrator ima novi odjeljak **Mail obavijesti**. Samo on podešava pristup mail serveru i pregledava primaoce/red. Uloge Lager i Slanje ne dobijaju SMTP postavke.

1. Odaberite **SMTP server**, unesite podatke iz cPanel → Email Accounts → Connect Devices. Tipično se koristi puna e-mail adresa kao korisničko ime. Port i zaštitu prepišite iz hostingovih podataka: STARTTLS najčešće 587, SMTPS najčešće 465. Certifikat servera mora biti važeći; provjera se ne isključuje.
2. Unesite SMTP lozinku. Prazno polje pri narednom spremanju zadržava postojeću lozinku; postoji posebna opcija za uklanjanje. Lozinka se ne prikazuje niti zapisuje u dnevnik. Za čuvanje se koristi OpenSSL i ključ u config/mail.key, izvan javnog direktorija. PHP mora imati mogućnost stvaranja tog fajla u config direktoriju. Nemojte davati globalnu dozvolu 777. Sačuvajte ključ uz privatni backup: bez njega se lozinka mora ponovo unijeti.
3. Unesite adresu i ime pošiljaoca, zatim jednu ili više adresa za pakovanje/slanje, svaku u novi red. Prihvaćeni su i zarez ili tačka-zarez; najviše 20 adresa. Svaki primalac dobija vlastitu poruku, bez otkrivanja ostalih adresa.
4. Unesite **https://shop.sdpkakanj.ba** kao adresu prodavnice. To služi za link na zaštićeni detalj narudžbe. Ne koriste se neprovjereni podaci iz browsera.
5. Uključite obavijesti i sačuvajte, pa kliknite **Pošalji probnu poruku sačuvanim primaocima**. Provjerite pristiglu i neželjenu poštu. Hosting treba imati ispravne SPF/DKIM postavke za domen pošiljaoca; cPanel ih prikazuje pod Email Deliverability.

Ako hosting podržava samo PHP mail, može se odabrati i taj način. SMTP je predvidljiviji jer koristi jasno podešen račun i autentifikaciju. Polja servera i lozinke se ne koriste u PHP mail režimu.

## Automatska obrada kroz cPanel Cron

**Bez Cron zadatka ponovni pokušaji nisu zagarantovani.** U cPanelu otvorite **Cron Jobs**, odaberite izvršavanje jednom u minuti i unesite CLI PHP putanju i punu putanju skripte. Terminal nije potreban.

Primjer komande, uz obaveznu zamjenu CPANEL_KORISNIK stvarnim nazivom iz File Managera:

```text
/usr/local/bin/php /home/CPANEL_KORISNIK/shop.sdpkakanj.ba/bin/send-notifications.php
```

Koristite CLI PHP 8.2+ koji hosting navodi; neki cPanel serveri koriste /opt/cpanel/ea-php82/root/usr/bin/php umjesto /usr/local/bin/php. Putanja direktorija mora odgovarati vašem hostingu. Zamijenite postojeći Cron za istu skriptu, nemojte praviti duplikat. CLI i web PHP moraju čitati isti config/mail.key. Za početnu provjeru zadržite Cron izlaz kako biste vidjeli broj obrađenih poruka.

Ako je uključena opcija trenutnog slanja i hosting ima PHP-FPM/fastcgi_finish_request, sistem pokušava slanje nakon što vrati odgovor kupcu. Narudžba i rezervacija su prije toga sačuvane. Na drugim serverima poruke obrađuje Cron. Ponovne pokušaje uvijek obrađuje Cron, čak i bez posjetilaca.

Svaka nova narudžba dobija po jednu poruku za svakog tada podešenog primaoca. Ponovljena potvrda iste kupovine ne pravi nove poruke. Mail sadrži broj narudžbe, artikle, količine i link u administraciju; ne sadrži adresu ili telefon kupca. Primaoci moraju imati ovlašten račun ako žele otvoriti narudžbu.

Uključivanje ne šalje zaostale stare narudžbe. Promjena liste primalaca važi za buduće narudžbe; već napravljene poruke ostaju adresirane ranijim primaocima i mogu se obustaviti u redu. Isključivanje obavijesti zaustavlja obradu novog reda. Poruke za narudžbe koje više nisu u pripremi automatski se obustavljaju prije slanja.

U redu su vidljivi status i pokušaji za svakog primaoca. Neuspjelo slanje jednom ne blokira ostale. Pokušava se do pet puta, s najmanje pet minuta razmaka. Super administrator može ponoviti neuspjele poruke ili ih obustaviti. Status Poslano znači da je transport/server prihvatio poruku, a ne potvrdu čitanja ili konačne isporuke. Kod prekida veze nakon što je server primio poruku, ali prije potvrde, ponovni pokušaj može proizvesti duplikat — SMTP ne daje apsolutnu garanciju tačno jednog primitka.

Stari notify_email/mail_from iz config/local.php ostaju podržani kroz raniji worker dok prvi put ne sačuvate novi odjeljak Mail obavijesti. Nakon toga mailom upravljaju nove postavke. Ranije stare poruke ne prebacuju se automatski u novi red.

Obavijest na mobitelu zavisi od podešavanja mail aplikacije primaoca; ova verzija ne dodaje SMS ili zasebne push poruke.

## Snižene cijene

U **Artikli i naslovnica → Uredi artikal** unesite novu cijenu u Prodajna cijena, a prethodnu cijenu u **Stara cijena za precrtavanje**. Primjer: trenutna 89.00, stara 139.00. Prikaz će biti 89,00 KM, prekriženo 139,00 KM i **Ušteda 50,00 KM (36%)**. Iznos uštede se računa u feningama, procenat se zaokružuje na cijeli broj.

Stara cijena mora biti veća od trenutne. Za uklanjanje akcije obrišite polje stare cijene; trenutna prodajna cijena ostaje kakvu ste unijeli. Sistem ne pogađa staru cijenu iz prethodnih narudžbi i ne dodaje izmišljene popuste. Unesite stvarnu prethodnu cijenu koju želite prikazati.

Prikaz postoji na karticama, detalju proizvoda i automatskoj velikoj naslovnici. Kupac plaća trenutnu cijenu sačuvanu u bazi; ranije narudžbe zadržavaju svoje cijene. Ako koristite zaseban grafički baner s nacrtanim iznosom, njegov tekst se ne može automatski izmijeniti: zamijenite sliku ili koristite automatski prikaz.

## Provjere

Prošli su migracije, čuvanje starih iznosa, red i ponovni pokušaji po primaocu, deduplikacija potvrđene kupovine, zaštita postavki, šifriranje lozinke i obustava neaktuelnih obavijesti. SMTP transport je provjeren preko lokalnog TLS servera, s autentifikacijom, provjerom sadržaja i odbijanjem nepouzdanog certifikata. Nije slan stvarni mail niti su testirani vaši produkcijski podaci za prijavu. Browser test pokriva snižene kartice, naslovnicu, detalj na mobitelu i mail administraciju.
