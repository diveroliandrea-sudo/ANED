<?php
session_start();

define('APP_NAME', 'ANED Roma - Gestione Associazione');
// URL dinamico - funziona sia in locale che sul server
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Rileva automaticamente la sottocartella (es: /ANED)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
// Risale fino alla cartella ANED
if (preg_match('#(/ANED)#i', $scriptDir, $m)) {
    $basePath = substr($scriptDir, 0, strpos(strtolower($scriptDir), '/aned') + strlen($m[1]));
} else {
    $basePath = '/ANED';
}
define('APP_URL', $protocol . '://' . $host . $basePath);
define('APP_VERSION', '1.0.0');

// Ruoli
define('ROLE_ADMIN', 'admin');
define('ROLE_DIRETTIVO', 'direttivo');
define('ROLE_SEGRETERIA', 'segreteria');
define('ROLE_UTENTE', 'utente');


// Upload paths
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/mail.php';

// Helpers
function isLogged() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLogged()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function hasRole(...$roles) {
    if (!isLogged()) return false;
    return in_array($_SESSION['user_role'], $roles);
}

function requireRole(...$roles) {
    requireLogin();
    if (!hasRole(...$roles)) {
        $_SESSION['flash_error'] = 'Accesso non autorizzato.';
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}

function flash($type = 'success') {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        $cls = $type === 'error' ? 'alert-danger' : ($type === 'warning' ? 'alert-warning' : 'alert-success');
        echo '<div class="alert ' . $cls . ' alert-dismissible fade show" role="alert">'
            . htmlspecialchars($msg)
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

function sanitize($val) {
    if ($val === null) return '';
    return htmlspecialchars(trim((string)$val), ENT_QUOTES, 'UTF-8');
}

function buildExportQueryString(array $filters): string
{
    $params = [];

    if (isset($filters['q']) && trim((string) $filters['q']) !== '') {
        $params['q'] = trim((string) $filters['q']);
    }

    if (!empty($filters['anno'])) {
        $params['anno'] = (int) $filters['anno'];
    }

    if (!empty($filters['tr'])) {
        $params['tr'] = 1;
    }

    if ($params === []) {
        return '';
    }

    return '?' . http_build_query($params);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function formatDate($date) {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

function formatMoney($amount) {
    return '€ ' . number_format((float)$amount, 2, ',', '.');
}
