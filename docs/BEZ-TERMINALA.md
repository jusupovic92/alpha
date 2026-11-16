# Prvi administrator bez Terminala

Za opisani hosting raspored glavni direktorij je shop2.sdpkakanj.ba, a document root shop2.sdpkakanj.ba/public. Baza i config/local.php trebaju već biti postavljeni prema glavnim uputama. Ovaj postupak ne traži SQL izmjene i ne prepisuje konfiguraciju baze.

1. Kroz cPanel File Manager postavite setup-admin.php u shop2.sdpkakanj.ba/public/setup-admin.php.
2. Postavite privatni fajl setup.key u shop2.sdpkakanj.ba/config/setup.key, izvan public direktorija. Fajl treba sadržati jedinstven nasumičan ključ od najmanje 32 znaka. Dodatni instalacijski paket sadrži zasebno generisan ključ; nemojte koristiti ključ iz tuđe instalacije. Izvorni GitHub kod ne sadrži ovaj ključ.
3. U File Manageru otvorite config/setup.key i kopirajte njegov sadržaj.
4. Otvorite https://shop2.sdpkakanj.ba/setup-admin.php. Unesite ključ, ime, e-mail za prijavu i vlastitu novu lozinku, pa ponovite lozinku i kreirajte račun. Ključ za postavljanje nije vaša administratorska lozinka.
5. Prijavite se na https://shop2.sdpkakanj.ba/admin.php. Obrišite public/setup-admin.php i config/setup.key ako je preostao. Ključ se pokušava automatski ukloniti nakon uspjeha.

Obrazac radi samo preko HTTPS-a, uz ispravan ključ, dok ne postoji nijedan korisnik. Provjera i kreiranje su transakcijski zaključani, a trajni marker sprečava ponovno otvaranje postavljanja čak i ako korisnici kasnije budu uklonjeni. Postojeći korisnici i produkcijski podaci se ne mijenjaju. Nakon deset evidentiranih pokušaja po IP-u čeka se 15 minuta. Ako već postoji korisnik, koristite postojeću prijavu; ovaj obrazac nije alat za resetovanje lozinke.
