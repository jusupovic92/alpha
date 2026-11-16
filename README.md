# ALPHA refinish 1.0.0

Tri nove teme: **Pro katalog**, **Premium Garage** i **Alpha Hybrid**. Administrator ih bira kroz **Teme → Teme naslovnice i prodavnice**. Ranijih pet tema i sve poslovne funkcije ostaju dostupne.

[Instalacija i nadogradnja Alpha](docs/ALPHA-1.0.md) · [Osnovno postavljanje](docs/POSTAVLJANJE.md)

Za novu testnu instalaciju uvesti **database/install-alpha-demo.sql** u novu praznu bazu. Sadrži 12 testnih artikala, šest kategorija, probne zalihe i početnu temu Alpha Hybrid. Za postojeću bazu koristiti migraciju 010 i opcionalno dugme za testne artikle u administraciji.

PHP 8.2+, MySQL 8.0+ ili MariaDB 10.6+, PDO MySQL, mbstring, OpenSSL i ZIP. Web root: public/.

Testni podaci nisu stvarna prodajna ponuda. Slike su lokalne ilustracije za probni prikaz. Nazivi šest MAXXT artikala i njihove referentne cijene uzeti su kao polazni primjer sa Alpha Refinish stranice i kataloga; ostalih šest su generički primjeri odgovarajućih kategorija.

Provjere: postojeći integracijski i HTTP testovi, plus tests/alpha.php i testovi tri teme u tests/http.mjs.
