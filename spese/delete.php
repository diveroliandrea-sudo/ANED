<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo');
$db = getDB(); $id = intval($_GET['id'] ?? 0);
if ($id) { $db->prepare('DELETE FROM aned_db_spese WHERE id=?')->execute([$id]); $_SESSION['flash_success']='Spesa eliminata.'; }
header('Location: index.php'); exit;

