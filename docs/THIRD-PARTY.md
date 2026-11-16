# Uključene biblioteke

Mail transport koristi **PHPMailer 7.1.1**, preuzet iz službenog izdanja:
https://github.com/PHPMailer/PHPMailer/releases/tag/v7.1.1

Uključeni su originalni src/PHPMailer.php, src/SMTP.php i src/Exception.php pod app/vendor/PHPMailer, zajedno s izvornom LGPL licencom u LICENSE. Nije potreban Composer na hostingu. Biblioteka nije mijenjana. Koristi se SMTP autentifikacija i provjera TLS certifikata; OAuth nije konfigurisan.
