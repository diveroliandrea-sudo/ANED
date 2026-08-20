<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo','segreteria');

$db = getDB();
$id = intval($_GET['id'] ?? 0);
$c = null;

if ($id) {
    $stmt = $db->prepare('SELECT * FROM aned_db_rubrica WHERE id=?');
    $stmt->execute([$id]);
    $c = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['nome','cognome','organizzazione','categoria','email','telefono','cellulare','indirizzo','citta','sito_web','note'];
    $data = [];
    foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '') ?: null;
    if ($data['nome']) {
        if ($id) {
            $sets = implode(',', array_map(fn($f)=>"$f=:$f", $fields));
            $data['id'] = $id;
            $db->prepare("UPDATE aned_db_rubrica SET $sets WHERE id=:id")->execute($data);
        } else {
            $cols = implode(',', $fields) . ',created_by';
            $vals = ':'.implode(',:', $fields).',:created_by';
            $data['created_by'] = $_SESSION['user_id'];
            $db->prepare("INSERT INTO aned_db_rubrica ($cols) VALUES ($vals)")->execute($data);
        }
        $_SESSION['flash_success'] = 'Contatto salvato.';
        header('Location: index.php'); exit;
    }
}

define('PAGE_TITLE', $id ? 'Modifica Contatto' : 'Nuovo Contatto');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content">
  <div class="page-header">
    <h1 class="page-title"><?= $id ? 'Modifica Contatto' : 'Nuovo Contatto' ?></h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
  </div>
  <div class="card" style="max-width:700px">
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Nome *</label><input type="text" name="nome" class="form-control" value="<?= sanitize($c['nome']??'') ?>" required></div>
          <div class="col-md-6"><label class="form-label">Cognome</label><input type="text" name="cognome" class="form-control" value="<?= sanitize($c['cognome']??'') ?>"></div>
          <div class="col-md-6"><label class="form-label">Organizzazione</label><input type="text" name="organizzazione" class="form-control" value="<?= sanitize($c['organizzazione']??'') ?>"></div>
          <div class="col-md-6"><label class="form-label">Categoria</label><input type="text" name="categoria" class="form-control" list="cats" value="<?= sanitize($c['categoria']??'') ?>">
            <datalist id="cats"><option>Istituzionale</option><option>Associazione</option><option>Media</option><option>Fornitore</option><option>Partner</option><option>Altro</option></datalist>
          </div>
          <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= sanitize($c['email']??'') ?>"></div>
          <div class="col-md-3"><label class="form-label">Telefono</label><input type="tel" name="telefono" class="form-control" value="<?= sanitize($c['telefono']??'') ?>"></div>
          <div class="col-md-3"><label class="form-label">Cellulare</label><input type="tel" name="cellulare" class="form-control" value="<?= sanitize($c['cellulare']??'') ?>"></div>
          <div class="col-md-8"><label class="form-label">Indirizzo</label><input type="text" name="indirizzo" class="form-control" value="<?= sanitize($c['indirizzo']??'') ?>"></div>
          <div class="col-md-4"><label class="form-label">Città</label><input type="text" name="citta" class="form-control" value="<?= sanitize($c['citta']??'') ?>"></div>
          <div class="col-md-6"><label class="form-label">Sito Web</label><input type="url" name="sito_web" class="form-control" value="<?= sanitize($c['sito_web']??'') ?>"></div>
          <div class="col-12"><label class="form-label">Note</label><textarea name="note" class="form-control" rows="2"><?= sanitize($c['note']??'') ?></textarea></div>
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

