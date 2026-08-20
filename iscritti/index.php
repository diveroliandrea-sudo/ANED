﻿<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo','segreteria');

$db = getDB();

$search   = trim($_GET['q'] ?? '');
$annoFil  = intval($_GET['anno'] ?? 0);
$trFilter = isset($_GET['tr']) ? 1 : null;
$page     = max(1, intval($_GET['page'] ?? 1));
$perPage  = 20;
$offset   = ($page - 1) * $perPage;

$where = ['i.attivo = 1'];
$params = [];

if ($search) {
    $where[] = '(i.nome LIKE ? OR i.cognome LIKE ? OR i.codice_fiscale LIKE ? OR i.email LIKE ? OR i.id LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s,$s,$s]);
}
if ($annoFil > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM aned_db_iscrizioni iz WHERE iz.iscritto_id=i.id AND iz.anno=?)';
    $params[] = $annoFil;
}
if ($trFilter !== null) {
    $where[] = 'i.flag_triangolo_rosso = 1';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$exportQuery = buildExportQueryString([
    'q' => $search,
    'anno' => $annoFil,
    'tr' => $trFilter !== null ? 1 : null,
]);

$total = $db->prepare("SELECT COUNT(*) FROM aned_db_iscritti i $whereSQL");
$total->execute($params);
$total = $total->fetchColumn();
$pages = max(1, ceil($total / $perPage));

$stmt = $db->prepare("SELECT i.*, 
    (SELECT GROUP_CONCAT(iz.anno ORDER BY iz.anno DESC) FROM aned_db_iscrizioni iz WHERE iz.iscritto_id=i.id) AS anni_iscrizione
    FROM aned_db_iscritti i $whereSQL ORDER BY i.cognome, i.nome LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$iscritti = $stmt->fetchAll();

// Anni disponibili per filtro
$anniDisp = $db->query("SELECT DISTINCT anno FROM aned_db_iscrizioni ORDER BY anno DESC")->fetchAll(PDO::FETCH_COLUMN);

define('PAGE_TITLE', 'Iscritti');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="bi bi-people-fill me-2 text-danger"></i>Gestione Iscritti</h1>
      <p class="page-subtitle"><?= $total ?> iscritti trovati</p>
    </div>
    <?php if (hasRole('admin','direttivo','segreteria')): ?>
    <div class="d-flex gap-2">
      <a href="<?= APP_URL ?>/iscritti/form.php" class="btn btn-aned">
        <i class="bi bi-person-plus-fill me-2"></i>Nuovo Iscritto
      </a>
      <a href="<?= APP_URL ?>/iscritti/export.php<?= $exportQuery ?>" class="btn btn-outline-secondary">
        <i class="bi bi-download me-2"></i>Esporta CSV
      </a>
    </div>
    <?php endif; ?>
  </div>

  <?php flash(); flash('error'); ?>

  <!-- Filtri -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-5">
          <label class="form-label">Cerca iscritto</label>
          <div class="search-bar">
            <i class="bi bi-search"></i>
            <input type="text" name="q" class="form-control" placeholder="Nome, cognome, CF, email..." value="<?= sanitize($search) ?>">
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Filtra per anno iscrizione</label>
          <select name="anno" class="form-select">
            <option value="0">Tutti gli anni</option>
            <?php foreach ($anniDisp as $a): ?>
              <option value="<?= $a ?>" <?= $annoFil==$a?'selected':'' ?>><?= $a ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" name="tr" id="trCheck" <?= $trFilter?'checked':'' ?>>
            <label class="form-check-label" for="trCheck">
              <i class="bi bi-triangle-fill flag-tr"></i> Solo Triangolo Rosso
            </label>
          </div>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-aned w-100">Filtra</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabella -->
  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($iscritti)): ?>
        <div class="empty-state">
          <i class="bi bi-people"></i>
          <h5>Nessun iscritto trovato</h5>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-aned mb-0">
          <thead>
            <tr>
              <th>Codice</th>
              <th>Nominativo</th>
              <th>Codice Fiscale</th>
              <th>Città</th>
              <th>Email</th>
              <th>Anni Iscrizione</th>
              <th class="text-center">TR</th>
              <th>Azioni</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($iscritti as $i): ?>
            <tr>
              <td><code><?= intval($i['id']) ?></code></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="member-avatar" style="width:34px;height:34px;font-size:12px;flex-shrink:0">
                    <?= strtoupper(substr($i['nome'],0,1).substr($i['cognome'],0,1)) ?>
                  </div>
                  <div>
                    <div class="fw-600"><?= sanitize($i['cognome'].' '.$i['nome']) ?></div>
                    <?php if ($i['telefono'] || $i['cellulare']): ?>
                      <small class="text-muted"><?= sanitize($i['cellulare'] ?: $i['telefono']) ?></small>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td><code><?= sanitize($i['codice_fiscale'] ?? '-') ?></code></td>
              <td><?= sanitize($i['citta'] ?? '-') ?></td>
              <td><?= sanitize($i['email'] ?? '-') ?></td>
              <td>
                <?php if ($i['anni_iscrizione']): ?>
                  <?php foreach (explode(',', $i['anni_iscrizione']) as $anno): ?>
                    <span class="badge bg-secondary me-1"><?= $anno ?></span>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ($i['flag_triangolo_rosso']): ?>
                  <i class="bi bi-triangle-fill flag-tr" title="Triangolo Rosso" data-bs-toggle="tooltip"></i>
                <?php else: ?>
                  <i class="bi bi-triangle text-muted" title="No" data-bs-toggle="tooltip"></i>
                <?php endif; ?>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <a href="<?= APP_URL ?>/iscritti/view.php?id=<?= $i['id'] ?>" class="btn btn-sm btn-outline-primary" title="Visualizza"><i class="bi bi-eye"></i></a>
                  <?php if (hasRole('admin','direttivo','segreteria')): ?>
                  <a href="<?= APP_URL ?>/iscritti/form.php?id=<?= $i['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifica"><i class="bi bi-pencil"></i></a>
                  <a href="<?= APP_URL ?>/iscritti/delete.php?id=<?= $i['id'] ?>" class="btn btn-sm btn-outline-danger" title="Elimina" data-confirm="Eliminare questo iscritto?"><i class="bi bi-trash"></i></a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($pages > 1): ?>
    <div class="card-footer">
      <nav>
        <ul class="pagination mb-0 justify-content-center">
          <?php for ($p=1; $p<=$pages; $p++): ?>
            <li class="page-item <?= $p==$page?'active':'' ?>">
              <a class="page-link" href="?q=<?= urlencode($search) ?>&anno=<?= $annoFil ?>&page=<?= $p ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
