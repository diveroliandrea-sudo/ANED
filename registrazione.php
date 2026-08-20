﻿<?php
require_once __DIR__ . '/config/config.php';

if (isLogged()) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome        = trim($_POST['nome'] ?? '');
    $cognome     = trim($_POST['cognome'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirm     = $_POST['password_confirm'] ?? '';
    $telefono    = trim($_POST['telefono'] ?? '');

    if (!$nome)    $errors[] = 'Il nome è obbligatorio.';
    if (!$cognome) $errors[] = 'Il cognome è obbligatorio.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email non valida.';
    if (strlen($password) < 8) $errors[] = 'La password deve essere di almeno 8 caratteri.';
    if ($password !== $confirm) $errors[] = 'Le password non coincidono.';

    if (empty($errors)) {
        $db = getDB();
        $chk = $db->prepare('SELECT id FROM aned_db_utenti WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $errors[] = 'Email già registrata.';
        } else {
            $token = generateToken();
            $hash  = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Inserisce utente
            $sql_user = 'INSERT INTO aned_db_utenti (nome,cognome,email,password_hash,ruolo,attivo,email_verificata,token_verifica,telefono)
                         VALUES (?,?,?,?,?,0,0,?,?)';
            $stmt_user = $db->prepare($sql_user);
            $stmt_user->execute([$nome, $cognome, $email, $hash, 'utente', $token, $telefono]);

            $newUserId = $db->lastInsertId();

            // Inserisce anche in rubrica
            $sql_rubrica = 'INSERT INTO aned_db_rubrica (nome,cognome,email,cellulare,categoria,note,created_by)
                            VALUES (?,?,?,?,?,?,?)';
            $stmt_rubrica = $db->prepare($sql_rubrica);
            $stmt_rubrica->execute([
                $nome, $cognome, $email, $telefono,
                'Utente',
                'Registrato automaticamente dal portale ANED',
                $newUserId
            ]);

            // Invia email di verifica
            $link = APP_URL . '/verifica-email.php?token=' . $token;
            $body = '<p>Ciao <strong>' . htmlspecialchars($nome) . '</strong>,</p>
                     <p>Grazie per esserti registrato al gestionale ANED Roma.</p>
                     <p>Per attivare il tuo account clicca il pulsante:</p>
                     <a href="' . $link . '" class="btn">Verifica Email</a>
                     <p style="margin-top:16px;font-size:12px;color:#718096">Oppure copia questo link: ' . $link . '</p>';

            sendMail($email, 'Verifica la tua email - ANED Roma', mailTemplate('Verifica Email', $body));
            $success = true;
        }
    }
}

define('PAGE_TITLE', 'Registrazione');
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="auth-card" style="max-width:500px">
  <div class="auth-logo">
    <svg viewBox="0 0 120 130" width="72" height="80" xmlns="http://www.w3.org/2000/svg">
      <text x="60" y="38" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="36" fill="#1a1a2e">ANED</text>
      <polygon points="10,48 110,48 60,128" fill="#c0392b"/>
      <text x="60" y="96" text-anchor="middle" font-family="Arial,sans-serif" font-weight="700" font-size="22" fill="#fff">IT</text>
    </svg>
  </div>
  <h1 class="auth-title">Crea Account</h1>
  <p class="auth-subtitle">Registrati al gestionale ANED Roma</p>

  <?php if ($success): ?>
    <div class="alert alert-success">
      <i class="bi bi-envelope-check-fill me-2"></i>
      Registrazione avvenuta! Controlla la tua email per verificare l'account.
    </div>
    <a href="<?= APP_URL ?>/login.php" class="btn btn-aned w-100">Vai al Login</a>
  <?php else: ?>
    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
      <div class="row g-3">
        <div class="col-6">
          <label class="form-label">Nome *</label>
          <input type="text" name="nome" class="form-control" value="<?= sanitize($_POST['nome']??'') ?>" required>
        </div>
        <div class="col-6">
          <label class="form-label">Cognome *</label>
          <input type="text" name="cognome" class="form-control" value="<?= sanitize($_POST['cognome']??'') ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label">Email *</label>
          <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email']??'') ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label">Telefono</label>
          <input type="tel" name="telefono" class="form-control" value="<?= sanitize($_POST['telefono']??'') ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Password * <small class="text-muted">(min. 8 caratteri)</small></label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-12">
          <label class="form-label">Conferma Password *</label>
          <input type="password" name="password_confirm" class="form-control" required>
        </div>
      </div>
      <button type="submit" class="btn btn-aned w-100 mt-4 py-2">
        <i class="bi bi-person-plus-fill me-2"></i>Registrati
      </button>
    </form>
    <p class="text-center mt-3" style="font-size:14px">
      Hai già un account? <a href="<?= APP_URL ?>/login.php" style="color:var(--aned-red);font-weight:600">Accedi</a>
    </p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
