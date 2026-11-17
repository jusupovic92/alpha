# Alpha 2.0.1 — Prijava partnera na naslovnici

Paket je za postojeću verziju 2.0. Napravite kopiju postojećeg `public/index.php`, zatim prekopirajte sadržaj paketa uz isti raspored direktorija i osvježite preglednik sa Ctrl+F5.

Nema SQL migracije. Računi, lozinke, cijene, narudžbe i postavke ostaju sačuvani.

U zaglavlju prodavnice dodano je istaknuto dugme **Prijava partnera**, s ikonom korisnika. Na računaru je pored korpe, a na mobitelu ispod pretrage, vidljivo bez otvaranja menija. Tekst ranijeg linka Poslovni saradnici u navigaciji sada je Prijava partnera.

Dugme vodi na postojeći `partner.php`. Neprijavljeni saradnik unosi korisničko ime i lozinku; već prijavljeni otvara svoj portal. Ovo nije samostalna registracija: račune i dalje dodjeljuje administrator. Prikaz je prilagođen svim trima Alpha temama.
