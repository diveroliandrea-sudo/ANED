﻿<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $cognome  = trim($_POST['cognome'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $pwd_cur  = $_POST['password_corrente'] ?? '';
    $pwd_new  = $_POST['password_nuova'] ?? '';
    $pwd_cfm  = $_POST['password_conferma'] ?? '';

    if (!$nome || !$cognome) $errors[] = 'Nome e cognome obbligatori.';

    if (empty($errors)) {
        $db->prepare('UPDATE aned_db_utenti SET nome=?,cognome=?,telefono=? WHERE id=?')
           ->execute([$nome, $cognome, $telefono, $_SESSION['user_id']]);
        $_SESSION['user_nome']    = $nome;
        $_SESSION['user_cognome'] = $cognome;

        // Cambio password
        if ($pwd_new) {
            $stmt = $db->prepare('SELECT password_hash FROM aned_db_utenti WHERE id=?');
            $stmt->execute([$_SESSION['user_id']]);
            $hash = $stmt->fetchColumn();
            if (!password_verify($pwd_cur, $hash)) {
                $errors[] = 'Password corrente non valida.';
            } elseif (strlen($pwd_new) < 8) {
                $errors[] = 'Nuova password troppo corta.';
            } elseif ($pwd_new !== $pwd_cfm) {
                $errors[] = 'Le nuove password non coincidono.';
            } else {
                $newHash = password_hash($pwd_new, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare('UPDATE aned_db_utenti SET password_hash=? WHERE id=?')->execute([$newHash, $_SESSION['user_id']]);
                $_SESSION['flash_success'] = 'Profilo e password aggiornati.';
            }
        } else {
            $_SESSION['flash_success'] = 'Profilo aggiornato.';
        }
        if (empty($errors)) { header('Location: profilo.php'); exit; }
    }
}

$stmt = $db->prepare('SELECT * FROM aned_db_utenti WHERE id=?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

define('PAGE_TITLE', 'Il mio Profilo');
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<div class="main-content">
  <div class="page-header">
    <h1 class="page-title"><i class="bi bi-person-circle me-2 text-danger"></i>Il mio Profilo</h1>
  </div>
  <?php flash(); foreach ($errors as $e): ?><div class="alert alert-danger"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

  <div class="row g-4" style="max-width:700px">
    <div class="col-12">
      <div class="card">
        <div class="card-header">Dati Personali</div>
        <div class="card-body">
          <form method="POST">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Nome *</label><input type="text" name="nome" class="form-control" value="<?= sanitize($user['nome']) ?>" required></div>
              <div class="col-md-6"><label class="form-label">Cognome *</label><input type="text" name="cognome" class="form-control" value="<?= sanitize($user['cognome']) ?>" required></div>
              <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled></div>
              <div class="col-md-6"><label class="form-label">Telefono</label><input type="tel" name="telefono" class="form-control" value="<?= sanitize($user['telefono']??'') ?>"></div>
              <div class="col-12"><hr><h6>Cambia Password <small class="text-muted">(lascia vuoto per non cambiare)</small></h6></div>
              <div class="col-md-4"><label class="form-label">Password Corrente</label><input type="password" name="password_corrente" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Nuova Password</label><input type="password" name="password_nuova" class="form-control" minlength="8"></div>
              <div class="col-md-4"><label class="form-label">Conferma</label><input type="password" name="password_conferma" class="form-control"></div>
            </div>
            <button type="submit" class="btn btn-aned mt-4"><i class="bi bi-save me-2"></i>Salva Modifiche</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
