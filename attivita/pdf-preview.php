<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$fileName = basename($_GET['file'] ?? '');
if ($fileName === '') {
    http_response_code(404);
    exit;
}

$path = UPLOAD_DIR . 'locandine/' . $fileName;
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
if ($extension !== 'pdf') {
    http_response_code(404);
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($path);
exit;
