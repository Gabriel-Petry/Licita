<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Verifica se o usuário está logado na sessão.
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_ok = false;
        $session_csrf = $_SESSION['csrf'] ?? '';

        if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && hash_equals($session_csrf, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $csrf_ok = true;
        }

        if (isset($_POST['csrf']) && hash_equals($session_csrf, $_POST['csrf'])) {
            $csrf_ok = true;
        }

        if (!$csrf_ok) {
            http_response_code(400);
            die('CSRF token inválido.');
        }
    }
}

function login($email, $password) {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash, tema FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $u = $stmt->fetch();

    if ($u && password_verify($password, $u['senha_hash'])) {
        session_regenerate_id(); 
        $_SESSION['user_id'] = $u['id'];
        
        $stmt_perm = $pdo->prepare("
            SELECT DISTINCT p.codigo 
            FROM usuarios_niveis un
            JOIN nivel_acesso_permissoes nap ON un.nivel_acesso_id = nap.nivel_acesso_id
            JOIN permissoes p ON nap.permissao_id = p.id
            WHERE un.usuario_id = :user_id
        ");
        $stmt_perm->execute([':user_id' => $u['id']]);
        $permissoes = $stmt_perm->fetchAll(PDO::FETCH_COLUMN);
        $_SESSION['permissoes'] = $permissoes;

        return true;
    }
    return false;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /login');
        exit;
    }
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, nome, email, tema, orgao_id FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    return $user;
}

function tem_permissao($codigo_permissao) {
    if (empty($_SESSION['permissoes']) || !is_array($_SESSION['permissoes'])) {
        return false;
    }
    return in_array($codigo_permissao, $_SESSION['permissoes']);
}

function logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}