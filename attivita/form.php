<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$db = getDB();
$id = intval($_GET['id'] ?? 0);
$ev = null;
$relatori = [];
$referenti = [];

if ($id) {
    $stmt = $db->prepare('SELECT * FROM aned_db_attivita WHERE id=?');
    $stmt->execute([$id]);
    $ev = $stmt->fetch();
    if (!$ev) { $_SESSION['flash_error']='Evento non trovato.'; header('Location: index.php'); exit; }
    $relatori  = $db->prepare('SELECT * FROM aned_db_attivita_relatori WHERE attivita_id=?');
    $relatori->execute([$id]); $relatori = $relatori->fetchAll();
    $referenti = $db->prepare('SELECT * FROM aned_db_attivita_referenti WHERE attivita_id=?');
    $referenti->execute([$id]); $referenti = $referenti->fetchAll();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titolo   = trim($_POST['titolo'] ?? '');
    $descr    = trim($_POST['descrizione'] ?? '');
    $data_ev  = trim($_POST['data_evento'] ?? '');
    $ora_ini  = trim($_POST['ora_inizio'] ?? '');
    $ora_fine = trim($_POST['ora_fine'] ?? '');
    $luogo    = trim($_POST['luogo'] ?? '');
    $ind_luogo= trim($_POST['indirizzo_luogo'] ?? '');
    $stato    = $_POST['stato'] ?? 'bozza';
    $max_part = intval($_POST['max_partecipanti'] ?? 0);

    if (!$titolo)  $errors[] = 'Il titolo è obbligatorio.';
    if (!$data_ev) $errors[] = 'La data è obbligatoria.';

    // Upload locandina
    $locandina_path = $ev['locandina'] ?? null;
    if (!empty($_FILES['locandina']['name'])) {
        $ext  = strtolower(pathinfo($_FILES['locandina']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','pdf','webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Formato locandina non supportato.';
        } elseif ($_FILES['locandina']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Locandina troppo grande (max 5MB).';
        } else {
            $fname = 'locandina_' . time() . '_' . uniqid() . '.' . $ext;
            $dest  = UPLOAD_DIR . 'locandine/' . $fname;
            if (move_uploaded_file($_FILES['locandina']['tmp_name'], $dest)) {
                // Elimina vecchia
                if ($locandina_path && file_exists(UPLOAD_DIR . 'locandine/' . basename($locandina_path))) {
                    @unlink(UPLOAD_DIR . 'locandine/' . basename($locandina_path));
                }
                $locandina_path = $fname;
            }
        }
    }

    if (empty($errors)) {
        if ($id) {
            $db->prepare('UPDATE aned_db_attivita SET titolo=?,descrizione=?,data_evento=?,ora_inizio=?,ora_fine=?,
                          luogo=?,indirizzo_luogo=?,locandina=?,stato=?,max_partecipanti=? WHERE id=?')
               ->execute([$titolo,$descr,$data_ev,$ora_ini?:null,$ora_fine?:null,$luogo,$ind_luogo,$locandina_path,$stato,$max_part,$id]);
        } else {
            $db->prepare('INSERT INTO aned_db_attivita (titolo,descrizione,data_evento,ora_inizio,ora_fine,luogo,indirizzo_luogo,locandina,stato,max_partecipanti,inserito_da)
                          VALUES (?,?,?,?,?,?,?,?,?,?,?)')
               ->execute([$titolo,$descr,$data_ev,$ora_ini?:null,$ora_fine?:null,$luogo,$ind_luogo,$locandina_path,$stato,$max_part,$_SESSION['user_id']]);
            $id = $db->lastInsertId();
        }

        // Relatori
        $db->prepare('DELETE FROM aned_db_attivita_relatori WHERE attivita_id=?')->execute([$id]);
        $nomiRel = $_POST['relatore_nome'] ?? [];
        $ruoliRel = $_POST['relatore_ruolo'] ?? [];
        $bioRel  = $_POST['relatore_bio'] ?? [];
        foreach ($nomiRel as $k => $nome) {
            if (trim($nome)) {
                $db->prepare('INSERT INTO aned_db_attivita_relatori (attivita_id,nome,ruolo,bio) VALUES (?,?,?,?)')
                   ->execute([$id, trim($nome), trim($ruoliRel[$k]??''), trim($bioRel[$k]??'')]);
            }
        }

        // Referenti
        $db->prepare('DELETE FROM aned_db_attivita_referenti WHERE attivita_id=?')->execute([$id]);
        $nomiRef = $_POST['referente_nome'] ?? [];
        $emailRef= $_POST['referente_email'] ?? [];
        $telRef  = $_POST['referente_tel'] ?? [];
        foreach ($nomiRef as $k => $nome) {
            if (trim($nome)) {
                $db->prepare('INSERT INTO aned_db_attivita_referenti (attivita_id,nome,email,telefono) VALUES (?,?,?,?)')
                   ->execute([$id, trim($nome), trim($emailRef[$k]??''), trim($telRef[$k]??'')]);
            }
        }

        $_SESSION['flash_success'] = 'Attività salvata.';
        header('Location: view.php?id=' . $id);
        exit;
    }
    $ev = compact('titolo','descr','data_ev','ora_ini','ora_fine','luogo','ind_luogo','stato','max_part');
}

define('PAGE_TITLE', $id ? 'Modifica Attività' : 'Nuova Attività');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><?= $id ? 'Modifica Attività' : 'Nuova Attività' ?></h1>
      <p class="page-subtitle"><a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i>Torna alla lista</a></p>
    </div>
  </div>

  <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

  <form method="POST" enctype="multipart/form-data">
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header">Dati Evento</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Titolo *</label>
              <input type="text" name="titolo" class="form-control" value="<?= sanitize($ev['titolo']??'') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Data *</label>
              <input type="date" name="data_evento" class="form-control" value="<?= sanitize($ev['data_evento']??$ev['data_ev']??'') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Ora Inizio</label>
              <input type="time" name="ora_inizio" class="form-control" value="<?= sanitize($ev['ora_inizio']??$ev['ora_ini']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Ora Fine</label>
              <input type="time" name="ora_fine" class="form-control" value="<?= sanitize($ev['ora_fine']??'') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Luogo</label>
              <input type="text" name="luogo" class="form-control" value="<?= sanitize($ev['luogo']??'') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Indirizzo Luogo</label>
              <input type="text" name="indirizzo_luogo" class="form-control" value="<?= sanitize($ev['indirizzo_luogo']??$ev['ind_luogo']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Max Partecipanti <small class="text-muted">(0 = illimitati)</small></label>
              <input type="number" name="max_partecipanti" class="form-control" value="<?= intval($ev['max_partecipanti']??$ev['max_part']??0) ?>" min="0">
            </div>
            <div class="col-12">
              <label class="form-label">Descrizione</label>
              <textarea name="descrizione" class="form-control" rows="4"><?= sanitize($ev['descrizione']??$ev['descr']??'') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Relatori -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Relatori</span>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addRelatore()"><i class="bi bi-plus me-1"></i>Aggiungi</button>
        </div>
        <div class="card-body" id="relatoriList">
          <?php if ($relatori): ?>
            <?php foreach ($relatori as $r): ?>
            <div class="row g-2 mb-2 relatore-row">
              <div class="col-md-5"><input type="text" name="relatore_nome[]" class="form-control form-control-sm" placeholder="Nome e cognome" value="<?= sanitize($r['nome']) ?>"></div>
              <div class="col-md-3"><input type="text" name="relatore_ruolo[]" class="form-control form-control-sm" placeholder="Ruolo/titolo" value="<?= sanitize($r['ruolo']??'') ?>"></div>
              <div class="col-md-3"><input type="text" name="relatore_bio[]" class="form-control form-control-sm" placeholder="Bio breve" value="<?= sanitize($r['bio']??'') ?>"></div>
              <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.relatore-row').remove()"><i class="bi bi-x"></i></button></div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="row g-2 mb-2 relatore-row">
              <div class="col-md-5"><input type="text" name="relatore_nome[]" class="form-control form-control-sm" placeholder="Nome e cognome"></div>
              <div class="col-md-3"><input type="text" name="relatore_ruolo[]" class="form-control form-control-sm" placeholder="Ruolo/titolo"></div>
              <div class="col-md-3"><input type="text" name="relatore_bio[]" class="form-control form-control-sm" placeholder="Bio breve"></div>
              <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.relatore-row').remove()"><i class="bi bi-x"></i></button></div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Referenti -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Referenti</span>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addReferente()"><i class="bi bi-plus me-1"></i>Aggiungi</button>
        </div>
        <div class="card-body" id="referentiList">
          <?php if ($referenti): ?>
            <?php foreach ($referenti as $r): ?>
            <div class="row g-2 mb-2 referente-row">
              <div class="col-md-5"><input type="text" name="referente_nome[]" class="form-control form-control-sm" placeholder="Nome" value="<?= sanitize($r['nome']) ?>"></div>
              <div class="col-md-4"><input type="email" name="referente_email[]" class="form-control form-control-sm" placeholder="Email" value="<?= sanitize($r['email']??'') ?>"></div>
              <div class="col-md-2"><input type="tel" name="referente_tel[]" class="form-control form-control-sm" placeholder="Tel." value="<?= sanitize($r['telefono']??'') ?>"></div>
              <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.referente-row').remove()"><i class="bi bi-x"></i></button></div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="row g-2 mb-2 referente-row">
              <div class="col-md-5"><input type="text" name="referente_nome[]" class="form-control form-control-sm" placeholder="Nome"></div>
              <div class="col-md-4"><input type="email" name="referente_email[]" class="form-control form-control-sm" placeholder="Email"></div>
              <div class="col-md-2"><input type="tel" name="referente_tel[]" class="form-control form-control-sm" placeholder="Tel."></div>
              <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.referente-row').remove()"><i class="bi bi-x"></i></button></div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header">Pubblicazione</div>
        <div class="card-body">
          <label class="form-label">Stato</label>
          <select name="stato" class="form-select mb-3">
            <option value="bozza" <?= ($ev['stato']??'')==='bozza'?'selected':'' ?>>Bozza</option>
            <option value="pubblicata" <?= ($ev['stato']??'')==='pubblicata'?'selected':'' ?>>Pubblicata</option>
            <option value="annullata" <?= ($ev['stato']??'')==='annullata'?'selected':'' ?>>Annullata</option>
            <option value="conclusa" <?= ($ev['stato']??'')==='conclusa'?'selected':'' ?>>Conclusa</option>
          </select>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">Locandina</div>
        <div class="card-body">
          <?php if (!empty($ev['locandina'])): ?>
            <div class="mb-3 text-center">
              <?php
              $ext = strtolower(pathinfo($ev['locandina'], PATHINFO_EXTENSION));
              if (in_array($ext, ['jpg','jpeg','png','webp','gif'])): ?>
                <img src="<?= APP_URL ?>/uploads/locandine/<?= sanitize(basename($ev['locandina'])) ?>"
                     class="img-fluid rounded" style="max-height:200px">
              <?php else: ?>
                <a href="<?= APP_URL ?>/uploads/locandine/<?= sanitize(basename($ev['locandina'])) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                  <i class="bi bi-file-earmark me-1"></i>Locandina attuale
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <div class="upload-zone">
            <input type="file" name="locandina" class="d-none" accept="image/*,.pdf">
            <i class="bi bi-image mb-2"></i>
            <p class="upload-label mb-0" style="font-size:13px;color:#718096">Clicca o trascina la locandina<br><small>JPG, PNG, PDF — max 5MB</small></p>
          </div>
        </div>
      </div>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-aned btn-lg">
          <i class="bi bi-save me-2"></i>Salva Attività
        </button>
        <a href="index.php" class="btn btn-outline-secondary">Annulla</a>
      </div>
    </div>
  </div>
  </form>
</div>

<script>
function addRelatore() {
  const row = `<div class="row g-2 mb-2 relatore-row">
    <div class="col-md-5"><input type="text" name="relatore_nome[]" class="form-control form-control-sm" placeholder="Nome e cognome"></div>
    <div class="col-md-3"><input type="text" name="relatore_ruolo[]" class="form-control form-control-sm" placeholder="Ruolo/titolo"></div>
    <div class="col-md-3"><input type="text" name="relatore_bio[]" class="form-control form-control-sm" placeholder="Bio breve"></div>
    <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.relatore-row').remove()"><i class="bi bi-x"></i></button></div>
  </div>`;
  document.getElementById('relatoriList').insertAdjacentHTML('beforeend', row);
}
function addReferente() {
  const row = `<div class="row g-2 mb-2 referente-row">
    <div class="col-md-5"><input type="text" name="referente_nome[]" class="form-control form-control-sm" placeholder="Nome"></div>
    <div class="col-md-4"><input type="email" name="referente_email[]" class="form-control form-control-sm" placeholder="Email"></div>
    <div class="col-md-2"><input type="tel" name="referente_tel[]" class="form-control form-control-sm" placeholder="Tel."></div>
    <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.referente-row').remove()"><i class="bi bi-x"></i></button></div>
  </div>`;
  document.getElementById('referentiList').insertAdjacentHTML('beforeend', row);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

