﻿﻿﻿﻿﻿<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo','segreteria');

$db = getDB();
$id = intval($_GET['id'] ?? 0);
$iscritto = null;
$iscrizioni = [];

if ($id) {
    $stmt = $db->prepare('SELECT * FROM aned_db_iscritti WHERE id=?');
    $stmt->execute([$id]);
    $iscritto = $stmt->fetch();
    if (!$iscritto) { $_SESSION['flash_error']='Iscritto non trovato.'; header('Location: index.php'); exit; }
    $iStmt = $db->prepare('SELECT * FROM aned_db_iscrizioni WHERE iscritto_id=? ORDER BY anno DESC');
    $iStmt->execute([$id]);
    $iscrizioni = $iStmt->fetchAll();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_iscritto'])) {
    $fields = ['nome','cognome','codice_fiscale','data_nascita','luogo_nascita','sesso',
               'indirizzo','cap','citta','provincia','telefono','cellulare','email',
               'note','tipo_utente','nominativo_familiare','campo_familiare'];
    $data = [];
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        if ($val === '') {
            $data[$f] = null;
        } else {
            // Trasforma in maiuscolo tutto tranne le note
            $data[$f] = ($f === 'note') ? $val : mb_strtoupper($val, 'UTF-8');
        }
    }
    $data['flag_triangolo_rosso'] = isset($_POST['flag_triangolo_rosso']) ? 1 : 0;
    $data['attivo'] = isset($_POST['attivo']) ? 1 : 0;

    if (!$data['nome'])    $errors[] = 'Il nome è obbligatorio.';
    if (!$data['cognome']) $errors[] = 'Il cognome è obbligatorio.';

    // Gestione File Allegati multipli (nelle Note)
    $existing_files = [];
    if (!empty($iscritto['file_allegato'])) {
        $existing_files = json_decode($iscritto['file_allegato'], true);
        if (!is_array($existing_files)) {
            $existing_files = array_filter(array_map('trim', explode(',', $iscritto['file_allegato'])));
        }
    }

    if (!empty($_POST['remove_files']) && is_array($_POST['remove_files'])) {
        foreach ($_POST['remove_files'] as $del_file) {
            if (($key = array_search($del_file, $existing_files)) !== false) {
                if (file_exists(UPLOAD_DIR . 'iscritti/' . $del_file)) {
                    unlink(UPLOAD_DIR . 'iscritti/' . $del_file);
                }
                unset($existing_files[$key]);
            }
        }
        $existing_files = array_values($existing_files);
    }

    if (isset($_FILES['file_allegati']['name']) && is_array($_FILES['file_allegati']['name']) && $_FILES['file_allegati']['name'][0] !== '') {
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'txt'];
        $target_dir = UPLOAD_DIR . 'iscritti/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

        foreach ($_FILES['file_allegati']['name'] as $key => $name) {
            if ($_FILES['file_allegati']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $new_name = 'doc_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['file_allegati']['tmp_name'][$key], $target_dir . $new_name)) {
                        $existing_files[] = $new_name;
                    }
                } else {
                    $errors[] = "Estensione file non consentita per: " . htmlspecialchars($name);
                }
            }
        }
    }
    $data['file_allegato'] = !empty($existing_files) ? json_encode($existing_files) : null;

    if (empty($errors)) {
        if ($id) {
            $sql = 'UPDATE aned_db_iscritti SET nome=:nome,cognome=:cognome,codice_fiscale=:codice_fiscale,data_nascita=:data_nascita,
                    luogo_nascita=:luogo_nascita,sesso=:sesso,indirizzo=:indirizzo,cap=:cap,citta=:citta,provincia=:provincia,
                    telefono=:telefono,cellulare=:cellulare,email=:email,note=:note,file_allegato=:file_allegato,
                    flag_triangolo_rosso=:flag_triangolo_rosso,attivo=:attivo,
                    tipo_utente=:tipo_utente,nominativo_familiare=:nominativo_familiare,campo_familiare=:campo_familiare WHERE id=:id';
            $data['id'] = $id;
            $db->prepare($sql)->execute($data);
            $_SESSION['flash_success'] = 'Iscritto aggiornato.';
        } else {
            $sql = 'INSERT INTO aned_db_iscritti (nome,cognome,codice_fiscale,data_nascita,luogo_nascita,sesso,indirizzo,cap,citta,
                    provincia,telefono,cellulare,email,note,file_allegato,flag_triangolo_rosso,attivo,created_by,tipo_utente,nominativo_familiare,campo_familiare)
                    VALUES (:nome,:cognome,:codice_fiscale,:data_nascita,:luogo_nascita,:sesso,:indirizzo,:cap,:citta,
                    :provincia,:telefono,:cellulare,:email,:note,:file_allegato,:flag_triangolo_rosso,:attivo,:created_by,:tipo_utente,:nominativo_familiare,:campo_familiare)';
            $data['created_by'] = $_SESSION['user_id'];
            $db->prepare($sql)->execute($data);
            $id = $db->lastInsertId();
            $_SESSION['flash_success'] = 'Iscritto aggiunto.';
        }
        header('Location: view.php?id=' . $id);
        exit;
    }
}

// Salva quota iscrizione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_quota'])) {
    $anno    = intval($_POST['anno_quota'] ?? date('Y'));
    $data_i  = trim($_POST['data_iscrizione'] ?? '');
    $importo = floatval($_POST['importo'] ?? 0);
    $note    = trim($_POST['note_quota'] ?? '');

    if ($anno && $data_i && $importo >= 0) {
        $db->prepare('INSERT INTO aned_db_iscrizioni (iscritto_id,anno,data_iscrizione,importo,note,inserito_da)
                      VALUES (?,?,?,?,?,?)
                      ON DUPLICATE KEY UPDATE data_iscrizione=VALUES(data_iscrizione),importo=VALUES(importo),note=VALUES(note)')
           ->execute([$id, $anno, $data_i, $importo, $note, $_SESSION['user_id']]);
        $_SESSION['flash_success'] = 'Quota iscrizione salvata.';
        header('Location: form.php?id=' . $id . '#iscrizioni');
        exit;
    }
}

define('PAGE_TITLE', $id ? 'Modifica Iscritto' : 'Nuovo Iscritto');
$v = $iscritto ?? [];
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><?= $id ? 'Modifica Iscritto' : 'Nuovo Iscritto' ?></h1>
      <p class="page-subtitle"><a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i>Torna alla lista</a></p>
    </div>
  </div>

  <?php flash(); flash('error'); ?>
  <?php foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <form method="POST" id="formIscritto" enctype="multipart/form-data">
  <input type="hidden" name="save_iscritto" value="1">
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header">Dati Anagrafici</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nome *</label>
              <input type="text" name="nome" class="form-control" value="<?= sanitize($v['nome']??$_POST['nome']??'') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Cognome *</label>
              <input type="text" name="cognome" class="form-control" value="<?= sanitize($v['cognome']??$_POST['cognome']??'') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Codice Fiscale</label>
              <input type="text" name="codice_fiscale" class="form-control text-uppercase" maxlength="16"
                     value="<?= sanitize($v['codice_fiscale']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Data di Nascita</label>
              <input type="date" name="data_nascita" class="form-control" value="<?= sanitize($v['data_nascita']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Luogo di Nascita</label>
              <input type="text" name="luogo_nascita" class="form-control" value="<?= sanitize($v['luogo_nascita']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Sesso</label>
              <select name="sesso" class="form-select">
                <option value="">-</option>
                <option value="M" <?= strcasecmp($v['sesso']??'','M')===0?'selected':'' ?>>Maschile</option>
                <option value="F" <?= strcasecmp($v['sesso']??'','F')===0?'selected':'' ?>>Femminile</option>
                <option value="Altro" <?= strcasecmp($v['sesso']??'','Altro')===0?'selected':'' ?>>Altro</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tipo Utente</label>
              <select name="tipo_utente" id="tipo_utente" class="form-select" onchange="toggleFamiliareFields()">
                <option value="">- Seleziona -</option>
                <option value="Familiare" <?= strcasecmp($v['tipo_utente']??'','Familiare')===0?'selected':'' ?>>Familiare</option>
                <option value="Amico" <?= strcasecmp($v['tipo_utente']??'','Amico')===0?'selected':'' ?>>Amico</option>
                <option value="Superstite" <?= strcasecmp($v['tipo_utente']??'','Superstite')===0?'selected':'' ?>>Superstite</option>
              </select>
            </div>
            <div class="col-md-4 familiare-fields" style="display: none;">
              <label class="form-label">Nominativo Familiare</label>
              <input type="text" name="nominativo_familiare" class="form-control" value="<?= sanitize($v['nominativo_familiare']??'') ?>">
            </div>
            <div class="col-md-4 familiare-fields" style="display: none;">
              <label class="form-label">Campo</label>
              <input type="text" name="campo_familiare" class="form-control" value="<?= sanitize($v['campo_familiare']??'') ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">Residenza e Contatti</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Indirizzo</label>
              <input type="text" name="indirizzo" class="form-control" value="<?= sanitize($v['indirizzo']??'') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">CAP</label>
              <input type="text" name="cap" class="form-control" maxlength="10" value="<?= sanitize($v['cap']??'') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Prov.</label>
              <input type="text" name="provincia" class="form-control" maxlength="5" value="<?= sanitize($v['provincia']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Città</label>
              <input type="text" name="citta" class="form-control" value="<?= sanitize($v['citta']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Telefono</label>
              <input type="tel" name="telefono" class="form-control" value="<?= sanitize($v['telefono']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Cellulare</label>
              <input type="tel" name="cellulare" class="form-control" value="<?= sanitize($v['cellulare']??'') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= sanitize($v['email']??'') ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Note</div>
        <div class="card-body">
          <textarea name="note" class="form-control mb-3" rows="3"><?= sanitize($v['note']??'') ?></textarea>
          <label class="form-label">File Allegati <small class="text-muted">(PDF, Word, Immagini, ZIP - selezione multipla consentita)</small></label>
          <input type="file" name="file_allegati[]" class="form-control" multiple>
          <?php 
          $existing_files = [];
          if (!empty($v['file_allegato'])) {
              $existing_files = json_decode($v['file_allegato'], true);
              if (!is_array($existing_files)) {
                  $existing_files = array_filter(array_map('trim', explode(',', $v['file_allegato'])));
              }
          }
          if (!empty($existing_files)): ?>
            <div class="mt-3">
              <label class="form-label fw-bold mb-2">File caricati:</label>
              <?php foreach ($existing_files as $f): ?>
              <div class="p-2 mb-2 bg-light border rounded d-flex justify-content-between align-items-center">
                <span class="text-truncate me-2">
                  <i class="bi bi-paperclip me-1"></i>
                  <a href="<?= UPLOAD_URL ?>iscritti/<?= sanitize($f) ?>" target="_blank" class="text-decoration-none">
                    <?= sanitize($f) ?>
                  </a>
                </span>
                <div class="form-check m-0 flex-shrink-0">
                  <input class="form-check-input" type="checkbox" name="remove_files[]" value="<?= sanitize($f) ?>" id="delFile_<?= md5($f) ?>">
                  <label class="form-check-label text-danger" for="delFile_<?= md5($f) ?>">Rimuovi</label>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header">Opzioni</div>
        <div class="card-body">
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="attivo" id="attivo"
                   <?= ($v['attivo']??1)?'checked':'' ?>>
            <label class="form-check-label" for="attivo">Iscritto Attivo</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="flag_triangolo_rosso" id="flagTR"
                   <?= ($v['flag_triangolo_rosso']??0)?'checked':'' ?>>
            <label class="form-check-label" for="flagTR">
              <i class="bi bi-triangle-fill flag-tr me-1"></i>Spedizione Triangolo Rosso
            </label>
          </div>
          <div class="alert alert-info mt-3 py-2" style="font-size:12px">
            <i class="bi bi-info-circle me-1"></i>
            Il <strong>Triangolo Rosso</strong> è il periodico dell'ANED.
          </div>
        </div>
      </div>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-aned btn-lg">
          <i class="bi bi-save me-2"></i><?= $id ? 'Salva Modifiche' : 'Aggiungi Iscritto' ?>
        </button>
        <a href="index.php" class="btn btn-outline-secondary">Annulla</a>
      </div>
    </div>
  </div>
  </form>

  <?php if ($id): ?>
  <div class="card mt-4" id="iscrizioni">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-calendar-check me-2"></i>Quote Iscrizione per Anno</span>
    </div>
    <div class="card-body">
      <div class="row g-4">
        <div class="col-md-5">
          <form method="POST">
            <input type="hidden" name="save_quota" value="1">
            <div class="row g-2">
              <div class="col-4">
                <label class="form-label">Anno</label>
                <input type="number" name="anno_quota" class="form-control" value="<?= date('Y') ?>" min="2000" max="2099">
              </div>
              <div class="col-4">
                <label class="form-label">Data</label>
                <input type="date" name="data_iscrizione" class="form-control" value="<?= date('Y-m-d') ?>">
              </div>
              <div class="col-4">
                <label class="form-label">Importo €</label>
                <input type="number" step="0.01" name="importo" class="form-control" value="25.00">
              </div>
              <div class="col-12">
                <label class="form-label">Note</label>
                <input type="text" name="note_quota" class="form-control" placeholder="Facoltativo">
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-aned w-100">
                  <i class="bi bi-plus-circle me-1"></i>Salva Quota
                </button>
              </div>
            </div>
          </form>
        </div>
        <div class="col-md-7">
          <?php if (empty($iscrizioni)): ?>
            <div class="empty-state py-3"><i class="bi bi-calendar-x"></i><h5 style="font-size:14px">Nessuna quota registrata</h5></div>
          <?php else: ?>
          <table class="table table-aned">
            <thead><tr><th>Anno</th><th>Data</th><th>Importo</th><th>Note</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($iscrizioni as $iz): ?>
              <tr>
                <td><span class="badge bg-dark"><?= $iz['anno'] ?></span></td>
                <td><?= formatDate($iz['data_iscrizione']) ?></td>
                <td><strong><?= formatMoney($iz['importo']) ?></strong></td>
                <td><?= sanitize($iz['note']??'') ?></td>
                <td>
                  <a href="delete_quota.php?id=<?= $iz['id'] ?>&iscritto_id=<?= $id ?>"
                     class="btn btn-sm btn-outline-danger"
                     data-confirm="Eliminare questa quota?"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
function toggleFamiliareFields() {
  const tipo = document.getElementById('tipo_utente').value;
  const fields = document.querySelectorAll('.familiare-fields');
  fields.forEach(f => {
    f.style.display = (tipo.toUpperCase() === 'FAMILIARE') ? 'block' : 'none';
  });
}
document.addEventListener('DOMContentLoaded', toggleFamiliareFields);
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
