<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

// ETAPA 1: Protege a página inteira
require_login();
if (!tem_permissao('atas.ver')) {
    header('Location: /dashboard');
    exit;
}

check_csrf();
$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create' || $action === 'update') {
            if ($action === 'create' && !tem_permissao('atas.criar')) throw new Exception('Acesso negado.');
            if ($action === 'update' && !tem_permissao('atas.editar')) throw new Exception('Acesso negado.');

            $stmt_objeto = $pdo->prepare("SELECT objeto FROM licitacoes WHERE id = :licitacao_id");
            $stmt_objeto->execute([':licitacao_id' => $_POST['licitacao_id']]);
            $objeto_licitacao = $stmt_objeto->fetchColumn();

            if (!$objeto_licitacao) throw new Exception('Erro: Licitação vinculada não encontrada.');

            $params = [
                ':licitacao_id' => $_POST['licitacao_id'],
                ':fornecedor_id' => $_POST['fornecedor_id'],
                ':numero_ata' => $_POST['numero_ata'],
                ':inicio_vigencia' => empty($_POST['inicio_vigencia']) ? null : $_POST['inicio_vigencia'],
                ':validade' => empty($_POST['validade']) ? null : $_POST['validade'],
                ':objeto' => $objeto_licitacao
            ];
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO atas_registro_preco (licitacao_id, fornecedor_id, numero_ata, inicio_vigencia, validade, objeto) VALUES (:licitacao_id, :fornecedor_id, :numero_ata, :inicio_vigencia, :validade, :objeto)");
            } else {
                $params[':id'] = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE atas_registro_preco SET licitacao_id=:licitacao_id, fornecedor_id=:fornecedor_id, numero_ata=:numero_ata, inicio_vigencia=:inicio_vigencia, validade=:validade, objeto=:objeto WHERE id=:id");
            }
            $stmt->execute($params);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Ata salva com sucesso!'];

        } elseif ($action === 'delete') {
            if (!tem_permissao('atas.excluir')) throw new Exception('Acesso negado.');
            $stmt = $pdo->prepare("DELETE FROM atas_registro_preco WHERE id = :id");
            $stmt->execute([':id' => $_POST['id']]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Ata excluída com sucesso!'];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => $e->getMessage()];
    }
    header('Location: /atas');
    exit;
}

$items_per_page = 50;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;
$where = [];
$params = [];
$current_orgao_id = $_GET['orgao_id'] ?? 0;

if ($user['orgao_id'] && !tem_permissao('dados.ver_todos_orgaos')) {
    $where[] = "l.orgao_id = :user_orgao_id";
    $params[':user_orgao_id'] = $user['orgao_id'];
}

if (!empty($_GET['q'])) {
    $where[] = "(a.numero_ata LIKE :q OR a.objeto LIKE :q OR l.processo LIKE :q OR f.nome LIKE :q)";
    $params[':q'] = '%' . $_GET['q'] . '%';
}
if (!empty($current_orgao_id)) {
    $where[] = "l.orgao_id = :orgao_id";
    $params[':orgao_id'] = $current_orgao_id;
}

$where_clause = $where ? " WHERE " . implode(" AND ", $where) : "";

$count_sql = "SELECT COUNT(a.id) FROM atas_registro_preco a LEFT JOIN licitacoes l ON l.id = a.licitacao_id LEFT JOIN fornecedores f ON f.id = a.fornecedor_id" . $where_clause;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();

$sql = "SELECT a.*, l.processo as licitacao_processo, f.nome as fornecedor_nome FROM atas_registro_preco a LEFT JOIN licitacoes l ON l.id = a.licitacao_id LEFT JOIN fornecedores f ON f.id = a.fornecedor_id" . $where_clause . " ORDER BY a.id DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
$params_with_pagination = array_merge($params, [':limit' => $items_per_page, ':offset' => $offset]);
foreach ($params_with_pagination as $key => &$val) { is_int($val) ? $stmt->bindParam($key, $val, PDO::PARAM_INT) : $stmt->bindParam($key, $val, PDO::PARAM_STR); }
$stmt->execute();
$rows = $stmt->fetchAll();

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function render_atas_table_content($rows, $total_items, $items_per_page, $current_page) {
    ob_start();
?>
    <table>
      <thead><tr><th>Nº Ata</th><th>Licitação Vinculada</th><th>Fornecedor</th><th>Objeto</th><th>Vigência</th><th>Tempo Restante</th><th>Ações</th></tr></thead>
      <tbody>
        <?php if (count($rows) > 0): foreach ($rows as $r):
                $status_texto = '--'; $status_classe = '';
                if (!empty($r['validade'])) {
                    $dias_restantes = (new DateTime())->diff(new DateTime($r['validade']))->format('%r%a');
                    if ($dias_restantes < 0) { $status_texto = 'Expirado'; $status_classe = 'expirado'; }
                    elseif ($dias_restantes <= 30) { $status_texto = $dias_restantes . ' dia(s)'; $status_classe = 'atencao'; }
                    else { $status_texto = $dias_restantes . ' dia(s)'; $status_classe = 'ok'; }
                }
            ?>
            <tr>
              <td><?= htmlspecialchars($r['numero_ata']) ?></td>
              <td><?= htmlspecialchars($r['licitacao_processo']) ?></td>
              <td><?= htmlspecialchars($r['fornecedor_nome']) ?></td>
              <td><?= htmlspecialchars($r['objeto']) ?></td>
              <td><?= !empty($r['inicio_vigencia']) ? htmlspecialchars(date('d/m/Y', strtotime($r['inicio_vigencia']))) : '--' ?> a <?= !empty($r['validade']) ? htmlspecialchars(date('d/m/Y', strtotime($r['validade']))) : '--' ?></td>
              <td><span class="status-vencimento <?= $status_classe ?>"><?= $status_texto ?></span></td>
              <td>
                <?php if (tem_permissao('atas.editar')): ?><a href="#editar-ata-popup-<?= $r['id'] ?>" class="btn">Editar</a><?php endif; ?>
              </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="7" style="text-align: center; padding: 2rem;">Nenhuma ata encontrada.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?php
      $base_url = '/atas?'.http_build_query(array_diff_key($_GET, ['page' => ''])).'&';
      render_pagination($total_items, $items_per_page, $current_page, $base_url);
    ?>
<?php
    return ob_get_clean();
}

if ($is_ajax) {
    echo render_atas_table_content($rows, $total_items, $items_per_page, $current_page);
    exit;
}

$licitacoes = db()->query("SELECT id, processo, n_edital, objeto FROM licitacoes ORDER BY processo")->fetchAll();
$fornecedores = db()->query("SELECT id, nome FROM fornecedores ORDER BY nome")->fetchAll();
$orgaos = $pdo->query("SELECT id, nome FROM orgaos ORDER BY nome")->fetchAll();

render_header('Atas de Registro de Preço - LicitAções', ['bodyClass' => 'page-licitacoes']);
?>
<style>
    .status-vencimento { font-weight: bold; padding: 4px 8px; border-radius: 4px; color: white; text-align: center; white-space: nowrap; }
    .status-vencimento.ok { background-color: var(--cor-sucesso); }
    .status-vencimento.atencao { background-color: var(--cor-aviso); }
    .status-vencimento.expirado { background-color: var(--cor-perigo); }
</style>
<div class="card">
  <?php display_flash_message(); ?>
  <div class="toolbar" style="flex-wrap: wrap; justify-content: flex-start;">
    <a href="/atas" class="btn btn-sm <?= empty($current_orgao_id) ? 'primary' : '' ?>">Todas</a>
    <?php foreach ($orgaos as $orgao): ?>
        <a href="/atas?orgao_id=<?= $orgao['id'] ?>" class="btn btn-sm <?= (int)$current_orgao_id === $orgao['id'] ? 'primary' : '' ?>"><?= htmlspecialchars($orgao['nome']) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="toolbar">
    <form class="inline" method="get" id="atas-filter-form">
      <?php if (!empty($current_orgao_id)): ?><input type="hidden" name="orgao_id" value="<?= htmlspecialchars($current_orgao_id) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Buscar por Nº, Objeto, Processo ou Fornecedor...">
      <?php if (tem_permissao('relatorios.gerar')): ?>
      <div class="dropdown-relatorio" style="margin-left: 8px;">
        <button type="button" class="btn">Gerar Relatório &#9662;</button>
        <div class="dropdown-relatorio-content">
          <a href="/gerar_relatorio?tipo=atas&<?= http_build_query($_GET) ?>&formato=pdf" target="_blank">PDF</a>
          <a href="/gerar_relatorio?tipo=atas&<?= http_build_query($_GET) ?>&formato=excel" target="_blank">Excel</a>
        </div>
      </div>
      <?php endif; ?>
    </form>
    <?php if (tem_permissao('atas.criar')): ?>
    <a href="#nova-ata-popup" class="btn primary right">Nova Ata</a>
    <?php endif; ?>
  </div>
  <div class="table-scroll-container">
    <div id="atas-table-container">
        <?php echo render_atas_table_content($rows, $total_items, $items_per_page, $current_page); ?>
    </div>
  </div>
</div>

<?php if (tem_permissao('atas.criar')): ?>
<div id="nova-ata-popup" class="popup-overlay">
    <div class="popup-card card">
        <a href="#" class="popup-close">&times;</a>
        <h2>Nova Ata de Registro de Preço</h2>
        <form method="post" class="form-popup">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="create">
            <div class="popup-content">
                <div class="grid grid-3">
                    <div><label>Nº da Ata (ex: 123/2025)</label><input name="numero_ata" required></div>
                    <div><label>Início da Vigência</label><input type="date" name="inicio_vigencia" required></div>
                    <div><label>Fim da Vigência</label><input type="date" name="validade" required></div>
                </div>
                <div class="grid grid-2">
                    <div><label>Licitação Vinculada</label>
                        <select name="licitacao_id" required class="searchable-select">
                            <option value="">-- Selecione --</option>
                            <?php foreach($licitacoes as $l): ?>
                                <option value="<?= $l['id'] ?>" data-objeto="<?= htmlspecialchars($l['objeto']) ?>" data-full-text="<?= htmlspecialchars(($l['n_edital'] ?: $l['processo']) . ' - ' . $l['objeto']) ?>">
                                    <?= htmlspecialchars(($l['n_edital'] ?: $l['processo']) . ' - ' . substr($l['objeto'], 0, 80) . (strlen($l['objeto']) > 80 ? '...' : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Fornecedor</label>
                        <select name="fornecedor_id" required class="searchable-select">
                            <option value="">-- Selecione --</option>
                            <?php foreach($fornecedores as $f) echo '<option value="'.$f['id'].'">'.htmlspecialchars($f['nome']).'</option>'; ?>
                        </select>
                    </div>
                </div>
                <div><label>Objeto</label><textarea name="objeto" rows="4" readonly style="background: #0e1430; cursor: not-allowed;"></textarea></div>
            </div>
            <div class="form-actions"><button class="btn good" type="submit">Salvar Nova Ata</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (tem_permissao('atas.editar')): ?>
<?php foreach ($rows as $r): ?>
<div id="editar-ata-popup-<?= $r['id'] ?>" class="popup-overlay">
    <div class="popup-card card">
        <a href="#" class="popup-close">&times;</a>
        <h2>Editar Ata #<?= $r['id'] ?></h2>
        <form method="post" class="form-popup">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <div class="popup-content">
                <div class="grid grid-3">
                    <div><label>Nº da Ata</label><input name="numero_ata" value="<?= htmlspecialchars($r['numero_ata']) ?>" required></div>
                    <div><label>Início da Vigência</label><input type="date" name="inicio_vigencia" value="<?= !empty($r['inicio_vigencia']) ? htmlspecialchars($r['inicio_vigencia']) : '' ?>" required></div>
                    <div><label>Fim da Vigência</label><input type="date" name="validade" value="<?= !empty($r['validade']) ? htmlspecialchars($r['validade']) : '' ?>" required></div>
                </div>
                 <div class="grid grid-2">
                    <div><label>Licitação Vinculada</label>
                        <select name="licitacao_id" required class="searchable-select">
                            <option value="">--</option>
                            <?php foreach($licitacoes as $l): ?>
                                <option <?= ($r['licitacao_id']==$l['id']?'selected':'') ?> value="<?= $l['id'] ?>" data-objeto="<?= htmlspecialchars($l['objeto']) ?>" data-full-text="<?= htmlspecialchars(($l['n_edital'] ?: $l['processo']) . ' - ' . $l['objeto']) ?>">
                                     <?= htmlspecialchars(($l['n_edital'] ?: $l['processo']) . ' - ' . substr($l['objeto'], 0, 80) . (strlen($l['objeto']) > 80 ? '...' : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div><label>Fornecedor</label>
                        <select name="fornecedor_id" required class="searchable-select">
                            <option value="">--</option>
                            <?php foreach($fornecedores as $f) echo '<option '.($r['fornecedor_id']==$f['id']?'selected':'').' value="'.$f['id'].'">'.htmlspecialchars($f['nome']).'</option>'; ?>
                        </select>
                    </div>
                </div>
                <div><label>Objeto</label><textarea name="objeto" rows="4" readonly style="background: #0e1430; cursor: not-allowed;"><?= htmlspecialchars($r['objeto']) ?></textarea></div>
            </div>
            <div class="form-actions">
                <?php if (tem_permissao('atas.excluir')): ?>
                <button class="btn warn btn-confirm-delete" type="submit" name="action" value="delete" data-confirm-message="Tem certeza que deseja excluir esta ata?">Excluir</button>
                <?php endif; ?>
                <button class="btn good" type="submit" name="action" value="update">Atualizar Ata</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php render_footer(); ?>