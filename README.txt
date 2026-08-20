=====================================================
  ANED Roma - Gestionale Web
  Versione 1.0.0
=====================================================

REQUISITI
---------
- XAMPP con PHP 8.0+ e MySQL 5.7+
- Modulo php_openssl attivo (per invio email SSL)
- Modulo php_pdo_mysql attivo

INSTALLAZIONE
-------------

1. COPIA FILES
   Assicurati che tutti i file siano in C:\xampp\htdocs\ANED\

2. PHP MAILER
   Scarica PHPMailer da: https://github.com/PHPMailer/PHPMailer
   Copia le 3 file nella cartella:
     C:\xampp\htdocs\ANED\libs\PHPMailer\PHPMailer.php
     C:\xampp\htdocs\ANED\libs\PHPMailer\SMTP.php
     C:\xampp\htdocs\ANED\libs\PHPMailer\Exception.php

   OPPURE usa Composer:
     cd C:\xampp\htdocs\ANED
     composer require phpmailer/phpmailer

3. DATABASE
   Avvia Apache e MySQL in XAMPP.
   Apri il browser e vai a:
     http://localhost/ANED/install/install.php
   
   Questo creerà il database aned_db e l'utente admin.

4. ACCESSO INIZIALE
   URL: http://localhost/ANED/
   Email: admin@aned.it
   Password: Admin@aned2024
   
   CAMBIA LA PASSWORD subito dopo il primo accesso!

5. SICUREZZA POST-INSTALLAZIONE
   - Elimina la cartella /install/ dopo l'installazione
   - Cambia la password dell'admin
   - Verifica che la cartella /uploads/ non sia accessibile via web diretto

CONFIGURAZIONE MAIL
-------------------
Il file config/mail.php contiene:
  - SMTP: smtps.aruba.it (porta 465, SSL)
  - POP3: pop3s.aruba.it
  - Email: roma@aned.it

STRUTTURA CARTELLE
------------------
/ANED/
  ├── admin/          Gestione utenti (solo admin)
  ├── assets/         CSS, JS, immagini
  ├── attivita/       Eventi e attività
  ├── config/         Configurazioni
  ├── consiglio/      Consiglio direttivo
  ├── estratti/       Estratti conto
  ├── includes/       Header, navbar, footer
  ├── install/        Script installazione (DA ELIMINARE)
  ├── iscritti/       Gestione iscritti
  ├── libs/           Librerie (PHPMailer)
  ├── rubrica/        Rubrica contatti
  ├── spese/          Gestione spese
  ├── statuto/        Documenti statuto
  ├── uploads/        File caricati
  └── verbali/        Verbali assemblee

RUOLI UTENTE
------------
  admin      -> Accesso completo
  direttivo  -> Tutti i moduli eccetto gestione utenti
  segreteria -> Iscritti, verbali, attività, rubrica
  utente     -> Solo visualizzazione eventi

SUPPORTO
--------
Per assistenza: roma@aned.it
=====================================================
