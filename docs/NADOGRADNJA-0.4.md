# WEBSHOP 0.4 — galerija, otprema, naplata i drugi izlazi robe

## Nadogradnja bez Terminala

Ovo je nadogradnja verzije 0.3 (baza s migracijama 002 i 003). Produkcijska baza se ne mijenja automatski. Postojeći korisnici, artikli, narudžbe, fotografije i postavke ostaju sačuvani.

1. U cPanel File Manageru napravite kopiju postojećih fajlova, a u phpMyAdmin izvezite cijelu bazu. Postupak prvo provjerite na staging kopiji.
2. Nakratko zaustavite kupovinu opcijom održavanja na hostingu. Nemojte koristiti stari checkout paralelno s novom verzijom.
3. U phpMyAdmin odaberite postojeću WEBSHOP bazu i uvezite **samo `database/migrations/004_fulfillment.sql`**, jednom. Ne uvozite install.sql, schema.sql, 002 ili 003 ponovo. Ako je instalacija starija od 0.3, prvo primijenite odgovarajuće ranije nadogradnje.
4. `WEBSHOP-0.4.0-NADOGRADNJA.zip` raspakujte u **`shop.sdpkakanj.ba`**, roditeljski direktorij javnog `public`. Potvrdite zamjenu fajlova iz paketa. Prenesite i nove fajlove u app/views, app i public/assets. Document root ostaje **`shop.sdpkakanj.ba/public`**.
5. Postojeći config/local.php i public/uploads sačuvajte. Paket nadogradnje ne sadrži lozinke, korisničke fotografije, instalacijski obrazac ili demonstracijske podatke. Ne pravite ponovo administratora.
6. Osvježite preglednik uz Ctrl+F5. Prijavite se postojećim računom i provjerite galeriju, narudžbe, lager i probni postupak otpreme. Uklonite ZIP s hostinga i ponovo omogućite kupovinu.

Ako SQL uvoz bude prekinut, nemojte naslijepo ponavljati migraciju. MySQL DDL nije transakcijski povratan. Provjerite staging backup i vratite usklađenu prethodnu bazu i fajlove prije ponavljanja. Pri vraćanju produkcije sačuvajte sve nove narudžbe nastale nakon nadogradnje.

Puni paket je za novu instalaciju: njegov database/install.sql uključuje sve migracije do 004. **Ne uvozite ga preko postojeće baze.**

## Narudžba, slanje i naplata

| Radnja | Status otpreme | Lager | Naplata |
| --- | --- | --- | --- |
| Kupac potvrdi narudžbu | U pripremi za slanje | Slobodna količina prelazi u rezervisanu; fizička količina ostaje ista | Nenaplaćeno |
| Otvorite ili odštampate list | Bez promjene | Bez promjene | Bez promjene |
| Predate paket kuriru i potvrdite slanje | Poslano | Zatvara rezervaciju; roba izlazi iz fizičkog lagera | Ostaje prethodni status |
| Evidentirate primljeni novac | Poslano ostaje Poslano | Bez nove promjene | Naplaćeno |
| Potvrdite stvarnu dostavu | Dostavljeno | Bez nove promjene | Ostaje prethodni status |

Fizički lager je **slobodno + rezervisano**. Kupac može naručiti samo slobodnu količinu. Slanje ne umanjuje slobodno stanje drugi put. Naplata unaprijed je dozvoljena: narudžba tada ostaje u pripremi uz jasno upozorenje dok se otprema zaista ne potvrdi.

U detalju narudžbe prvo odštampajte list, zatim nakon predaje kuriru unesite **kurirsku službu i obavezno obrazloženje** i kliknite **Potvrdi slanje i razduži lager**. Broj pošiljke nije obavezan u trenutku predaje; može se dopuniti kasnije s razlogom dopune. Čuvaju se kurir, način predaje, datum, broj pošiljke, obrazloženje i operater u historiji. Odabrani način dostave kupca ostaje zasebno sačuvan.

Nakon primitka novca izaberite **Evidentiraj naplatu**, unesite dokument ili obrazloženje i potvrdite. Prikazuju se odvojene oznake **Poslano** i **Naplaćeno**, te obrada **Razduženo i naplaćeno**. Ovo je operativno zatvaranje narudžbe, bez automatskog knjiženja u glavnu knjigu.

Ako web narudžbu predate lično, koristite opciju **Lična predaja kupcu umjesto slanja** unutar te narudžbe. Ona razdužuje postojeću rezervaciju. Nemojte za istu robu praviti dodatni izlaz. Lična predaja ne mijenja prethodno ugovoreni iznos ni cijenu dostave u narudžbi.

Otkazivanje oslobađa rezervaciju. Povrat otpremljene robe vraća stanje jednom. Za naplaćenu narudžbu prvo evidentirajte povrat novca. Svaka promjena zahtijeva obrazloženje; ponovljeno slanje ili vraćanje ne može ponovo razdužiti/zadužiti lager.

## Vaša ranije testirana narudžba

Migracija **ne pogađa da li je paket stvarno predan kuriru** i ne prepravlja ranije statuse ni količine. Ako narudžba prikazuje U pripremi + Naplaćeno, provjerite karticu lagera i stvarnu predaju. Kada potvrdite da je rezervisana roba zaista otišla, potvrdite slanje iz detalja narudžbe. Stari status completed prikazuje se kao **Otpremljeno (stari zapis)**; nedostajući kurir i datum nisu izmišljeni. Zapisi sa starim inventory_state=legacy imaju upozorenje za provjeru.

## Drugi izlazi robe

Novi odjeljak **Drugi izlazi robe** odvaja povlačenje, otpis, ličnu prodaju iz slobodnog lagera i ostale razloge. Unose se artikal, količina, vrsta i **obavezno obrazloženje**, uz primaoca i broj dokumenta po potrebi. Rezervisanu robu nije moguće izdati tim putem. Obično zaduženje u Lageru sada prihvata samo pozitivan unos; za svaki negativan unos koristite odgovarajući izlaz.

Za ličnu prodaju obavezni su primalac i prodajna cijena. Roba odmah izlazi iz lagera; naplata može biti evidentirana odmah ako korisnik ima dozvolu Naplata, ili naknadno kroz finansijskog korisnika. Povlačenje i otpis nemaju naplatu. Čuvaju se naziv/SKU i prodajni iznos u trenutku izlaza, veza na karticu prometa i historija radnji.

Grešku ispravljajte **stornom s obaveznim razlogom**, čime se količina vraća jednom. Zapis se ne briše. Za naplaćenu ličnu prodaju prvo evidentirajte povrat novca, pa storno izlaza. Storno radite tek kada roba stvarno pripada povratnom lageru.

Registar se pretražuje po artiklu/SKU-u, primaocu, dokumentu, broju i obrazloženju; filtrira po vrsti, naplati, operateru, periodu i stornu, uz CSV izvoz. Korisnik Lager upravlja količinama; korisnik Naplata vidi lične prodaje i evidentira novac, ali ne dobija pristup otpisima ili promjeni lagera samo na osnovu te dozvole. Finansijski izvještaj obuhvata web narudžbe i lične prodaje, s odvojenim izvorima.

Narudžbe imaju odvojene preglede pripreme, slanja, lične predaje, povrata i otkazivanja, pretragu i filtere naplate, kurira, perioda i razduženja. Liste narudžbi i izlaza imaju straničenje. Historije i finansijski prikaz imaju ograničenje broja zapisa; CSV daje rezultate odabranih filtera.

## Galerija i provjere

Klik na glavnu sliku otvara galeriju u prozorčiću na istoj stranici. Strelice i tipke lijevo/desno listaju slike; Esc ili dugme zatvaraju prikaz. Fokus se vraća na početnu sliku. Jedna fotografija nema nepotrebne strelice.

Provjereni su migracija bez prepravljanja starih narudžbi, rezervacije i paralelna kupovina, naplata prije/poslije slanja, obavezan kurir uz opcionalni broj pošiljke, povrati, zaštita rezervisanog lagera, tipovi izlaza, ponovljeni zahtjevi, storno, finansijski CSV i odvojene dozvole. Browser provjera pokriva galeriju, tastaturu, fokus, mobilni prikaz i potvrdu slanja naplaćene narudžbe. Testovi koriste isključivo zasebne lokalne testne baze.
