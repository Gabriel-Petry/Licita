<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_login();
header('Content-Type: application/json');

if (!tem_permissao('fluxogramas.excluir')) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Você não tem permissão para excluir fluxogramas.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$dadosRecebidos = json_decode(file_get_contents('php://input'), true);
$fluxograma_id = $dadosRecebidos['id'] ?? null;

if (!$fluxograma_id) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID do fluxograma inválido.']);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("DELETE FROM fluxogramas WHERE id = :id");
    $stmt->execute([':id' => $fluxograma_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Fluxograma excluído com sucesso!']);
    } else {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Fluxograma não encontrado.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>