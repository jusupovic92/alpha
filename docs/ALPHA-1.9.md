# Alpha 1.9 — Besplatno lično preuzimanje

Nadogradnja postojeće Alpha 1.8 / 1.8.1 prodavnice na shop2.sdpkakanj.ba. Uključuje prethodnu ispravku promjene lozinke saradnika.

## Postavljanje

1. Napravite backup baze i fajlova.
2. Prekopirajte sadržaj paketa preko istog projekta, uz postojeći raspored `app/`, `public/`, `bin/` i `database/`.
3. Sačuvajte privatni `config/`, ključeve, `storage/` i `public/uploads/`; paket ih ne sadrži.
4. U phpMyAdmin odaberite **postojeću Alpha bazu** i uvezite **samo `database/migrations/015_personal_pickup.sql`**. Ne uvozite kompletnu instalaciju i ne kreirajte novu bazu. Ranije migracije 013 i 014 trebaju već biti primijenjene.
5. Osvježite preglednik sa Ctrl+F5.

Migracija dodaje vrstu isporuke i čuvanje te vrste u javnim i poslovnim narudžbama. Dodaje jedan aktivan način **Lično preuzimanje**, sa cijenom 0,00 KM. Postojeće cijene, narudžbe, stanja artikala i uslovi ne mijenjaju se. Ponovno pokretanje ne dodaje duplikate niti ponovo uključuje prethodno obrisano lično preuzimanje.

## Administracija → Dostava

U formi **Vrsta isporuke** izaberite:

- **Dostava kurirskom službom:** unos naziva, opcionog kurira, troška, platioca i uslova. Trošak prihvata `10,00`, `10.00` i `0`. Ako plaća prodavac, kupcu je naknada 0 KM kao i ranije.
- **Lično preuzimanje — besplatno:** cijena je uvijek **0,00 KM**. Polja za kurira, cijenu i platioca su sakrivena i ne šalju se. Server takođe prisilno postavlja nulu i briše kurira, čak i ako mu se pošalje druga cijena.

Unesite naziv koji kupac vidi. U polje **Uslovi dostave / lokacija i termin ličnog preuzimanja** upišite adresu prodavnice, radno vrijeme ili dogovor za preuzimanje. Uključite **Dostupno kupcima** i spremite. Automatski dodani način možete urediti umjesto dodavanja novog.

Kod greške se uneseni podaci zadržavaju u formi. Neaktivne opcije ne nude se pri novom naručivanju; obrisane ostaju u historiji ranijih narudžbi.

## Kupci i poslovni saradnici

Pri potvrdi narudžbe kupac ili saradnik bira **Lično preuzimanje — 0,00 KM**. Plaćaju se odabrani artikli po odgovarajućem javnom ili partnerskom cjenovniku; za ovu isporuku se ne dodaje trošak dostave. Javni checkout ažurira ukupni iznos i naziv plaćanja pri izboru. Kontakt podaci narudžbe ostaju kao i ranije. Kupac narudžbu prati preko obavijesti prodavnice, a poslovni saradnik kroz svoj portal.

Narudžba čuva izabranu vrstu, naziv, cijenu i uslove. Kasnija promjena ili brisanje načina isporuke ne mijenja već prihvaćenu narudžbu. Starim narudžbama se ne vraćaju automatski ranije prihvaćeni troškovi dostave.

## Potvrda preuzimanja

**Javna narudžba:** u detalju narudžbe otvorena je **Potvrda ličnog preuzimanja**. Unesite kome je predano i napomenu/dokument, pa potvrdite ličnu predaju. Nema pripreme kurira ili kurirske pošiljke. Ako je lager uključen, predaja razdužuje rezervaciju. Naplata se i dalje evidentira zasebno kao i ranije. Štampani list označen je kao list za lično preuzimanje.

**Poslovna narudžba:** kroz realizaciju po stavkama izaberite **Potvrdi lično preuzimanje navedenih količina**. Moguće je preuzeti dio ili sve artikle. Kurir i broj pošiljke se ne traže. Otkazivanje ostatka i povrati nastavljaju raditi po količinama. Obavijesti i historija čuvaju sve promjene.

Ranija opcija da administrator lično preda narudžbu prvobitno naručenu kurirskom dostavom zadržana je; ona ne mijenja ranije ugovoreni iznos. Za novu narudžbu bez troška dostave kupac od početka bira novu opciju ličnog preuzimanja.

## Provjera

Uredite lokaciju preuzimanja, napravite probnu javnu i poslovnu narudžbu, izaberite lično preuzimanje i provjerite da je dostava 0,00 KM. U administraciji potvrdite predaju i provjerite historiju, lager (ako ga pratite), mail i štampani list. SMTP i cron ostaju kao u prethodnoj verziji.
