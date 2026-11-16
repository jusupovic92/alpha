# WEBSHOP 0.9.2 — logo, slogan i ikonica stranice

Za nadogradnju s verzije 0.9 ili 0.9.1 nije potrebna nova SQL migracija.

1. Sačuvajte kopiju fajlova i baze.
2. U cPanel File Manageru otvorite **shop.sdpkakanj.ba**, direktorij iznad `public/`.
3. Učitajte i raspakujte **WEBSHOP-0.9.2-NADOGRADNJA.zip**, uz zamjenu istoimenih fajlova.
4. Otvorite administraciju > **Logo i brend**.
5. U polje **Logo za svijetlu pozadinu** učitajte zeleni Praktiko PNG ili varijantu za željenu temu. U polje **Logo za tamnu pozadinu** učitajte bijeli logo.
6. Kliknite **Spremi logo i brend**. Naziv prodavnice možete promijeniti u Praktiko kroz **Izgled i postavke**. Tema se bira zasebno kroz **Teme**.

Logo za tamnu pozadinu prikazuje se u zaglavlju naslovnice kada je gornji dio stranice taman. Glavni logo koristi se na stranicama artikala, u korpi, podnožju i uz Bijeli minimalizam. Ako tamna verzija nije učitana, koristi se glavni logo. Ako nema glavnog loga, stranica prikazuje postojeći tekstualni naziv. Sama nadogradnja ne mijenja naziv ili postojeće podatke prodavnice.

Podržani su PNG, JPG i WebP do 5 MB i 6000 × 6000 piksela; SVG nije podržan za učitavanje. Prozirni PNG je najbolji izbor za promjenjive pozadine. Bijela verzija na zelenoj pozadini je neprozirna i prikazuje se kao zeleni pravougaonik; koristite je kada želite zadržati originalne boje brenda i na drugim temama.

Širina loga podešava se od 160 do 360 px i prilagođava mobitelu. Preporuka za Praktiko: 280–320 px. Ako je naziv i slogan već na slici, ostavite **Prikaži naziv i slogan uz učitani logo** isključeno da se ne ponavljaju. Tekst slogana pojavljuje se uz tekstualni naziv kada je ta opcija uključena ili glavni logo nije postavljen. Za slogane ugrađene u sliku treba učitati novu sliku.

Za **ikonicu stranice** preporučen je kvadratni PNG, npr. 64 × 64 ili 256 × 256. Prazna polja ne mijenjaju postojeće slike. Opcija **Ukloni iz prikaza** uklanja vezu iz postavki; stari fajl ostaje sačuvan za prethodne sigurnosne kopije i druge reference. Promjene zahtijevaju dozvolu za postavke, štite se prijavom i CSRF provjerom i upisuju u dnevnik aktivnosti.

Učitani logotipi i ikonica nalaze se u `public/uploads/`, a postavke u postojećoj tabeli `settings`. Uključeni su u sigurnosne kopije i vraćaju se s ostalim fotografijama i podacima. Paket za nadogradnju ne sadrži vaše podatke, konfiguraciju, ključeve ili postojeće logotipe.

Ako još koristite verziju 0.8, prvo postavite 0.9 i migraciju 009 prema uputama te verzije. Document root ostaje `shop.sdpkakanj.ba/public`.
