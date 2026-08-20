<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo');

$db = getDB();

// Salva nuovo bilancio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bilancio'])) {
    $id = intval($_POST['id'] ?? 0);
    $anno = intval($_POST['anno'] ?? date('Y'));
    $note = trim($_POST['note'] ?? '');
    $file_allegato = null;

    if (isset($_FILES['file_allegato']) && $_FILES['file_allegato']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['file_allegato']['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $new_name = 'bilancio_' . $anno . '_' . uniqid() . '.pdf';
            $target_dir = UPLOAD_DIR . 'bilanci/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
            if (move_uploaded_file($_FILES['file_allegato']['tmp_name'], $target_dir . $new_name)) {
                $file_allegato = $new_name;
            }
        } else {
            $_SESSION['flash_error'] = "Solo i file in formato PDF sono consentiti.";
        }
    }

    if (!isset($_SESSION['flash_error'])) {
        if ($id > 0) {
            // Modifica bilancio esistente
            if ($file_allegato) {
                // Se è stato caricato un nuovo file, eliminiamo quello vecchio
                $stmt = $db->prepare('SELECT file_allegato FROM aned_db_bilanci WHERE id=?');
                $stmt->execute([$id]);
                $old_file = $stmt->fetchColumn();
                if ($old_file && file_exists(UPLOAD_DIR . 'bilanci/' . $old_file)) {
                    @unlink(UPLOAD_DIR . 'bilanci/' . $old_file);
                }
                $db->prepare('UPDATE aned_db_bilanci SET anno=?, note=?, file_allegato=? WHERE id=?')
                   ->execute([$anno, $note, $file_allegato, $id]);
            } else {
                // Aggiorniamo solo dati testuali (mantiene PDF precedente)
                $db->prepare('UPDATE aned_db_bilanci SET anno=?, note=? WHERE id=?')
                   ->execute([$anno, $note, $id]);
            }
            $_SESSION['flash_success'] = 'Bilancio aggiornato con successo.';
        } else {
            // Inserimento nuovo bilancio - Controllo duplicati
            $stmt = $db->prepare('SELECT id FROM aned_db_bilanci WHERE anno = ?');
            $stmt->execute([$anno]);
            if ($stmt->fetch()) {
                $_SESSION['flash_error'] = "Esiste già un bilancio caricato per l'anno $anno.";
            } elseif ($anno > 0 && $file_allegato) {
                $db->prepare('INSERT INTO aned_db_bilanci (anno, note, file_allegato, created_by) VALUES (?, ?, ?, ?)')
                   ->execute([$anno, $note, $file_allegato, $_SESSION['user_id']]);
                $_SESSION['flash_success'] = 'Bilancio salvato con successo.';
            } else {
                $_SESSION['flash_error'] = 'Il file PDF è obbligatorio per un nuovo bilancio.';
            }
        }
    }
    
    header('Location: index.php');
    exit;
}

// Elimina bilancio
if (isset($_GET['delete']) && ($del_id = intval($_GET['delete']))) {
    $stmt = $db->prepare('SELECT file_allegato FROM aned_db_bilanci WHERE id=?');
    $stmt->execute([$del_id]);
    $file = $stmt->fetchColumn();
    
    if ($file) {
        if (file_exists(UPLOAD_DIR . 'bilanci/' . $file)) {
            @unlink(UPLOAD_DIR . 'bilanci/' . $file);
        }
        $db->prepare('DELETE FROM aned_db_bilanci WHERE id=?')->execute([$del_id]);
        $_SESSION['flash_success'] = 'Bilancio eliminato.';
    }
    header('Location: index.php');
    exit;
}

$bilanci = $db->query('SELECT b.*, u.nome, u.cognome FROM aned_db_bilanci b LEFT JOIN aned_db_utenti u ON u.id = b.created_by ORDER BY b.anno DESC')->fetchAll();

define('PAGE_TITLE', 'Bilanci Annuali');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="bi bi-file-earmark-bar-graph me-2 text-danger"></i>Bilanci Annuali</h1>
      <p class="page-subtitle">Archivio dei bilanci</p>
    </div>
    <button class="btn btn-aned" onclick="openModalBilancio()">
      <i class="bi bi-upload me-2"></i>Carica Bilancio
    </button>
  </div>

  <?php flash(); flash('error'); ?>

  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($bilanci)): ?>
        <div class="empty-state p-5 text-center">
          <i class="bi bi-folder2-open" style="font-size: 3rem; color: #ccc;"></i>
          <h5 class="mt-3">Nessun bilancio caricato</h5>
        </div>
      <?php else: ?>
        <table class="table table-striped table-aned mb-0">
          <thead>
            <tr>
              <th>Anno</th>
              <th>Data caricamento</th>
              <th>Caricato da</th>
              <th>Note</th>
              <th>Azioni</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bilanci as $b): ?>
            <tr>
              <td><strong><?= $b['anno'] ?></strong></td>
              <td><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></td>
              <td><?= sanitize(($b['nome']??'') . ' ' . ($b['cognome']??'')) ?></td>
              <td><?= sanitize($b['note']??'-') ?></td>
              <td>
                <div class="d-flex gap-1">
                  <a href="<?= UPLOAD_URL ?>bilanci/<?= sanitize($b['file_allegato']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Scarica/Visualizza PDF">
                    <i class="bi bi-download"></i>
                  </a>
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick='openModalBilancio(<?= $b['id'] ?>, <?= $b['anno'] ?>, <?= htmlspecialchars(json_encode((string)($b['note']??'')), ENT_QUOTES, "UTF-8") ?>)' title="Modifica">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <a href="?delete=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Eliminare definitivamente questo bilancio?">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal Carica/Modifica Bilancio -->
<div class="modal fade" id="modalBilancio" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="save_bilancio" value="1">
        <input type="hidden" name="id" id="bilancio_id" value="0">
        <div class="modal-header">
          <h5 class="modal-title" id="modalBilancioTitle"><i class="bi bi-upload me-2"></i>Carica Nuovo Bilancio</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Anno di riferimento *</label>
            <input type="number" name="anno" id="bilancio_anno" class="form-control" value="<?= date('Y') - 1 ?>" required min="2000" max="2100">
          </div>
          <div class="mb-3">
            <label class="form-label">File PDF <span id="file_asterisk">*</span></label>
            <input type="file" name="file_allegato" id="bilancio_file" class="form-control" accept=".pdf" required>
            <small class="text-muted d-none" id="file_help">Lascia vuoto per mantenere il file attuale.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Note</label>
            <textarea name="note" id="bilancio_note" class="form-control" rows="3" placeholder="Note aggiuntive (opzionale)"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-aned">
            <i class="bi bi-save me-2"></i>Salva Bilancio
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openModalBilancio(id = 0, anno = <?= date('Y') - 1 ?>, note = '') {
  // Imposta i valori nel form della modale
  document.getElementById('bilancio_id').value = id;
  document.getElementById('bilancio_anno').value = anno;
  document.getElementById('bilancio_note').value = note;
  
  const title = document.getElementById('modalBilancioTitle');
  const fileInput = document.getElementById('bilancio_file');
  const fileAsterisk = document.getElementById('file_asterisk');
  const fileHelp = document.getElementById('file_help');

  if (id > 0) {
    title.innerHTML = '<i class="bi bi-pencil me-2"></i>Modifica Bilancio';
    fileInput.required = false; // In modifica il file NON è obbligatorio
    fileAsterisk.style.display = 'none';
    fileHelp.classList.remove('d-none');
  } else {
    title.innerHTML = '<i class="bi bi-upload me-2"></i>Carica Nuovo Bilancio';
    fileInput.required = true;
    fileAsterisk.style.display = 'inline';
    fileHelp.classList.add('d-none');
  }

  new bootstrap.Modal(document.getElementById('modalBilancio')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>