# Alpha 1.6 — Kontakt, mapa i podaci firme

Za shop2.sdpkakanj.ba. Nadogradnja postojeće verzije 1.5; ne kreirati novu bazu.

1. Napravite backup baze i fajlova.
2. Raspakujte paket preko istog projekta, uz postojeći raspored `app/` i `public/`.
3. Sačuvajte `config/local.php`, `config/mail.key`, `config/backup.key`, `storage/` i `public/uploads/`. Paket ih ne sadrži.
4. Za prelazak sa 1.5 na 1.6 nema nove SQL migracije. Ne uvozite kompletnu instalaciju.
5. Osvježite preglednik sa Ctrl+F5.

## Administracija → Kontakt i lokacija

Uredite naziv firme, javni e-mail, telefon, adresu, naslov i uvod, radno vrijeme, podršku, ID/PDV/MBS i opis u podnožju. Početne vrijednosti prepisane su sa dostavljenih prikaza stare stranice; provjerite ih i spremite. Prazna opciona polja se ne prikazuju. Telefon se koristi i za WhatsApp/Viber linkove, koje možete zasebno isključiti.

Gornja kontakt traka ima prekidač i kratku poruku. Isti kontakt podaci koriste se u sve tri Alpha teme, na kontakt stranici i u podnožju. Postavke se čuvaju u postojećoj tabeli settings.

## Google Maps

Uključite prikaz mape. Bez dodatnih postavki prikazuje se pretraga po nazivu i adresi firme. Za tačan profil otvorite firmu u Google Maps → Podijeli → Ugradi mapu i kopirajte HTML u polje za embed. Sistem zadržava samo dozvoljeni HTTPS Google Maps src. Opcionalno unesite Google Maps link za dugme za otvaranje lokacije. Nije potreban vlastiti API ključ. Učitavanje mape zahtijeva dostupnu Google Maps uslugu u pregledniku posjetioca.

## Forma i e-mail obavijesti

Uključite prikaz forme i prijem upita. U privatne primaoce unesite adresu za ove obavijesti (ili više adresa, jednu po redu). Ako je prazno, koriste se primaoci iz Mail obavijesti. Primaoci se ne objavljuju kupcima.

U **Mail obavijesti** super administrator podešava stvarni SMTP server, port, TLS/SSL, korisničko ime, lozinku i adresu pošiljaoca. Kontakt obavijesti mogu raditi dok su obavijesti za narudžbe isključene. Bez podešenog slanja forma je vidljiva, ali slanje je onemogućeno uz javni telefon/e-mail. Sama instalacija ne može odrediti SMTP lozinku hostinga.

Upit sadrži ime, e-mail, opcioni telefon i poruku; obavezna je saglasnost. Odgovor na mail ide pošiljaocu upita (Reply-To). Zaštite: CSRF, jednokratni token, skriveno anti-spam polje, ograničenje pokušaja i provjera unosa.

Postojeći cron `php /APSOLUTNA/PUTANJA/DO/PROJEKTA/bin/send-notifications.php` treba ostati aktivan svake minute za ponavljanje slanja pri privremenom prekidu SMTP-a. Evidenciju slanja pregledajte u Mail obavijestima. Nakon podešavanja napravite probni upit kroz javnu formu i provjerite prijem.

Lager, narudžbe, logotipi i naslovni slider ostaju sačuvani. Novi kontakt ekran je prilagođen desktopu i mobilnim ekranima u sve tri Alpha teme.
