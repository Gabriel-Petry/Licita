<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_login();
header('Content-Type: application/json');

if (!tem_permissao('fluxogramas.ver')) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Você não tem permissão para ver fluxogramas.']);
    exit;
}

$fluxograma_id = $_GET['id'] ?? null;
if (!$fluxograma_id) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID do fluxograma inválido.']);
    exit;
}

try {
    $pdo = db();
    // SELECT sem a verificação de 'usuario_id'
    $stmt = $pdo->prepare("SELECT dados_json FROM fluxogramas WHERE id = :id");
    $stmt->execute([':id' => $fluxograma_id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado && !empty($resultado['dados_json'])) {
        echo $resultado['dados_json'];
    } else {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Fluxograma não encontrado ou está vazio.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no banco de dados.']);
}
?>