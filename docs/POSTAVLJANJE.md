# Postavljanje na cPanel

Za postojeću verziju 0.2 i novi redizajn 0.3 koristite [posebne upute za migraciju 003](NADOGRADNJA-0.3.md).

## Priprema i direktoriji

Paket ne sadrži produkcijske podatke, demo artikle ili lozinke. Prvo koristite zasebnu testnu poddomenu i bazu. Sačuvajte backup fajlova i potpuni SQL izvoz postojeće baze.

Potrebni su PHP 8.2+, MySQL 8.0+ ili MariaDB 10.6+, InnoDB, PDO MySQL, mbstring, PHP sessions i HTTPS. Za slike uključite file_uploads, upload_max_filesize=5M i post_max_size=8M. Opcionalni e-mail koristi hostingov PHP mail.

```text
/home/CPANEL_KORISNIK/webshop/
    app/
    bin/
    config/local.php
    database/
    public/                 ← document root poddomene
        index.php
        admin.php
        assets/
        uploads/
```

U cPanel Domains postavite document root prodaja.sdpkakanj.ba na `/home/CPANEL_KORISNIK/webshop/public`. U File Manageru uključite prikaz skrivenih fajlova da se prenesu .htaccess datoteke. App, config, bin, database i tests ne smiju biti javno dostupni. Aplikacija ne zahtijeva URL rewrite.

Ako hosting zahtijeva fiksni document root, sadržaj public/ ide u njega, a ostatak ostaje izvan. Tada prilagodite require putanje do bootstrap.php u index.php i admin.php, putanju do print.php u admin.php i upload direktorij u app/uploads.php. Preporučeni raspored iznad ne traži izmjene koda.

## SQL instalacija ili nadogradnja

Kroz MySQL Database Wizard napravite bazu i korisnika i dodijelite mu pristup toj bazi. cPanel obično dodaje prefiks imenima baze i korisnika. U phpMyAdmin prvo odaberite odgovarajuću bazu.

- **Nova prazna baza:** uvezite database/install.sql, jednom. Uključuje osnovnu shemu i migracije 002 i 003, kategoriju, uloge i dostavu. Nemojte zatim uvoziti schema.sql ili migraciju 002 ponovo.
- **Postojeća v0.1 baza:** primijenite database/migrations/002_management.sql, zatim 003_storefront.sql, svaku jednom, ili `php bin/migrate.php` nakon konfiguracije. Ne koristite install.sql na postojećoj bazi. Originalne tabele moraju biti InnoDB.

Migracija ne briše ili prepisuje postojeće proizvode, narudžbe, kupce i količine. MySQL ALTER/CREATE nisu transakcijski povratne naredbe. Nakon prekinutog uvoza vratite backup na staging i otklonite uzrok prije ponavljanja. CLI prepoznaje završenu migraciju i neke oblike djelimičnog uvoza, ali ne popravlja djelimičnu shemu.

Stari stock ostaje slobodna količina jer ga je v0.1 već umanjivala pri kupovini. Stare narudžbe označene su inventory_state=legacy i ne troše stock ponovo pri slanju. Njihovo otkazivanje/povrat vraća količinu jednom. Prije obrade starih otvorenih narudžbi uskladite ih sa stvarnim stanjem. Promet lagera počinje ovom verzijom; stari promet se ne rekonstruiše.

## Konfiguracija i prijava

Ako vaš cPanel nema Terminal, slijedite [postupak bez Terminala](BEZ-TERMINALA.md), s privatnim ključem i jednokratnim web obrascem. Nisu potrebne izmjene postojeće baze ili config/local.php.

Kopirajte config/local.example.php u config/local.php i unesite db_host, db_name, db_user i db_pass. Za cPanel db_host je obično localhost. Ovaj fajl ne objavljujte na GitHubu.

U cPanel terminalu otvorite glavni direktorij projekta:

```sh
php bin/create-admin.php
```

Unesite e-mail, ime i jedinstvenu lozinku od 12–72 znaka. Čuva se hash lozinke; nema javnog instalacijskog ekrana ili zadane lozinke. CLI unos može biti vidljiv na terminalu, pa koristite privatnu terminalsku sesiju. Ako cPanel php nije verzija 8.2+, koristite putanju iz MultiPHP Managera, često `/opt/cpanel/ea-php82/root/usr/bin/php`.

Otvorite /admin.php. Podesite naziv prodavnice, tekst i boju naslovnice, kontakt i pošiljaoca. Provjerite početnu dostavu od 10 KM i zamijenite je svojom. Uplata na račun dostupna je nakon unosa bankovnih podataka. Unesite vlastitu obavijest o obradi podataka.

Dodajte kategorije i proizvode. Slike postavite s računara ili unesite HTTPS adresu. public/uploads treba pisanje za PHP korisnika, obično 755 ili 775 prema hostingu, bez 777. Količine unesite kroz Lager uz razlog ili dokument. Stari proizvodi ostaju istaknuti na naslovnici dok im ne promijenite oznaku.

## Uloge i obrada narudžbi

Početne uloge: Super administrator, Prodaja, Lager i Finansije. Samo super administrator upravlja korisnicima, ulogama i dnevnikom. Slanje traži dozvole Narudžbe + Slanje, naplata Narudžbe + Naplata, a štampa dozvolu Štampa i pristup odgovarajućim podacima.

Kupovina rezerviše količinu. Pripremite narudžbu pa pri slanju unesite broj pošiljke. Naplata poslane/dostavljene narudžbe automatski je završava. Kod uplate unaprijed evidentirajte naplatu, a nakon slanja odaberite Završena. Otkazivanje oslobađa rezervaciju, a povrat vraća poslanu robu. Naplaćenu narudžbu možete otkazati/vratiti tek nakon evidentiranja povrata novca. Rezervacije nemaju automatski istek.

Štampu otvorite iz narudžbe ili lagerliste pa odaberite Štampaj / sačuvaj PDF. Obrazac nije službena kurirska naljepnica niti fiskalni račun. Naplata nije kartična transakcija nego evidencija primljenog novca.

## Opcionalni e-mail

Interna obavijest nastaje zajedno s narudžbom. Za e-mail postavite notify_email, mail_from (adresa vaše domene) i HTTPS base_url u local.php. Dodajte cPanel Cron svakih pet minuta, uz stvarne putanje:

```sh
/opt/cpanel/ea-php82/root/usr/bin/php /home/CPANEL_KORISNIK/webshop/bin/send-notifications.php
```

Šalje do 20 poruka po pokretanju, ponavlja neuspjehe najranije za 10 minuta, najviše pet pokušaja. PHP mail prihvatanje nije potvrda dostave: provjerite inbox, spam i hosting log. Nakon pet neuspjeha interna obavijest ostaje dostupna. U rijetkom prekidu nakon slanja prije upisa emailed_at moguć je dupli e-mail. Checkout ne zavisi od mail servera. SMTP servis nije ugrađen.

## Provjera i prelazak

Na stagingu provjerite kupovinu, ukupnu dostavu, rezervacije, otkazivanje, slanje, naplatu, povrat, štampu, upload i svaku ulogu. Uključite AutoSSL i Force HTTPS Redirect. Isključite display_errors u produkcijskom PHP-u, a error_log držite izvan web root direktorija. Definišite čuvanje i backup narudžbi i logova. Stari login_attempts i request_limits zapisi mogu se periodično čistiti; provjera limita zanemaruje istekle prozore.

Pri prelasku zaustavite nove narudžbe na staroj verziji, napravite novi backup, primijenite provjerenu migraciju i prebacite fajlove. Rollback traži usklađen backup i fajlova i baze. Prije vraćanja sačuvajte narudžbe nastale poslije prebacivanja; nemojte ih prebrisati starim backupom. Produkcijski prelazak nije izvršen ovom isporukom.
