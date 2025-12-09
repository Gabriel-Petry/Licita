<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

function json_error($code, $message) {
    http_response_code($code);
    echo json_encode(['sucesso' => false, 'mensagem' => $message]);
    exit;
}

if (!is_logged_in()) {
    json_error(401, 'Acesso não autorizado. Por favor, faça login.');
}
if (!tem_permissao('fluxogramas.ver')) {
    json_error(403, 'Você não tem permissão para ver fluxogramas.');
}

$fluxograma_id = $_GET['id'] ?? null;
if (!$fluxograma_id) {
    json_error(400, 'ID do fluxograma inválido.');
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT dados_json FROM fluxogramas WHERE id = :id");
    $stmt->execute([':id' => $fluxograma_id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado && !empty($resultado['dados_json'])) {
        echo $resultado['dados_json'];
    } else {
        json_error(404, 'Fluxograma não encontrado ou está vazio.');
    }
} catch (PDOException $e) {
    json_error(500, 'Erro interno no servidor ao consultar o banco de dados.');
}