<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';

function render_pagination($total_items, $items_per_page, $current_page, $base_url) {
    $total_pages = ceil($total_items / $items_per_page);
    if ($total_pages <= 1) {
        return;
    }

    echo '<div class="pagination">';

    if ($current_page > 1) {
        echo '<a href="' . $base_url . 'page=1">&laquo; Primeira</a>';
        echo '<a href="' . $base_url . 'page=' . ($current_page - 1) . '">&lsaquo; Anterior</a>';
    } else {
        echo '<span class="disabled">&laquo; Primeira</span>';
        echo '<span class="disabled">&lsaquo; Anterior</span>';
    }

    $range = 2;
    for ($i = ($current_page - $range); $i < (($current_page + $range) + 1); $i++) {
        if (($i > 0) && ($i <= $total_pages)) {
            if ($i == $current_page) {
                echo '<span class="current">' . $i . '</span>';
            } else {
                echo '<a href="' . $base_url . 'page=' . $i . '">' . $i . '</a>';
            }
        }
    }

    if ($current_page < $total_pages) {
        echo '<a href="' . $base_url . 'page=' . ($current_page + 1) . '">Próxima &rsaquo;</a>';
        echo '<a href="' . $base_url . 'page=' . $total_pages . '">Última &raquo;</a>';
    } else {
        echo '<span class="disabled">Próxima &rsaquo;</span>';
        echo '<span class="disabled">Última &raquo;</span>';
    }

    echo '</div>';
}

function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $message['type'] === 'success' ? 'good' : 'error';
        echo '<div class="chip ' . $type . '" style="margin-bottom: 1rem;">' . htmlspecialchars($message['text']) . '</div>';
        unset($_SESSION['flash_message']);
    }
}

function render_header(string $title = "LicitAções", array $options = []) {
$csp = "default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data:; object-src 'none'; frame-ancestors 'none'; form-action 'self'; base-uri 'self';";
    header("Content-Security-Policy: " . $csp);
    $user = current_user();
    $bodyClass = $options['bodyClass'] ?? '';
    $userTheme = $user['tema'] ?? 'dark';     
    $showHeader = $options['showHeader'] ?? true;
    global $page_scripts;
    $page_scripts = $options['scripts'] ?? [];

    $cache_bust = '?v=' . time();
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">

        <title><?= htmlspecialchars($title) ?></title>
        <link rel="stylesheet" href="/css/base.css<?= $cache_bust ?>">
        <link rel="stylesheet" href="/css/layout.css<?= $cache_bust ?>">
        <link rel="stylesheet" href="/css/components.css<?= $cache_bust ?>">
        <link rel="stylesheet" href="/css/pages.css<?= $cache_bust ?>">
        <link rel="stylesheet" href="/css/searchable-select.css<?= $cache_bust ?>">
        <link rel="stylesheet" href="/css/theme.css<?= $cache_bust ?>">
        <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/jointjs/3.4.1/joint.min.css" />
    	<link rel="stylesheet" href="/css/fluxograma.css">
    </head>
    <body class="<?= htmlspecialchars($bodyClass) ?>" data-theme="<?= htmlspecialchars($userTheme) ?>">
        <?php if ($showHeader): ?>
        <header class="topbar">
            <div class="brand">LicitAções</div>
            
            <div class="nav-wrapper" id="nav-menu">
                <nav class="nav">
                    <?php if ($user): ?>
                        <?php if (tem_permissao('dashboard.ver')): ?>
                            <a href="/dashboard">Dashboard</a>
                        <?php endif; ?>
                        
                        <?php // O Resumo pode usar a mesma permissão de licitações, por exemplo.
                        if (tem_permissao('licitacoes.ver')): ?>
                            <a href="/resumo">Resumo</a>
                        <?php endif; ?>

                        <?php if (tem_permissao('pca.gerenciar') || tem_permissao('demandas.criar')): ?>
                            <a href="/pca">Plano Anual (PCA)</a>
                        <?php endif; ?>

                        <?php // Dropdown de Cadastros só aparece se o usuário tiver permissão para ver PELO MENOS UM item
                        if (
                            tem_permissao('licitacoes.ver') || tem_permissao('diretas.ver') || tem_permissao('concluidos.ver') ||
                            tem_permissao('contratos.ver') || tem_permissao('atas.ver') || tem_permissao('parametros.gerenciar') ||
                            tem_permissao('fornecedores.ver')
                        ): ?>
                        <div class="nav-dropdown">
                            <button class="linklike">Cadastros &#9662;</button>
                            <div class="nav-dropdown-content">
                                <?php if (tem_permissao('licitacoes.ver')): ?><a href="/licitacoes">Licitações</a><?php endif; ?>
                                <?php if (tem_permissao('diretas.ver')): ?><a href="/diretas">Diretas/Inex</a><?php endif; ?>
                                <?php if (tem_permissao('concluidos.ver')): ?><a href="/homologadas">Concluídos</a><?php endif; ?>
                                <?php if (tem_permissao('contratos.ver')): ?><a href="/contratos">Contratos</a><?php endif; ?>
                                <?php if (tem_permissao('atas.ver')): ?><a href="/atas">Atas</a><?php endif; ?>
                                <?php if (tem_permissao('parametros.gerenciar')): ?><a href="/cadastros">Parâmetros</a><?php endif; ?>
                                <?php if (tem_permissao('fornecedores.ver')): ?><a href="/fornecedores">Fornecedores</a><?php endif; ?>
                                <a href="/fluxogramas">Fluxogramas</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="nav-dropdown">
                            <button class="linklike"><?= htmlspecialchars($user["nome"]) ?> &#9662;</button>
                            <div class="nav-dropdown-content">
                                <a href="#alterar-senha-popup">Alterar Senha</a>
                                <a href="#themes-popup">Alterar Tema</a>
                                <?php if (tem_permissao('usuarios.gerenciar')): ?>
                                    <a href="/cadastrar_usuario">Gerenciar Usuários</a>
                                <?php endif; ?>
                                <form action="/logout" method="post" style="margin: 0;">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                                    <button type="submit" class="linklike" style="width: 100%; text-align: left; padding: 12px 16px; display: block;">Sair</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </nav>
                <nav class="nav-mobile">
                     <?php if ($user): ?>
                        <?php if (tem_permissao('dashboard.ver')): ?><a href="/dashboard">Dashboard</a><?php endif; ?>
                        <?php if (tem_permissao('licitacoes.ver')): ?><a href="/resumo">Resumo</a><?php endif; ?>
                        <?php if (tem_permissao('pca.gerenciar') || tem_permissao('demandas.criar')): ?><a href="/pca">Plano Anual (PCA)</a><?php endif; ?>
                        
                        <span class="nav-mobile-divider">Cadastros</span>
                        <?php if (tem_permissao('licitacoes.ver')): ?><a href="/licitacoes">Licitações</a><?php endif; ?>
                        <?php if (tem_permissao('diretas.ver')): ?><a href="/diretas">Diretas/Inex</a><?php endif; ?>
                        <?php if (tem_permissao('concluidos.ver')): ?><a href="/homologadas">Concluídos</a><?php endif; ?>
                        <?php if (tem_permissao('contratos.ver')): ?><a href="/contratos">Contratos</a><?php endif; ?>
                        <?php if (tem_permissao('atas.ver')): ?><a href="/atas">Atas</a><?php endif; ?>
                        <?php if (tem_permissao('parametros.gerenciar')): ?><a href="/cadastros">Parâmetros</a><?php endif; ?>
                        <?php if (tem_permissao('fornecedores.ver')): ?><a href="/fornecedores">Fornecedores</a><?php endif; ?>
                    	<a href="/fluxogramas">Fluxograma</a>
                        
                        <span class="nav-mobile-divider"><?= htmlspecialchars($user["nome"]) ?></span>
                        <a href="#alterar-senha-popup">Alterar Senha</a>
                        <a href="#themes-popup">Alterar Tema</a>
                        <?php if (tem_permissao('usuarios.gerenciar')): ?><a href="/cadastrar_usuario">Gerenciar Usuários</a><?php endif; ?>
                        <form action="/logout" method="post" style="margin-top: 1rem;">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <button type="submit" class="linklike">Sair</button>
                        </form>
                    <?php endif; ?>
                </nav>
            </div>
            
            <button class="nav-toggle" id="nav-toggle" aria-label="Abrir menu">
                <span></span><span></span><span></span>
            </button>
        </header>
        <?php endif; ?>
        <main class="container">
    <?php
        if (isset($_SESSION['flash_message'])) {
            $msg = $_SESSION['flash_message'];
            echo '<div class="chip ' . htmlspecialchars($msg['type']) . '" style="margin-bottom: 1rem; text-align: center;">' . htmlspecialchars($msg['text']) . '</div>';
            unset($_SESSION['flash_message']);
        }
}

function render_footer() {
    global $page_scripts;
    $cache_bust = '?v=' . time();
    ?>
        </main>
        <footer>
            <div class="connection-status">✅ Conectado ao MySQL</div>
            &copy; <?= date("Y") ?> LicitAções
        </footer>
        <script src="/js/searchable-select.js<?= $cache_bust ?>" defer></script>
        <script src="/js/app.js<?= $cache_bust ?>" defer></script>
        
        <div id="alterar-senha-popup" class="popup-overlay">
            <div class="popup-card card popup-small">
                <a href="#" class="popup-close">&times;</a>
                <h2>Alterar Senha</h2>
                <form method="post" action="/switch_password" class="form-popup" style="height: auto;">
                    <div class="popup-content" style="height: auto; padding-right: 0;">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                        
                        <div>
                            <label for="senha_atual">Senha Atual</label>
                            <input type="password" id="senha_atual" name="senha_atual" required>
                        </div>
                        
                        <div>
                            <label for="nova_senha">Nova Senha</label>
                            <input type="password" id="nova_senha" name="nova_senha" required>
                        </div>

                        <div>
                            <label for="confirmar_nova_senha">Confirmar Nova Senha</label>
                            <input type="password" id="confirmar_nova_senha" name="confirmar_nova_senha" required>
                        </div>
                    </div>
                    <div class="form-actions" style="margin: 1.5rem -24px -24px -24px;">
                        <button class="btn good" type="submit">Salvar Nova Senha</button>
                    </div>
                </form>
            </div>
        </div>
        <div id="themes-popup" class="popup-overlay">
          <div class="popup-card card popup-small">
            <a href="#" class="popup-close">&times;</a>
            <h2>Escolha um Tema</h2>
                <div class="themes-container" style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                    <button class="btn" data-theme="dark">Escuro</button>
                    <button class="btn" data-theme="light" style="background: #f1f5f9; color: #1e293b;">Claro</button>
                    <button class="btn" data-theme="blue" style="background: #1b1a55; color: #f0f3ff;">Roxo</button>
                    <button class="btn" data-theme="oceano" style="background: #172a46; color: #64ffda;">Oceano</button>
                    <button class="btn" data-theme="matrix" style="background: #0d0d0d; color: #39ff14;">Matrix</button>
                    <button class="btn" data-theme="solarized" style="background: #073642; color: #eee8d5;">Solarized</button>
                    <button class="btn" data-theme="contraste" style="background: white; color: #005fcc; border-color: #005fcc;">Alto Contraste</button>
                </div>
          </div>
        </div>

        <div id="relatorio-popup" class="popup-overlay">
            </div>

        <?php foreach ($page_scripts as $script): ?>
            <script src="<?= htmlspecialchars($script) . $cache_bust ?>" defer></script>
        <?php endforeach; ?>
    </body>
    </html>
    <?php
}