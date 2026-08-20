<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$db = getDB();

// Cambio ruolo o stato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid    = intval($_POST['user_id'] ?? 0);
    $ruolo  = $_POST['ruolo'] ?? '';
    $attivo = intval($_POST['attivo'] ?? 0);
    $allowed = ['admin','direttivo','segreteria','utente'];

    if ($uid && in_array($ruolo, $allowed)) {
        if ($uid !== $_SESSION['user_id']) { // Non può cambiare se stesso
            $db->prepare('UPDATE aned_db_utenti SET ruolo=?, attivo=? WHERE id=?')->execute([$ruolo, $attivo, $uid]);
            $_SESSION['flash_success'] = 'Utente aggiornato.';
        } else {
            $_SESSION['flash_error'] = 'Non puoi modificare il tuo stesso account da qui.';
        }
    }
    header('Location: utenti.php'); exit;
}

// Reset password manuale
if (isset($_GET['reset']) && ($uid = intval($_GET['reset']))) {
    $pwd  = 'ANED_' . substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 8);
    $hash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare('SELECT email,nome FROM aned_db_utenti WHERE id=?');
    $stmt->execute([$uid]);
    $u = $stmt->fetch();
    if ($u) {
        $db->prepare('UPDATE aned_db_utenti SET password_hash=? WHERE id=?')->execute([$hash, $uid]);
        $body = '<p>Ciao <strong>'.htmlspecialchars($u['nome']).'</strong>,</p>
                 <p>La tua password è stata reimpostata dall\'amministratore.</p>
                 <p>Nuova password temporanea: <strong>' . $pwd . '</strong></p>
                 <p>Accedi e cambiala subito.</p>';
        sendMail($u['email'], 'Password reimpostata - ANED Roma', mailTemplate('Nuova Password', $body));
        $_SESSION['flash_success'] = "Password reimpostata e inviata a {$u['email']}.";
    }
    header('Location: utenti.php'); exit;
}

// Elimina utente
if (isset($_GET['delete']) && ($uid = intval($_GET['delete']))) {
    if ($uid !== $_SESSION['user_id']) {
        $db->prepare('UPDATE aned_db_utenti SET attivo=0 WHERE id=?')->execute([$uid]);
        $_SESSION['flash_success'] = 'Utente disabilitato.';
    }
    header('Location: utenti.php'); exit;
}

$utenti = $db->query("SELECT * FROM aned_db_utenti ORDER BY ruolo, cognome, nome")->fetchAll();
define('PAGE_TITLE', 'Gestione Utenti');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content">
  <div class="page-header">
    <h1 class="page-title"><i class="bi bi-shield-lock-fill me-2 text-danger"></i>Gestione Utenti</h1>
    <a href="<?= APP_URL ?>/registrazione.php" class="btn btn-aned" target="_blank"><i class="bi bi-person-plus-fill me-2"></i>Aggiungi Utente</a>
  </div>
  <?php flash(); flash('error'); ?>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-aned mb-0">
        <thead><tr><th>Utente</th><th>Email</th><th>Tipo</th><th>Ruolo</th><th>Stato</th><th>Registrato</th><th>Azioni</th></tr></thead>
        <tbody>
          <?php foreach ($utenti as $u): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="user-avatar" style="width:34px;height:34px;font-size:12px">
                  <?= strtoupper(substr($u['nome'],0,1).substr($u['cognome'],0,1)) ?>
                </div>
                <div>
                  <div class="fw-600"><?= sanitize($u['nome'].' '.$u['cognome']) ?></div>
                  <?php if ($u['telefono']): ?><small class="text-muted"><?= sanitize($u['telefono']) ?></small><?php endif; ?>
                </div>
              </div>
            </td>
            <td><?= sanitize($u['email']) ?></td>
            <td>
              <?php if ($u['id'] === $_SESSION['user_id']): ?>
                <span class="badge bg-dark"><?= ucfirst($u['ruolo']) ?></span>
              <?php else: ?>
              <form method="POST" class="d-inline">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <input type="hidden" name="attivo" value="<?= $u['attivo'] ?>">
                <select name="ruolo" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                  <?php foreach (['admin','direttivo','segreteria','utente'] as $r): ?>
                    <option value="<?= $r ?>" <?= $u['ruolo']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($u['id'] === $_SESSION['user_id']): ?>
                <span class="badge bg-success">Attivo (tu)</span>
              <?php else: ?>
              <form method="POST" class="d-inline">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <input type="hidden" name="ruolo" value="<?= $u['ruolo'] ?>">
                <div class="form-check form-switch d-inline">
                  <input class="form-check-input" type="checkbox" name="attivo" value="1"
                         <?= $u['attivo']?'checked':'' ?> onchange="this.form.submit()">
                </div>
              </form>
              <?php if (!$u['email_verificata']): ?>
                <span class="badge bg-warning text-dark ms-1">Email non verificata</span>
              <?php endif; ?>
              <?php endif; ?>
            </td>
            <td><small class="text-muted"><?= formatDate($u['created_at']) ?></small></td>
            <td>
              <?php if ($u['id'] !== $_SESSION['user_id']): ?>
              <a href="utenti.php?reset=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning" title="Reset Password"><i class="bi bi-key"></i></a>
              <a href="utenti.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" title="Disabilita" data-confirm="Disabilitare questo utente?"><i class="bi bi-person-x"></i></a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Ruoli e permessi info -->
  <div class="card mt-4">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>Riepilogo Ruoli e Permessi</div>
    <div class="card-body">
      <div class="row g-3" style="font-size:14px">
        <div class="col-md-3">
          <div class="p-3 rounded" style="background:#fff3cd">
            <strong><i class="bi bi-shield-fill-check me-1"></i>Admin</strong>
            <ul class="mt-2 mb-0 ps-3">
              <li>Accesso completo a tutto</li>
              <li>Gestione Utenti e ruoli</li>
              <li>Reset password</li>
            </ul>
          </div>
        </div>
        <div class="col-md-3">
          <div class="p-3 rounded" style="background:#d4edda">
            <strong><i class="bi bi-person-badge me-1"></i>Direttivo</strong>
            <ul class="mt-2 mb-0 ps-3">
              <li>Iscritti, consiglio, statuto</li>
              <li>Verbali, estratti, spese</li>
              <li>Attività ed eventi</li>
            </ul>
          </div>
        </div>
        <div class="col-md-3">
          <div class="p-3 rounded" style="background:#d1ecf1">
            <strong><i class="bi bi-person-gear me-1"></i>Segreteria</strong>
            <ul class="mt-2 mb-0 ps-3">
              <li>Iscritti e quote</li>
              <li>Verbali e statuto</li>
              <li>Attività, rubrica</li>
            </ul>
          </div>
        </div>
        <div class="col-md-3">
          <div class="p-3 rounded" style="background:#e2e8f0">
            <strong><i class="bi bi-person me-1"></i>Utente</strong>
            <ul class="mt-2 mb-0 ps-3">
              <li>Solo visualizzazione eventi</li>
              <li>Dashboard personale</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

