<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo');

$db = getDB();
$id = intval($_GET['id'] ?? 0);
$m = null;

if ($id) {
    $stmt = $db->prepare('SELECT * FROM aned_db_consiglio_direttivo WHERE id=?');
    $stmt->execute([$id]);
    $m = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome       = trim($_POST['nome'] ?? '');
    $cognome    = trim($_POST['cognome'] ?? '');
    $carica     = trim($_POST['carica'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $telefono   = trim($_POST['telefono'] ?? '');
    $data_ini   = trim($_POST['data_inizio'] ?? '') ?: null;
    $data_fine  = trim($_POST['data_fine'] ?? '') ?: null;
    $ordine     = intval($_POST['ordine'] ?? 0);

    $foto_path = $m['foto'] ?? null;
    if (!empty($_FILES['foto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $fname = 'foto_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], UPLOAD_DIR.'foto/'.$fname)) {
                if ($foto_path) @unlink(UPLOAD_DIR.'foto/'.basename($foto_path));
                $foto_path = $fname;
            }
        }
    }

    if ($id) {
        $db->prepare('UPDATE aned_db_consiglio_direttivo SET nome=?,cognome=?,carica=?,email=?,telefono=?,data_inizio=?,data_fine=?,foto=?,ordine=? WHERE id=?')
           ->execute([$nome,$cognome,$carica,$email,$telefono,$data_ini,$data_fine,$foto_path,$ordine,$id]);
    } else {
        $db->prepare('INSERT INTO aned_db_consiglio_direttivo (nome,cognome,carica,email,telefono,data_inizio,data_fine,foto,ordine) VALUES (?,?,?,?,?,?,?,?,?)')
           ->execute([$nome,$cognome,$carica,$email,$telefono,$data_ini,$data_fine,$foto_path,$ordine]);
    }
    $_SESSION['flash_success'] = 'Membro salvato.';
    header('Location: index.php');
    exit;
}

define('PAGE_TITLE', $id ? 'Modifica Membro' : 'Nuovo Membro');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content">
  <div class="page-header">
    <h1 class="page-title"><?= $id ? 'Modifica Membro' : 'Nuovo Membro' ?></h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
  </div>
  <div class="card" style="max-width:640px">
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Nome *</label><input type="text" name="nome" class="form-control" value="<?= sanitize($m['nome']??'') ?>" required></div>
          <div class="col-md-6"><label class="form-label">Cognome *</label><input type="text" name="cognome" class="form-control" value="<?= sanitize($m['cognome']??'') ?>" required></div>
          <div class="col-md-8"><label class="form-label">Carica *</label><input type="text" name="carica" class="form-control" value="<?= sanitize($m['carica']??'') ?>" required></div>
          <div class="col-md-4"><label class="form-label">Ordine</label><input type="number" name="ordine" class="form-control" value="<?= intval($m['ordine']??0) ?>"></div>
          <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= sanitize($m['email']??'') ?>"></div>
          <div class="col-md-6"><label class="form-label">Telefono</label><input type="tel" name="telefono" class="form-control" value="<?= sanitize($m['telefono']??'') ?>"></div>
          <div class="col-md-6"><label class="form-label">Data Inizio</label><input type="date" name="data_inizio" class="form-control" value="<?= sanitize($m['data_inizio']??'') ?>"></div>
          <div class="col-md-6"><label class="form-label">Data Fine</label><input type="date" name="data_fine" class="form-control" value="<?= sanitize($m['data_fine']??'') ?>"></div>
          <div class="col-12">
            <label class="form-label">Foto</label>
            <?php if (!empty($m['foto'])): ?>
              <div class="mb-2"><img src="<?= APP_URL ?>/uploads/foto/<?= sanitize(basename($m['foto'])) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:50%"></div>
            <?php endif; ?>
            <input type="file" name="foto" class="form-control" accept="image/*">
          </div>
        </div>
        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-aned"><i class="bi bi-save me-2"></i>Salva</button>
          <a href="index.php" class="btn btn-outline-secondary">Annulla</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

