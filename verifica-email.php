<?php
require_once __DIR__ . '/config/config.php';

$token = trim($_GET['token'] ?? '');
$msg   = '';
$ok    = false;

if ($token) {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM aned_db_utenti WHERE token_verifica = ? AND email_verificata = 0');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) {
        $db->prepare('UPDATE aned_db_utenti SET email_verificata=1, attivo=1, token_verifica=NULL WHERE id=?')
           ->execute([$user['id']]);
        $ok  = true;
        $msg = 'Email verificata con successo! Ora puoi accedere.';
        // Notifica admin
        sendMail(MAIL_FROM, 'Nuovo utente registrato - ANED Roma',
            mailTemplate('Nuovo utente', '<p>Un nuovo utente ha verificato la propria email e attivato l\'account.</p>'));
    } else {
        $msg = 'Token non valido o già utilizzato.';
    }
} else {
    $msg = 'Nessun token fornito.';
}

define('PAGE_TITLE', 'Verifica Email');
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="auth-card" style="text-align:center">
  <div class="auth-logo">
    <svg viewBox="0 0 120 130" width="72" height="80" xmlns="http://www.w3.org/2000/svg">
      <text x="60" y="38" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="36" fill="#1a1a2e">ANED</text>
      <polygon points="10,48 110,48 60,128" fill="#c0392b"/>
      <text x="60" y="96" text-anchor="middle" font-family="Arial,sans-serif" font-weight="700" font-size="22" fill="#fff">IT</text>
    </svg>
  </div>
  <?php if ($ok): ?>
    <div class="mb-3"><i class="bi bi-check-circle-fill" style="font-size:56px;color:#27ae60"></i></div>
    <h3 style="color:#27ae60">Verifica completata</h3>
    <p><?= htmlspecialchars($msg) ?></p>
    <a href="<?= APP_URL ?>/login.php" class="btn btn-aned">Accedi ora</a>
  <?php else: ?>
    <div class="mb-3"><i class="bi bi-x-circle-fill" style="font-size:56px;color:var(--aned-red)"></i></div>
    <h3 style="color:var(--aned-red)">Errore</h3>
    <p><?= htmlspecialchars($msg) ?></p>
    <a href="<?= APP_URL ?>/login.php" class="btn btn-aned-outline">Torna al Login</a>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

