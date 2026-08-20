<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo','segreteria');
$db = getDB();
$id = intval($_GET['id'] ?? 0);
$iscritto_id = intval($_GET['iscritto_id'] ?? 0);
if ($id) {
    $db->prepare('DELETE FROM aned_db_iscrizioni WHERE id=?')->execute([$id]);
    $_SESSION['flash_success'] = 'Quota eliminata.';
}
header('Location: form.php?id=' . $iscritto_id . '#aned_db_iscrizioni');
exit;

