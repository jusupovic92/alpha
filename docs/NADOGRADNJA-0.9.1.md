# WEBSHOP 0.9.1 — teme naslovnice i javne prodavnice

## Postavljanje bez Terminala

Za postojeću verziju **0.9** nije potrebna nova SQL migracija.

1. Napravite kopiju postojećih fajlova i baze.
2. U cPanel File Manageru otvorite **shop.sdpkakanj.ba**, roditeljski direktorij od `public/`.
3. Učitajte i raspakujte **WEBSHOP-0.9.1-NADOGRADNJA.zip**, uz zamjenu istoimenih fajlova.
4. Osvježite administraciju, otvorite **Teme**, zatim odjeljak **Teme naslovnice i prodavnice**.
5. Kliknite **Primijeni na prodavnicu** uz željenu temu. Otvorite prodavnicu da pregledate rezultat.

Document root ostaje `shop.sdpkakanj.ba/public`. Ne mijenjajte `config/local.php`, ključeve, fotografije ili postojeće kopije. Paket ih ne sadrži. Ako još koristite verziju 0.8, prvo postavite nadogradnju 0.9 i primijenite migraciju 009 prema njenim uputama.

## Dostupne teme

- **Zelena klasika** — postojeća naslovnica, s vašom odabranom bojom iz postavki.
- **Plavi horizont** — tamnoplavo zaglavlje, ledenoplava naslovnica i zaobljene kartice.
- **Topli pijesak** — smeđi naglasci i kremaste površine.
- **Ljubičasti studio** — ljubičasto zaglavlje i pastelna pozadina.
- **Bijeli minimalizam** — svijetlo zaglavlje, čiste linije i ravnije kartice.

Odabir se pamti za sve posjetioce i usklađuje naslovnicu, katalog, stranicu artikla, korpu i narudžbu. Tema administracije bira se zasebno. Potrebna je dozvola za postavke. Svaka promjena se evidentira na bosanskom u dnevniku aktivnosti.

Rotacija tri artikla, galerije, slike, kategorije, cijene i tok kupovine ostaju isti. Tema ne prebojava tekst i grafiku unutar vaših fotografija ili posebnih reklamnih banera. Zelena klasika ostaje početna tema na postojećim i novim instalacijama. Odabir teme se čuva u postojećoj tabeli postavki i uključen je u buduće sigurnosne kopije.
