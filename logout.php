<?php
require_once __DIR__ . '/config/config.php';
if (isLogged()) {
    $db = getDB();
    $db->prepare('INSERT INTO aned_db_log_attivita (utente_id, azione, ip) VALUES (?,?,?)')
       ->execute([$_SESSION['user_id'], 'logout', $_SERVER['REMOTE_ADDR']??'']);
}
session_destroy();
header('Location: ' . APP_URL . '/login.php');
exit;

