<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$search = trim($_GET['q'] ?? '');
$stato  = $_GET['stato'] ?? '';
$page   = max(1, intval($_GET['page'] ?? 1));
$perPage= 12;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = '(a.titolo LIKE ? OR a.luogo LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s,$s]);
}
if ($stato) {
    $where[] = 'a.stato = ?';
    $params[] = $stato;
} elseif (!hasRole('admin')) {
    $where[] = "a.stato IN ('pubblicata','conclusa')";
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);
$total = $db->prepare("SELECT COUNT(*) FROM aned_db_attivita a $whereSQL");
$total->execute($params);
$total = $total->fetchColumn();
$pages = max(1, ceil($total / $perPage));

$stmt = $db->prepare("SELECT a.* FROM aned_db_attivita a $whereSQL ORDER BY a.data_evento DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$eventi = $stmt->fetchAll();

define('PAGE_TITLE', 'Attività ed Eventi');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="bi bi-calendar-event-fill me-2 text-danger"></i>Attività ed Eventi</h1>
      <p class="page-subtitle"><?= $total ?> eventi</p>
    </div>
    <?php if (hasRole('admin','direttivo','segreteria')): ?>
    <a href="form.php" class="btn btn-aned"><i class="bi bi-plus-circle me-2"></i>Nuova Attività</a>
    <?php endif; ?>
  </div>

  <?php flash(); flash('error'); ?>

  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-6">
          <div class="search-bar">
            <i class="bi bi-search"></i>
            <input type="text" name="q" class="form-control" placeholder="Cerca evento..." value="<?= sanitize($search) ?>">
          </div>
        </div>
        <div class="col-md-3">
          <select name="stato" class="form-select">
            <option value="">Tutti gli stati</option>
            <option value="bozza" <?= $stato==='bozza'?'selected':'' ?>>Bozza</option>
            <option value="pubblicata" <?= $stato==='pubblicata'?'selected':'' ?>>Pubblicata</option>
            <option value="annullata" <?= $stato==='annullata'?'selected':'' ?>>Annullata</option>
            <option value="conclusa" <?= $stato==='conclusa'?'selected':'' ?>>Conclusa</option>
          </select>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-aned w-100">Filtra</button>
        </div>
      </form>
    </div>
  </div>

  <?php if (empty($eventi)): ?>
    <div class="empty-state card p-5">
      <i class="bi bi-calendar-x"></i>
      <h5>Nessuna attività trovata</h5>
    </div>
  <?php else: ?>
  <div class="row g-4">
    <?php foreach ($eventi as $ev): ?>
    <div class="col-md-6 col-xl-4">
      <div class="card h-100" style="border-top:3px solid var(--aned-red)">
        <?php if ($ev['locandina'] && isPreviewableMediaFile($ev['locandina'])): ?>
          <?php $locandinaUrl = APP_URL . '/uploads/locandine/' . rawurlencode(basename($ev['locandina'])); ?>
          <?php $locandinaExt = strtolower(pathinfo($ev['locandina'], PATHINFO_EXTENSION)); ?>
          <?php if (in_array($locandinaExt, ['jpg','jpeg','png','webp','gif'], true)): ?>
            <img src="<?= $locandinaUrl ?>"
                 class="card-img-top" style="height:160px;object-fit:cover" alt="Locandina">
          <?php else: ?>
            <div style="height:160px;background:#f8f9fa;border-bottom:1px solid #e9ecef;display:flex;align-items:center;justify-content:center;flex-direction:column;padding:16px;text-align:center">
              <i class="bi bi-file-earmark-pdf-fill text-danger" style="font-size:32px"></i>
              <div class="mt-2 fw-600">Anteprima PDF</div>
              <small class="text-muted">Clicca su Dettagli per aprire il PDF</small>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div style="height:80px;background:linear-gradient(135deg,var(--aned-dark),var(--aned-accent));display:flex;align-items:center;justify-content:center">
            <i class="bi bi-calendar-event text-white" style="font-size:32px;opacity:.6"></i>
          </div>
        <?php endif; ?>
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="badge-stato badge-<?= $ev['stato'] ?>"><?= ucfirst($ev['stato']) ?></span>
            <small class="text-muted"><?= formatDate($ev['data_evento']) ?></small>
          </div>
          <h5 class="card-title mb-1" style="font-size:16px"><?= sanitize($ev['titolo']) ?></h5>
          <?php if ($ev['luogo']): ?>
            <p class="text-muted mb-0" style="font-size:13px"><i class="bi bi-geo-alt me-1"></i><?= sanitize($ev['luogo']) ?></p>
          <?php endif; ?>
          <?php if ($ev['ora_inizio']): ?>
            <p class="text-muted mb-0" style="font-size:13px"><i class="bi bi-clock me-1"></i><?= sanitize($ev['ora_inizio']) ?><?= $ev['ora_fine']?' - '.sanitize($ev['ora_fine']):'' ?></p>
          <?php endif; ?>
        </div>
        <div class="card-footer bg-transparent d-flex gap-2">
          <a href="view.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-aned flex-1">Dettagli</a>
          <?php if (hasRole('admin')): ?>
          <a href="form.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
          <a href="delete.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Eliminare questo evento?"><i class="bi bi-trash"></i></a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($pages > 1): ?>
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <?php for ($p=1; $p<=$pages; $p++): ?>
        <li class="page-item <?= $p==$page?'active':'' ?>">
          <a class="page-link" href="?q=<?= urlencode($search) ?>&stato=<?= $stato ?>&page=<?= $p ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

