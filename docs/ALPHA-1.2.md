# Alpha 1.2 — Premium Garage

Odobreni tamni izgled: linijske ikonice sa zelenim sjajem i blagim podizanjem, kompaktan baner, bijele kartice artikala, jasne cijene i zalihe te prilagođen mobilni prikaz.

## Nadogradnja shop2.sdpkakanj.ba

1. Napravite rezervnu kopiju postojećih fajlova.
2. Raspakujte paket preko istog Alpha projekta, uz isti raspored app/ i public/. Sačuvajte config/local.php, storage/ i public/uploads/.
3. U administraciji otvorite Teme → Teme naslovnice i prodavnice i odaberite Premium Garage.
4. Osvježite javnu stranicu sa Ctrl+F5.

Za nadogradnju sa verzije 1.1 nije potrebna izmjena baze niti uvoz SQL-a. Cijene i artikli prikazuju se iz vaše postojeće baze. Ostale teme ostaju dostupne.

## Prikaz i ponašanje

- Šest ikonica kategorija na računaru, tri u redu na telefonu.
- Četiri artikla u redu na računaru, dva na telefonu.
- Izdvojeno prikazuje označene artikle; Novo u ponudi prikazuje aktivne artikle po datumu dodavanja; Svi artikli otvara puni katalog sa filterima.
- Ikonica oka otvara stranicu proizvoda. Prikazana cijena i raspoloživost dolaze iz baze; nedostupan artikal ima onemogućeno dugme kupovine.
- Efekti podržavaju tastaturu i poštuju sistemsku postavku smanjenog kretanja.
- Tamni logo se prikazuje na bijeloj podlozi radi čitljivosti.

Fotografije i proizvodi ostaju iz postojeće ponude. Konačni prikaz zavisi od vaših naziva, cijena, fotografija i odabrane teme.
