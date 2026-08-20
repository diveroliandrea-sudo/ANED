﻿﻿<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$db = getDB();

// Statistiche
$totIscritti   = $db->query('SELECT COUNT(*) FROM aned_db_iscritti WHERE attivo=1')->fetchColumn();
$totEventi     = $db->query('SELECT COUNT(*) FROM aned_db_attivita WHERE stato="pubblicata"')->fetchColumn();
$totContatti   = $db->query('SELECT COUNT(*) FROM aned_db_rubrica')->fetchColumn();
$annoCorrente  = date('Y');
$totIscrittiAnno = $db->prepare('SELECT COUNT(*) FROM aned_db_iscrizioni WHERE anno=?');
$totIscrittiAnno->execute([$annoCorrente]);
$totIscrittiAnno = $totIscrittiAnno->fetchColumn();

// Quote iscrizioni anno corrente
$entrateIscrizioni = $db->prepare('SELECT COALESCE(SUM(importo),0) FROM aned_db_iscrizioni WHERE anno=?');
$entrateIscrizioni->execute([$annoCorrente]);
$totEntrateIscrizioni = $entrateIscrizioni->fetchColumn();

// Entrate extra anno corrente (tabella aned_db_entrate)
$totEntrateExtra = 0;
try {
    $entrateExtra = $db->prepare("SELECT COALESCE(SUM(importo),0) FROM aned_db_entrate WHERE YEAR(data_entrata)=?");
    $entrateExtra->execute([$annoCorrente]);
    $totEntrateExtra = $entrateExtra->fetchColumn();
} catch (Exception $e) { /* tabella non ancora creata */ }

$totEntrate = $totEntrateIscrizioni + $totEntrateExtra;

// Spese anno corrente
$speseStmt = $db->query("SELECT COALESCE(SUM(importo),0) FROM aned_db_spese WHERE YEAR(data_spesa)=$annoCorrente");
$totSpese  = $speseStmt->fetchColumn();

// Prossimi eventi
$eventi = $db->query("SELECT * FROM aned_db_attivita WHERE stato='pubblicata' AND data_evento >= CURDATE() ORDER BY data_evento ASC LIMIT 5")->fetchAll();

// Ultimi iscritti
$ultimi = $db->query("SELECT * FROM aned_db_iscritti ORDER BY created_at DESC LIMIT 6")->fetchAll();

// Triangolo Rosso da spedire
$triangolo = $db->query("SELECT COUNT(*) FROM aned_db_iscritti WHERE flag_triangolo_rosso=1 AND attivo=1")->fetchColumn();

define('PAGE_TITLE', 'Dashboard');
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title">Dashboard</h1>
      <p class="page-subtitle">Benvenuto, <?= sanitize($_SESSION['user_nome']) ?>! — <?= date('l d F Y') ?></p>
    </div>
  </div>

  <?php flash(); flash('error'); ?>

  <!-- Stat Cards -->
  <?php if (!hasRole('utente')): ?>
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-2">
      <div class="stat-card stat-card-red">
        <i class="bi bi-people-fill stat-icon"></i>
        <div class="stat-info">
          <div class="stat-value"><?= $totIscritti ?></div>
          <div class="stat-label">Iscritti Totali</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card stat-card-dark">
        <i class="bi bi-person-check-fill stat-icon"></i>
        <div class="stat-info">
          <div class="stat-value"><?= $totIscrittiAnno ?></div>
          <div class="stat-label">Iscritti <?= $annoCorrente ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card stat-card-green">
        <i class="bi bi-cash-coin stat-icon"></i>
        <div class="stat-info">
          <div class="stat-value" style="font-size:18px"><?= formatMoney($totEntrate) ?></div>
          <div class="stat-label">Entrate <?= $annoCorrente ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card stat-card-orange">
        <i class="bi bi-receipt stat-icon"></i>
        <div class="stat-info">
          <div class="stat-value" style="font-size:18px"><?= formatMoney($totSpese) ?></div>
          <div class="stat-label">Spese <?= $annoCorrente ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card stat-card-blue">
        <i class="bi bi-calendar-event-fill stat-icon"></i>
        <div class="stat-info">
          <div class="stat-value"><?= $totEventi ?></div>
          <div class="stat-label">Attività</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="stat-card stat-card-purple">
        <i class="bi bi-newspaper stat-icon"></i>
        <div class="stat-info">
          <div class="stat-value"><?= $triangolo ?></div>
          <div class="stat-label">Triangolo Rosso</div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Prossimi eventi -->
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-calendar3 me-2 text-danger"></i>Prossimi Eventi</span>
          <a href="<?= APP_URL ?>/attivita/index.php" class="btn btn-sm btn-aned">Tutti</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($eventi)): ?>
            <div class="empty-state py-4">
              <i class="bi bi-calendar-x"></i>
              <h5>Nessun evento programmato</h5>
            </div>
          <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($eventi as $ev): ?>
              <div class="list-group-item list-group-item-action px-4 py-3">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="fw-600"><?= sanitize($ev['titolo']) ?></div>
                    <small class="text-muted">
                      <i class="bi bi-calendar me-1"></i><?= formatDate($ev['data_evento']) ?>
                      <?php if ($ev['luogo']): ?>&nbsp;|&nbsp;<i class="bi bi-geo-alt me-1"></i><?= sanitize($ev['luogo']) ?><?php endif; ?>
                    </small>
                  </div>
                  <a href="<?= APP_URL ?>/attivita/view.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-secondary ms-2">Dettaglio</a>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Ultimi Iscritti -->
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-person-plus-fill me-2 text-danger"></i>Ultimi Iscritti</span>
          <a href="<?= APP_URL ?>/iscritti/index.php" class="btn btn-sm btn-aned">Tutti</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($ultimi)): ?>
            <div class="empty-state py-4"><i class="bi bi-people"></i><h5>Nessun iscritto</h5></div>
          <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($ultimi as $u): ?>
              <div class="list-group-item px-4 py-2 d-flex align-items-center gap-3">
                <div class="member-avatar" style="width:34px;height:34px;font-size:12px">
                  <?= strtoupper(substr($u['nome'],0,1).substr($u['cognome'],0,1)) ?>
                </div>
                <div class="flex-1">
                  <div class="fw-600" style="font-size:14px"><?= sanitize($u['nome'].' '.$u['cognome']) ?></div>
                  <small class="text-muted"><?= sanitize($u['citta']??'') ?></small>
                </div>
                <?php if ($u['flag_triangolo_rosso']): ?>
                  <i class="bi bi-triangle-fill flag-tr" title="Triangolo Rosso" data-bs-toggle="tooltip"></i>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Saldo finanziario -->
  <?php if (hasRole('admin','direttivo')): ?>
  <div class="row g-4 mt-1">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><i class="bi bi-graph-up me-2 text-success"></i>Saldo <?= $annoCorrente ?></div>
        <div class="card-body text-center py-4">
          <?php $saldo = $totEntrate - $totSpese; ?>
          <div style="font-size:32px;font-weight:800;color:<?= $saldo>=0?'#27ae60':'#c0392b' ?>">
            <?= formatMoney($saldo) ?>
          </div>
          <div class="text-muted mt-2">Entrate totali - Spese</div>
          <div class="row mt-3">
            <div class="col text-success"><small>↑ Entrate totali</small><br><strong><?= formatMoney($totEntrate) ?></strong></div>
            <div class="col text-danger"><small>↓ Spese</small><br><strong><?= formatMoney($totSpese) ?></strong></div>
          </div>
          <hr class="my-3">
          <div class="row text-muted" style="font-size:12px">
            <div class="col">Quote iscrizioni<br><strong class="text-dark"><?= formatMoney($totEntrateIscrizioni) ?></strong></div>
            <div class="col">Altre entrate<br><strong class="text-dark"><?= formatMoney($totEntrateExtra) ?></strong></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
