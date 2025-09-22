<?php

require_once __DIR__ . '/includes/auth.php';

$request_uri = strtok($_SERVER['REQUEST_URI'], '?');

$base_path = '';
$route = str_replace($base_path, '', $request_uri);

if (empty($route) || $route === '/') {
    $route = '/login';
}

$routes = [
    '/login' => 'login.php',
    '/logout' => 'logout.php',
    '/dashboard' => 'dashboard.php',
    '/resumo' => 'resumo.php',
    '/licitacoes' => 'licitacoes.php',
    '/diretas' => 'diretas.php',
    '/homologadas' => 'homologadas.php',
    '/cadastros' => 'cadastros.php',
    '/fornecedores' => 'fornecedores.php',
    '/atas' => 'atas.php',
    '/contratos' => 'contratos.php',
    '/cadastrar_usuario' => 'cadastrar_usuario.php',
    '/switch_password' => 'switch_password.php',
    '/gerar_relatorio' => 'gerar_relatorio.php',
    '/pca' => 'pca.php',
    '/demandas' => 'demandas.php',
    '/salvar_tema' => 'salvar_tema.php'
];

if (array_key_exists($route, $routes)) {
    require_once __DIR__ . '/pages/' . $routes[$route];
} else {
    http_response_code(404);
    echo "<h1>Erro 404: Página não encontrada</h1>";
}