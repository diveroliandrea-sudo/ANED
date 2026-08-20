﻿<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titolo  = trim($_POST['titolo'] ?? '');
    $anno    = intval($_POST['anno'] ?? date('Y'));
    $mese    = intval($_POST['mese'] ?? 0) ?: null;
    $note    = trim($_POST['note'] ?? '');
    $fp      = null;

    if (!empty($_FILES['file_estratto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file_estratto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf','jpg','jpeg','png','xlsx','csv'])) {
            $fname = 'estratto_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['file_estratto']['tmp_name'], UPLOAD_DIR.'estratti/'.$fname)) $fp = $fname;
        }
    }
    if ($titolo) {
        $db->prepare('INSERT INTO aned_db_estratti_conto (titolo,anno,mese,note,file_path,inserito_da) VALUES (?,?,?,?,?,?)')
           ->execute([$titolo,$anno,$mese,$note,$fp,$_SESSION['user_id']]);
        $_SESSION['flash_success'] = 'Estratto caricato.';
        header('Location: index.php'); exit;
    }
}

$estratti = $db->query("SELECT e.*,u.nome as ins_nome,u.cognome as ins_cognome FROM aned_db_estratti_conto e LEFT JOIN aned_db_utenti u ON u.id=e.inserito_da ORDER BY e.anno DESC, e.mese DESC")->fetchAll();
$mesiNomi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];

define('PAGE_TITLE', 'Estratti Conto');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content">
  <div class="page-header">
    <h1 class="page-title"><i class="bi bi-bank2 me-2 text-danger"></i>Estratti Conto</h1>
  </div>
  <?php flash(); flash('error'); ?>

  <div class="card mb-4">
    <div class="card-header">Deposita Estratto Conto</div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Titolo *</label><input type="text" name="titolo" class="form-control" required></div>
          <div class="col-md-2"><label class="form-label">Anno</label><input type="number" name="anno" class="form-control" value="<?= date('Y') ?>" min="2000" max="2099"></div>
          <div class="col-md-3"><label class="form-label">Mese</label>
            <select name="mese" class="form-select">
              <option value="0">Annuale</option>
              <?php for($i=1;$i<=12;$i++): ?><option value="<?= $i ?>"><?= $mesiNomi[$i] ?></option><?php endfor; ?>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">File</label><input type="file" name="file_estratto" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.xlsx,.csv"></div>
          <div class="col-12"><label class="form-label">Note</label><input type="text" name="note" class="form-control"></div>
        </div>
        <button type="submit" class="btn btn-aned mt-3"><i class="bi bi-upload me-2"></i>Deposita</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($estratti)): ?>
        <div class="empty-state p-5"><i class="bi bi-bank"></i><h5>Nessun estratto</h5></div>
      <?php else: ?>
      <table class="table table-striped table-hover table-aned mb-0">
        <thead><tr><th>Anno</th><th>Mese</th><th>Titolo</th><th>Note</th><th>Caricato da</th><th>File</th><th>Azioni</th></tr></thead>
        <tbody>
          <?php foreach ($estratti as $e): ?>
          <tr>
            <td><strong><?= $e['anno'] ?></strong></td>
            <td><?= $e['mese'] ? $mesiNomi[$e['mese']] : 'Annuale' ?></td>
            <td><?= sanitize($e['titolo']) ?></td>
            <td><?= sanitize($e['note']??'') ?></td>
            <td><?= sanitize(($e['ins_nome']??'').' '.($e['ins_cognome']??'')) ?></td>
            <td>
              <?php if ($e['file_path']): ?>
                <a href="<?= APP_URL ?>/uploads/estratti/<?= sanitize(basename($e['file_path'])) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Scarica</a>
              <?php else: ?><span class="text-muted">-</span><?php endif; ?>
            </td>
            <td>
              <a href="delete.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Eliminare?"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
