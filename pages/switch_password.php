<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
check_csrf();

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_nova_senha = $_POST['confirmar_nova_senha'] ?? '';

    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_nova_senha)) {
        $error = "Todos os campos são obrigatórios.";
    } elseif ($nova_senha !== $confirmar_nova_senha) {
        $error = "A nova senha e a confirmação não correspondem.";
    } else {
        $user_id = current_user()['id'];
        $pdo = db();
        
        $stmt = $pdo->prepare("SELECT senha_hash FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha_atual, $user['senha_hash'])) {
            $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = :novo_hash WHERE id = :id");
            $update_stmt->execute([':novo_hash' => $novo_hash, ':id' => $user_id]);
            $message = "Senha alterada com sucesso!";
        } else {
            $error = "A senha atual está incorreta.";
        }
    }
    
    if ($error) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => $error];
    } else {
        $_SESSION['flash_message'] = ['type' => 'good', 'text' => $message];
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/dashboard'));
    exit;
}

header('Location: /dashboard');
exit;