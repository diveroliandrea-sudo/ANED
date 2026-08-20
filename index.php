<?php
require_once __DIR__ . '/config/config.php';
if (isLogged()) {
    header('Location: ' . APP_URL . '/dashboard.php');
} else {
    header('Location: ' . APP_URL . '/login.php');
}
exit;
