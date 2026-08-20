<?php
require_once __DIR__ . '/config/config.php';

$step    = 'request';
$success = false;
$error   = '';

if (isset($_GET['token'])) {
    $step  = 'reset';
    $token = trim($_GET['token']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();

    if (($_POST['step'] ?? '') === 'request') {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Inserisci un\'email valida.';
        } else {
            $stmt = $db->prepare('SELECT id,nome FROM aned_db_utenti WHERE email=? AND attivo=1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $token   = generateToken();
                $scadenza = date('Y-m-d H:i:s', strtotime('+2 hours'));
                $db->prepare('UPDATE aned_db_utenti SET token_reset=?, token_reset_scadenza=? WHERE id=?')
                   ->execute([$token, $scadenza, $user['id']]);
                $link = APP_URL . '/recupera-password.php?token=' . $token;
                $body = '<p>Ciao <strong>' . htmlspecialchars($user['nome']) . '</strong>,</p>
                         <p>Hai richiesto il reset della password. Clicca il pulsante entro 2 ore:</p>
                         <a href="' . $link . '" class="btn">Reimposta Password</a>
                         <p style="margin-top:16px;font-size:12px;color:#718096">Link: ' . $link . '</p>';
                sendMail($email, 'Reset Password - ANED Roma', mailTemplate('Reset Password', $body));
            }
            // Rispondiamo sempre uguale per sicurezza
            $success = true;
        }
    } elseif (($_POST['step'] ?? '') === 'reset') {
        $token    = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if (strlen($password) < 8) {
            $error = 'Password troppo corta (min 8 caratteri).';
        } elseif ($password !== $confirm) {
            $error = 'Le password non coincidono.';
        } else {
            $stmt = $db->prepare('SELECT id FROM aned_db_utenti WHERE token_reset=? AND token_reset_scadenza > NOW()');
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            if ($user) {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare('UPDATE aned_db_utenti SET password_hash=?, token_reset=NULL, token_reset_scadenza=NULL WHERE id=?')
                   ->execute([$hash, $user['id']]);
                $_SESSION['flash_success'] = 'Password aggiornata! Ora puoi accedere.';
                header('Location: ' . APP_URL . '/login.php');
                exit;
            } else {
                $error = 'Token non valido o scaduto.';
            }
        }
    }
}

define('PAGE_TITLE', 'Recupera Password');
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="auth-card">
  <div class="auth-logo">
    <svg viewBox="0 0 120 130" width="72" height="80" xmlns="http://www.w3.org/2000/svg">
      <text x="60" y="38" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="36" fill="#1a1a2e">ANED</text>
      <polygon points="10,48 110,48 60,128" fill="#c0392b"/>
      <text x="60" y="96" text-anchor="middle" font-family="Arial,sans-serif" font-weight="700" font-size="22" fill="#fff">IT</text>
    </svg>
  </div>

  <?php if ($step === 'request'): ?>
    <h1 class="auth-title">Password Dimenticata</h1>
    <p class="auth-subtitle">Inserisci la tua email per ricevere il link di reset</p>
    <?php if ($success): ?>
      <div class="alert alert-success"><i class="bi bi-envelope me-2"></i>Se l'email è registrata, riceverai le istruzioni a breve.</div>
      <a href="<?= APP_URL ?>/login.php" class="btn btn-aned w-100">Torna al Login</a>
    <?php else: ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="step" value="request">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="tua@email.it" required>
        </div>
        <button type="submit" class="btn btn-aned w-100">
          <i class="bi bi-send me-2"></i>Invia link di reset
        </button>
      </form>
      <p class="text-center mt-3" style="font-size:14px">
        <a href="<?= APP_URL ?>/login.php" style="color:var(--aned-red)"><i class="bi bi-arrow-left me-1"></i>Torna al login</a>
      </p>
    <?php endif; ?>

  <?php else: ?>
    <h1 class="auth-title">Nuova Password</h1>
    <p class="auth-subtitle">Scegli la tua nuova password</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="step" value="reset">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
      <div class="mb-3">
        <label class="form-label">Nuova Password</label>
        <input type="password" name="password" class="form-control" required minlength="8">
      </div>
      <div class="mb-3">
        <label class="form-label">Conferma Password</label>
        <input type="password" name="password_confirm" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-aned w-100">
        <i class="bi bi-lock me-2"></i>Imposta Password
      </button>
    </form>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

