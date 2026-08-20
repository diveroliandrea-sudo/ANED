<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo');
$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id) {
    $db->prepare('UPDATE aned_db_consiglio_direttivo SET attivo=0 WHERE id=?')->execute([$id]);
    $_SESSION['flash_success'] = 'Membro rimosso dal consiglio.';
}
header('Location: index.php');
exit;

