# WEBSHOP 0.9.2

Logo i brend kroz administraciju, s glavnim i tamnim logom, sloganom i ikonicom stranice. Nova administracija i pet zasebnih tema javne prodavnice. Za nadogradnju s 0.9 nije potrebna SQL migracija. [Upute za teme i nadogradnju bez Terminala](docs/NADOGRADNJA-0.9.2.md). Za verziju 0.8 prvo primijenite nadogradnju 0.9 i migraciju 009.

PHP 8.2+ / MySQL 8.0+ internet prodavnica za cPanel. Bez Composer ili Node instalacije u produkciji; PHPMailer 7.1.1 je uključen u izvorne fajlove. Podržana je i MariaDB 10.6+; lokalne provjere su izvršene na MariaDB 11.4.

## Postavljanje

Detaljne upute: [docs/POSTAVLJANJE.md](docs/POSTAVLJANJE.md).

1. Za **novu praznu bazu** uvezite `database/install.sql`, jednom.
2. Za **postojeću v0.8 bazu** primijenite samo `database/migrations/009_admin_backups.sql`, nakon backupa i staging provjere. Za starije verzije prvo primijenite nedostajuće 002/003/004/005/006/007/008 migracije redom. CLI `php bin/migrate.php` primjenjuje nedostajuće migracije. Ne uvozite install.sql preko postojeće baze.
3. Kopirajte `config/local.example.php` u `config/local.php` i unesite svoje podatke.
4. Usmjerite document root poddomene na `public/`. Ostali direktoriji moraju ostati izvan web root direktorija.
5. Uključite PDO MySQL, mbstring, OpenSSL, ZIP i HTTPS. PHP korisnik treba pisanje u `public/uploads/`, `storage/` i `config/`.
6. Pokrenite `php bin/create-admin.php` iz cPanel terminala. Ako Terminal nije dostupan, koristite [zaštićeni obrazac za prvog administratora](docs/BEZ-TERMINALA.md). Nema podrazumijevane lozinke.
7. Otvorite `/admin.php`, podesite naziv, izgled, pošiljaoca i dostavu. Dodajte artikle i unesite stanje kroz Lager.

## Funkcije

Katalog, pretraga, kategorije, naslovnica, slike, korpa, kupovina bez registracije, pouzeće i opcionalna uplata na račun. Administracija proizvoda, lagera, rezervacija, narudžbi, slanja, naplate i povrata. Kurir, broj pošiljke, datum i obrazloženje otpreme. Posebni izlazi: povlačenje, otpis, lična prodaja i ostalo; storno bez brisanja historije. Korisnici, prilagodljive uloge, dozvole i dnevnik promjena. Obavijesti u administraciji i opcionalni e-mail red. Štampa listova za slanje, pakovanje i lagerliste. Promjenjiv naziv prodavnice, boja, naslovnica, kontakt i dostava.

Naplata je ručna evidencija primljenog novca, bez kartičnog procesora. Štampani list je interni obrazac; službenu naljepnicu izdaje kurir. Rezervacije traju do slanja ili otkazivanja. Administrativne historije imaju ograničenje na 100/200 prikazanih zapisa. Ova verzija ne zamjenjuje fiskalni ili računovodstveni sistem.

## Testiranje

U zasebnom checkoutu koristite novu praznu bazu imena `webshop_test` ili `webshop_test_*`:

```sh
export TEST_DB_HOST=127.0.0.1 TEST_DB_PORT=3306 TEST_DB_NAME=webshop_test
export TEST_DB_USER=root TEST_DB_PASSWORD=your_test_password
php tests/integration.php
node tests/http.mjs
node tests/setup.mjs
node tests/smtp.mjs
php tests/backups.php
```

HTTP test odbija prepisati postojeći config/local.php. Testni podaci ostaju u testnoj bazi. CI provjerava PHP 8.2 i MySQL 8.0. [Arhitektura](docs/ARHITEKTURA.md) · [Provjere](docs/TESTIRANJE.md).

