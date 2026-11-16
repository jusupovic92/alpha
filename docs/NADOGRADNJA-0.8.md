# WEBSHOP 0.8 — podaci kupca u mailu, brojevi artikala i čitljiviji tekst

## Nadogradnja bez Terminala

Za postojeću verziju 0.7 s primijenjenom migracijom 007:

1. Napravite backup fajlova u cPanel File Manageru i izvoz baze u phpMyAdminu. Sačuvajte config/local.php i **config/mail.key**. Postupak prvo provjerite na staging kopiji. Nakratko zaustavite kupovinu dok se nadogradnja završi.
2. U postojećoj WEBSHOP bazi uvezite **samo database/migrations/008_readability_numbering.sql**, jednom. Ne uvozite install.sql ili prethodne migracije ponovo. Za starije verzije prvo dovršite nedostajuće ranije nadogradnje redom.
3. ZIP nadogradnje raspakujte u **shop.sdpkakanj.ba**, iznad direktorija public. Zamijenite fajlove iz paketa. Document root ostaje **shop.sdpkakanj.ba/public**.
4. Ne brišite config, korisničke fotografije ili SMTP ključ. Ne kreirajte administratora ponovo. Osvježite uz Ctrl+F5, provjerite novi artikal, postavke teksta i probnu narudžbu. Uklonite ZIP s hostinga i ponovo omogućite kupovinu.

Migracija dodaje brojač i dvije postavke teksta. Ne mijenja stare šifre, ID-ove, cijene, zalihe ili narudžbe. MySQL DDL nije transakcijski povratan; kod prekida provjerite djelimičnu migraciju prije ponavljanja. Povrat zahtijeva usklađen prethodni backup baze i fajlova, uz čuvanje novih narudžbi.

## Sadržaj mail obavijesti

Nove obavijesti sadrže broj i datum narudžbe, ime i prezime kupca, e-mail, telefon, adresu, grad i napomenu. Navode se svi naručeni artikli s količinom, jediničnom cijenom i iznosom, zbir proizvoda, odabrana dostava i njena cijena, uslovi dostave i platilac, način plaćanja i ukupni iznos, te link na detalj narudžbe.

Podaci dolaze iz sačuvane narudžbe. Prazna napomena je jasno označena. Tehnička polja poput tokena kupovine ne šalju se. Već poslani mailovi i ranije pripremljene poruke u redu se ne prepisuju niti se automatski ponovo šalju.

Postojeće mail postavke, primaoci, SMTP lozinka i cPanel Cron ostaju isti. Nije potrebno ponovo unositi mail podatke. Prošireni sadržaj koristi novi mail sistem podešen kroz administraciju; ako još koristite isključivo stari config notify_email worker, prvo sačuvajte novu Mail obavijesti konfiguraciju prema uputama za verziju 0.7.

## Automatski brojevi novih artikala

U obrascu **Novi artikal** broj više ne unosite ručno. Dobija se tek nakon uspješnog spremanja: **1, 2, 3…**. To je vidljiva šifra/SKU artikla, odvojena od internog ID-a baze koji se koristi za veze i narudžbe.

Postojeći artikli zadržavaju ranije oznake. Ako je broj već zauzet među starim šiframa, preskače se. Primjer: ako već postoji šifra 1, prvi novi slobodni broj je 2. Stari nazivi i nestandardne šifre se ne prepravljaju, jer su povezani s lagerom i prethodnim evidencijama.

Brojač je zaštićen transakcijom i zaključavanjem: dva istovremena spremanja dobijaju različite brojeve. Neuspjelo spremanje ne troši broj. Isključivanje artikla ne vraća njegov broj u upotrebu. Uređivanje postojećeg artikla čuva njegovu šifru; ne kreira novi broj. Raniji programski unosi s izričito zadanom šifrom ostaju podržani radi kompatibilnosti, dok novi administrativni obrazac koristi automatski unos.

## Veći tekst i podešavanje

Tekst prodavnice i administracije je povećan, a prethodno najsitniji natpisi imaju čitljiviju osnovu. U **Izgled i postavke** postoje dva nezavisna izbora:

- **Veličina teksta u prodavnici** — početno 115%.
- **Veličina teksta u administraciji** — početno 120%.

Mogu se odabrati 100%, 110%, 115%, 120%, 130% ili 140%. Spremite postavke i osvježite prikaz. Promjena važi za sve korisnike odgovarajućeg dijela aplikacije. I na 100% ostaje čitljivija osnova sitnih oznaka; to nije vraćanje svih ranijih minijaturnih slova.

Povećavaju se tekst, oznake i kontrole, a fotografije i ukupna širina aplikacije se ne povećavaju kao kod zooma preglednika. Naslovi i tabele mogu zauzeti više redova. Široke tabele zadržavaju vlastito horizontalno pomicanje. Sadržaj tekstualnog maila prikazuje se veličinom koju odabere mail aplikacija primaoca. Štampa zadržava osnovni format za papir.

## Provjere

Prošli su testovi svih podataka kupca u mailu, iznosa/dostave, početka i preskakanja brojeva, povrata broja nakon neuspjelog spremanja, paralelnog kreiranja artikala i čuvanja postojećih evidencija. Browser provjera pokriva stvarni unos artikla bez ručne šifre, sačuvane postavke i izračunatu veličinu teksta, te glavne stranice prodavnice i administracije na računaru i mobitelu pri 140%. Regresijski testovi pokrivaju kupovinu, lager, naplatu, mail red, SMTP/TLS i postavljanje sistema.
