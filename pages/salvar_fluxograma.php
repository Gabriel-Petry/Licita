<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$dadosRecebidos = json_decode(file_get_contents('php://input'), true);
$nome = $dadosRecebidos['nome'] ?? '';
$dados_json = $dadosRecebidos['dados_json'] ?? '';
$fluxograma_id = $dadosRecebidos['id'] ?? null;

if ($fluxograma_id) {
    if (!tem_permissao('fluxogramas.editar')) {
        http_response_code(403);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Você não tem permissão para editar fluxogramas.']);
        exit;
    }
} else {
    if (!tem_permissao('fluxogramas.criar')) {
        http_response_code(403);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Você não tem permissão para criar fluxogramas.']);
        exit;
    }
}

$usuario = current_user();
if (empty($nome) || empty($dados_json)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'O nome e os dados do fluxograma são obrigatórios.']);
    exit;
}

try {
    $pdo = db();
    
    if ($fluxograma_id) {
        $stmt = $pdo->prepare(
            "UPDATE fluxogramas SET nome = :nome, dados_json = :dados_json WHERE id = :id"
        );
        $stmt->execute([
            ':nome' => $nome,
            ':dados_json' => json_encode($dados_json),
            ':id' => $fluxograma_id
        ]);
        $mensagem = 'Fluxograma atualizado com sucesso!';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO fluxogramas (nome, dados_json, usuario_id) VALUES (:nome, :dados_json, :usuario_id)"
        );
        $stmt->execute([
            ':nome' => $nome,
            ':dados_json' => json_encode($dados_json),
            ':usuario_id' => $usuario['id']
        ]);
        $mensagem = 'Fluxograma salvo com sucesso!';
    }

    echo json_encode(['sucesso' => true, 'mensagem' => $mensagem]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no Banco de Dados: ' . $e->getMessage()]);
}
?>