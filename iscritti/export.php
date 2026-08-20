﻿<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin','direttivo','segreteria');

$db = getDB();
$search = trim($_GET['q'] ?? '');
$anno   = intval($_GET['anno'] ?? 0);
$trFilter = isset($_GET['tr']) ? 1 : null;

$where = ['i.attivo = 1'];
$params = [];
if ($search) {
    $where[] = '(i.nome LIKE ? OR i.cognome LIKE ? OR i.codice_fiscale LIKE ? OR i.email LIKE ? OR i.id LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s, $s]);
}
if ($anno > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM aned_db_iscrizioni iz WHERE iz.iscritto_id=i.id AND iz.anno=?)';
    $params[] = $anno;
}
if ($trFilter !== null) {
    $where[] = 'i.flag_triangolo_rosso = 1';
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = 'SELECT i.* FROM aned_db_iscritti i ' . $whereSQL . ' ORDER BY i.cognome, i.nome';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="iscritti_' . date('Ymd') . '.csv"');
echo "\xEF\xBB\xBF"; // BOM UTF-8
$out = fopen('php://output','w');
fputcsv($out, ['Codice','Cognome','Nome','CF','Data Nascita','Indirizzo','CAP','Città','Prov','Tel','Cell','Email','Triangolo Rosso'], ';', '"', '\\');
foreach ($rows as $r) {
    $cap = trim((string) ($r['cap'] ?? ''));
    if ($cap !== '' && strlen($cap) < 5) {
        $cap = "'" . str_pad($cap, 5, '0', STR_PAD_LEFT);
    } elseif ($cap !== '') {
        $cap = "'" . $cap;
    }

    fputcsv($out, [
        $r['id'], $r['cognome'], $r['nome'], $r['codice_fiscale'],
        $r['data_nascita'], $r['indirizzo'], $cap, $r['citta'], $r['provincia'],
        $r['telefono'], $r['cellulare'], $r['email'],
        $r['flag_triangolo_rosso'] ? 'Sì' : 'No'
    ], ';', '"', '\\');
}
fclose($out);
exit;
