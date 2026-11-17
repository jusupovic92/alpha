# Alpha 1.8.1 — Ispravka promjene lozinke saradnika

Paket je za postojeću verziju 1.8. Napravite kopiju postojećih fajlova pa prekopirajte `app/partners.php` i `public/partner.php` na iste lokacije. Nema nove SQL migracije. Privatne postavke, računi, lozinke i narudžbe se ne brišu.

Nakon postavljanja odjavite saradnika i ponovo se prijavite korisničkim imenom i dosadašnjom lozinkom. Ako je to prva obavezna promjena, u prvih deset minuta od uspješne prijave obrazac traži **novu lozinku i ponavljanje nove lozinke**. Trenutna lozinka je već provjerena prijavom.

Pri redovnoj kasnijoj promjeni ili nakon isteka te potvrde obrazac i dalje traži trenutnu lozinku. Nova lozinka ostaje dužine 12–72 bajta, bez kontrolnih znakova i rubnih razmaka. Lozinke se pri čitanju obrasca više ne skraćuju niti automatski obrađuju funkcijom trim.

Provjera verzije prijave sada poredi numeričke vrijednosti umjesto PHP tipova. Ako administrator u međuvremenu izmijeni ili isključi račun, prikazuje se poruka za ponovnu prijavu, a ne pogrešna poruka o netačnoj trenutnoj lozinci. Nova lozinka se sprema samo ako se podudara sa potvrdom i ako je prijava i dalje važeća.

Ako je administrator resetovao lozinku, za novu prijavu koristite posljednju početnu lozinku koju je administrator postavio. Nakon uspješne promjene prijavljujete se novom lozinkom.
