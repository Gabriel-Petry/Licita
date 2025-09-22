<?php
require_once __DIR__ . '/../includes/auth.php';
check_csrf();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logout();
}
header('Location: /login');
exit;
?>