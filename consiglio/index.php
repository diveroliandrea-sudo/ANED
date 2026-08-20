<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo','segreteria');

$db = getDB();
$membri = $db->query("SELECT * FROM aned_db_consiglio_direttivo WHERE attivo=1 ORDER BY ordine, cognome")->fetchAll();

define('PAGE_TITLE', 'Consiglio Direttivo');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="bi bi-person-badge-fill me-2 text-danger"></i>Consiglio Direttivo</h1>
    </div>
    <?php if (hasRole('admin','direttivo')): ?>
    <a href="form.php" class="btn btn-aned"><i class="bi bi-plus-circle me-2"></i>Aggiungi Membro</a>
    <?php endif; ?>
  </div>

  <?php flash(); flash('error'); ?>

  <?php if (empty($membri)): ?>
    <div class="empty-state card p-5"><i class="bi bi-people"></i><h5>Nessun membro inserito</h5></div>
  <?php else: ?>
  <div class="row g-4">
    <?php foreach ($membri as $m): ?>
    <div class="col-md-6 col-xl-4">
      <div class="card h-100 text-center p-4">
        <?php if ($m['foto'] && file_exists(UPLOAD_DIR.'foto/'.basename($m['foto']))): ?>
          <img src="<?= APP_URL ?>/uploads/foto/<?= sanitize(basename($m['foto'])) ?>"
               class="rounded-circle mx-auto mb-3" style="width:80px;height:80px;object-fit:cover">
        <?php else: ?>
          <div class="member-avatar mx-auto mb-3" style="width:70px;height:70px;font-size:24px">
            <?= strtoupper(substr($m['nome'],0,1).substr($m['cognome'],0,1)) ?>
          </div>
        <?php endif; ?>
        <h5 class="mb-0"><?= sanitize($m['nome'].' '.$m['cognome']) ?></h5>
        <div class="badge bg-danger mt-1 mb-2"><?= sanitize($m['carica']) ?></div>
        <?php if ($m['email']): ?><p class="text-muted mb-1" style="font-size:13px"><i class="bi bi-envelope me-1"></i><a href="mailto:<?= sanitize($m['email']) ?>"><?= sanitize($m['email']) ?></a></p><?php endif; ?>
        <?php if ($m['telefono']): ?><p class="text-muted mb-0" style="font-size:13px"><i class="bi bi-telephone me-1"></i><?= sanitize($m['telefono']) ?></p><?php endif; ?>
        <?php if ($m['data_inizio'] || $m['data_fine']): ?>
          <small class="text-muted d-block mt-2"><?= formatDate($m['data_inizio']) ?><?= $m['data_fine']?' → '.formatDate($m['data_fine']):' → in carica' ?></small>
        <?php endif; ?>
        <?php if (hasRole('admin','direttivo')): ?>
        <div class="mt-3 d-flex gap-2 justify-content-center">
          <a href="form.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Modifica</a>
          <a href="delete.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Rimuovere questo membro?"><i class="bi bi-trash"></i></a>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

