# Alpha 1.3 — kompletan Premium Garage

## Postavljanje na shop2.sdpkakanj.ba

1. Napravite backup fajlova postojećeg projekta.
2. Raspakujte paket preko istog Alpha projekta uz isti raspored foldera app/ i public/. Ne premještajte app/ unutar public/.
3. Sačuvajte config/local.php, storage/ i vlastite slike u public/uploads/. Paket ih ne sadrži.
4. U administraciji odaberite Teme → Teme naslovnice i prodavnice → Premium Garage.
5. Osvježite stranicu sa Ctrl+F5.

Nadogradnja postojeće fotografske Alpha 1.1 ili 1.2 instalacije ne zahtijeva uvoz SQL-a. Ne pokrećite novu instalaciju niti uvozite novu bazu preko postojeće.

## Uključeno

- Sve vizuelne izmjene Premium Garage 1.2: ikonice, efekti, kartice artikala i mobilni raspored.
- Nova generisana ambijentalna fotografija studija bez teksta, ugrađena lokalno kao optimizovani WebP. Koristi se za glavni baner i diskretnu pozadinu. Fotografije artikala ostaju stvarne fotografije iz postojeće ponude.
- Sekcija prednosti, brendovi iz aktivnih artikala, tri potpuna savjeta, kontaktni baner i podnožje u četiri kolone.
- Brendovi su prikazani čitljivim nazivima; nema izmišljenih logotipa. Klik otvara pretragu njihovih artikala. Nema neprovjerenih statistika, radnog vremena ili praznih društvenih linkova.
- Stranice Dostava i plaćanje, Kontakt, Reklamacije i povrat, Uslovi kupovine i Privatnost. Dostava koristi aktivne metode i cijene iz baze. Privatnost preuzima postojeći tekst iz postavki.
- Savjeti imaju vlastite stranice i pune tekstove. Sadržaj je u app/alpha-content.php. Ne dodaje se novi administrativni CMS modul.
- Kontakt preuzima contact_email i sender_phone iz postojećih postavki. Unesite stvarne podatke u administraciji da bi e-mail i telefonski linkovi bili dostupni.

Informativne stranice sadrže osnovne informacije o toku kupovine. Prodavac treba dopuniti vlastite poslovne podatke, politiku privatnosti i konkretne uslove trgovine prema svojim procesima prije početka stvarne prodaje.

Ostale teme ostaju dostupne. Novi donji dio i studijska pozadina koriste se u Premium Garage temi. Ponuda, cijene, zalihe i narudžbe dolaze iz postojeće baze.
