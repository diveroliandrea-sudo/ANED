# ANED Roma — Documento di Specifica del Sistema
**Codice documento:** ANED-DOC-SPEC  
**Versione:** 1.0.0  
**Data:** 20 Agosto 2026  
**Stato:** Bozza di lavoro  

---

## Indice

1. [Introduzione](#1-introduzione)
2. [Obiettivi del sistema](#2-obiettivi-del-sistema)
3. [Stack tecnologico](#3-stack-tecnologico)
4. [Architettura del sistema](#4-architettura-del-sistema)
5. [Struttura del database](#5-struttura-del-database)
6. [Sistema di autenticazione e ruoli](#6-sistema-di-autenticazione-e-ruoli)
7. [Moduli applicativi](#7-moduli-applicativi)
   - 7.1 [Dashboard](#71-dashboard)
   - 7.2 [Iscritti](#72-iscritti)
   - 7.3 [Attività ed eventi](#73-attività-ed-eventi)
   - 7.4 [Consiglio direttivo](#74-consiglio-direttivo)
   - 7.5 [Rubrica contatti](#75-rubrica-contatti)
   - 7.6 [Spese](#76-spese)
   - 7.7 [Estratti conto](#77-estratti-conto)
   - 7.8 [Bilanci](#78-bilanci)
   - 7.9 [Statuto](#79-statuto)
   - 7.10 [Note interne](#710-note-interne)
   - 7.11 [Triangolo Rosso](#711-triangolo-rosso)
   - 7.12 [Stampa tessera](#712-stampa-tessera)
   - 7.13 [Gestione utenti](#713-gestione-utenti)
   - 7.14 [Verbali *(modulo mancante)*](#714-verbali-modulo-mancante)
8. [Flussi principali](#8-flussi-principali)
9. [Gestione file e upload](#9-gestione-file-e-upload)
10. [Sicurezza — stato attuale e criticità](#10-sicurezza--stato-attuale-e-criticità)
11. [Problemi noti e TODO](#11-problemi-noti-e-todo)
12. [Matrici di accesso per ruolo](#12-matrici-di-accesso-per-ruolo)
13. [Sviluppi futuri suggeriti](#13-sviluppi-futuri-suggeriti)

---

## 1. Introduzione

Il sistema **ANED Roma Gestionale** è un'applicazione web PHP sviluppata per la gestione interna dell'**Associazione Nazionale Ex Deportati (ANED) — Sezione di Roma**. L'ANED è l'associazione dei deportati nei campi di sterminio nazisti e dei loro familiari.

Il gestionale centralizza le attività amministrative, contabili, di comunicazione e di gestione dei soci in un'unica piattaforma web, accessibile tramite browser. Il sistema è installato su XAMPP in ambiente locale ed è collegato a un database MySQL remoto su VPS.

**Repository locale:** `C:\xampp\htdocs\ANED`  
**URL applicazione (locale):** `http://localhost/ANED`  
**Database remoto:** `172.245.156.21`, schema `deportati`, prefisso tabelle `aned_db_`

---

## 2. Obiettivi del sistema

| # | Obiettivo |
|---|-----------|
| O-01 | Gestire l'anagrafica degli iscritti e il rinnovo delle quote annuali |
| O-02 | Tracciare le entrate (quote) e le uscite (spese) per il bilancio associativo |
| O-03 | Organizzare e pubblicare eventi e attività dell'associazione |
| O-04 | Gestire la composizione del consiglio direttivo |
| O-05 | Archiviare documenti ufficiali (statuto, bilanci, estratti conto, verbali) |
| O-06 | Mantenere una rubrica di contatti istituzionali ed esterni |
| O-07 | Gestire le note operative interne |
| O-08 | Generare le tessere associative in PDF |
| O-09 | Identificare i soci a cui spedire il periodico "Triangolo Rosso" |
| O-10 | Gestire gli account degli operatori con controllo dei permessi per ruolo |

---

## 3. Stack tecnologico

| Componente | Tecnologia | Versione minima |
|---|---|---|
| Backend | PHP | 8.0+ |
| Database | MySQL | 5.7+ / 8.0 |
| Accesso DB | PDO (PHP Data Objects) | — |
| Frontend CSS | Bootstrap | 5.3.3 (CDN) |
| Icone | Bootstrap Icons | 1.11 (CDN) |
| Font | Google Fonts — Inter | — |
| CSS custom | `assets/css/style.css` | — |
| JS | Vanilla JavaScript | — |
| JS custom | `assets/js/app.js` | — |
| Email | PHPMailer | incluso in `libs/PHPMailer/` |
| SMTP | Aruba Mail (`smtps.aruba.it:465`, SSL) | — |
| PDF | FPDF (via Composer `vendor/autoload.php`) | — |
| Web server | Apache (XAMPP) | — |

**Note di dipendenza:**
- FPDF è richiesto solo per il modulo Stampa Tessera. Se `vendor/autoload.php` non è presente, la funzionalità di generazione tessere non è disponibile.
- PHPMailer è incluso manualmente nella cartella `libs/` senza Composer.

---

## 4. Architettura del sistema

### 4.1 Pattern architetturale

Il progetto segue un pattern **Page Controller**: ogni file PHP è sia controller che view. Non viene usato nessun framework MVC. Il flusso per ogni pagina è:

```
1. require_once config/config.php      → sessione, costanti, helpers
2. requireLogin() / requireRole(...)   → controllo accesso
3. Logica PHP (query DB, elaborazione POST)
4. define('PAGE_TITLE', '...')
5. include includes/header.php
6. include includes/navbar.php
7. Output HTML della pagina
8. include includes/footer.php
```

### 4.2 Struttura directory

```
ANED/
├── config/
│   ├── config.php          ← costanti globali, helper, auth
│   ├── database.php        ← connessione PDO (singleton)
│   └── mail.php            ← configurazione PHPMailer, template HTML email
├── includes/
│   ├── header.php          ← boilerplate HTML, CDN Bootstrap, CSS
│   ├── navbar.php          ← sidebar di navigazione responsiva
│   └── footer.php          ← Bootstrap JS CDN, app.js
├── assets/
│   ├── css/style.css       ← stili custom (variabili CSS, layout sidebar)
│   ├── js/app.js           ← JS: sidebar toggle, alert auto-hide, confirm, CF uppercase
│   └── img/favicon.svg
├── uploads/                ← file caricati dagli utenti (suddivisi per modulo)
│   ├── .htaccess           ← protezione accesso diretto
│   ├── bilanci/
│   ├── estratti/
│   ├── foto/               ← foto consiglio direttivo
│   ├── iscritti/
│   ├── locandine/          ← locandine eventi
│   ├── note/
│   ├── ricevute/           ← ricevute spese
│   └── statuto/
├── iscritti/               ← modulo iscritti (index, form, view, delete, export)
├── attivita/               ← modulo eventi (index, form, view, delete, pdf-preview)
├── consiglio/              ← modulo consiglio direttivo (index, form, delete)
├── rubrica/                ← rubrica contatti (index, form, delete)
├── spese/                  ← modulo spese (index, delete)
├── estratti/               ← estratti conto (index, delete)
├── bilanci/                ← documenti bilancio (index)
├── statuto/                ← statuto (index, delete)
├── note/                   ← note interne (index)
├── admin/
│   └── utenti.php          ← gestione utenti (solo admin)
├── libs/PHPMailer/         ← PHPMailer incluso manualmente
├── dashboard.php
├── index.php               ← redirect a dashboard o login
├── login.php
├── logout.php
├── registrazione.php
├── recupera-password.php
├── profilo.php
├── stampa_tessera.php
├── TesseraAnedGenerator.php
└── triangolo-rosso.php
```

### 4.3 Helper globali (config/config.php)

| Funzione | Descrizione |
|---|---|
| `isLogged()` | Verifica presenza di `$_SESSION['user_id']` |
| `requireLogin()` | Redirect a `login.php` se non autenticato |
| `hasRole(...$roles)` | Controlla `$_SESSION['user_role']` contro i ruoli passati |
| `requireRole(...$roles)` | Chiama `requireLogin()` + `hasRole()` + redirect dashboard se non autorizzato |
| `flash($type)` | Stampa e cancella il messaggio flash dalla sessione (`flash_success`, `flash_error`, `flash_warning`) |
| `sanitize($val)` | `htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8')` |
| `generateToken($length)` | `bin2hex(random_bytes($length))` |
| `formatDate($date)` | Formato `d/m/Y` da stringa data |
| `formatMoney($amount)` | Formato `€ X.XXX,XX` |
| `buildExportQueryString($filters)` | Costruisce query string per export CSV preservando i filtri attivi |

---

## 5. Struttura del database

Tutte le tabelle usano il prefisso `aned_db_`. Di seguito la struttura dedotta dal codice sorgente (non esiste uno script SQL CREATE TABLE centralizzato nel repository).

> **Nota:** la cartella `database/migrations/` contiene migration in stile Laravel che creano tabelle diverse (`users`, `contacts`, `board_members`, ecc.) — si tratta di residui di uno scaffolding Laravel non utilizzato. La struttura reale del DB è quella descritta di seguito.

---

### `aned_db_utenti` — Utenti del sistema

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `nome` | VARCHAR | |
| `cognome` | VARCHAR | |
| `email` | VARCHAR UNIQUE | |
| `password_hash` | VARCHAR | bcrypt cost=12 |
| `ruolo` | ENUM | `admin`, `direttivo`, `segreteria`, `utente` |
| `attivo` | TINYINT(1) | 0=disabilitato, 1=attivo |
| `email_verificata` | TINYINT(1) | 0=non verificata, 1=verificata |
| `token_verifica` | VARCHAR NULL | token di verifica email |
| `token_reset` | VARCHAR NULL | token reset password |
| `token_reset_scadenza` | DATETIME NULL | scadenza token reset (+2 ore) |
| `telefono` | VARCHAR NULL | |
| `created_at` | DATETIME | |

---

### `aned_db_iscritti` — Anagrafica iscritti ANED

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `nome` | VARCHAR | salvato in MAIUSCOLO |
| `cognome` | VARCHAR | salvato in MAIUSCOLO |
| `codice_fiscale` | VARCHAR NULL | max 16 char, MAIUSCOLO |
| `data_nascita` | DATE NULL | |
| `luogo_nascita` | VARCHAR NULL | MAIUSCOLO |
| `sesso` | ENUM NULL | `M`, `F`, `Altro` |
| `tipo_utente` | VARCHAR NULL | `Familiare`, `Amico`, `Superstite` |
| `nominativo_familiare` | VARCHAR NULL | Visibile solo se tipo_utente = Familiare |
| `campo_familiare` | VARCHAR NULL | Luogo di deportazione del familiare |
| `indirizzo` | VARCHAR NULL | MAIUSCOLO |
| `cap` | VARCHAR NULL | MAIUSCOLO |
| `citta` | VARCHAR NULL | MAIUSCOLO |
| `provincia` | VARCHAR NULL | MAIUSCOLO |
| `telefono` | VARCHAR NULL | |
| `cellulare` | VARCHAR NULL | |
| `email` | VARCHAR NULL | |
| `note` | TEXT NULL | non convertito in maiuscolo |
| `file_allegato` | TEXT NULL | JSON array di filename (es. `["doc_123.pdf"]`) |
| `flag_triangolo_rosso` | TINYINT(1) | 1 = riceve il periodico cartaceo |
| `attivo` | TINYINT(1) | soft delete: 0=inattivo |
| `created_by` | INT FK | riferimento a `aned_db_utenti.id` |
| `created_at` | DATETIME | |

---

### `aned_db_iscrizioni` — Quote di iscrizione annuali

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `iscritto_id` | INT FK | → `aned_db_iscritti.id` |
| `anno` | YEAR | |
| `data_iscrizione` | DATE | |
| `importo` | DECIMAL | |
| `note` | TEXT NULL | |
| `inserito_da` | INT FK | → `aned_db_utenti.id` |
| — | UNIQUE | su `(iscritto_id, anno)` |

---

### `aned_db_attivita` — Attività ed eventi

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `titolo` | VARCHAR | |
| `descrizione` | TEXT NULL | |
| `data_evento` | DATE | |
| `ora_inizio` | TIME NULL | |
| `ora_fine` | TIME NULL | |
| `luogo` | VARCHAR NULL | |
| `indirizzo_luogo` | VARCHAR NULL | |
| `locandina` | VARCHAR NULL | filename del file caricato |
| `stato` | ENUM | `bozza`, `pubblicata`, `annullata`, `conclusa` |
| `max_partecipanti` | INT | 0 = illimitati |
| `inserito_da` | INT FK | → `aned_db_utenti.id` |
| `created_at` | DATETIME | |

---

### `aned_db_attivita_relatori` — Relatori degli eventi

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `attivita_id` | INT FK | → `aned_db_attivita.id` |
| `nome` | VARCHAR | |
| `ruolo` | VARCHAR NULL | es. "Professore", "Moderatore" |
| `bio` | TEXT NULL | |

---

### `aned_db_attivita_referenti` — Referenti degli eventi

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `attivita_id` | INT FK | → `aned_db_attivita.id` |
| `nome` | VARCHAR | |
| `email` | VARCHAR NULL | |
| `telefono` | VARCHAR NULL | |

---

### `aned_db_consiglio_direttivo` — Membri del consiglio

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `nome` | VARCHAR | |
| `cognome` | VARCHAR | |
| `carica` | VARCHAR | es. "Presidente", "Segretario" |
| `email` | VARCHAR NULL | |
| `telefono` | VARCHAR NULL | |
| `data_inizio` | DATE NULL | inizio mandato |
| `data_fine` | DATE NULL | fine mandato (NULL = in carica) |
| `foto` | VARCHAR NULL | filename immagine in `uploads/foto/` |
| `ordine` | INT | per ordinamento visuale |
| `attivo` | TINYINT(1) | soft delete |

---

### `aned_db_rubrica` — Rubrica contatti

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `nome` | VARCHAR | |
| `cognome` | VARCHAR NULL | |
| `organizzazione` | VARCHAR NULL | |
| `categoria` | VARCHAR NULL | Istituzionale / Associazione / Media / Fornitore / Partner / Utente / Altro |
| `email` | VARCHAR NULL | |
| `telefono` | VARCHAR NULL | |
| `cellulare` | VARCHAR NULL | |
| `indirizzo` | VARCHAR NULL | |
| `citta` | VARCHAR NULL | |
| `sito_web` | VARCHAR NULL | |
| `note` | TEXT NULL | |
| `created_by` | INT FK | → `aned_db_utenti.id` |

---

### `aned_db_spese` — Registro spese

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `data_spesa` | DATE | |
| `categoria` | VARCHAR | Amministrativa / Logistica / Comunicazione / Evento / Affitto / Forniture / Altro |
| `descrizione` | TEXT | |
| `importo` | DECIMAL | |
| `fornitore` | VARCHAR NULL | |
| `file_ricevuta` | VARCHAR NULL | filename in `uploads/ricevute/` |
| `approvata` | TINYINT(1) | 0=in attesa, 1=approvata |
| `approvata_da` | INT FK NULL | → `aned_db_utenti.id` |
| `inserito_da` | INT FK | → `aned_db_utenti.id` |

---

### `aned_db_estratti_conto` — Estratti conto bancari

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `titolo` | VARCHAR | |
| `anno` | YEAR | |
| `mese` | TINYINT NULL | 0 = estratto annuale, 1-12 = mese specifico |
| `note` | TEXT NULL | |
| `file_path` | VARCHAR | filename in `uploads/estratti/` |
| `inserito_da` | INT FK | → `aned_db_utenti.id` |

---

### `aned_db_bilanci` — Documenti di bilancio

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `anno` | YEAR | |
| `tipo_documento` | VARCHAR | Bilancio / Verbale / Ricevuta / Altro |
| `note` | TEXT NULL | |
| `file_allegato` | VARCHAR | filename in `uploads/bilanci/` |
| `created_by` | INT FK | → `aned_db_utenti.id` |
| `created_at` | DATETIME | |

---

### `aned_db_statuto` — Documenti dello statuto

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `titolo` | VARCHAR | |
| `descrizione` | TEXT NULL | |
| `versione` | VARCHAR NULL | es. "1.2", "2024" |
| `data_approvazione` | DATE NULL | |
| `file_path` | VARCHAR | filename in `uploads/statuto/` |
| `inserito_da` | INT FK | → `aned_db_utenti.id` |

---

### `aned_db_note` — Note interne

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `data_nota` | DATE | |
| `titolo` | VARCHAR NULL | |
| `nota` | TEXT | |
| `allegati` | TEXT NULL | JSON array di filename in `uploads/note/` |
| `created_by` | INT FK | → `aned_db_utenti.id` |
| `created_at` | DATETIME | |

---

### `aned_db_log_attivita` — Log accessi

| Colonna | Tipo | Note |
|---|---|---|
| `id` | INT PK AI | |
| `utente_id` | INT FK | → `aned_db_utenti.id` |
| `azione` | VARCHAR | es. "login", "logout" |
| `ip` | VARCHAR | indirizzo IP del client |
| `created_at` | DATETIME | |

---

## 6. Sistema di autenticazione e ruoli

### 6.1 Ruoli definiti

| Costante | Valore | Descrizione |
|---|---|---|
| `ROLE_ADMIN` | `admin` | Accesso completo: gestione utenti, tutte le operazioni CRUD, hard delete, approvazione |
| `ROLE_DIRETTIVO` | `direttivo` | Accesso operativo completo (escluso gestione utenti) |
| `ROLE_SEGRETERIA` | `segreteria` | Iscritti, attività (view), rubrica, note; no finanze |
| `ROLE_UTENTE` | `utente` | Solo visualizzazione eventi pubblicati, note proprie, dashboard personale |

### 6.2 Flusso di login

```
POST login.php
  → verifica email/password su aned_db_utenti (attivo=1)
  → password_verify() contro bcrypt hash
  → verifica email_verificata=1
  → set $_SESSION[user_id, user_nome, user_cognome, user_email, user_role]
  → INSERT INTO aned_db_log_attivita (azione='login')
  → redirect dashboard.php
```

### 6.3 Flusso di registrazione

```
POST registrazione.php
  → valida campi (nome, cognome, email, password ≥8 char)
  → verifica email non duplicata
  → INSERT aned_db_utenti (attivo=0, email_verificata=0, ruolo='utente', token_verifica=random)
  → INSERT aned_db_rubrica (categoria='Utente', nota auto)
  → sendMail() → link verifica-email.php?token=...
  
GET verifica-email.php?token=...
  → UPDATE aned_db_utenti SET email_verificata=1, attivo=1, token_verifica=NULL
  → notifica email all'admin (roma@aned.it)
```

### 6.4 Flusso reset password

```
Step 1 — Richiesta
POST recupera-password.php (step=request)
  → genera token (random_bytes 32)
  → UPDATE aned_db_utenti SET token_reset=?, token_reset_scadenza=NOW()+2h
  → sendMail() → link con token
  → risposta sempre identica (no user enumeration)

Step 2 — Reset
POST recupera-password.php (step=reset, token=?)
  → verifica token_reset_scadenza > NOW()
  → UPDATE password_hash=bcrypt(nuova_password, cost=12), token_reset=NULL
```

### 6.5 Sessione PHP

Variabili di sessione impostate al login:

| Chiave | Contenuto |
|---|---|
| `$_SESSION['user_id']` | ID utente |
| `$_SESSION['user_nome']` | Nome |
| `$_SESSION['user_cognome']` | Cognome |
| `$_SESSION['user_email']` | Email |
| `$_SESSION['user_role']` | Ruolo (stringa) |
| `$_SESSION['flash_success']` | Messaggio flash di successo (usa/cancella in `flash()`) |
| `$_SESSION['flash_error']` | Messaggio flash di errore |
| `$_SESSION['flash_warning']` | Messaggio flash di avvertimento |

---

## 7. Moduli applicativi

### 7.1 Dashboard

**File:** `dashboard.php`  
**Accesso:** tutti i ruoli autenticati

**Funzionalità:**
- Stat card con indicatori chiave (visibili a tutti tranne `utente`):
  - Iscritti totali attivi
  - Iscritti anno corrente
  - Totale quote incassate anno corrente
  - Totale spese anno corrente
  - Numero attività con stato "pubblicata"
  - Iscritti con flag Triangolo Rosso
- Saldo finanziario `(entrate - spese)` anno corrente — solo `admin`/`direttivo`
- Prossimi 5 eventi futuri con stato "pubblicata" (tabella)
- Ultimi 6 iscritti per data inserimento (card griglia)

---

### 7.2 Iscritti

**File:** `iscritti/index.php`, `form.php`, `view.php`, `delete.php`, `delete_quota.php`, `export.php`  
**Accesso:** `admin`, `direttivo`, `segreteria`  
**Tabelle:** `aned_db_iscritti`, `aned_db_iscrizioni`

#### Lista (index.php)
- Paginazione: 20 record per pagina
- Filtri: testo libero (nome, cognome, CF, email, ID), anno iscrizione, flag Triangolo Rosso
- Badge colorati per ogni anno di iscrizione presente in `aned_db_iscrizioni`
- Link a view/form/delete

#### Crea e Modifica (form.php)
- Sezione **Dati Anagrafici**: nome*, cognome*, CF (max 16), data/luogo nascita, sesso, tipo utente
- Campo `tipo_utente` (`Familiare` / `Amico` / `Superstite`): se "Familiare" appaiono dinamicamente i campi `nominativo_familiare` e `campo_familiare`
- Sezione **Residenza e Contatti**: indirizzo, CAP, città, provincia, telefono, cellulare, email
- Sezione **Note e Allegati**: note libere + upload multiplo (fino a N file); lista allegati esistenti con possibilità di rimozione singola
- Checkbox: `flag_triangolo_rosso`, `attivo`
- Tutti i campi stringa vengono convertiti in **MAIUSCOLO** prima del salvataggio (eccetto `note`)
- File allegati memorizzati come JSON array in `file_allegato`

#### Gestione quote (form.php)
- Form inline per aggiungere quota: anno, data iscrizione, importo, note
- `ON DUPLICATE KEY UPDATE` → un solo record per `(iscritto_id, anno)`
- Lista storico anni con totale cumulativo

#### Eliminazione
- `delete.php`: soft delete (`attivo=0`) — solo `admin`/`direttivo`
- `delete_quota.php`: hard delete da `aned_db_iscrizioni` — `admin`/`direttivo`/`segreteria`

#### Export CSV (export.php)
- Rispetta i filtri attivi (via `buildExportQueryString()`)
- BOM UTF-8 per compatibilità Excel
- Delimitatore `;`
- CAP prefissato con `'` per preservare gli zeri iniziali in Excel

---

### 7.3 Attività ed eventi

**File:** `attivita/index.php`, `form.php`, `view.php`, `delete.php`, `pdf-preview.php`  
**Accesso creazione/modifica/eliminazione:** solo `admin`  
**Accesso visualizzazione:** tutti gli autenticati (`utente` vede solo stati `pubblicata`/`conclusa`)  
**Tabelle:** `aned_db_attivita`, `aned_db_attivita_relatori`, `aned_db_attivita_referenti`

#### Lista (index.php)
- Griglia card con anteprima locandina
- Se locandina è immagine → `<img>`; se PDF → placeholder con icona PDF
- Badge stato colorato: verde (pubblicata), giallo (bozza), rosso (annullata), grigio (conclusa)
- Filtri: testo libero, stato

#### Crea e Modifica (form.php)
- Campi: titolo, data_evento, ora_inizio, ora_fine, luogo, indirizzo_luogo, max_partecipanti (0=illimitati), descrizione, stato, upload locandina (JPG/PNG/PDF/WebP, max 5MB)
- Sezione **Relatori** (dinamica JS): array `relatore_nome[]`, `relatore_ruolo[]`, `relatore_bio[]` — pulsante "Aggiungi relatore"
- Sezione **Referenti** (dinamica JS): array `referente_nome[]`, `referente_email[]`, `referente_tel[]`
- Save: elimina i relatori/referenti esistenti per l'evento, reinserisce tutti dal POST

#### Visualizzazione (view.php)
- Card dettaglio con tutte le informazioni
- Preview locandina: `<img>` per immagini; iframe verso `pdf-preview.php?file=...` per PDF
- Lista relatori e referenti

#### PDF Preview (pdf-preview.php)
- Richiede login
- Serve il file PDF da `uploads/locandine/` con `Content-Type: application/pdf`
- Verifica che il file esista e abbia estensione `.pdf`

---

### 7.4 Consiglio direttivo

**File:** `consiglio/index.php`, `form.php`, `delete.php`  
**Accesso visualizzazione:** `admin`, `direttivo`, `segreteria`  
**Accesso creazione/modifica/eliminazione:** `admin`, `direttivo`  
**Tabella:** `aned_db_consiglio_direttivo`

**Funzionalità:**
- Lista griglia card con foto circolare (o avatar con iniziali se nessuna foto)
- Badge carica rosso
- Periodo mandato (data inizio — data fine o "In carica")
- Campi: nome, cognome, carica, email, telefono, data_inizio, data_fine, foto (upload JPG/PNG/WebP in `uploads/foto/`), ordine
- Eliminazione: soft delete (`attivo=0`)

---

### 7.5 Rubrica contatti

**File:** `rubrica/index.php`, `form.php`, `delete.php`  
**Accesso:** `admin`, `direttivo`, `segreteria`  
**Tabella:** `aned_db_rubrica`

**Funzionalità:**
- Lista paginata (20/pagina) con filtri per testo e categoria
- Categorie dinamiche da valori distinti nel DB + datalist predefinito: Istituzionale, Associazione, Media, Fornitore, Partner, Altro
- Crea/Modifica: nome, cognome, organizzazione, categoria, email, telefono, cellulare, indirizzo, città, sito_web, note
- Eliminazione: hard delete
- Export CSV con filtri (BOM UTF-8, delimitatore `;`)
- Auto-inserimento: ogni nuovo utente registrato viene automaticamente aggiunto in rubrica con categoria `Utente`

---

### 7.6 Spese

**File:** `spese/index.php`, `delete.php`  
**Accesso:** `admin`, `direttivo`  
**Tabella:** `aned_db_spese`

**Funzionalità:**
- Form inline di registrazione (data, categoria, descrizione, importo, fornitore, upload ricevuta)
- Lista filtrata per anno (pulsanti di selezione anno)
- Totale spese anno visualizzato
- Approvazione: `GET ?approva=ID` → imposta `approvata=1` e `approvata_da=$_SESSION['user_id']`
  - **TODO:** questo dovrebbe essere un POST per sicurezza
- Spese non approvate mostrano badge giallo "In attesa"
- Eliminazione: hard delete (con rimozione file ricevuta fisico)

**Categorie spese:** Amministrativa, Logistica, Comunicazione, Evento, Affitto, Forniture, Altro

---

### 7.7 Estratti conto

**File:** `estratti/index.php`, `delete.php`  
**Accesso:** `admin`, `direttivo`  
**Tabella:** `aned_db_estratti_conto`

**Funzionalità:**
- Upload estratto bancario: titolo, anno, mese (opzionale, 0=annuale), note, file
- Lista ordinata per anno DESC, mese DESC
- Download file caricato
- Eliminazione: hard delete + rimozione file fisico
- Tipi file consentiti: PDF, JPG, JPEG, PNG, XLSX, CSV

---

### 7.8 Bilanci

**File:** `bilanci/index.php`  
**Accesso visualizzazione:** tutti gli autenticati  
**Accesso upload/eliminazione:** `admin`, `direttivo`  
**Tabella:** `aned_db_bilanci`

**Funzionalità:**
- Upload documento: anno, tipo (Bilancio/Verbale/Ricevuta/Altro), note, file PDF
- Lista documenti con download PDF
- Eliminazione con rimozione file fisico

---

### 7.9 Statuto

**File:** `statuto/index.php`, `delete.php`  
**Accesso upload/eliminazione:** solo `admin`  
**Accesso visualizzazione lista:** tutti gli autenticati  
**Accesso download:** solo `admin`  
**Tabella:** `aned_db_statuto`

**Funzionalità:**
- Upload documento: titolo, descrizione, versione, data_approvazione, file (PDF/DOC/DOCX)
- Lista documenti; utenti non-admin vedono la riga ma senza link di download ("Documento disponibile — richiedi accesso agli atti")
- Eliminazione (admin): hard delete + rimozione file fisico

---

### 7.10 Note interne

**File:** `note/index.php`  
**Accesso:** tutti gli autenticati (con distinzione ownership)  
**Tabella:** `aned_db_note`

**Funzionalità:**
- Lista paginata (15/pagina) con ricerca full-text su titolo e testo nota
- Creazione e modifica tramite modal Bootstrap nella stessa pagina
- Campi: data_nota, titolo (opzionale), nota, allegati multipli (JSON array in `uploads/note/`)
- Regole di visibilità e modifica:
  - `admin`/`direttivo`/`segreteria` → vedono tutte le note e possono modificarle
  - `utente` → vede solo le proprie note (`created_by = $_SESSION['user_id']`)
- Regole di eliminazione:
  - Solo l'autore (`created_by`) o `admin` possono eliminare una nota

---

### 7.11 Triangolo Rosso

**File:** `triangolo-rosso.php`  
**Accesso:** tutti gli autenticati

**Funzionalità:**
- Embeds l'URL esterno `https://deportati.it/triangolo-rosso/` in un iframe a larghezza piena
- Il flag `flag_triangolo_rosso` su `aned_db_iscritti` identifica i soci che devono ricevere il periodico cartaceo
- La dashboard mostra il conteggio dei soci con questo flag attivo

---

### 7.12 Stampa tessera

**File:** `stampa_tessera.php`, `TesseraAnedGenerator.php`  
**Accesso:** nessun controllo di autenticazione *(bug — vedi §10)*  
**Dipendenze:** FPDF (Composer), `TesseraFronte.jpg`, `TesseraFamiliare.jpg` *(non presenti nel repo)*

**Funzionalità:**
- Genera un PDF a 2 pagine (fronte tessera + retro con info familiare) sovrimprimendo i dati su immagini di sfondo JPEG
- Parametri in input: `anno`, `sezione`, `utente` (nome socio), `rappresenta` (familiare), `campo` (luogo deportazione)
- Output: PDF inline nel browser o download
- Encoding: `mb_convert_encoding()` da UTF-8 a ISO-8859-1 per compatibilità FPDF
- Sanitizzazione filename: sostituisce caratteri non alfanumerici con `_`

---

### 7.13 Gestione utenti

**File:** `admin/utenti.php`  
**Accesso:** solo `admin`  
**Tabella:** `aned_db_utenti`

**Funzionalità:**
- Lista tutti gli utenti ordinati per ruolo, cognome, nome
- Badge "Email non verificata" per utenti con `email_verificata=0`
- **Cambio ruolo**: select inline (auto-submit `onchange`); non può modificare il proprio account
- **Attivazione/disattivazione**: toggle switch (auto-submit); non può modificare il proprio account
- **Reset password**: genera password temporanea formato `ANED_XXXXXXXX`, imposta come bcrypt hash, invia per email
- **Disabilita utente**: soft delete (`attivo=0`)
- Scheda informativa con riepilogo permessi per ciascun ruolo
- Aggiunta utente: pulsante che apre `registrazione.php` in nuova scheda (flusso standard)

---

### 7.14 Verbali *(modulo mancante)*

**Stato:** la voce è presente nella navbar (`verbali/index.php`) ma il modulo non è implementato.  
**Priorità:** alta — rappresenta una funzionalità core per un'associazione.

**Funzionalità attese:**
- Upload verbali delle assemblee e delle riunioni del consiglio
- Metadati: data, tipo riunione (assemblea ordinaria/straordinaria, riunione consiglio), oggetto, presenti
- Download file PDF
- Accesso differenziato per ruolo

---

## 8. Flussi principali

### 8.1 Iscrizione nuovo socio

```
1. Operatore (admin/direttivo/segreteria) accede a iscritti/form.php
2. Compila dati anagrafici (nome e cognome obbligatori)
3. Opzionale: specifica tipo_utente (Familiare → aggiunge nominativo e campo deportazione)
4. Salva → INSERT INTO aned_db_iscritti
5. Dalla stessa pagina (form.php?id=X), sezione "Quote":
   - Aggiunge quota per l'anno corrente con importo e data
   → INSERT INTO aned_db_iscrizioni (ON DUPLICATE KEY UPDATE)
6. Opzionale: upload allegati documentali
```

### 8.2 Rinnovo quota annuale

```
1. Operatore apre iscritti/form.php?id=X (o cerca iscritto da index)
2. Scorre alla sezione "Storico iscrizioni"
3. Inserisce anno, data iscrizione e importo nel form quota
4. Submit → ON DUPLICATE KEY UPDATE se l'anno esiste già
```

### 8.3 Creazione evento

```
1. Admin accede a attivita/form.php
2. Compila dati (titolo, data, luogo, stato=bozza)
3. Opzionale: carica locandina (JPG/PNG/PDF/WebP ≤5MB)
4. Aggiunge relatori e referenti via pulsanti dinamici JS
5. Salva → INSERT aned_db_attivita + DELETE/INSERT relatori e referenti
6. Quando pronto: modifica stato a "pubblicata" → visibile a tutti
```

### 8.4 Registrazione spesa

```
1. Admin/direttivo compila form inline in spese/index.php
2. Inserisce data, categoria, descrizione, importo, fornitore
3. Opzionale: carica ricevuta (PDF/JPG/PNG)
4. Salva → INSERT aned_db_spese (approvata=0)
5. Admin/direttivo approva via ?approva=ID
```

---

## 9. Gestione file e upload

Tutti i file caricati vengono salvati in `uploads/` con nome randomizzato (`prefisso_uniqid().estensione`). I file fisici vengono eliminati quando:
- Un allegato iscritto viene rimosso dall'elenco (`remove_files[]` nel POST)
- Un documento viene eliminato con hard delete
- Una locandina evento viene sostituita con una nuova

| Modulo | Cartella upload | Prefisso | Tipi consentiti |
|---|---|---|---|
| Iscritti allegati | `uploads/iscritti/` | `doc_` | pdf, doc, docx, jpg, jpeg, png, zip, txt |
| Locandine eventi | `uploads/locandine/` | — | jpg, jpeg, png, gif, pdf, webp (max 5MB) |
| Foto consiglio | `uploads/foto/` | — | jpg, jpeg, png, webp |
| Ricevute spese | `uploads/ricevute/` | — | pdf, jpg, jpeg, png |
| Estratti conto | `uploads/estratti/` | `estratto_` | pdf, jpg, jpeg, png, xlsx, csv |
| Bilanci | `uploads/bilanci/` | `bilancio_` | pdf |
| Statuto | `uploads/statuto/` | — | pdf, doc, docx |
| Note allegati | `uploads/note/` | `nota_` | pdf, doc, docx, jpg, jpeg, png, zip, txt |

**Protezione uploads:** il file `uploads/.htaccess` è presente ma il suo contenuto non è stato verificato. Si raccomanda di includervi almeno:
```apache
Options -Indexes
php_flag engine off
```

---

## 10. Sicurezza — stato attuale e criticità

### ✅ Implementato correttamente

| Controllo | Implementazione |
|---|---|
| Hash password | bcrypt con cost=12 tramite `password_hash()` |
| Token crittografici | `bin2hex(random_bytes(32))` |
| Prepared statements | PDO con prepared statements ovunque |
| Output escaping | `sanitize()` → `htmlspecialchars()` su tutti gli output |
| Controllo ruoli | `requireRole()` all'inizio di ogni file protetto |
| Scadenza token reset | 2 ore; verificata con `token_reset_scadenza > NOW()` |
| No user enumeration | Reset password restituisce sempre la stessa risposta |
| Log accessi | `aned_db_log_attivita` registra login/logout con IP |

### 🔴 Criticità alta

| ID | Problema | File | Raccomandazione |
|---|---|---|---|
| SEC-01 | **`stampa_tessera.php` non richiede login** | `stampa_tessera.php` | Aggiungere `requireLogin()` all'inizio del file |
| SEC-02 | **Credenziali DB in chiaro** nel codice (`root`, password VPS) | `config/database.php` | Usare variabili d'ambiente (`.env`) o file di configurazione fuori dalla document root |
| SEC-03 | **Credenziali SMTP in chiaro** nel codice | `config/mail.php` | Idem SEC-02 |

### 🟡 Criticità media

| ID | Problema | File | Raccomandazione |
|---|---|---|---|
| SEC-04 | **Nessuna protezione CSRF** sui form POST | Tutti i form | Generare e verificare un token CSRF per ogni form con effetti collaterali |
| SEC-05 | **Operazione modificante via GET** (approvazione spese) | `spese/index.php` | Convertire in POST con form e token CSRF |
| SEC-06 | **Upload: solo controllo estensione**, senza verifica MIME type reale | Tutti i moduli upload | Aggiungere `finfo_file()` per verificare il MIME type effettivo del file |

### 🟢 Punti di attenzione (bassa criticità)

| ID | Problema | Nota |
|---|---|---|
| SEC-07 | Funzione `isPreviewableMediaFile()` usata ma non definita | Causa fatal error PHP in `attivita/index.php` e `view.php` — da verificare e correggere |
| SEC-08 | Cartella `/install/` menzionata nel README come da eliminare | Verificare l'esistenza e rimuovere se presente |

---

## 11. Problemi noti e TODO

| ID | Tipo | Modulo | Descrizione | Priorità |
|---|---|---|---|---|
| BUG-01 | Bug | Attività | `isPreviewableMediaFile()` non definita → fatal error PHP | Alta |
| BUG-02 | Sicurezza | Tessera | `stampa_tessera.php` accessibile senza autenticazione | Alta |
| TODO-01 | Modulo mancante | Verbali | Il modulo verbali è nella navbar ma non implementato | Alta |
| TODO-02 | Asset mancanti | Tessera | `TesseraFronte.jpg` e `TesseraFamiliare.jpg` non presenti nel repo | Media |
| TODO-03 | Sicurezza | Globale | Assenza protezione CSRF su tutti i form | Media |
| TODO-04 | Sicurezza | Spese | Approvazione spese via GET anziché POST | Media |
| TODO-05 | Sicurezza | Globale | Credenziali DB e SMTP in chiaro nel codice | Alta |
| TODO-06 | Duplicazione | Bilanci | `includes/index.php` contiene codice della pagina Bilanci (diverso da `bilanci/index.php`) — da rimuovere o unificare | Bassa |
| TODO-07 | Sicurezza | Upload | Verifica MIME type solo da estensione — aggiungere `finfo_file()` | Media |
| TODO-08 | Funzionalità | Iscritti | Nessuna funzionalità di importazione massiva da CSV/Excel | Bassa |
| TODO-09 | UX | Attività | Non esiste un modulo iscrizioni/RSVP agli eventi | Bassa |
| TODO-10 | Feature | Spese | Non c'è un modulo per gestire il preventivo vs consuntivo | Bassa |

---

## 12. Matrici di accesso per ruolo

### 12.1 Matrice completa per modulo

| Modulo | Admin | Direttivo | Segreteria | Utente |
|---|---|---|---|---|
| Dashboard (statistiche complete) | ✅ | ✅ | ✅ | ❌ (solo prossimi eventi) |
| Iscritti — visualizzazione lista | ✅ | ✅ | ✅ | ❌ |
| Iscritti — crea/modifica | ✅ | ✅ | ✅ | ❌ |
| Iscritti — soft delete | ✅ | ✅ | ❌ | ❌ |
| Iscritti — gestione quote | ✅ | ✅ | ✅ | ❌ |
| Iscritti — export CSV | ✅ | ✅ | ✅ | ❌ |
| Attività — visualizzazione | ✅ | ✅ | ✅ | ✅ (solo pubbl./concluse) |
| Attività — crea/modifica/elimina | ✅ | ❌ | ❌ | ❌ |
| Consiglio — visualizzazione | ✅ | ✅ | ✅ | ❌ |
| Consiglio — crea/modifica/elimina | ✅ | ✅ | ❌ | ❌ |
| Rubrica — CRUD + export | ✅ | ✅ | ✅ | ❌ |
| Spese — CRUD + approvazione | ✅ | ✅ | ❌ | ❌ |
| Estratti conto — CRUD | ✅ | ✅ | ❌ | ❌ |
| Bilanci — upload/elimina | ✅ | ✅ | ❌ | ❌ |
| Bilanci — visualizzazione | ✅ | ✅ | ✅ | ✅ |
| Statuto — upload/elimina/download | ✅ | ❌ | ❌ | ❌ |
| Statuto — visualizzazione lista | ✅ | ✅ | ✅ | ✅ |
| Note — visualizzazione tutte | ✅ | ✅ | ✅ | ❌ (solo proprie) |
| Note — crea/modifica | ✅ | ✅ | ✅ | ✅ (solo proprie) |
| Note — elimina | ✅ (tutte) | ✅ (solo proprie) | ✅ (solo proprie) | ✅ (solo proprie) |
| Triangolo Rosso | ✅ | ✅ | ✅ | ✅ |
| Stampa Tessera | ✅ | ✅ | ✅ | ✅ *(e anche non autenticati — bug)* |
| Gestione Utenti | ✅ | ❌ | ❌ | ❌ |
| Profilo personale | ✅ | ✅ | ✅ | ✅ |

---

## 13. Sviluppi futuri suggeriti

I seguenti sviluppi sono consigliati in ordine di priorità:

### Priorità alta (sicurezza e completamento funzionale)

1. **Correggere BUG-01**: definire o importare correttamente `isPreviewableMediaFile()` in `config/config.php`
2. **Correggere BUG-02**: aggiungere `requireLogin()` in `stampa_tessera.php`
3. **Implementare il modulo Verbali** (`verbali/index.php`) con upload, lista e download dei verbali delle assemblee
4. **Protezione CSRF**: implementare token CSRF su tutti i form con metodo POST
5. **Spostare le credenziali** in variabili d'ambiente o file `.env` fuori dalla document root

### Priorità media (qualità e usabilità)

6. **Modulo statistiche avanzate**: grafici andamento iscrizioni, entrate/uscite per anno (es. con Chart.js)
7. **Importazione iscritti da CSV**: wizard per importazione massiva da file CSV/Excel
8. **Verifica MIME type upload**: sostituire il solo controllo estensione con `finfo_file()`
9. **Convertire approvazione spese da GET a POST**: protezione contro CSRF

### Priorità bassa (funzionalità aggiuntive)

10. **RSVP eventi**: modulo per registrazione partecipanti agli eventi con lista presenti
11. **Preventivo vs consuntivo spese**: confronto budget previsto vs spese effettive per categoria
12. **Notifiche automatiche**: promemoria rinnovo quote agli iscritti con email
13. **Audit log esteso**: tracciare tutte le operazioni CRUD (non solo login/logout) in `aned_db_log_attivita`
14. **2FA (autenticazione a due fattori)**: TOTP via app per gli account admin

---

*Documento generato analizzando il codice sorgente del progetto ANED Roma Gestionale, versione 1.0.0.*  
*Autore documento: Kiro AI — 20 Agosto 2026*
