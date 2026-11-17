# Alpha 2.0 — Cijene i dodatni popusti u poslovnoj narudžbi

Nadogradnja postojeće Alpha 1.9 prodavnice na shop2.sdpkakanj.ba. Administrator može izmijeniti obračun artikla u već kreiranoj poslovnoj narudžbi, bez promjene javnog cjenovnika ili općih/individualnih uslova saradnika.

## Postavljanje

1. Napravite backup baze i fajlova.
2. Prekopirajte paket preko istog Alpha projekta, uz isti raspored direktorija. Sačuvajte privatni `config/`, ključeve, `storage/` i `public/uploads/`; paket ih ne sadrži.
3. U phpMyAdmin odaberite **postojeću Alpha bazu** i uvezite **samo `database/migrations/016_order_prices.sql`**. Ne uvozite kompletnu instalaciju i ne kreirajte novu bazu. Ranije migracije do 015 trebaju već biti primijenjene.
4. Osvježite preglednik sa Ctrl+F5.

Migracija dodaje originalne cijene, dodatni popust i historiju izmjena na postojeće poslovne narudžbe. Ne mijenja prihvaćene iznose ili lager. Ponovljivo pokretanje čuva već uređene cijene i originalne snimke. Backup obuhvata nove kolone u postojećim tabelama; staru kopiju vraćajte uz odgovarajuću prethodnu verziju baze/aplikacije.

## Gdje se uređuje

Administracija → **Poslovne narudžbe** → **Pregled i realizacija** → **Cijene, marža i dodatni popust**.

Super administrator ima pristup. Za drugu administrativnu ulogu u **Uloge i dozvole** uključite i **Pregled i priprema narudžbi** i novu dozvolu **Izmjena cijena poslovne narudžbe**. Sama dozvola za narudžbe, isporuke ili uređivanje cjenovnika saradnika ne omogućava ovu izmjenu. Saradnik ne može uređivati vlastite cijene.

## Mogućnosti po stavci

| Izbor | Obračun |
| --- | --- |
| Popust od osnovice | osnovica × (1 − popust/100) |
| Marža / dodatak na osnovicu | osnovica × (1 + marža/100) |
| Ručna jedinična cijena | administrator direktno unosi ugovorenu cijenu prije dodatnog popusta |
| Vrati uslove pri kreiranju | vraća prvobitni obračun te stavke i uklanja dodatni popust |

Za popust je osnovica prodajna cijena, a za maržu odgovarajuća nabavna osnovica. Osnovicu možete izmijeniti za ovu narudžbu; time ne mijenjate cijenu artikla u katalogu ili stvarnu nabavnu cijenu u lageru. Pri promjeni načina obračuna provjerite da je unesena prava osnovica. „Marža” i dalje znači dodatak na osnovicu, ne bruto maržu kao udio konačne cijene.

Polje **Dodatni popust (%)** umanjuje cijenu poslije navedenog obračuna. To je naknadni popust na već dobijenu cijenu, a ne sabiranje stopa.

- Nabavna osnovica 50 KM, marža 20% → 60 KM. Dodatni popust 10% → **54 KM**.
- Prodajna osnovica 19 KM, popust 10% → 17,10 KM. Dodatni popust 5% → **16,25 KM**.

Svaka faza obračuna jedinične cijene zaokružuje se na fening. Dozvoljen je dodatni popust 0–100%. Ručna cijena može biti i 0 KM. Cijene i postoci prihvataju decimalni zarez ili tačku. Dostava se ovom radnjom ne mijenja; lično preuzimanje ostaje 0,00 KM.

Promijenite željene stavke, provjerite prikaz **Nova cijena / Za količinu**, unesite obavezno obrazloženje i kliknite **Sačuvaj cijene i obavijesti saradnika**. Konačni iznos ponovo računa server; cijena poslana iz preglednika ne koristi se kao konačan obračun. Ako nema izmjena, ne kreira se nova historija ili mail.

## Djelimično realizovane narudžbe

Cijena se mijenja **samo za preostale, još neisporučene i neotkazane količine**. Ako je dio iste stavke već isporučen ili otkazan, aplikacija automatski izdvaja preostalu količinu u novu stavku:

Primjer: od 5 komada po 60 KM isporučena su 2. Za preostala 3 dodaje se popust 10%. Pregled sada prikazuje 2 isporučena komada po 60 KM i 3 preostala po 54 KM. Nova vrijednost artikala je **282 KM**, a već realizovani dio ostaje **120 KM**. Količine i lager se ne mijenjaju.

Povrat već isporučene robe koristi njenu prvobitnu cijenu. Sljedeća isporuka preostalih stavki koristi novu ugovorenu cijenu. Potpuno isporučene ili otkazane stavke ne mogu se retroaktivno prepisivati kroz ovu formu.

## Historija, iznosi i obavijesti

Svaka promjena čuva datum, administratora, obrazloženje, količinu, prethodni i novi obračun. U detalju narudžbe administrator i saradnik vide **Izmjena cijena** i stare/nove jedinične cijene, uz dodatni popust. Ako se ukupan iznos razlikuje, prikazuje se i vrijednost pri kreiranju.

**Vrijednost narudžbe** prikazuje trenutno ugovorene cijene svih stavki s dostavom. **Realizovano za plaćanje** čuva cijene stvarnih isporuka umanjene za njihove povrate. **Preostali artikli** koristi izmijenjene cijene otvorenih količina. Otkazane količine ostaju u historijskoj vrijednosti narudžbe kao i ranije, ali nisu dio realizovanog iznosa.

Kad su B2B obavijesti uključene, izmjena priprema novu poruku saradniku i administratoru sa ažuriranim stavkama i starom/novom cijenom. Koriste se postojeći SMTP i cron. Ranije pripremljene ili poslane potvrde ostaju snimci stanja u vrijeme svog kreiranja; poruka o izmjeni predstavlja novu verziju dogovora.

Promjene su transakcijske. Ako jedna stavka nije ispravna, ništa se ne sprema. Stara otvorena administratorska forma se odbija ako je drugi operater već promijenio cijenu ili realizovao stavku. Ponovno slanje iste promjene ne obračunava popust drugi put.

Ovo je pregled dogovorenih cijena narudžbe; ne izdaje novi fiskalni račun, knjiženje uplate ili povrat novca. Poslovne finansijske dokumente vodite kao i do sada.
