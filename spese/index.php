﻿<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_spesa'])) {
    $data_s  = trim($_POST['data_spesa'] ?? '');
    $categ   = trim($_POST['categoria'] ?? '');
    $descr   = trim($_POST['descrizione'] ?? '');
    $importo = floatval($_POST['importo'] ?? 0);
    $forn    = trim($_POST['fornitore'] ?? '');
    $fp      = null;

    if (!empty($_FILES['file_ricevuta']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file_ricevuta']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf','jpg','jpeg','png'])) {
            $fname = 'ricevuta_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['file_ricevuta']['tmp_name'], UPLOAD_DIR.'ricevute/'.$fname)) $fp = $fname;
        }
    }
    if ($data_s && $descr) {
        $db->prepare('INSERT INTO aned_db_spese (data_spesa,categoria,descrizione,importo,fornitore,file_ricevuta,inserito_da) VALUES (?,?,?,?,?,?,?)')
           ->execute([$data_s,$categ,$descr,$importo,$forn,$fp,$_SESSION['user_id']]);
        $_SESSION['flash_success'] = 'Spesa registrata.';
        header('Location: index.php'); exit;
    }
}

// Approva
if (isset($_GET['approva']) && hasRole('admin','direttivo')) {
    $db->prepare('UPDATE aned_db_spese SET approvata=1, approvata_da=? WHERE id=?')->execute([$_SESSION['user_id'], intval($_GET['approva'])]);
    $_SESSION['flash_success'] = 'Spesa approvata.';
    header('Location: index.php'); exit;
}

$annoFil = intval($_GET['anno'] ?? date('Y'));
$spese = $db->prepare("SELECT s.*,u.nome as ins_nome,u.cognome as ins_cognome FROM aned_db_spese s LEFT JOIN aned_db_utenti u ON u.id=s.inserito_da WHERE YEAR(s.data_spesa)=? ORDER BY s.data_spesa DESC");
$spese->execute([$annoFil]);
$spese = $spese->fetchAll();
$totale = array_sum(array_column($spese, 'importo'));

$categorie = ['Amministrativa','Logistica','Comunicazione','Evento','Affitto','Forniture','Altro'];
$anniDisp  = $db->query("SELECT DISTINCT YEAR(data_spesa) AS anno FROM aned_db_spese ORDER BY anno DESC")->fetchAll(PDO::FETCH_COLUMN);

define('PAGE_TITLE', 'Gestione Spese');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="bi bi-receipt-cutoff me-2 text-danger"></i>Gestione Spese</h1>
      <p class="page-subtitle">Totale <?= $annoFil ?>: <strong><?= formatMoney($totale) ?></strong></p>
    </div>
  </div>
  <?php flash(); flash('error'); ?>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header">Registra Spesa</div>
        <div class="card-body">
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_spesa" value="1">
            <div class="mb-3"><label class="form-label">Data *</label><input type="date" name="data_spesa" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
            <div class="mb-3"><label class="form-label">Categoria</label>
              <select name="categoria" class="form-select">
                <?php foreach ($categorie as $c): ?><option><?= $c ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3"><label class="form-label">Descrizione *</label><textarea name="descrizione" class="form-control" rows="2" required></textarea></div>
            <div class="mb-3"><label class="form-label">Importo € *</label><input type="number" step="0.01" name="importo" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Fornitore</label><input type="text" name="fornitore" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Ricevuta (PDF/immagine)</label><input type="file" name="file_ricevuta" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
            <button type="submit" class="btn btn-aned w-100"><i class="bi bi-plus-circle me-2"></i>Registra</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="d-flex gap-2 mb-3 flex-wrap">
        <?php foreach (array_unique(array_merge([date('Y')], $anniDisp)) as $a): ?>
          <a href="?anno=<?= $a ?>" class="btn btn-sm <?= $a==$annoFil?'btn-aned':'btn-outline-secondary' ?>"><?= $a ?></a>
        <?php endforeach; ?>
      </div>
      <div class="card">
        <div class="card-body p-0">
          <?php if (empty($spese)): ?>
            <div class="empty-state p-4"><i class="bi bi-receipt"></i><h5>Nessuna spesa per <?= $annoFil ?></h5></div>
          <?php else: ?>
          <table class="table table-striped table-aned mb-0">
            <thead><tr><th>Data</th><th>Categoria</th><th>Descrizione</th><th>Fornitore</th><th>Importo</th><th>Stato</th><th>Ricevuta</th><th>Azioni</th></tr></thead>
            <tbody>
              <?php foreach ($spese as $s): ?>
              <tr>
                <td><?= formatDate($s['data_spesa']) ?></td>
                <td><span class="badge bg-secondary"><?= sanitize($s['categoria']??'') ?></span></td>
                <td><?= sanitize($s['descrizione']) ?></td>
                <td><?= sanitize($s['fornitore']??'-') ?></td>
                <td><strong><?= formatMoney($s['importo']) ?></strong></td>
                <td>
                  <?php if ($s['approvata']): ?>
                    <span class="badge bg-success">Approvata</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">In attesa</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($s['file_ricevuta']): ?>
                    <a href="<?= APP_URL ?>/uploads/ricevute/<?= sanitize(basename($s['file_ricevuta'])) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-paperclip"></i></a>
                  <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                </td>
                <td>
                  <?php if (!$s['approvata']): ?>
                    <a href="?approva=<?= $s['id'] ?>" class="btn btn-sm btn-outline-success" title="Approva"><i class="bi bi-check-lg"></i></a>
                  <?php endif; ?>
                  <a href="delete.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Eliminare questa spesa?"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
              <?php endforeach; ?>
              <tr class="table-light fw-bold">
                <td colspan="4">Totale <?= $annoFil ?></td>
                <td><?= formatMoney($totale) ?></td>
                <td colspan="3"></td>
              </tr>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
