<?php
require_once __DIR__ . '/config/config.php';

if (isLogged()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Inserisci email e password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM aned_db_utenti WHERE email = ? AND attivo = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['email_verificata']) {
                $error = 'Email non ancora verificata. Controlla la tua casella di posta.';
            } else {
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['user_nome']    = $user['nome'];
                $_SESSION['user_cognome'] = $user['cognome'];
                $_SESSION['user_email']   = $user['email'];
                $_SESSION['user_role']    = $user['ruolo'];

                // Log
                $db->prepare('INSERT INTO aned_db_log_attivita (utente_id, azione, ip) VALUES (?,?,?)')
                   ->execute([$user['id'], 'login', $_SERVER['REMOTE_ADDR']??'']);

                header('Location: ' . APP_URL . '/dashboard.php');
                exit;
            }
        } else {
            $error = 'Credenziali non valide o account disabilitato.';
        }
    }
}

define('PAGE_TITLE', 'Accedi');
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="auth-card">
  <div class="auth-logo">
    <svg viewBox="0 0 120 130" width="90" height="98" xmlns="http://www.w3.org/2000/svg">
      <text x="60" y="38" text-anchor="middle" font-family="Arial Black,Arial,sans-serif"
            font-weight="900" font-size="36" fill="#1a1a2e" letter-spacing="2">ANED</text>
      <polygon points="10,48 110,48 60,128" fill="#c0392b"/>
      <text x="60" y="96" text-anchor="middle" font-family="Arial,sans-serif"
            font-weight="700" font-size="22" fill="#ffffff">IT</text>
    </svg>
  </div>
  <h1 class="auth-title">Bentornato</h1>
  <p class="auth-subtitle">Accedi al gestionale ANED Roma</p>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php flash('success'); ?>

  <form method="POST" novalidate>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" placeholder="tua@email.it"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label d-flex justify-content-between">
        Password
        <a href="<?= APP_URL ?>/recupera-password.php" class="text-decoration-none" style="color:var(--aned-red);font-size:13px">Password dimenticata?</a>
      </label>
      <div class="input-group">
        <input type="password" name="password" id="pwd" class="form-control" placeholder="••••••••" required>
        <button type="button" class="btn btn-outline-secondary" onclick="togglePwd()">
          <i class="bi bi-eye" id="eyeIcon"></i>
        </button>
      </div>
    </div>
    <div class="mb-4 form-check">
      <input type="checkbox" class="form-check-input" id="ricordami" name="ricordami">
      <label class="form-check-label" for="ricordami" style="font-size:13px">Ricordami</label>
    </div>
    <button type="submit" class="btn btn-aned w-100 py-2">
      <i class="bi bi-box-arrow-in-right me-2"></i>Accedi
    </button>
  </form>

  <hr class="my-3">
  <p class="text-center" style="font-size:14px">
    Non hai un account?
    <a href="<?= APP_URL ?>/registrazione.php" style="color:var(--aned-red);font-weight:600">Registrati</a>
  </p>
</div>

<script>
function togglePwd() {
  const p = document.getElementById('pwd');
  const i = document.getElementById('eyeIcon');
  if (p.type === 'password') { p.type = 'text'; i.className = 'bi bi-eye-slash'; }
  else { p.type = 'password'; i.className = 'bi bi-eye'; }
}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>

