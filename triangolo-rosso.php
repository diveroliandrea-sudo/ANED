<?php
require_once __DIR__ . '/config/config.php';
requireRole('admin', 'direttivo', 'segreteria', 'utente');

define('PAGE_TITLE', 'Triangolo Rosso');
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="bi bi-triangle-fill me-2 text-danger"></i>Triangolo Rosso</h1>
      <p class="page-subtitle">Visualizza la pagina esterna mantenendo il menu di navigazione.</p>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <iframe
        src="https://deportati.it/triangolo-rosso/"
        title="Triangolo Rosso"
        style="width:100%; min-height:85vh; border:0;"
      ></iframe>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
