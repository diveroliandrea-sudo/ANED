<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$search = trim($_GET['q'] ?? '');
$page   = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

// Salva nuova nota o modifica
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_nota'])) {
    $nota_id   = intval($_POST['nota_id'] ?? 0);
    $data_nota = trim($_POST['data_nota'] ?? date('Y-m-d'));
    $titolo    = trim($_POST['titolo'] ?? '');
    $nota      = trim($_POST['nota'] ?? '');

    // Gestione allegati
    $existing_files = [];
    if ($nota_id) {
        $stmt = $db->prepare('SELECT allegati FROM aned_db_note WHERE id=?');
        $stmt->execute([$nota_id]);
        $old = $stmt->fetchColumn();
        if ($old) {
            $existing_files = json_decode($old, true) ?: [];
        }
    }

    if (!empty($_POST['remove_files']) && is_array($_POST['remove_files'])) {
        foreach ($_POST['remove_files'] as $del_file) {
            if (($key = array_search($del_file, $existing_files)) !== false) {
                if (file_exists(UPLOAD_DIR . 'note/' . $del_file)) @unlink(UPLOAD_DIR . 'note/' . $del_file);
                unset($existing_files[$key]);
            }
        }
        $existing_files = array_values($existing_files);
    }

    if (isset($_FILES['file_allegati']['name']) && is_array($_FILES['file_allegati']['name']) && $_FILES['file_allegati']['name'][0] !== '') {
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'txt'];
        $target_dir = UPLOAD_DIR . 'note/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

        foreach ($_FILES['file_allegati']['name'] as $key => $name) {
            if ($_FILES['file_allegati']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $new_name = 'nota_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['file_allegati']['tmp_name'][$key], $target_dir . $new_name)) {
                        $existing_files[] = $new_name;
                    }
                }
            }
        }
    }
    
    $allegati_json = !empty($existing_files) ? json_encode($existing_files) : null;

    if ($nota) {
        if ($nota_id) {
            // Modifica — solo chi l'ha creata o admin
            $chk = $db->prepare('SELECT created_by FROM aned_db_note WHERE id=?');
            $chk->execute([$nota_id]);
            $row = $chk->fetch();
            if ($row && ($row['created_by'] == $_SESSION['user_id'] || hasRole('admin'))) {
                $db->prepare('UPDATE aned_db_note SET data_nota=?,titolo=?,nota=?,allegati=? WHERE id=?')
                   ->execute([$data_nota, $titolo, $nota, $allegati_json, $nota_id]);
                $_SESSION['flash_success'] = 'Nota aggiornata.';
            }
        } else {
            $db->prepare('INSERT INTO aned_db_note (data_nota,titolo,nota,allegati,created_by) VALUES (?,?,?,?,?)')
               ->execute([$data_nota, $titolo, $nota, $allegati_json, $_SESSION['user_id']]);
            $_SESSION['flash_success'] = 'Nota salvata.';
        }
        header('Location: index.php');
        exit;
    }
}

// Elimina
if (isset($_GET['delete']) && ($del_id = intval($_GET['delete']))) {
    $chk = $db->prepare('SELECT created_by, allegati FROM aned_db_note WHERE id=?');
    $chk->execute([$del_id]);
    $row = $chk->fetch();
    if ($row && ($row['created_by'] == $_SESSION['user_id'] || hasRole('admin'))) {
        if (!empty($row['allegati'])) {
            $files = json_decode($row['allegati'], true) ?: [];
            foreach ($files as $f) {
                if (file_exists(UPLOAD_DIR . 'note/' . $f)) {
                    @unlink(UPLOAD_DIR . 'note/' . $f);
                }
            }
        }
        $db->prepare('DELETE FROM aned_db_note WHERE id=?')->execute([$del_id]);
        $_SESSION['flash_success'] = 'Nota eliminata.';
    }
    header('Location: index.php');
    exit;
}

// Carica nota per modifica
$editNota = null;
if (isset($_GET['edit']) && ($edit_id = intval($_GET['edit']))) {
    $stmt = $db->prepare('SELECT * FROM aned_db_note WHERE id=?');
    $stmt->execute([$edit_id]);
    $editNota = $stmt->fetch();
    if ($editNota && $editNota['created_by'] != $_SESSION['user_id'] && !hasRole('admin')) {
        $editNota = null;
    }
}

// Lista note
$where  = ['1=1']; $params = [];
if ($search) {
    $where[] = '(n.titolo LIKE ? OR n.nota LIKE ?)';
    $params  = ["%$search%", "%$search%"];
}
// Utenti normali vedono solo le proprie
if (!hasRole('admin','direttivo','segreteria')) {
    $where[] = 'n.created_by = ?';
    $params[] = $_SESSION['user_id'];
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM aned_db_note n $whereSQL");
$total->execute($params); $total = $total->fetchColumn();
$pages = max(1, ceil($total / $perPage));

$stmt = $db->prepare("SELECT n.*, u.nome as aut_nome, u.cognome as aut_cognome
    FROM aned_db_note n
    LEFT JOIN aned_db_utenti u ON u.id = n.created_by
    $whereSQL ORDER BY n.data_nota DESC, n.created_at DESC
    LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$note = $stmt->fetchAll();

define('PAGE_TITLE', 'Note');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="bi bi-journal-text me-2 text-danger"></i>Note</h1>
      <p class="page-subtitle"><?= $total ?> note</p>
    </div>
    <button class="btn btn-aned" data-bs-toggle="modal" data-bs-target="#modalNota" onclick="resetForm()">
      <i class="bi bi-plus-circle me-2"></i>Nuova Nota
    </button>
  </div>

  <?php flash(); flash('error'); ?>

  <!-- Ricerca -->
  <div class="card mb-4">
    <div class="card-body py-3">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-8">
          <div class="search-bar">
            <i class="bi bi-search"></i>
            <input type="text" name="q" class="form-control" placeholder="Cerca nelle note..." value="<?= sanitize($search) ?>">
          </div>
        </div>
        <div class="col-md-2"><button type="submit" class="btn btn-aned w-100">Cerca</button></div>
        <?php if ($search): ?>
        <div class="col-md-2"><a href="index.php" class="btn btn-outline-secondary w-100">Reset</a></div>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <?php if (empty($note)): ?>
    <div class="empty-state card p-5">
      <i class="bi bi-journal-x"></i>
      <h5>Nessuna nota trovata</h5>
      <?php if (!$search): ?>
        <button class="btn btn-aned mt-3" data-bs-toggle="modal" data-bs-target="#modalNota" onclick="resetForm()">
          <i class="bi bi-plus-circle me-2"></i>Scrivi la prima nota
        </button>
      <?php endif; ?>
    </div>
  <?php else: ?>

  <div class="row g-3">
    <?php foreach ($note as $n): ?>
    <div class="col-12">
      <div class="card" style="border-left: 4px solid var(--aned-red)">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="flex-1">
              <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span class="badge bg-dark">
                  <i class="bi bi-calendar3 me-1"></i><?= formatDate($n['data_nota']) ?>
                </span>
                <small class="text-muted">
                  <i class="bi bi-person me-1"></i><?= sanitize(($n['aut_nome']??'').' '.($n['aut_cognome']??'')) ?>
                </small>
              </div>
              <?php if ($n['titolo']): ?>
                <h5 class="mb-2" style="font-size:16px;font-weight:700"><?= sanitize($n['titolo']) ?></h5>
              <?php endif; ?>
              <p class="mb-0" style="line-height:1.7;white-space:pre-wrap"><?= nl2br(sanitize($n['nota'])) ?></p>
              <?php if (!empty($n['allegati'])): ?>
                <?php $files = json_decode($n['allegati'], true) ?: []; ?>
                <?php if ($files): ?>
                <div class="mt-2 d-flex gap-2 flex-wrap">
                  <?php foreach ($files as $f): ?>
                  <a href="<?= UPLOAD_URL ?>note/<?= sanitize($f) ?>" target="_blank" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-paperclip"></i> <?= sanitize($f) ?>
                  </a>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
            <?php if ($n['created_by'] == $_SESSION['user_id'] || hasRole('admin','direttivo')): ?>
            <div class="d-flex gap-1 flex-shrink-0">
              <button class="btn btn-sm btn-outline-secondary"
                      onclick="editNota(this)"
                      data-id="<?= $n['id'] ?>"
                      data-data="<?= htmlspecialchars($n['data_nota']) ?>"
                      data-titolo="<?= htmlspecialchars($n['titolo'] ?? '') ?>"
                      data-nota="<?= htmlspecialchars($n['nota']) ?>"
                      data-allegati="<?= htmlspecialchars($n['allegati'] ?? '[]') ?>"
                      data-bs-toggle="modal" data-bs-target="#modalNota">
                <i class="bi bi-pencil"></i>
              </button>
              <a href="?delete=<?= $n['id'] ?>" class="btn btn-sm btn-outline-danger"
                 data-confirm="Eliminare questa nota?">
                <i class="bi bi-trash"></i>
              </a>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($pages > 1): ?>
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <?php for ($p=1; $p<=$pages; $p++): ?>
        <li class="page-item <?= $p==$page?'active':'' ?>">
          <a class="page-link" href="?q=<?= urlencode($search) ?>&page=<?= $p ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>

  <?php endif; ?>
</div>

<!-- Modal Nuova/Modifica Nota -->
<div class="modal fade" id="modalNota" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="save_nota" value="1">
        <input type="hidden" name="nota_id" id="nota_id" value="0">
        <div class="modal-header">
          <h5 class="modal-title" id="modalNotaTitolo"><i class="bi bi-journal-plus me-2"></i>Nuova Nota</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Data *</label>
              <input type="date" name="data_nota" id="input_data" class="form-control"
                     value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-8">
              <label class="form-label">Titolo</label>
              <input type="text" name="titolo" id="input_titolo" class="form-control"
                     placeholder="Titolo breve (facoltativo)">
            </div>
            <div class="col-12">
              <label class="form-label">Nota *</label>
              <textarea name="nota" id="input_nota" class="form-control" rows="6"
                        placeholder="Scrivi la tua nota qui..." required></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">File Allegati <small class="text-muted">(PDF, Word, Immagini, ZIP - selezione multipla)</small></label>
              <input type="file" name="file_allegati[]" class="form-control" multiple>
              <div id="existing_files_container" class="mt-3 d-none">
                <label class="form-label fw-bold mb-2">File caricati:</label>
                <div id="existing_files_list"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-aned">
            <i class="bi bi-save me-2"></i>Salva Nota
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function resetForm() {
  document.getElementById('nota_id').value = '0';
  document.getElementById('input_data').value = '<?= date('Y-m-d') ?>';
  document.getElementById('input_titolo').value = '';
  document.getElementById('input_nota').value = '';
  document.getElementById('modalNotaTitolo').innerHTML = '<i class="bi bi-journal-plus me-2"></i>Nuova Nota';
  document.getElementById('existing_files_container').classList.add('d-none');
  document.getElementById('existing_files_list').innerHTML = '';
}

function editNota(btn) {
  const id = btn.getAttribute('data-id');
  const data = btn.getAttribute('data-data');
  const titolo = btn.getAttribute('data-titolo');
  const nota = btn.getAttribute('data-nota');
  const allegati = btn.getAttribute('data-allegati');

  document.getElementById('nota_id').value = id;
  document.getElementById('input_data').value = data;
  document.getElementById('input_titolo').value = titolo;
  document.getElementById('input_nota').value = nota;
  document.getElementById('modalNotaTitolo').innerHTML = '<i class="bi bi-journal-text me-2"></i>Modifica Nota';

  let files = [];
  try { files = JSON.parse(allegati); } catch(e) {}
  
  const container = document.getElementById('existing_files_container');
  const list = document.getElementById('existing_files_list');
  list.innerHTML = '';
  
  if (files && files.length > 0) {
    container.classList.remove('d-none');
    files.forEach(f => {
      let safeId = f.replace(/\W/g, '');
      list.innerHTML += `
        <div class="p-2 mb-2 bg-light border rounded d-flex justify-content-between align-items-center">
          <span class="text-truncate me-2">
            <i class="bi bi-paperclip me-1"></i>
            <a href="<?= UPLOAD_URL ?>note/${f}" target="_blank" class="text-decoration-none">${f}</a>
          </span>
          <div class="form-check m-0 flex-shrink-0">
            <input class="form-check-input" type="checkbox" name="remove_files[]" value="${f}" id="delFile_${safeId}">
            <label class="form-check-label text-danger" for="delFile_${safeId}">Rimuovi</label>
          </div>
        </div>
      `;
    });
  } else {
    container.classList.add('d-none');
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
