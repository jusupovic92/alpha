# ALPHA refinish 1.0.0

## Nova testna instalacija

1. Kreirati novu praznu bazu za Alpha projekat. Ne koristiti bazu postojećeg webshopa.
2. U phpMyAdmin uvesti database/install-alpha-demo.sql. To je kompletan instalacijski fajl, ne uvoziti dodatno install.sql ili pojedinačne migracije.
3. Kopirati config/local.example.php u config/local.php i unijeti pristup novoj bazi. Konfiguraciju i lozinke ne slati na GitHub.
4. Postaviti document root na public/. Zadržati app/, config/, database/ i storage/ izvan javnog direktorija. Omogućiti PDO MySQL, mbstring, ZIP i OpenSSL.
5. Kreirati prvog administratora prema docs/BEZ-TERMINALA.md ili php bin/create-admin.php. Nema podrazumijevane lozinke.
6. Otvoriti admin.php → Teme i izabrati Pro katalog, Premium Garage ili Alpha Hybrid. Početno je aktivan Alpha Hybrid.

## Nadogradnja postojeće Alpha baze 0.9.2

Napraviti backup, postaviti izmijenjene fajlove i uvesti samo database/migrations/010_alpha_themes.sql. Alternativa je php bin/migrate.php. Postojeća odabrana tema, artikli, narudžbe i zalihe se čuvaju. Za testne artikle otvoriti Teme → Dodaj testne artikle. Potrebne su dozvole settings, products i inventory. Alternativa je ručni uvoz database/demo-alpha.sql. Demo skripta se može ponoviti: ne vraća postojeće cijene i zalihe na probne vrijednosti.

## Tri izgleda

- Pro katalog: kompaktniji uvod, horizontalne kategorije, pet artikala u redu na većem desktopu.
- Premium Garage: tamno zaglavlje i naslovnica, zelena i narandžasta, bijele kartice artikala.
- Alpha Hybrid: zelena navigacija, podijeljen uvod i vizuelne kategorije, četiri artikla u redu.

Na telefonu se pretraga prikazuje u zasebnom redu, a artikli u dvije kolone. Tema važi za naslovnicu, katalog, detalje, korpu i naručivanje. Izbor se čuva u bazi i nije vezan za preglednik. Logo i tamna verzija logotipa i dalje se uređuju kroz Logo i brend. Alpha Premium koristi tamnu verziju logotipa ako je učitana. Naslovnica koristi prvi artikal označen za naslovnicu; njegov opcionalni baner zamjenjuje ilustraciju. Ostale postojeće teme zadržavaju svoj rotirajući prikaz.

## Testni artikli i fotografije

Ukupno 12 artikala: šest MAXXT primjera i šest generičkih primjera boja/lakova, pripreme, abraziva, poliranja i detailinga. Šifre ALPHA-DEMO-001 do ALPHA-DEMO-012. Zalihe su probne, jedan artikal je namjerno rasprodan. Svaki unos ima evidentirano početno kretanje robe. Ponavljanje unosa ne duplira artikle ni kretanja i ne mijenja ručno uređene podatke.

Slike su uključene lokalne SVG ilustracije i nisu fotografije stvarnih proizvoda. Zamijeniti ih fotografijama kroz administraciju artikala. Postojeći upload i galerija nastavljaju raditi. U testnim opisima i na katalogu je navedeno da su cijene/zalihe probne. Nakon zamjene testnih podataka provjeriti cijene i zalihe, a u Izgled i postavke isključiti Oznaku testne ponude. Ne koristiti tvrdnje o dostavi ili plaćanju prije podešavanja stvarnih uslova.

Referentni izvori: https://www.alpha-refinish.com/ i https://www.alpha-refinish.com/wp-content/uploads/2025/03/alpha-refinish-ponuda-25.pdf .

## Izmjene i provjere

Dodana tri rasporeda, pregledi tema u administraciji, zaštićen ponovljiv unos testnih artikala, pretraga po šifri i nazivu te nova SQL instalacija. Sačuvane stare teme i postojeći moduli poslovanja. GitHub workflow provjerava PHP sintaksu, postojeće integracijske/HTTP testove i Alpha demo regresije.
