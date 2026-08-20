<?php
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', 'ANED Roma');
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= PAGE_TITLE ?> | ANED Roma</title>
<link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.svg" type="image/svg+xml">
<!-- Bootstrap 5.3 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<!-- Custom CSS -->
<link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
<style>
  /* Forza il colore di background delle righe alternate per evitare conflitti */
  .table-striped > tbody > tr:nth-of-type(odd) > *,
  .table-striped > tbody > tr:nth-of-type(odd) {
      background-color: rgba(0, 0, 0, 0.05) !important;
  }

  /* Modifica il colore hover delle righe per renderlo in tono con l'interfaccia rossa (rosso ANED) */
  .table-hover > tbody > tr:hover > *,
  .table-hover > tbody > tr:hover {
      background-color: rgba(192, 57, 43, 0.08) !important; /* #c0392b con 8% di opacità */
  }
</style>
</head>
<body class="<?= isLogged() ? 'app-layout' : 'auth-layout' ?>">
