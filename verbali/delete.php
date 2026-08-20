<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$db = getDB(); $id = intval($_GET['id'] ?? 0);
if ($id) { $db->prepare('DELETE FROM aned_db_verbali WHERE id=?')->execute([$id]); $_SESSION['flash_success']='Verbale eliminato.'; }
header('Location: index.php'); exit;

