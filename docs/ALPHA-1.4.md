# Alpha 1.4 — tri potpuna predloška, opcionalni lager i kontakt upiti

## Postavljanje

Napravite rezervnu kopiju fajlova. Raspakujte ZIP preko postojećeg Alpha projekta uz isti raspored app/ i public/. Sačuvajte config/local.php, config/mail.key, storage/ i public/uploads/. Paket ne sadrži privatnu konfiguraciju, ključeve niti SQL instalaciju. Nije potreban uvoz baze. Osvježite preglednik sa Ctrl+F5.

## Rad bez lagera za ovaj shop

Administracija → Izgled i postavke → Način rada prodavnice → Praćenje zaliha → Isključeno → Spremi način rada.

Nakon isključivanja nema unosa količina niti oznaka stanja u prodavnici. Svi objavljeni proizvodi mogu se naručiti, uključujući one sa starim stanjem 0. Količina pojedinačne stavke u narudžbi i dalje se unosi (do 100 komada). Narudžbe se čuvaju, ali ne rezervišu niti umanjuju lager. Lager moduli, upozorenja i kolone se skrivaju.

Postojeće količine se ne brišu. Ranije narudžbe zadržavaju način obrade u trenutku naručivanja: ranija rezervacija se uredno oslobađa, dok povrat narudžbe nastale bez lagera ne povećava zalihe ni nakon ponovnog uključivanja. Prije uključivanja provjerite i uskladite sačuvane količine kroz lager. Početna postavka ostaje uključena da nadogradnja ne promijeni postojeće poslovanje bez odabira administratora.

## Kontakt forma i obavijesti

1. Administracija → Mail obavijesti.
2. Uključite „Slanje kontakt upita na sačuvane primaoce“.
3. U polje Primaoci unesite adresu za prijem upita (ili jednu adresu po redu).
4. Sačuvajte SMTP server, port, zaštitu, korisničko ime, lozinku, e-mail pošiljaoca i HTTPS adresu shopa: https://shop2.sdpkakanj.ba.
5. Pošaljite probnu poruku, zatim otvorite Kontakt na javnoj stranici.

Kontakt forma sadrži ime, e-mail, opcionalni telefon i poruku. Nakon slanja upit ulazi u postojeći mail red. E-mail sadrži sve unesene podatke, a Odgovori/Reply-To vodi na adresu pošiljaoca upita. Kontakt obavijesti se mogu koristiti nezavisno od obavijesti o narudžbama.

Poruka kupcu potvrđuje da je upit zaprimljen; ne tvrdi da je već isporučen u sanduče. Status slanja i ponovne pokušaje vidite u Mail obavijesti. Ako SMTP trenutno nije dostupan, upit ostaje u redu. Kao i ranije, u cPanel Cron postavite obradu bin/send-notifications.php jednom u minuti. Bez ispravnih mail postavki nema stvarne isporuke.

Primjer cron naredbe (prilagodite stvarnom korisniku i putanji):

`php /home/CPANEL_KORISNIK/PUTANJA_PROJEKTA/bin/send-notifications.php`

Javni kontakt e-mail i telefon prodavnice uređuju se u Izgled i postavke. Primaoci obavijesti uređuju se odvojeno u Mail obavijesti i ne objavljuju se kupcima.

## Predlošci

Pro katalog i Alpha Hybrid imaju ujednačene ikonice, efekte, brendove, savjete, kontaktni baner i puno podnožje, u svijetlim bojama. Premium Garage zadržava tamni izgled. Administrator bira temu u Teme → Teme naslovnice i prodavnice. Svi Alpha predlošci imaju Kontakt formu i podržavaju oba načina rada lagera.

Ranije fotografije, ponuda, cijene, narudžbe i korisnici ostaju iz postojeće instalacije. Paket uključuje i prethodne Alpha izmjene.
