# WEBSHOP 0.9 — administracija, teme i sigurnosne kopije

## Nadogradnja postojeće verzije 0.8 bez Terminala

1. U cPanelu preuzmite kopiju postojeće baze kroz phpMyAdmin i kopiju fajlova. Sačuvajte i postojeće privatne ključeve iz `config/`.
2. U cPanel > Select PHP Version uključite **pdo_mysql, mbstring, openssl i zip**, uz PHP 8.2 ili noviji.
3. U File Manageru otvorite direktorij **shop.sdpkakanj.ba**, roditeljski direktorij od `public/`. Ovdje učitajte i raspakujte `WEBSHOP-0.9.0-NADOGRADNJA.zip`, uz zamjenu istoimenih fajlova. Ne raspakujte paket unutar `public/`.
4. U phpMyAdminu odaberite postojeću WEBSHOP bazu i jednom uvezite **database/migrations/009_admin_backups.sql**. Za instalaciju 0.8 potrebna je samo ova migracija. Nemojte uvoziti `install.sql` preko postojeće baze. Za starije verzije prvo primijenite nedostajuće migracije 002–008 redom.
5. PHP mora imati pravo pisanja u `storage/`, `config/` i `public/uploads/`. `storage/` i `config/` ostaju izvan javnog document roota. Document root ostaje **shop.sdpkakanj.ba/public**.
6. Prijavite se ponovo i osvježite stranicu. Otvorite **Teme**, zatim **Backup i vraćanje**. Napravite prvu kopiju, provjerite je i preuzmite kopiju i zaseban ključ oporavka.

Paket ne sadrži produkcijsku konfiguraciju, lozinke, ključeve ni fotografije. Migracija dodaje samo nove postavke i oznaku verzije; ne mijenja narudžbe, artikle ili postojeće zapise aktivnosti. Za novu praznu instalaciju koristi se puni paket i `database/install.sql`.

## Novi izgled i teme

Početni poslovni pregled slijedi varijantu 1: zelena bočna navigacija, pregled pripreme i slanja, naplate, niskih zaliha, dnevnika i sigurnosnih kopija. Postojeće funkcije ostaju dostupne prema dozvolama korisnika.

U stavci **Teme** odaberite Smaragdnu, Mornarsku, Svijetlu, Šljivu ili Tamnu. Odabir se pamti za cijelu administraciju. Potrebna je dozvola za postavke. Postavke veličine teksta ostaju u **Izgled i postavke**. Tema administracije ne mijenja javnu prodavnicu.

## Dnevnik na bosanskom

Dnevnik prikazuje razumljive nazive aktivnosti i obrazloženja, uključujući prethodni i novi status, načine dostave i razloge izlaza robe. Može se pretraživati i filtrirati po aktivnosti, korisniku i datumu, uz straničenje. Izvorni kodovi i podaci u bazi ostaju sačuvani za historiju.

## Backup i vraćanje

Pristup ima samo super administrator. Kopija `.wsb` je šifrovana i obuhvata 18 WEBSHOP tabela, lokalne fotografije iz `public/uploads/`, SMTP ključ i privatnu konfiguraciju za oporavak. Fotografije koje su samo udaljeni URL nisu preuzete u kopiju. Dodatne tabele drugih aplikacija nisu uključene.

Kopije se čuvaju u `storage/backups/`, izvan javnog direktorija. **Ključ `config/backup.key` čuvajte odvojeno od kopija**, na sigurnom mjestu. Preuzimanje ključa traži trenutnu administratorsku lozinku. Bez izvornog ključa šifrovane kopije nije moguće vratiti. Preuzmite kopije i na drugi uređaj; kopija koja postoji samo na istom hostingu neće pomoći ako se izgubi cijeli hosting.

Za vraćanje prvo odaberite **Provjeri / pregled**. Sistem provjerava šifru, sadržaj, veličine, kontrolne sume i strukturu baze. Podržana je kopija iste strukture i migracija kao trenutna instalacija. Učitavanje preuzete kopije samo je provjerava i dodaje na listu; ne vraća podatke.

Vraćanje traži upis **VRATI** i trenutnu administratorsku lozinku. Prije zamjene automatski nastaje kopija trenutnog stanja. Odabrana kopija zamjenjuje WEBSHOP podatke i lokalne fotografije; narudžbe i druge izmjene nakon datuma kopije neće biti u vraćenom stanju. Privremeno se zaustavlja rad aplikacije. Aktivne prijave se zatvaraju i treba se prijaviti računom iz vraćene kopije. Stari mailovi koji su čekali slanje zaustavljaju se da ne bi ponovo otišli.

Postojeći `config/local.php` se **ne prepisuje niti izvršava iz arhive**; veza prema trenutnoj bazi ostaje ista. SMTP ključ vraća se zajedno s pripadajućim podacima. Ako proces prekine hosting, aplikacija pri sljedećem otvaranju pokušava završiti čišćenje ili vratiti prethodne fajlove prema privatnom zapisniku. Dok oporavak nije završen, prikazuje poruku održavanja.

Podržan obim kopije je do **512 MB** raspakovanog sadržaja. Potreban je slobodan prostor za kopiju, privremene fajlove i sigurnosnu kopiju prije vraćanja. Ograničenja upload veličine i vremena izvršavanja na hostingu mogu biti manja; podesite ih u cPanelu prema veličini podataka. Za veće instalacije koristite hosting backup. Vraćanje prvo isprobajte na zasebnoj probnoj instalaciji iste verzije s kopijom baze i izvornim ključem.

## Automatska dnevna kopija u cPanelu

Terminal nije potreban. U **cPanel > Cron Jobs** dodajte zaseban zadatak koji se pokreće **jednom u minuti**:

```text
/usr/local/bin/ea-php82 /home/CPANEL_KORISNIK/shop.sdpkakanj.ba/bin/backup.php
```

Zamijenite `CPANEL_KORISNIK` stvarnim imenom računa. Ako hosting koristi drugu PHP putanju, preuzmite ispravnu putanju od podrške hostinga. Odaberite PHP 8.2+ sa ZIP ekstenzijom. Postojeći zadatak za mail ostaje zaseban.

U administraciji uključite dnevne kopije i postavite vrijeme, npr. 03:00 po Europe/Sarajevo. Zadatak napravi najviše jednu automatsku kopiju dnevno kada prođe odabrano vrijeme; ponovljeni pozivi ne dupliraju kopiju. Nakon uspješne provjere zadržava posljednjih 7, 14 ili 30 automatskih kopija. Ručne kopije i kopije prije vraćanja ne brišu se automatski. Automatske kopije su početno isključene.

## Provjere prije korištenja

Provjerite kupovinu, pripremu dostave, štampanje, slanje i naplatu na probnoj narudžbi. Provjerite promjenu teme, čitljivost dnevnika i izradu/preuzimanje kopije. Nadogradnja ne mijenja produkcijske podatke automatski osim dodavanja postavki migracijom koju pokrećete sami.
