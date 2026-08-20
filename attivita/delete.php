<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id) {
    $db->prepare('DELETE FROM aned_db_attivita WHERE id=?')->execute([$id]);
    $_SESSION['flash_success'] = 'Attività eliminata.';
}
header('Location: index.php');
exit;

