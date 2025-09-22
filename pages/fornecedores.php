<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();
if (!tem_permissao('fornecedores.ver')) {
    header('Location: /dashboard');
    exit;
}

check_csrf();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            if (!tem_permissao('fornecedores.criar')) throw new Exception('Acesso negado.');
            $stmt = $pdo->prepare(
                "INSERT INTO fornecedores (nome, cnpj, contato) VALUES (:nome, :cnpj, :contato)"
            );
            $stmt->execute([':nome' => $_POST['nome'], ':cnpj' => $_POST['cnpj'], ':contato' => $_POST['contato'] ?? null]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Fornecedor cadastrado com sucesso!'];

        } elseif ($action === 'update') {
            if (!tem_permissao('fornecedores.editar')) throw new Exception('Acesso negado.');
            $stmt = $pdo->prepare("UPDATE fornecedores SET nome=:nome, cnpj=:cnpj, contato=:contato WHERE id=:id");
            $stmt->execute([':id' => $_POST['id'], ':nome' => $_POST['nome'], ':cnpj' => $_POST['cnpj'], ':contato' => $_POST['contato'] ?? null]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Fornecedor atualizado com sucesso!'];

        } elseif ($action === 'delete') {
            if (!tem_permissao('fornecedores.excluir')) throw new Exception('Acesso negado.');
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM contratos WHERE fornecedor_id = :id");
            $check_stmt->execute([':id' => $_POST['id']]);
            if ($check_stmt->fetchColumn() > 0) {
                 $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erro: Este fornecedor não pode ser excluído pois está vinculado a um ou mais contratos.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM fornecedores WHERE id=:id");
                $stmt->execute([':id' => $_POST['id']]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Fornecedor excluído com sucesso.'];
            }
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => $e->getMessage()];
    }
    header('Location: /fornecedores');
    exit;
}

$items_per_page = 50;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$where = [];
$params = [];
if (!empty($_GET['q'])) {
    $where[] = "(nome LIKE :q OR cnpj LIKE :q)";
    $params[':q'] = '%' . $_GET['q'] . '%';
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

$count_stmt = $pdo->prepare("SELECT COUNT(id) FROM fornecedores $where_clause");
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM fornecedores $where_clause ORDER BY nome LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val);
}
$stmt->execute();
$fornecedores = $stmt->fetchAll();

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function render_fornecedores_table_content($fornecedores, $total_items, $items_per_page, $current_page) {
    ob_start();
?>
    <div class="table-scroll-container">
        <table>
            <thead><tr><th>Nome</th><th>CNPJ</th><th>Contato</th><th>Ações</th></tr></thead>
            <tbody>
                <?php foreach ($fornecedores as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['nome']) ?></td>
                    <td><?= htmlspecialchars($f['cnpj']) ?></td>
                    <td><?= htmlspecialchars($f['contato']) ?></td>
                    <td>
                        <?php if (tem_permissao('fornecedores.editar')): ?>
                            <a href="#editar-fornecedor-popup-<?= $f['id'] ?>" class="btn">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                 <?php if (empty($fornecedores)): ?>
                    <tr><td colspan="4" style="text-align:center; padding: 1.5rem;">Nenhum fornecedor encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
        $base_url = '/fornecedores?';
        $query_params = $_GET;
        unset($query_params['page']);
        $base_url .= http_build_query($query_params) . '&';
        render_pagination($total_items, $items_per_page, $current_page, $base_url);
    ?>
<?php
    return ob_get_clean();
}

if ($is_ajax) {
    echo render_fornecedores_table_content($fornecedores, $total_items, $items_per_page, $current_page);
    exit;
}

render_header('Fornecedores - LicitAções');
?>

<div class="card">
    <?php display_flash_message(); ?>
    <div class="toolbar">
        <form class="inline" method="get" id="fornecedores-filter-form">
            <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Buscar por Nome ou CNPJ...">
        </form>
        <?php if (tem_permissao('fornecedores.criar')): ?>
            <a href="#novo-fornecedor-popup" class="btn primary right">Novo Fornecedor</a>
        <?php endif; ?>
    </div>

    <div id="fornecedores-table-container">
        <?= render_fornecedores_table_content($fornecedores, $total_items, $items_per_page, $current_page) ?>
    </div>
</div>

<?php if (tem_permissao('fornecedores.criar')): ?>
<div id="novo-fornecedor-popup" class="popup-overlay">
    <div class="popup-card card">
        <a href="#" class="popup-close">&times;</a>
        <h2>Novo Fornecedor</h2>
        <form method="post" class="form-popup">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="create">
            <div class="popup-content">
                <div class="grid grid-2">
                    <div><label>Nome</label><input name="nome" required></div>
                    <div><label>CNPJ</label><input name="cnpj" class="cnpj-mask" required></div>
                </div>
                 <div><label>Contato</label><input type="text" name="contato"></div>
            </div>
            <div class="form-actions">
                <button class="btn good" type="submit">Salvar Novo Fornecedor</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (tem_permissao('fornecedores.editar')): ?>
<?php foreach ($fornecedores as $f): ?>
<div id="editar-fornecedor-popup-<?= $f['id'] ?>" class="popup-overlay">
  <div class="popup-card card">
    <a href="#" class="popup-close">&times;</a>
    <h2>Editar Fornecedor #<?= $f['id'] ?></h2>
    <form method="post" class="form-popup">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $f['id'] ?>">
        <div class="popup-content">
            <div class="grid grid-2">
                <div><label>Nome</label><input name="nome" value="<?= htmlspecialchars($f['nome']) ?>" required></div>
                <div><label>CNPJ</label><input name="cnpj" value="<?= htmlspecialchars($f['cnpj']) ?>" class="cnpj-mask" required></div>
            </div>
            <div><label>Contato</label><input type="text" name="contato" value="<?= htmlspecialchars($f['contato']) ?>"></div>
        </div>
        <div class="form-actions">
            <?php if (tem_permissao('fornecedores.excluir')): ?>
                <button class="btn warn btn-confirm-delete" type="submit" name="action" value="delete" data-confirm-message="Excluir este fornecedor? Esta ação não pode ser desfeita.">Excluir</button>
            <?php endif; ?>
            <button class="btn good" type="submit" name="action" value="update">Atualizar Fornecedor</button>
        </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php

$footer_scripts = <<<HTML
<script>
document.querySelectorAll('.cnpj-mask').forEach(input => {
    input.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/^(\d{2})(\d)/, '$1.$2');
        value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
        value = value.replace(/(\d{4})(\d)/, '$1-$2');
        e.target.value = value.slice(0, 18);
    });
});
</script>
HTML;

render_footer(['custom_script' => $footer_scripts]);
?>