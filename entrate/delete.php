<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo');

$db  = getDB();
$id  = intval($_GET['id'] ?? 0);
$anno = intval($_GET['anno'] ?? date('Y'));

if ($id) {
    $stmt = $db->prepare('SELECT file_ricevuta FROM aned_db_entrate WHERE id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        // Rimuove il file fisico se presente
        if (!empty($row['file_ricevuta'])) {
            $path = UPLOAD_DIR . 'ricevute_entrate/' . basename($row['file_ricevuta']);
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        $db->prepare('DELETE FROM aned_db_entrate WHERE id=?')->execute([$id]);
        $_SESSION['flash_success'] = 'Entrata eliminata.';
    } else {
        $_SESSION['flash_error'] = 'Entrata non trovata.';
    }
}

header('Location: index.php?anno=' . $anno);
exit;
