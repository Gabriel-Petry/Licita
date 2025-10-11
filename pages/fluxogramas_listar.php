<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$user = current_user();

try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, nome FROM fluxogramas WHERE usuario_id = :usuario_id ORDER BY nome ASC");
    $stmt->execute([':usuario_id' => $user['id']]);
    $fluxogramas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $fluxogramas = [];
    set_flash_message('error', 'Não foi possível carregar os fluxogramas.');
}

$page_scripts = [
    'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/backbone.js/1.4.0/backbone-min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/jointjs/3.4.1/joint.min.js',
    '/js/fluxogramas_listar.js'
];

render_header('Meus Fluxogramas', ['scripts' => $page_scripts]);
?>

<div class="card">
    <div class="card-header-grid">
        <h2>Meus Fluxogramas</h2>
        <div class="card-header-actions">
            <a href="/fluxograma/editor" class="btn primary">Criar Novo Fluxograma</a>
        </div>
    </div>

    <div id="fluxograma-browser-container">
        <div id="fluxograma-sidebar">
            <h3>Fluxogramas Salvos</h3>
            <ul id="fluxograma-list">
                <?php if (empty($fluxogramas)): ?>
                    <li>Nenhum fluxograma encontrado.</li>
                <?php else: ?>
                    <?php foreach ($fluxogramas as $fluxograma): ?>
                        <li class="fluxo-item" data-id="<?= htmlspecialchars($fluxograma['id']) ?>">
                            <span class="fluxo-item-name"><?= htmlspecialchars($fluxograma['nome']) ?></span>
                            <div class="fluxo-item-actions">
                                <a href="/fluxograma/editor?id=<?= htmlspecialchars($fluxograma['id']) ?>" class="btn-action edit" title="Editar">✏️</a>
                                <button class="btn-action delete" data-id="<?= htmlspecialchars($fluxograma['id']) ?>" title="Excluir">🗑️</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        
        <div id="fluxograma-preview-container">
            <div id="preview-paper"></div>
            <div id="preview-placeholder" class="placeholder-active">
                <p>Selecione um fluxograma na lista ao lado para visualizar.</p>
                <small>Use CTRL+Roda do Mouse para zoom e ALT+Arrastar para mover a tela.</small>
            </div>
        </div>
    </div>
</div>

<?php
render_footer();
?>