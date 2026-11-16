# Arhitektura i promjene 0.2.0

Pregledana je main revizija 65fcc3f2691df801019e2bc1a9964eed61b8f58b. Verzija 0.1 imala je četiri tabele, PDO bootstrap, katalog, sesijsku korpu, checkout pouzećem i CSS. Checkout je zaključavao proizvod i odmah umanjivao stock. Administracija nije postojala.

## Struktura

- app/bootstrap.php: PDO, konfiguracija, sesija i sigurnosna zaglavlja.
- app/domain.php: iznosi u feningima i prijelazi statusa.
- app/services.php: dozvole, transakcije, checkout, rezervacije, slanje, naplata, dnevnik i limiter.
- app/uploads.php: provjera slika i nasumična imena.
- public/index.php: postojeći tok kupovine uz kategorije, pretragu, naslovnicu i dostavu.
- public/admin.php: forme, validacija i provjera pristupa svakoj radnji.
- app/print.php: obrasci pošiljke, pakovanja i lagerliste.
- bin/: CLI kreiranje administratora, migracija i e-mail red.

Sačuvane su categories, products, orders i order_items s postojećim ključevima. Proizvod dobija featured i reserved. Narudžba čuva snimak dostave i njene cijene, način/status naplate, broj pošiljke, stanje rezervacije i jedinstveni checkout token. Nazivi i cijene stavki ostaju historijski snimak.

Dodane su roles, users, shipping_methods, settings, stock_movements, audit_log, notifications, login_attempts, request_limits i schema_migrations. Korisnik ima jednu prilagodljivu ulogu. Dozvole su JSON lista u tekstualnom polju; wildcard je rezervisan za zaštićenu super administratorsku ulogu. Poslovni zapisi se ne brišu; proizvodi, dostave i korisnici se deaktiviraju.

## Lager i novac

Stock je slobodna količina, reserved je izdvojena roba. Fizičko stanje nove verzije je njihov zbir. Checkout u InnoDB transakciji zaključava dostavu i proizvode po ID-u, računa iznos cijelim feningima, rezerviše robu i upisuje stavke, promet i obavijest. Jedinstveni token sprečava duplu narudžbu. Slanje zatvara rezervaciju bez ponovnog oduzimanja slobodnog stanja. Otkazivanje oslobađa rezervaciju, povrat jednom vraća poslanu robu. Status i naplata zaključavaju isti red narudžbe.

Stari zapisi su legacy jer im je stock već umanjen. Migracija ne mijenja njihove količine i ne rekonstruiše stare rezervacije. Nepoznati stari statusi ostaju sačuvani i traže zasebno usklađivanje. Dnevnik lagera počinje primjenom ove verzije.

## Zaštita i granice

Pripremljeni upiti, CSRF, HTML escaping, hashirane lozinke, novi ID sesije pri prijavi i istek administratorske neaktivnosti nakon 30 minuta. Aktivna uloga se provjerava na svakom zahtjevu. Login ima limit deset evidentiranih pokušaja po IP-u/e-mailu u 15 minuta, checkout deset po IP-u u 15 minuta. Ne koristi se nepouzdani X-Forwarded-For; korisnici iza istog proxy IP-a dijele limit.

Slike: JPG/PNG/WebP do 5 MB i 6000 px, bez SVG-a. Nazivi su nasumični, a izvršne ekstenzije zabranjene u upload direktoriju. Stare slike se pri zamjeni ne brišu radi mogućih referenci; čišćenje zahtijeva backup i provjeru korištenja. Vanjske HTTPS slike učitavaju se iz browsera kupca.

Obavijesti su u bazi, e-mail obrada je odvojena od checkouta. Limiteri čuvaju hash identifikatora, bez sirovih IP adresa. Audit ne sprema lozinke. Finansijske radnje su evidencija, bez kartičnog procesora, kurirskog API-ja ili fiskalizacije.

Izmjene su namijenjene razvojnoj grani feat/webshop-v0.2-admin-inventory i PR-u prema main. Produkcija nije mijenjana. Detaljna arhitektura iz prethodnog odgovora nije bila dostupna u referenci razgovora; ovdje su opisane konkretne odluke prema dostupnim korisnikovim zahtjevima.


## Nadogradnja 0.3

Aktuelne promjene naslovnice, galerije, poslovne administracije i migracije 003 opisane su u [NADOGRADNJA-0.3.md](NADOGRADNJA-0.3.md). Nova app/catalog.php sadrži zajednički izbor tri objave, app/views odvaja prikaze, a app/reports.php provjerava dozvole i generiše CSV. Stari opisi gore dokumentuju početnu verziju 0.2.
