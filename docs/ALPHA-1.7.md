# Alpha 1.7 — Poslovni saradnici

Nadogradnja postojeće Alpha 1.6 prodavnice na **shop2.sdpkakanj.ba**. Javna prodavnica i njene narudžbe ostaju dostupne. Poslovne narudžbe imaju zaseban pregled i realizaciju po stavkama.

## Postavljanje

1. Napravite backup baze i fajlova prije nadogradnje. Zadržite kopiju prethodnih fajlova uz prethodnu bazu za eventualno vraćanje.
2. Prekopirajte sadržaj paketa preko istog projekta uz postojeći raspored `app/`, `public/`, `bin/` i `database/`.
3. Sačuvajte privatni `config/local.php`, `config/mail.key`, `config/backup.key`, `storage/` i `public/uploads/`. Paket ih ne sadrži.
4. U phpMyAdmin odaberite **postojeću Alpha bazu** i uvezite **samo `database/migrations/013_business_partners.sql`**. Ne uvozite kompletnu instalaciju i ne kreirajte novu bazu. Migracija je ponovljiva i ne briše postojeće artikle, cijene, narudžbe ili lager.
5. Osvježite preglednik sa Ctrl+F5. Otvorite Administracija → Poslovni saradnici.

Ako imate stariju verziju od 1.6, prvo primijenite pripadajuće ranije nadogradnje. Nova sigurnosna kopija nakon nadogradnje uključuje svih šest poslovnih tabela. Kopije se vraćaju uz odgovarajuću verziju strukture baze.

## Dodavanje saradnika i pristup

Administracija → **Poslovni saradnici** → Novi saradnik. Unesite firmu, kontakt osobu, e-mail, telefon, adresu, grad, opcioni ID/PDV, početnu lozinku i opće uslove kupovine. Uključite „Aktivan”. Nema javne samostalne registracije.

Saradniku dostavite adresu **https://shop2.sdpkakanj.ba/partner.php**, njegov e-mail i početnu lozinku lično. Lozinke se ne šalju automatskim mailom. Lozinka mora imati 12–72 bajta; jednostavna kombinacija slova, brojeva i znakova olakšava unos. Pri prvoj prijavi i nakon resetovanja saradnik mora postaviti vlastitu lozinku. Za zaboravljenu lozinku administrator unosi novu početnu lozinku. Lozinke se čuvaju kao hash.

Saradnik vidi svoj cjenovnik, korpu, narudžbe i historiju. Nema pristup administraciji ni tuđim narudžbama. Neaktivni saradnik ne može se prijaviti. Uređivanje njegovog računa opoziva postojeće prijave. Prijava ističe nakon 30 minuta neaktivnosti.

Uloge administratora: nova dozvola **Poslovni saradnici i cijene** omogućava upravljanje računima/cjenovnicima. Dozvola **Narudžbe** omogućava pregled, pripremu i otkazivanje poslovnih narudžbi; za isporuke i povrate potrebna je i **Slanje, dostava i povrati**. Super administrator ima sve dozvole. Postojeće uloge se automatski ne proširuju.

## Obračun cijena — precizno značenje marže

| Način | Formula | Primjer |
| --- | --- | --- |
| Popust na prodajnu cijenu | prodajna × (1 − postotak/100) | 100 KM − 20% = 80 KM |
| Marža / dodatak na nabavnu cijenu | nabavna × (1 + postotak/100) | 50 KM + 20% = 60 KM |

„Marža” ovdje znači **dodatak na nabavnu cijenu**, a ne udio dobiti u konačnoj prodajnoj cijeni. Popust može biti 0–100%, a dodatak 0–1000%. Obračun se radi u cijelim feningima, sa zaokruživanjem konačne jedinične cijene na dvije decimale.

Saradnik na artiklu vidi osnovicu, postotak, iznos popusta/dodatka i svoju konačnu cijenu. **Kod marže vidi nabavnu osnovicu**, jer je korisnički zahtjev prikaz samog obračuna marže; koristite popust ako saradniku želite prikazivati osnovicu prodajne cijene. Za maržu artikal mora imati pozitivnu nabavnu cijenu. Ako je nema, artikal je vidljiv, ali naručivanje po tom obračunu je onemogućeno dok administrator ne unese cijenu.

Na kartonu saradnika, ispod općih podataka, pretražite artikle po nazivu/šifri i spremite zaseban način i postotak za svaki artikal. Individualna postavka uvijek ima prednost nad općom. „Vrati opće uslove” uklanja individualno pravilo. Ne mijenja se javna cijena artikla.

Ovo nije novi porezni obračun: koriste se iznosi artikala i nabavne cijene već uneseni u shop. Uredite osnovice prema načinu na koji vodite cijene. Poslovna narudžba nije fiskalni račun.

## Naručivanje

Saradnik se prijavi u portal, pregleda katalog/kategorije, pretraži šifru ili naziv i doda artikle u poslovnu korpu. Detalj artikla zadržava njegov lični obračun. Javna prodavnica i dalje ima javne cijene i zasebnu korpu.

U korpi se vide svi pojedinačni obračuni i ukupan iznos. Izmijenite količine i kliknite **Ažuriraj korpu**. Količina 0 uklanja stavku. Najviše je 100 različitih artikala, do 100 komada po stavci. Odaberite način dostave i kliknite **Izračunaj dostavu**. Provjerite adresu i potvrdite narudžbu. Cijene i dostava ponovo se provjeravaju na serveru; promjena od posljednjeg pregleda zahtijeva novi pregled/potvrdu.

Narudžba trajno čuva naziv/šifru artikla, osnovicu, način, stopu i konačnu cijenu, kontakt, adresu i dostavu. Kasnija izmjena cijena ili uslova saradnika ne mijenja ranije narudžbe. Dupli zahtjev sa istom oznakom ne stvara novu narudžbu.

## Realizacija po stavkama

Administracija → **Poslovne narudžbe** → Pregled i realizacija. Nove poslovne narudžbe vidljive su i na početnoj ploči.

- **Priprema:** označava da je narudžba preuzeta u obradu.
- **Isporuka:** unesite samo količine koje sada šaljete. Druge ostavite na 0. Opcionalno unesite kurira i broj pošiljke.
- **Otkazivanje ostatka:** unesite preostale količine koje se neće isporučiti. Već isporučena roba ne može se otkazivati.
- **Povrat:** unesite količine prethodno isporučene robe koja se vraća. Količina ne smije preći isporučeno umanjeno za već evidentirane povrate.

Obrazloženje/dokument promjene je obavezno. Svaka promjena ima vlastitu historiju stavki, datuma, napomene i pošiljke. Za novu isporuku dodajte novu promjenu. Status se računa iz količina: zaprimljena, u pripremi, djelimično realizovana, u potpunosti realizovana, djelimično realizovana uz otkazivanje ostatka, otkazana, djelimičan povrat ili vraćena. Stara otvorena administratorska forma se odbija ako je narudžba u međuvremenu izmijenjena.

**Vrijednost narudžbe** je prvobitna vrijednost svih naručenih artikala s dostavom. **Realizovano za plaćanje** je vrijednost isporučenog umanjena za povrate, uz dostavu naplaćenu jednom nakon prve isporuke. **Preostali artikli** prikazuje još neisporučene i neotkazane stavke bez dodatne dostave. Povrat artikala automatski ne poništava već nastali trošak dostave. Pregled nije evidencija uplata, fiskalni račun ili povrat novca; finansijske dokumente vodite u svom računovodstvu.

## Opcionalni lager

Narudžba pamti da li je lager bio uključen pri njenom kreiranju. Ako jeste, cijela naručena količina se rezerviše; isporuka razdužuje rezervaciju, otkazivanje vraća neisporučenu robu u slobodno stanje, a povrat vraća isporučenu robu. Ako je lager bio isključen, poslovna narudžba i kasnije promjene ne mijenjaju zalihe. Naknadno uključivanje/isključivanje lagera ne mijenja način evidencije ranijih narudžbi. Javni i poslovni checkout zaključavaju iste artikle i sprečavaju istovremenu prodaju iste slobodne količine.

## Mail obavijesti

U Poslovni saradnici → **Mail obavijesti poslovnih saradnika** uključite slanje i unesite administratorske primaoce. Prazno koristi primaoce iz Mail obavijesti. Saradnik prima poruke na svoj e-mail iz računa. Poruke se šalju pri kreiranju i svakoj promjeni, s pregledom stavki, količina, cijena, statusa i isporuke. Tuđe narudžbe nisu dostupne preko linkova; potrebna je prijava odgovarajućeg saradnika/admina.

Stvarni SMTP, pošiljalac, lozinka i HTTPS adresa shopa podešavaju se u **Mail obavijesti**. B2B prekidač je odvojen od javnih narudžbi i kontakt forme. Bez konfigurisanog transporta poslovne poruke čekaju u redu. Cron mora raditi svake minute:

`php /APSOLUTNA/PUTANJA/DO/PROJEKTA/bin/send-notifications.php`

Provjerite red u Mail obavijestima i izvršite probnu narudžbu. Status Poslano znači da je SMTP server prihvatio poruku; dostavu u inbox provjerite kod primaoca. Poslije pet neuspjelih pokušaja ispravite SMTP i pokrenite Ponovi kroz red. Isključivanje B2B obavijesti pauzira i ranije poruke koje još čekaju slanje.

## Provjera nakon postavljanja

Dodajte probnog saradnika s popustom 10%, a jednom artiklu postavite individualni popust 20%. Prijavite se kao saradnik, promijenite početnu lozinku i provjerite obje cijene. Naručite više komada; kao administrator isporučite dio, zatim ostatak isporučite ili otkažite. Provjerite status, historiju, iznose i obje mail obavijesti. Testni račun nakon provjere isključite.
