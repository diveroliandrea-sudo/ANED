<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT a.*, u.nome as ins_nome, u.cognome as ins_cognome FROM aned_db_attivita a LEFT JOIN aned_db_utenti u ON u.id=a.inserito_da WHERE a.id=?');
$stmt->execute([$id]);
$ev = $stmt->fetch();
if (!$ev) { $_SESSION['flash_error']='Evento non trovato.'; header('Location: index.php'); exit; }

$relatori  = $db->prepare('SELECT * FROM aned_db_attivita_relatori WHERE attivita_id=?');
$relatori->execute([$id]); $relatori = $relatori->fetchAll();
$referenti = $db->prepare('SELECT * FROM aned_db_attivita_referenti WHERE attivita_id=?');
$referenti->execute([$id]); $referenti = $referenti->fetchAll();

define('PAGE_TITLE', $ev['titolo']);
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title"><?= sanitize($ev['titolo']) ?></h1>
      <p class="page-subtitle"><a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i>Torna agli eventi</a></p>
    </div>
    <?php if (hasRole('admin')): ?>
    <div class="d-flex gap-2">
      <a href="form.php?id=<?= $id ?>" class="btn btn-aned"><i class="bi bi-pencil me-2"></i>Modifica</a>
      <a href="delete.php?id=<?= $id ?>" class="btn btn-outline-danger" data-confirm="Eliminare questo evento?"><i class="bi bi-trash me-1"></i>Elimina</a>
    </div>
    <?php endif; ?>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <?php if ($ev['locandina'] && isPreviewableMediaFile($ev['locandina'])): ?>
          <?php $ext = strtolower(pathinfo($ev['locandina'], PATHINFO_EXTENSION)); ?>
          <?php if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)): ?>
            <img src="<?= APP_URL ?>/uploads/locandine/<?= sanitize(basename($ev['locandina'])) ?>"
                 class="img-fluid rounded mb-4 w-100" style="max-height:360px;object-fit:cover" alt="Locandina">
          <?php else: ?>
            <div class="rounded overflow-hidden mb-4 border" style="height:520px;background:#f8f9fa">
              <iframe src="<?= APP_URL ?>/attivita/pdf-preview.php?file=<?= rawurlencode(basename($ev['locandina'])) ?>"
                      title="Anteprima PDF"
                      class="w-100 h-100"
                      style="border:0"></iframe>
            </div>
          <?php endif; ?>
      <?php endif; ?>

      <div class="card mb-4">
        <div class="card-body">
          <div class="d-flex gap-2 mb-3 flex-wrap">
            <span class="badge-stato badge-<?= $ev['stato'] ?>"><?= ucfirst($ev['stato']) ?></span>
            <span class="text-muted"><i class="bi bi-calendar me-1"></i><?= formatDate($ev['data_evento']) ?></span>
            <?php if ($ev['ora_inizio']): ?>
              <span class="text-muted"><i class="bi bi-clock me-1"></i><?= $ev['ora_inizio'] ?><?= $ev['ora_fine']?' – '.$ev['ora_fine']:'' ?></span>
            <?php endif; ?>
            <?php if ($ev['luogo']): ?>
              <span class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= sanitize($ev['luogo']) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($ev['descrizione']): ?>
            <div style="line-height:1.7"><?= nl2br(sanitize($ev['descrizione'])) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($relatori): ?>
      <div class="card mb-4">
        <div class="card-header"><i class="bi bi-mic-fill me-2"></i>Relatori</div>
        <div class="card-body">
          <?php foreach ($relatori as $r): ?>
          <div class="d-flex gap-3 mb-3">
            <div class="member-avatar"><?= strtoupper(substr($r['nome'],0,2)) ?></div>
            <div>
              <div class="fw-600"><?= sanitize($r['nome']) ?></div>
              <?php if ($r['ruolo']): ?><div class="text-muted" style="font-size:13px"><?= sanitize($r['ruolo']) ?></div><?php endif; ?>
              <?php if ($r['bio']): ?><div style="font-size:13px;margin-top:4px"><?= sanitize($r['bio']) ?></div><?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header"><i class="bi bi-info-circle me-2"></i>Informazioni</div>
        <div class="card-body" style="font-size:14px">
          <?php if ($ev['indirizzo_luogo']): ?>
          <p><strong>Indirizzo:</strong><br><?= sanitize($ev['indirizzo_luogo']) ?></p>
          <?php endif; ?>
          <?php if ($ev['max_partecipanti'] > 0): ?>
          <p><strong>Max partecipanti:</strong> <?= $ev['max_partecipanti'] ?></p>
          <?php endif; ?>
          <?php if ($ev['locandina']): ?>
          <a href="<?= APP_URL ?>/uploads/locandine/<?= sanitize(basename($ev['locandina'])) ?>"
             target="_blank" class="btn btn-outline-secondary btn-sm w-100 mb-2"
             download>
            <i class="bi bi-download me-2"></i>Scarica Locandina
          </a>
      <?php endif; ?>
          <small class="text-muted">Inserito da: <?= sanitize(($ev['ins_nome']??'').' '.($ev['ins_cognome']??'')) ?></small>
        </div>
      </div>

      <?php if ($referenti): ?>
      <div class="card">
        <div class="card-header"><i class="bi bi-person-lines-fill me-2"></i>Referenti</div>
        <div class="card-body" style="font-size:14px">
          <?php foreach ($referenti as $r): ?>
          <div class="mb-3">
            <div class="fw-600"><?= sanitize($r['nome']) ?></div>
            <?php if ($r['email']): ?><div><i class="bi bi-envelope me-1 text-muted"></i><a href="mailto:<?= sanitize($r['email']) ?>"><?= sanitize($r['email']) ?></a></div><?php endif; ?>
            <?php if ($r['telefono']): ?><div><i class="bi bi-telephone me-1 text-muted"></i><?= sanitize($r['telefono']) ?></div><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

