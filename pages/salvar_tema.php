<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$json_data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido enviado.']);
    exit;
}

$session_csrf = $_SESSION['csrf'] ?? null;
$request_csrf = $json_data['csrf'] ?? null;

if (!$session_csrf || !$request_csrf || !hash_equals($session_csrf, $request_csrf)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Falha na validação de segurança (CSRF).']);
    exit;
}

$tema = $json_data['tema'] ?? '';
$user = current_user();
$allowed_themes = ['dark', 'light', 'blue', 'oceano', 'matrix', 'solarized', 'contraste'];

if (in_array($tema, $allowed_themes) && $user) {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("UPDATE usuarios SET tema = :tema WHERE id = :user_id");
        $stmt->execute([':tema' => $tema, ':user_id' => $user['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Tema atualizado no banco de dados.']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro de banco de dados: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tema inválido ou usuário não encontrado.']);
}

exit;