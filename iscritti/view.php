﻿﻿﻿<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo','segreteria');

$db = getDB();
$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM aned_db_iscritti WHERE id=?');
$stmt->execute([$id]);
$iscritto = $stmt->fetch();
if (!$iscritto) { $_SESSION['flash_error']='Iscritto non trovato.'; header('Location: index.php'); exit; }

$iscrizioni = $db->prepare('SELECT iz.*,u.nome as ins_nome,u.cognome as ins_cognome FROM aned_db_iscrizioni iz LEFT JOIN aned_db_utenti u ON u.id=iz.inserito_da WHERE iz.iscritto_id=? ORDER BY iz.anno DESC');
$iscrizioni->execute([$id]);
$iscrizioni = $iscrizioni->fetchAll();

define('PAGE_TITLE', $iscritto['cognome'].' '.$iscritto['nome']);
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><?= sanitize($iscritto['cognome'].' '.$iscritto['nome']) ?></h1>
      <p class="page-subtitle"><a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i>Lista Iscritti</a></p>
    </div>
    <?php if (hasRole('admin','direttivo','segreteria')): ?>
    <div class="d-flex gap-2">
      <a href="form.php?id=<?= $id ?>" class="btn btn-aned"><i class="bi bi-pencil me-2"></i>Modifica</a>
      <a href="delete.php?id=<?= $id ?>" class="btn btn-outline-danger" data-confirm="Eliminare questo iscritto?">
        <i class="bi bi-trash me-2"></i>Elimina
      </a>
    </div>
    <?php endif; ?>
  </div>

  <?php flash(); flash('error'); ?>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card text-center p-4">
        <div class="member-avatar mx-auto mb-3" style="width:70px;height:70px;font-size:26px">
          <?= strtoupper(substr($iscritto['nome'],0,1).substr($iscritto['cognome'],0,1)) ?>
        </div>
        <h4 class="mb-0"><?= sanitize($iscritto['cognome'].' '.$iscritto['nome']) ?></h4>
        <div class="badge bg-secondary mt-1">N° <?= intval($iscritto['id']) ?></div>
        <?php if ($iscritto['flag_triangolo_rosso']): ?>
          <div class="mt-2"><i class="bi bi-triangle-fill flag-tr me-1"></i><small>Spedizione Triangolo Rosso</small></div>
        <?php endif; ?>
        <?php if (!$iscritto['attivo']): ?>
          <div class="badge bg-danger mt-2">Non attivo</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-header">Dati Anagrafici</div>
        <div class="card-body">
          <div class="row g-2" style="font-size:14px">
            <div class="col-md-6"><strong>Codice Fiscale:</strong> <code><?= sanitize($iscritto['codice_fiscale']??'-') ?></code></div>
            <div class="col-md-6"><strong>Data Nascita:</strong> <?= formatDate($iscritto['data_nascita']) ?></div>
            <div class="col-md-6"><strong>Luogo Nascita:</strong> <?= sanitize($iscritto['luogo_nascita']??'-') ?></div>
            <div class="col-md-6"><strong>Sesso:</strong> <?= sanitize($iscritto['sesso']??'-') ?></div>
            <?php if (!empty($iscritto['tipo_utente'])): ?>
            <div class="col-md-6"><strong>Tipo Utente:</strong> <?= sanitize($iscritto['tipo_utente']) ?></div>
            <?php if (strcasecmp($iscritto['tipo_utente'], 'Familiare') === 0): ?>
            <?php if (!empty($iscritto['nominativo_familiare'])): ?>
            <div class="col-md-6"><strong>Nominativo Familiare:</strong> <?= sanitize($iscritto['nominativo_familiare']) ?></div>
            <?php endif; ?>
            <?php if (!empty($iscritto['campo_familiare'])): ?>
            <div class="col-md-6"><strong>Campo:</strong> <?= sanitize($iscritto['campo_familiare']) ?></div>
            <?php endif; ?>
            <?php endif; ?>
            <?php endif; ?>
            <div class="col-12"><hr class="my-2"></div>
            <div class="col-12"><strong>Indirizzo:</strong> <?= sanitize(($iscritto['indirizzo']??'').', '.($iscritto['cap']??'').' '.($iscritto['citta']??'').' ('.($iscritto['provincia']??'').')') ?></div>
            <div class="col-md-4"><strong>Telefono:</strong> <?= sanitize($iscritto['telefono']??'-') ?></div>
            <div class="col-md-4"><strong>Cellulare:</strong> <?= sanitize($iscritto['cellulare']??'-') ?></div>
            <div class="col-md-4"><strong>Email:</strong> <?= sanitize($iscritto['email']??'-') ?></div>
            <?php if ($iscritto['note']): ?>
            <div class="col-12"><strong>Note:</strong><br><?= nl2br(sanitize($iscritto['note'])) ?></div>
            <?php endif; ?>
            <?php 
            $existing_files = [];
            if (!empty($iscritto['file_allegato'])) {
                $existing_files = json_decode($iscritto['file_allegato'], true);
                if (!is_array($existing_files)) {
                    $existing_files = array_filter(array_map('trim', explode(',', $iscritto['file_allegato'])));
                }
            }
            if (!empty($existing_files)): ?>
              <div class="col-12 mt-2">
                <strong>Allegati:</strong><br>
                <div class="d-flex gap-2 flex-wrap mt-1">
                  <?php foreach ($existing_files as $f): ?>
                  <a href="<?= UPLOAD_URL ?>iscritti/<?= sanitize($f) ?>" class="btn btn-sm btn-outline-dark" target="_blank">
                    <i class="bi bi-download me-1"></i><?= sanitize($f) ?>
                  </a>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-calendar-check me-2"></i>Storico Iscrizioni</span>
          <a href="form.php?id=<?= $id ?>#iscrizioni" class="btn btn-sm btn-aned"><i class="bi bi-plus me-1"></i>Aggiungi Anno</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($iscrizioni)): ?>
            <div class="empty-state py-4"><i class="bi bi-calendar-x"></i><h5>Nessuna quota</h5></div>
          <?php else: ?>
          <table class="table table-aned mb-0">
            <thead><tr><th>Anno</th><th>Data Iscrizione</th><th>Importo</th><th>Note</th><th>Inserito da</th></tr></thead>
            <tbody>
              <?php $totale=0; foreach ($iscrizioni as $iz): $totale+=$iz['importo']; ?>
              <tr>
                <td><span class="badge bg-dark fs-6"><?= $iz['anno'] ?></span></td>
                <td><?= formatDate($iz['data_iscrizione']) ?></td>
                <td><strong class="text-success"><?= formatMoney($iz['importo']) ?></strong></td>
                <td><?= sanitize($iz['note']??'') ?></td>
                <td><?= sanitize(($iz['ins_nome']??'').' '.($iz['ins_cognome']??'')) ?></td>
              </tr>
              <?php endforeach; ?>
              <tr class="table-light fw-bold">
                <td colspan="2">Totale versato</td>
                <td class="text-success"><?= formatMoney($totale) ?></td>
                <td colspan="2"></td>
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
