<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$canManage = hasRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    $titolo     = trim($_POST['titolo'] ?? '');
    $descr      = trim($_POST['descrizione'] ?? '');
    $versione   = trim($_POST['versione'] ?? '');
    $data_appr  = trim($_POST['data_approvazione'] ?? '') ?: null;
    $file_path  = null;

    if (!empty($_FILES['file_statuto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file_statuto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf','doc','docx'])) {
            $fname = 'statuto_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['file_statuto']['tmp_name'], UPLOAD_DIR.'statuto/'.$fname)) {
                $file_path = $fname;
            }
        }
    }
    if ($titolo) {
        $db->prepare('INSERT INTO aned_db_statuto (titolo,descrizione,versione,data_approvazione,file_path,inserito_da) VALUES (?,?,?,?,?,?)')
           ->execute([$titolo,$descr,$versione,$data_appr,$file_path,$_SESSION['user_id']]);
        $_SESSION['flash_success'] = 'Documento statuto caricato.';
        header('Location: index.php');
        exit;
    }
}

$documenti = $db->query("SELECT s.*,u.nome as ins_nome,u.cognome as ins_cognome FROM aned_db_statuto s LEFT JOIN aned_db_utenti u ON u.id=s.inserito_da ORDER BY s.versione DESC")->fetchAll();

define('PAGE_TITLE', 'Statuto');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content">
  <div class="page-header">
    <h1 class="page-title"><i class="bi bi-journal-bookmark-fill me-2 text-danger"></i>Statuto</h1>
  </div>
  <?php flash(); flash('error'); ?>

  <?php if ($canManage): ?>
  <div class="card mb-4">
    <div class="card-header">Carica Documento Statuto</div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-5"><label class="form-label">Titolo *</label><input type="text" name="titolo" class="form-control" required></div>
          <div class="col-md-3"><label class="form-label">Versione</label><input type="text" name="versione" class="form-control" placeholder="es. 2024"></div>
          <div class="col-md-4"><label class="form-label">Data Approvazione</label><input type="date" name="data_approvazione" class="form-control"></div>
          <div class="col-12"><label class="form-label">Descrizione</label><textarea name="descrizione" class="form-control" rows="2"></textarea></div>
          <div class="col-12"><label class="form-label">File (PDF, DOC)</label><input type="file" name="file_statuto" class="form-control" accept=".pdf,.doc,.docx"></div>
        </div>
        <button type="submit" class="btn btn-aned mt-3"><i class="bi bi-upload me-2"></i>Carica</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($documenti)): ?>
        <div class="empty-state p-5"><i class="bi bi-file-earmark-text"></i><h5>Nessun documento</h5></div>
      <?php else: ?>
      <table class="table table-striped table-hover table-aned mb-0">
        <thead><tr><th>Titolo</th><th>Versione</th><th>Data Approvazione</th><th>Caricato da</th><th>File</th><th>Azioni</th></tr></thead>
        <tbody>
          <?php foreach ($documenti as $d): ?>
          <tr>
            <td><strong><?= sanitize($d['titolo']) ?></strong><?php if ($d['descrizione']): ?><br><small class="text-muted"><?= sanitize($d['descrizione']) ?></small><?php endif; ?></td>
            <td><?= sanitize($d['versione']??'-') ?></td>
            <td><?= formatDate($d['data_approvazione']) ?></td>
            <td><?= sanitize(($d['ins_nome']??'').' '.($d['ins_cognome']??'')) ?></td>
            <td>
              <?php if ($d['file_path']): ?>
                <?php if ($canManage): ?>
                  <a href="<?= APP_URL ?>/uploads/statuto/<?= sanitize(basename($d['file_path'])) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i>Scarica
                  </a>
                <?php else: ?>
                  <span class="text-muted">Documento disponibile - richiedi accesso agli atti</span>
                <?php endif; ?>
              <?php else: ?><span class="text-muted">-</span><?php endif; ?>
            </td>
            <td>
              <?php if ($canManage): ?>
              <a href="delete.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Eliminare?"><i class="bi bi-trash"></i></a>
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
