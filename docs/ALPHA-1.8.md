# Alpha 1.8 — Prijava korisničkim imenom

Nadogradnja postojeće verzije 1.7 na shop2.sdpkakanj.ba. Administrator/zaposlenici i poslovni saradnici sada se prijavljuju **korisničkim imenom i lozinkom**. E-mail ostaje kontakt podatak i adresa za obavijesti. E-mail se više ne koristi za prijavu.

## Postavljanje

1. Napravite backup baze i fajlova.
2. Prekopirajte paket preko istog Alpha projekta uz isti raspored direktorija. Paket ne sadrži privatni `config/`, ključeve, `storage/` ni `public/uploads/`; sačuvajte ih.
3. U phpMyAdmin odaberite **postojeću Alpha bazu** i uvezite **samo `database/migrations/014_usernames.sql`**. Ne uvozite kompletnu instalaciju i ne kreirajte novu bazu.
4. Osvježite preglednik sa Ctrl+F5 i prijavite se novim korisničkim imenom, uz dosadašnju lozinku.

Ovaj paket pretpostavlja da je verzija 1.7 i migracija 013 već postavljena. Ne pokrećite ponovo instalaciju korisnika ili baze. Migracija 014 je ponovljiva i ne mijenja lozinke, e-mail adrese, cjenovnike, narudžbe ili lager.

## Početna imena postojećih računa

| Vrsta računa | Automatski dodijeljeno ime | Primjer za ID 1 |
| --- | --- | --- |
| Administrator ili zaposlenik | `admin` + postojeći ID računa | `admin1` |
| Poslovni saradnik | `saradnik` + postojeći ID računa | `saradnik1` |

Ako prvi administrator ima ID 1, prijavite se kao **admin1**, uz postojeću lozinku. Za druge ID-eve koristite odgovarajući broj. Dodijeljena imena možete pregledati u phpMyAdmin:

```sql
SELECT id, name, username, email FROM users ORDER BY id;
SELECT id, company, username, email FROM business_partners ORDER BY id;
```

Nakon prijave otvorite **Administracija → Korisnici**, kliknite račun i upišite željeno korisničko ime, npr. `tarik` ili `admin.alpha`. Prazna lozinka u uređivanju zadržava postojeću. Za saradnike otvorite **Poslovni saradnici → Podaci i cijene** i unesite npr. `test` ili `radionica1`. Promjena korisničkog imena saradnika zatvara njegove postojeće prijave; ponovo se prijavljuje novim imenom i istom lozinkom. Prva obavezna promjena lozinke nakon kreiranja/resetovanja ostaje na snazi.

## Pravila korisničkog imena

- 3–64 znaka: engleska slova a–z, brojevi, tačka, crtica i donja crta.
- Prvi znak mora biti slovo ili broj. Nema razmaka ili znaka `@`.
- Velika i mala slova tretiraju se jednako; ime se sprema malim slovima.
- Ime mora biti jedinstveno unutar administracije, odnosno među saradnicima. Ta dva prostora su odvojena.

Kontakt e-mail ostaje obavezan za korisnike/saradnike i koristi se za obavijesti. Promjena e-maila ne mijenja korisničko ime. Postojeće jedinstvenosti e-mail adresa ostaju.

## Prijave i novi računi

- Administracija: `https://shop2.sdpkakanj.ba/admin.php`.
- Saradnici: `https://shop2.sdpkakanj.ba/partner.php`.
- Prijavne forme imaju tekstualno polje Korisničko ime, bez browser provjere e-mail formata.
- Novi korisnici i saradnici dobijaju korisničko ime koje administrator unese; ne generiše se iz e-mail adrese.
- Za potpuno novu instalaciju zaštićeni `setup-admin.php` i komandni `bin/create-admin.php` takođe traže korisničko ime odvojeno od kontaktnog e-maila. Nema promjene sigurnosnog ključa ili postupka kreiranja prvog administratora.

Narudžbe, mail obavijesti, popusti/marže i realizacija po stavkama rade kao u 1.7. Nije potrebno mijenjati SMTP postavke.
