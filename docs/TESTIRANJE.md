# Provjere verzije 0.2.0

Integration test radi samo na novoj praznoj bazi webshop_test ili webshop_test_*. Uvozi originalnu v0.1 shemu, stvara stare zapise pa primjenjuje migraciju.

Provjerava očuvanje starih količina, jednokratni povrat robe, iznose u feningima i dostavu, rezervacije, idempotentnost, rollback, nedozvoljene statuse, obavezan broj pošiljke, slanje bez dvostrukog umanjenja, završetak naplatom, refund prije povrata i ograničenje pokušaja kupovine. Dva stvarna paralelna PHP procesa pokušavaju kupiti posljednji komad: samo jedan uspijeva.

HTTP test pokreće privremeni PHP server i provjerava prijavu, CSRF, administrativne ekrane, proizvode, ulaz lagera, escaping, checkout, štampu i odbijanje korisnika bez dozvola, uključujući direktni POST. Testna konfiguracija ne može prepisati postojeći config/local.php. Testni podaci ostaju u testnoj bazi.

Lokalno okruženje: Windows, PHP 8.2.34, MariaDB 11.4.5. CI definicija dodatno provjerava PHP 8.2 / MySQL 8.0; rezultat konkretne izvedbe vidi se u PR provjerama. Ne pretpostavlja se iz lokalnih rezultata.

Svježi install.sql provjerava se na dodatnoj praznoj bazi, a CLI migracija na dovršenoj shemi treba prijaviti da je već primijenjena. PHP fajlovi prolaze lint. Naslovnica je vizuelno pregledana u browseru.

Dostava e-maila i štampa na stvarnom pisaču nisu potvrđene lokalnim testom; zavise od cPanel konfiguracije i uređaja. Prije prodaje provjerite ciljnu staging instalaciju, HTTPS, upload, pošiljaoca, dostavu i uloge. Produkcijska migracija nije izvršena.
