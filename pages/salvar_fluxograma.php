<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$usuario = current_user();
if (!$usuario || !isset($usuario['id'])) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível identificar o usuário da sessão.']);
    exit;
}
$usuario_id = $usuario['id'];

$dadosRecebidos = json_decode(file_get_contents('php://input'), true);
$nome = $dadosRecebidos['nome'] ?? '';
$dados_json = $dadosRecebidos['dados_json'] ?? '';

if (empty($nome) || empty($dados_json)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'O nome e os dados do fluxograma são obrigatórios.']);
    exit;
}

try {
    $pdo = db();
    
    $stmt = $pdo->prepare(
        "INSERT INTO fluxogramas (nome, dados_json, usuario_id) VALUES (:nome, :dados_json, :usuario_id)"
    );

    $stmt->execute([
        ':nome' => $nome,
        ':dados_json' => json_encode($dados_json),
        ':usuario_id' => $usuario_id
    ]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Fluxograma salvo com sucesso!']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no Banco de Dados: ' . $e->getMessage()]);
}

?>