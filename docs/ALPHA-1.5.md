# Alpha 1.5 — zvanični logotipi i naslovni slider

## Postavljanje na shop2.sdpkakanj.ba

1. Napravite backup baze kroz phpMyAdmin i kopiju fajlova.
2. Raspakujte ZIP preko postojećeg Alpha projekta, uz isti raspored app/ i public/.
3. Sačuvajte config/local.php, config/mail.key, config/backup.key, storage/ i public/uploads/. Paket ih ne sadrži.
4. U phpMyAdmin odaberite POSTOJEĆU Alpha bazu i uvezite samo database/migrations/012_brands_slider.sql.
5. Osvježite administraciju i prodavnicu sa Ctrl+F5.

Za razliku od prethodnih vizuelnih nadogradnji, ova verzija dodaje tabelu brendova i podatke o vremenu odabira naslovne objave. Zato je potreban uvoz migracije 012. Ne uvozite kompletnu instalaciju niti kreirajte novu bazu. Migracija 012 može se ponoviti: čuva ručno izmijenjene brendove, veze artikala i postavke slidera. Cijene, zalihe, narudžbe i fotografije artikala se ne mijenjaju.

Ako se koriste bin/migrate.php i terminal, isti program primjenjuje i migraciju 012. Nema potrebe koristiti oba postupka.

## Brendovi i zvanični logotipi

Administracija → Brendovi i logotipi.

Uključeni su lokalni logotipi za MAXXT, Mipa, ChemicalWorkz, P&S, EWOCAR i 3D. Izvori su navedeni u BRAND-LOGO-SOURCES.md. Kod bijelih logotipa odabrana je tamna podloga radi čitljivosti. Logotipi se prikazuju proporcionalno, bez promjene boja i rezanja.

Brend može imati naziv, oznaku, logo, zvaničnu web adresu, redoslijed, status i oznaku za naslovnicu. Možete učitati novi PNG/JPG/WebP ili postaviti HTTPS adresu originalnog logotipa. Prikaz na naslovnici zavisi i od toga da brend ima objavljene povezane artikle. Klik na logo otvara katalog tog brenda.

Na uređivanju artikla izaberite Brend / proizvođač. Početna migracija povezuje postojeće artikle prepoznate po nazivu; ostale povežite ručno. Ne pretražuje se više samo riječ u nazivu, nego stvarna veza brenda i artikla.

## Naslovne objave i rotacija

1. Otvorite Artikli i naslovnica i uredite željeni proizvod.
2. Uključite Objavljen u prodavnici i Velika naslovna objava, pa sačuvajte.
3. Ili na listi kliknite U naslovni slider. Time se već objavljeni artikal dodaje među posljednje odabrane objave.
4. Otvorite Teme → Rotirajući prikaz artikala.

Postavke:

- Broj objava u rotaciji: 1–12, početno 3.
- Trajanje pojedine objave: 2–30 sekundi, početno 6.
- Način promjene: pretapanje, klizanje ili bez animacije.
- Trajanje prelaza: 150–1200 ms.
- Automatska izmjena i pauza pri prelasku mišem mogu se uključiti ili isključiti.

Redoslijed se računa po vremenu odabira za slider, a ne po starosti artikla. Obično uređivanje cijene ili opisa već odabranog artikla ne pomjera ga na početak. Za ponovnu objavu koristite U naslovni slider. Ostali označeni artikli ostaju na čekanju. Isključena objava ne prikazuje se kupcima.

Svaka objava prikazuje naziv, opis, cijenu, fotografiju i link na proizvod. Ako je artiklu postavljen vlastiti baner, koristi se kao njegova slika u slideru. Strelice, numerisane tačke, pauza, tastatura i dodir omogućavaju ručno upravljanje. Na uređajima sa smanjenim kretanjem automatska izmjena se ne pokreće sama.

Ako nema označenih proizvoda, ostaje postojeći uvodni baner teme. Ako je označen jedan proizvod, nema nepotrebne rotacije i kontrola.

## Očuvani podaci

Postavka lagera, kontakt forma, mail postavke i sve ranije Alpha funkcije ostaju sačuvane. Novi backup uključuje i brendove. Backup napravljen prije promjene strukture baze ostaje za staru strukturu i ne vraća se preko nove kroz administraciju; koristite odgovarajući izvoz baze i fajlova ako vraćate cijelu raniju verziju.
