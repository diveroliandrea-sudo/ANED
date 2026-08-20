<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo');

$db = getDB();

// Crea la tabella se non esiste ancora
$db->exec("CREATE TABLE IF NOT EXISTS aned_db_entrate (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data_entrata  DATE         NOT NULL,
    categoria     VARCHAR(100) NOT NULL DEFAULT '',
    descrizione   TEXT         NOT NULL,
    importo       DECIMAL(10,2) NOT NULL DEFAULT 0,
    fonte         VARCHAR(255) NULL,
    file_ricevuta VARCHAR(255) NULL,
    approvata     TINYINT(1)   NOT NULL DEFAULT 0,
    approvata_da  INT UNSIGNED NULL,
    inserito_da   INT UNSIGNED NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Salva nuova entrata
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_entrata'])) {
    $data_e  = trim($_POST['data_entrata'] ?? '');
    $categ   = trim($_POST['categoria'] ?? '');
    $descr   = trim($_POST['descrizione'] ?? '');
    $importo = floatval($_POST['importo'] ?? 0);
    $fonte   = trim($_POST['fonte'] ?? '');
    $fp      = null;

    if (!empty($_FILES['file_ricevuta']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file_ricevuta']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf','jpg','jpeg','png'])) {
            $dir = UPLOAD_DIR . 'ricevute_entrate/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fname = 'entrata_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['file_ricevuta']['tmp_name'], $dir . $fname)) {
                $fp = $fname;
            }
        }
    }

    if ($data_e && $descr) {
        $db->prepare('INSERT INTO aned_db_entrate (data_entrata,categoria,descrizione,importo,fonte,file_ricevuta,inserito_da) VALUES (?,?,?,?,?,?,?)')
           ->execute([$data_e, $categ, $descr, $importo, $fonte, $fp, $_SESSION['user_id']]);
        $_SESSION['flash_success'] = 'Entrata registrata.';
        header('Location: index.php'); exit;
    }
}

// Approva entrata
if (isset($_GET['approva']) && hasRole('admin','direttivo')) {
    $db->prepare('UPDATE aned_db_entrate SET approvata=1, approvata_da=? WHERE id=?')
       ->execute([$_SESSION['user_id'], intval($_GET['approva'])]);
    $_SESSION['flash_success'] = 'Entrata approvata.';
    header('Location: index.php'); exit;
}

$annoFil  = intval($_GET['anno'] ?? date('Y'));
$entrate  = $db->prepare("SELECT e.*, u.nome AS ins_nome, u.cognome AS ins_cognome
                           FROM aned_db_entrate e
                           LEFT JOIN aned_db_utenti u ON u.id = e.inserito_da
                           WHERE YEAR(e.data_entrata) = ?
                           ORDER BY e.data_entrata DESC");
$entrate->execute([$annoFil]);
$entrate  = $entrate->fetchAll();
$totale   = array_sum(array_column($entrate, 'importo'));

$categorie = ['Quote Associative','Donazione','Contributo Pubblico','Contributo Privato','Rimborso','Evento','Altro'];
$anniDisp  = $db->query("SELECT DISTINCT YEAR(data_entrata) AS anno FROM aned_db_entrate ORDER BY anno DESC")->fetchAll(PDO::FETCH_COLUMN);

define('PAGE_TITLE', 'Gestione Entrate');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="bi bi-cash-coin me-2 text-success"></i>Gestione Entrate</h1>
      <p class="page-subtitle">Totale <?= $annoFil ?>: <strong><?= formatMoney($totale) ?></strong></p>
    </div>
  </div>
  <?php flash(); flash('error'); ?>

  <div class="row g-4">
    <!-- Form registrazione -->
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header">Registra Entrata</div>
        <div class="card-body">
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_entrata" value="1">
            <div class="mb-3">
              <label class="form-label">Data *</label>
              <input type="date" name="data_entrata" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Categoria</label>
              <select name="categoria" class="form-select">
                <?php foreach ($categorie as $c): ?>
                  <option><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Descrizione *</label>
              <textarea name="descrizione" class="form-control" rows="2" required></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Importo € *</label>
              <input type="number" step="0.01" min="0" name="importo" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Fonte / Provenienza</label>
              <input type="text" name="fonte" class="form-control" placeholder="es. Comune di Roma, Sig. Rossi…">
            </div>
            <div class="mb-3">
              <label class="form-label">Documento (PDF/immagine)</label>
              <input type="file" name="file_ricevuta" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            </div>
            <button type="submit" class="btn btn-aned w-100">
              <i class="bi bi-plus-circle me-2"></i>Registra
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Lista entrate -->
    <div class="col-lg-8">
      <!-- Filtro anni -->
      <div class="d-flex gap-2 mb-3 flex-wrap">
        <?php foreach (array_unique(array_merge([date('Y')], $anniDisp)) as $a): ?>
          <a href="?anno=<?= $a ?>" class="btn btn-sm <?= $a == $annoFil ? 'btn-aned' : 'btn-outline-secondary' ?>">
            <?= $a ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <div class="card-body p-0">
          <?php if (empty($entrate)): ?>
            <div class="empty-state p-4">
              <i class="bi bi-cash-stack"></i>
              <h5>Nessuna entrata per <?= $annoFil ?></h5>
            </div>
          <?php else: ?>
          <table class="table table-striped table-aned mb-0">
            <thead>
              <tr>
                <th>Data</th>
                <th>Categoria</th>
                <th>Descrizione</th>
                <th>Fonte</th>
                <th>Importo</th>
                <th>Stato</th>
                <th>Doc.</th>
                <th>Azioni</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($entrate as $e): ?>
              <tr>
                <td><?= formatDate($e['data_entrata']) ?></td>
                <td><span class="badge bg-success"><?= sanitize($e['categoria'] ?? '') ?></span></td>
                <td><?= sanitize($e['descrizione']) ?></td>
                <td><?= sanitize($e['fonte'] ?? '-') ?></td>
                <td><strong><?= formatMoney($e['importo']) ?></strong></td>
                <td>
                  <?php if ($e['approvata']): ?>
                    <span class="badge bg-success">Approvata</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">In attesa</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($e['file_ricevuta']): ?>
                    <a href="<?= APP_URL ?>/uploads/ricevute_entrate/<?= sanitize(basename($e['file_ricevuta'])) ?>"
                       target="_blank" class="btn btn-sm btn-outline-secondary" title="Visualizza documento">
                      <i class="bi bi-paperclip"></i>
                    </a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!$e['approvata']): ?>
                    <a href="?approva=<?= $e['id'] ?>&anno=<?= $annoFil ?>"
                       class="btn btn-sm btn-outline-success" title="Approva">
                      <i class="bi bi-check-lg"></i>
                    </a>
                  <?php endif; ?>
                  <a href="delete.php?id=<?= $e['id'] ?>&anno=<?= $annoFil ?>"
                     class="btn btn-sm btn-outline-danger"
                     data-confirm="Eliminare questa entrata?">
                    <i class="bi bi-trash"></i>
                  </a>
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
