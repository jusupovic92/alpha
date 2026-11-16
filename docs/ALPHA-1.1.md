# Alpha 1.1 — fotografske teme i mobilna verzija

## Nadogradnja shop2.sdpkakanj.ba

1. Napraviti backup fajlova i baze.
2. Raspakovati nadogradnju preko foldera istog Alpha projekta. Sačuvati config/local.php i sve korisničke fajlove u public/uploads/ i storage/. Paket ih ne sadrži.
3. Nije potreban ponovni uvoz baze. Ne uvoziti install.sql niti install-alpha-demo.sql preko postojeće baze. Stare demo ilustracije automatski se prikazuju kao fotografije prema šiframa ALPHA-DEMO-001 do 012. Učitane vlastite fotografije imaju prednost.
4. U administraciji otvoriti Teme i odabrati jednu od tri Alpha teme. Osvježiti stranicu sa Ctrl+F5. Mobilni pregled ima zaseban raspored, sklopivi meni i filtere, bez horizontalnog skrolanja cijele stranice.
5. Za novu instalaciju i dalje koristiti database/install-alpha-demo.sql u novoj praznoj bazi. Adresa je https://shop2.sdpkakanj.ba/ i document root završava na public/.

## Šta je promijenjeno

Veliki fotografski baneri umjesto nacrtanog artikla. Pro katalog ima svijetli baner s pištoljima za lakiranje, Premium Garage tamni baner s poliranjem vozila, Alpha Hybrid dijagonalno podijeljenu fotografiju radionice. Četiri artikla u redu na desktopu i dva na telefonu. Šest foto-kategorija, osam izdvojenih artikala na naslovnici, svi artikli u katalogu, kompaktni filteri i promotivna sekcija. Originalni Alpha logo se koristi dok se ne učita vlastiti.

Fotografije MAXXT polirke, rotacione polirke, brusilice i usisivača te fotografije radionice preuzete su sa alpha-refinish.com. Za dva druga demo MAXXT alata koristi se reprezentativna fotografija brusilice — provjeriti fotografije stvarnih modela prije prodaje. Ostali testni artikli koriste fotografije odgovarajućih Mipa, ChemicalWorkz, P&S, EWOCAR i 3D artikala po uzoru na asdetailing.ba. Testne cijene i zalihe ostaju nepromijenjene. Stari generički demo nazivi se u prikazu usklađuju s fotografijama. To nije masovno preimenovanje stvarnih artikala.

Fotografije su uključene lokalno: webshop ne zavisi od učitavanja sa tuđeg servera. Fotografije, kategorije i nazive možete zamijeniti kroz postojeću administraciju. Učitavanje vlastite fotografije isključuje automatski demo prikaz tog artikla.

Provjere uključuju postojeće poslovne regresije, izbor svih tema, zaštićen unos demo artikala i renderiranje stvarne PHP naslovnice za sva tri izgleda. Mobilni raspored predviđen je za 320, 390, 760 i desktop širine.
