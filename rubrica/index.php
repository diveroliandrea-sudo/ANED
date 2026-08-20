﻿<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo','segreteria');

$db = getDB();
$search = trim($_GET['q'] ?? '');
$cat    = trim($_GET['cat'] ?? '');
$page   = max(1, intval($_GET['page'] ?? 1));
$perPage = 20; $offset = ($page-1)*$perPage;

$where = ['1=1']; $params = [];
if ($search) {
    $where[] = '(nome LIKE ? OR cognome LIKE ? OR organizzazione LIKE ? OR email LIKE ? OR telefono LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s,$s,$s]);
}
if ($cat) { $where[] = 'categoria=?'; $params[] = $cat; }
$whereSQL = 'WHERE '.implode(' AND ',$where);

if (isset($_GET['export']) && $_GET['export'] == '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rubrica_contatti_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    // Aggiungo il BOM UTF-8 per la corretta lettura dei caratteri speciali in Excel
    fputs($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Cognome', 'Nome', 'Organizzazione', 'Categoria', 'Email', 'Telefono', 'Cellulare', 'Indirizzo', 'Città', 'Sito Web', 'Note'], ';', '"', '\\');
    
    $stmtExport = $db->prepare("SELECT * FROM aned_db_rubrica $whereSQL ORDER BY cognome ASC, nome ASC");
    $stmtExport->execute($params);
    while ($row = $stmtExport->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['cognome'],
            $row['nome'],
            $row['organizzazione'],
            $row['categoria'],
            $row['email'],
            $row['telefono'],
            $row['cellulare'],
            $row['indirizzo'],
            $row['citta'],
            $row['sito_web'],
            $row['note']
        ], ';', '"', '\\');
    }
    fclose($output);
    exit;
}

$total = $db->prepare("SELECT COUNT(*) FROM aned_db_rubrica $whereSQL");
$total->execute($params); $total = $total->fetchColumn();
$pages = max(1, ceil($total/$perPage));

$stmt = $db->prepare("SELECT * FROM aned_db_rubrica $whereSQL ORDER BY cognome ASC, nome ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$contatti = $stmt->fetchAll();

$categorie = $db->query("SELECT DISTINCT categoria FROM aned_db_rubrica WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);

define('PAGE_TITLE', 'Rubrica Contatti');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="bi bi-telephone-fill me-2 text-danger"></i>Rubrica Contatti</h1>
      <p class="page-subtitle"><?= $total ?> contatti</p>
    </div>
    <div class="d-flex gap-2">
      <a href="?export=1&q=<?= urlencode($search) ?>&cat=<?= urlencode($cat) ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-2"></i>Esporta in Excel</a>
      <a href="form.php" class="btn btn-aned"><i class="bi bi-person-plus-fill me-2"></i>Nuovo Contatto</a>
    </div>
  </div>
  <?php flash(); flash('error'); ?>

  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-6">
          <div class="search-bar"><i class="bi bi-search"></i>
            <input type="text" name="q" class="form-control" placeholder="Cerca..." value="<?= sanitize($search) ?>">
          </div>
        </div>
        <div class="col-md-3">
          <select name="cat" class="form-select">
            <option value="">Tutte le categorie</option>
            <?php foreach ($categorie as $c): ?><option value="<?= sanitize($c) ?>" <?= $cat===$c?'selected':'' ?>><?= sanitize($c) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3"><button type="submit" class="btn btn-aned w-100">Filtra</button></div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($contatti)): ?>
        <div class="empty-state p-5"><i class="bi bi-person-x"></i><h5>Nessun contatto trovato</h5></div>
      <?php else: ?>
      <table class="table table-striped table-aned mb-0">
        <thead><tr><th>Cognome e Nome</th><th>Organizzazione</th><th>Categoria</th><th>Email</th><th>Telefono</th><th>Azioni</th></tr></thead>
        <tbody>
          <?php foreach ($contatti as $c): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="member-avatar" style="width:34px;height:34px;font-size:12px">
                  <?= strtoupper(substr($c['nome'],0,1).substr($c['cognome']??'',0,1)) ?>
                </div>
                <div>
                  <div class="fw-600"><?= sanitize(trim(($c['cognome'] ?? '') . ' ' . ($c['nome'] ?? ''))) ?></div>
                  <?php if ($c['citta']): ?><small class="text-muted"><?= sanitize($c['citta']) ?></small><?php endif; ?>
                </div>
              </div>
            </td>
            <td><?= sanitize($c['organizzazione']??'-') ?></td>
            <td><?= $c['categoria']?'<span class="badge bg-secondary">'.sanitize($c['categoria']).'</span>':'-' ?></td>
            <td><?= $c['email']?'<a href="mailto:'.sanitize($c['email']).'">'.sanitize($c['email']).'</a>':'-' ?></td>
            <td><?= sanitize($c['cellulare']?:($c['telefono']??'-')) ?></td>
            <td>
              <a href="form.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
              <a href="delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Eliminare?"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <?php if ($pages > 1): ?>
    <div class="card-footer">
      <nav><ul class="pagination mb-0 justify-content-center">
        <?php for($p=1;$p<=$pages;$p++): ?>
          <li class="page-item <?= $p==$page?'active':'' ?>"><a class="page-link" href="?q=<?= urlencode($search) ?>&cat=<?= urlencode($cat) ?>&page=<?= $p ?>"><?= $p ?></a></li>
        <?php endfor; ?>
      </ul></nav>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
