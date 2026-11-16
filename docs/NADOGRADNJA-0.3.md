# WEBSHOP 0.3 — naslovnica i poslovna administracija

## Nadogradnja postojeće instalacije bez Terminala

Za vašu instalaciju glavni direktorij je `shop.sdpkakanj.ba`, a javni document root `shop.sdpkakanj.ba/public`. Korisnici, podaci baze i fotografije ostaju na hostingu. Ova isporuka ne mijenja produkciju automatski.

1. U cPanel File Manageru sačuvajte kopiju postojećih fajlova. U phpMyAdmin izvezite cijelu trenutnu bazu. Prvo provjerite postupak na staging kopiji.
2. Za kratko vrijeme nadogradnje zaustavite kupovinu na hostingu kroz opciju održavanja koju vaš hosting podržava. Ne koristite staru verziju checkouta paralelno s novom.
3. U phpMyAdmin odaberite postojeću WEBSHOP bazu. Uvezite **samo** `database/migrations/003_storefront.sql`, jednom. Ova migracija je za postojeću verziju 0.2 s migracijom 002. Ne uvozite ponovo install.sql, schema.sql ili migraciju 002 preko postojeće baze.
4. ZIP nadogradnje raspakujte u direktorij `shop.sdpkakanj.ba`, tako da se ažuriraju poddirektoriji app, public i bin. ZIP ne sadrži config/local.php, setup.key, korisničke fotografije ili demo bazu. Postojeći config i uploads ne brišite. Potvrdite zamjenu samo fajlova iz paketa. Prenesite i sve nove fajlove iz app/views i public/assets.
5. Osvježite stranicu uz Ctrl+F5, prijavite se postojećim računom i provjerite probnu narudžbu. Ne kreirajte administratora ponovo.
6. Uklonite ZIP s hostinga nakon raspakivanja. Sačuvajte backup izvan javnog direktorija.

Ako migracija bude prekinuta, ne pokrećite je ponovo bez provjere. MySQL DDL nije transakcijski povratan; djelimičnu nadogradnju prvo vratite iz staging backupa. Rollback traži usklađenu prethodnu bazu i prethodne fajlove. Narudžbe nastale nakon prebacivanja sačuvajte prije vraćanja.

Za potpuno novu praznu bazu postoji puni paket s database/install.sql koji uključuje 002 i 003. Postojeću prodavnicu nadograđujte prethodnim koracima.

## Tri velike naslovne objave

U administraciji otvorite **Artikli i naslovnica → Novi artikal** ili **Uredi**. Postoje tri nezavisne oznake:

| Oznaka | Ponašanje |
| --- | --- |
| Objavljen u prodavnici | Artikal je dostupan kupcima; isključivanje skriva i njegovu detaljnu stranicu. |
| Lista artikala na naslovnici | Artikal se prikazuje u običnoj listi na početnoj stranici. |
| Velika naslovna objava | Kandidat za veliki rotirajući prikaz. |

Veliki prikaz uzima tri najnovija aktivna označena artikla, prema datumu objave i ID-u kada je datum isti. Uređivanje opisa ne mijenja redoslijed. Ako označite četvrti noviji artikal, najstariji izlazi iz rotacije, ali njegova oznaka ostaje kao kandidat. Ako sakrijete ili odznačite jedan od prikazanih artikala, sljedeći kandidat automatski ulazi.

Administracija uvijek prikazuje trenutne tri pozicije i nudi filter **Trenutne tri velike objave**. Postoji i filter svih kandidata, uključujući one na čekanju. Artikli se automatski izmjenjuju svakih šest sekundi. Kupac može pauzirati ili ručno promijeniti prikaz; rotacija staje tokom fokusa, prelaska mišem i kada je kartica browsera skrivena. Uz reduced-motion postavku automatsko kretanje je početno isključeno. Bez JavaScripta ostaje prvi artikal. Jedan/dva kandidata se prikazuju bez dupliranja; bez kandidata prikazuje se postojeći uvodni tekst prodavnice.

## Kategorije, detalj artikla i galerija

U **Kategorije** odaberite Prikaži na naslovnici i unesite redoslijed (manji broj prvi). Kartica kategorije koristi sliku njenog najnovijeg objavljenog artikla; bez slike koristi neutralni placeholder. Skrivanje kategorije s početne stranice ne skriva njene artikle iz kataloga.

Kartice artikala otvaraju detaljni prikaz sa slikama, nazivom, SKU-om, cijenom, dostupnošću, količinom, dodavanjem u korpu, načinima dostave, punim opisom i srodnim artiklima. Glavna slika i do osam dodatnih slika mogu se postaviti u administraciji. Možete unijeti HTTPS adrese ili postaviti JPG/PNG/WebP fajlove do 5 MB po slici. Ako glavna slika nedostaje, prva slika galerije postaje glavna. Ukupan zahtjev za više slika mora stati u hostingov post_max_size; za istovremeni unos više većih fotografija povećajte ga u MultiPHP INI Editoru ili postavljajte slike u manjim grupama. Stare fotografije se ne brišu automatski.

Detaljan opis čuva tekst i prijelome redova. HTML se prikazuje kao tekst radi sigurnosti. Varijante s različitim lagerom i cijenom u ovoj verziji vodite kao zasebne artikle/SKU-ove. Fajlovi primjera, recenzije i tuđe tvrdnje s referentne stranice nisu uvezeni u vašu prodavnicu.

## Poslovna administracija

Novi raspored odvaja narudžbe, skladište, katalog, izvještaje i postavke. Pregled poslovanja prikazuje nove/pripremljene/poslane narudžbe, slobodan i rezervisan lager, niske zalihe, vrijednost robe po trenutnoj nabavnoj cijeni i otvorene iznose za naplatu. Podaci se prikazuju prema dozvolama korisnika.

Za artikal se mogu unijeti trenutna nabavna cijena i vlastiti prag upozorenja. Njih može mijenjati korisnik koji ima dozvole Proizvodi + Lager. Nabavna cijena se ne prikazuje kupcima. Nedostajuće cijene ostaju nula i jasno se označavaju kao nepotpuna procjena. Migracija ne pokušava pogoditi stare nabavne cijene.

Svaki novi zapis prometa čuva slobodno i rezervisano stanje nakon radnje i nabavnu cijenu u tom trenutku. Za stare zapise ta polja ostaju prazna. Izlaz pri slanju može imati promjenu slobodnog stanja nula jer je slobodan lager već umanjen rezervacijom; promjena fizičkog stanja vidi se kroz zatvaranje rezervacije.

**Izvještaji i izvoz** nude lagerlistu, karticu prometa po artiklu/datumu i evidenciju naplata/povrata. CSV koristi UTF-8 i separator tačka-zarez i štiti tekstualna polja od spreadsheet formula. Lista prometa/naplata prikazuje do 200 posljednjih rezultata, a CSV sve rezultate odabranog perioda. Lagerlista je trenutni presjek, bez obzira na izabrane datume. Finansijski period koristi datum evidentirane radnje iz dnevnika. Korisnik Lager vidi skladišne izvještaje; korisnik s dozvolama Narudžbe + Naplata vidi finansijske izvještaje.

Ovo je operativna prodajna i skladišna evidencija. Nabavna vrijednost nije FIFO ili ponderisana prosječna cijena; nisu dodani glavna knjiga, dobavljačke fakture, djelimične uplate, obračun poreza, fiskalizacija ili automatsko usklađivanje banke/kurira. Za te funkcije trebaju zasebni poslovni zahtjevi i pouzdani početni podaci.

## Provjere

Regresijski testovi pokrivaju migracije, čuvanje starih podataka, rezervacije, paralelnu kupovinu posljednjeg komada, naplatu i povrate. Novi testovi provjeravaju tri najnovija označena artikla, identičan administrativni filter, 0–3 kandidata, skriveni/nepostojeći detalj, galeriju, kategorije, privatnost nabavne cijene, CSV zaštitu i dozvole izvoza. Lokalna browser provjera obuhvata automatsku/ručnu rotaciju, pauzu, galeriju, količinu, desktop i mobilni raspored. Pregledi koriste zasebne demonstracijske podatke, ne vašu produkcijsku bazu.
