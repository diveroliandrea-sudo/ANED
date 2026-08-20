<?php
// Database remoto VPS
define('DB_HOST',    '172.245.156.21');
define('DB_PORT',    '3306');
define('DB_NAME',    'deportati');
define('DB_USER',    'root');
define('DB_PASS',    'Deportati.1');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX',  'aned_db_');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Connessione al database fallita: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
