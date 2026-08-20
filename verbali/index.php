<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$canManage = hasRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    $titolo     = trim($_POST['titolo'] ?? '');
    $tipo       = $_POST['tipo'] ?? 'assemblea';
    $data_r     = trim($_POST['data_riunione'] ?? '');
    $luogo      = trim($_POST['luogo'] ?? '');
    $note       = trim($_POST['note'] ?? '');
    $visibile   = $_POST['visibile_a'] ?? 'tutti';
    $file_path  = null;

    if (!empty($_FILES['file_verbale']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file_verbale']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf','doc','docx'])) {
            $fname = 'verbale_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['file_verbale']['tmp_name'], UPLOAD_DIR.'verbali/'.$fname)) {
                $file_path = $fname;
            }
        }
    }
    if ($titolo && $data_r) {
        $db->prepare('INSERT INTO aned_db_verbali (titolo,tipo,data_riunione,luogo,note,visibile_a,file_path,inserito_da) VALUES (?,?,?,?,?,?,?,?)')
           ->execute([$titolo,$tipo,$data_r,$luogo,$note,$visibile,$file_path,$_SESSION['user_id']]);
        $_SESSION['flash_success'] = 'Verbale caricato.';
        header('Location: index.php');
        exit;
    }
}

// Filtro visibilità
$where = "1=1";
if (!hasRole('admin')) {
    if (hasRole('direttivo')) $where = "visibile_a IN ('tutti','direttivo')";
    else $where = "visibile_a = 'tutti'";
}
$verbali = $db->query("SELECT v.*,u.nome as ins_nome,u.cognome as ins_cognome FROM aned_db_verbali v LEFT JOIN aned_db_utenti u ON u.id=v.inserito_da WHERE $where ORDER BY v.data_riunione DESC")->fetchAll();

define('PAGE_TITLE', 'Verbali');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content">
  <div class="page-header">
    <h1 class="page-title"><i class="bi bi-file-earmark-text-fill me-2 text-danger"></i>Verbali Assemblee</h1>
  </div>
  <?php flash(); flash('error'); ?>

  <?php if ($canManage): ?>
  <div class="card mb-4">
    <div class="card-header">Carica Verbale</div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-5"><label class="form-label">Titolo *</label><input type="text" name="titolo" class="form-control" required></div>
          <div class="col-md-3"><label class="form-label">Tipo</label>
            <select name="tipo" class="form-select">
              <option value="assemblea">Assemblea</option>
              <option value="consiglio">Consiglio</option>
              <option value="straordinaria">Straordinaria</option>
            </select>
          </div>
          <div class="col-md-2"><label class="form-label">Data *</label><input type="date" name="data_riunione" class="form-control" required></div>
          <div class="col-md-2"><label class="form-label">Visibile a</label>
            <select name="visibile_a" class="form-select">
              <option value="tutti">Tutti</option>
              <option value="direttivo">Solo Direttivo</option>
              <option value="admin">Solo Admin</option>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Luogo</label><input type="text" name="luogo" class="form-control"></div>
          <div class="col-md-5"><label class="form-label">Note</label><input type="text" name="note" class="form-control"></div>
          <div class="col-md-3"><label class="form-label">File (PDF, DOC)</label><input type="file" name="file_verbale" class="form-control" accept=".pdf,.doc,.docx"></div>
        </div>
        <button type="submit" class="btn btn-aned mt-3"><i class="bi bi-upload me-2"></i>Carica Verbale</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($verbali)): ?>
        <div class="empty-state p-5"><i class="bi bi-file-earmark-x"></i><h5>Nessun verbale</h5></div>
      <?php else: ?>
      <table class="table table-aned mb-0">
        <thead><tr><th>Data</th><th>Tipo</th><th>Titolo</th><th>Luogo</th><th>Visibilità</th><th>File</th><th>Azioni</th></tr></thead>
        <tbody>
          <?php foreach ($verbali as $v): ?>
          <tr>
            <td><?= formatDate($v['data_riunione']) ?></td>
            <td><span class="badge bg-secondary"><?= ucfirst($v['tipo']) ?></span></td>
            <td><?= sanitize($v['titolo']) ?></td>
            <td><?= sanitize($v['luogo']??'-') ?></td>
            <td><span class="badge bg-info text-dark"><?= ucfirst($v['visibile_a']) ?></span></td>
            <td>
              <?php if ($v['file_path']): ?>
                <?php if ($canManage): ?>
                  <a href="<?= APP_URL ?>/uploads/verbali/<?= sanitize(basename($v['file_path'])) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Scarica</a>
                <?php else: ?>
                  <span class="text-muted">Documento disponibile - richiedi accesso agli atti</span>
                <?php endif; ?>
              <?php else: ?><span class="text-muted">-</span><?php endif; ?>
            </td>
            <td>
              <?php if ($canManage): ?>
              <a href="delete.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Eliminare?"><i class="bi bi-trash"></i></a>
              <?php endif; ?>
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

