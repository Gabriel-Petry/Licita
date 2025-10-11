<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

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
        if ($action === 'create' && tem_permissao('atas.criar')) {
            $validade_calculada = null;
            if (!empty($_POST['inicio_vigencia'])) {
                $dataInicio = new DateTime($_POST['inicio_vigencia']);
                $dataInicio->modify('+365 days');
                $validade_calculada = $dataInicio->format('Y-m-d');
            }

            $stmt_objeto = $pdo->prepare("SELECT objeto FROM licitacoes WHERE id = :licitacao_id");
            $stmt_objeto->execute([':licitacao_id' => $_POST['licitacao_id']]);
            $objeto_licitacao = $stmt_objeto->fetchColumn();

            $stmt = $pdo->prepare(
                "INSERT INTO atas_registro_preco (licitacao_id, fornecedor_id, numero_ata, validade, objeto, inicio_vigencia, assinado, created_at) 
                 VALUES (:licitacao_id, :fornecedor_id, :numero_ata, :validade, :objeto, :inicio_vigencia, :assinado, NOW())"
            );
            $stmt->execute([
                ':licitacao_id' => $_POST['licitacao_id'],
                ':fornecedor_id' => $_POST['fornecedor_id'],
                ':numero_ata' => $_POST['numero_ata'],
                ':validade' => $validade_calculada,
                ':objeto' => $objeto_licitacao,
                ':inicio_vigencia' => empty($_POST['inicio_vigencia']) ? null : $_POST['inicio_vigencia'],
                ':assinado' => isset($_POST['assinado']) ? 1 : 0
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Ata cadastrada com sucesso!'];

        } elseif ($action === 'update' && tem_permissao('atas.editar')) {
            $validade_calculada = null;
            if (!empty($_POST['inicio_vigencia'])) {
                $dataInicio = new DateTime($_POST['inicio_vigencia']);
                $dataInicio->modify('+365 days');
                $validade_calculada = $dataInicio->format('Y-m-d');
            }

            $stmt_objeto = $pdo->prepare("SELECT objeto FROM licitacoes WHERE id = :licitacao_id");
            $stmt_objeto->execute([':licitacao_id' => $_POST['licitacao_id']]);
            $objeto_licitacao = $stmt_objeto->fetchColumn();

            $stmt = $pdo->prepare(
                "UPDATE atas_registro_preco 
                 SET licitacao_id = :licitacao_id, fornecedor_id = :fornecedor_id, numero_ata = :numero_ata, 
                     validade = :validade, objeto = :objeto, inicio_vigencia = :inicio_vigencia, assinado = :assinado
                 WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $_POST['id'],
                ':licitacao_id' => $_POST['licitacao_id'],
                ':fornecedor_id' => $_POST['fornecedor_id'],
                ':numero_ata' => $_POST['numero_ata'],
                ':validade' => $validade_calculada,
                ':objeto' => $objeto_licitacao,
                ':inicio_vigencia' => empty($_POST['inicio_vigencia']) ? null : $_POST['inicio_vigencia'],
                ':assinado' => isset($_POST['assinado']) ? 1 : 0
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Ata atualizada com sucesso!'];

        } elseif ($action === 'delete' && tem_permissao('atas.excluir')) {
            $stmt = $pdo->prepare("DELETE FROM atas_registro_preco WHERE id = :id");
            $stmt->execute([':id' => $_POST['id']]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Ata excluída com sucesso!'];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erro: ' . $e->getMessage()];
    }
    header('Location: /atas');
    exit;
}

$where = [];
$params = [];
$current_orgao_id = $_GET['orgao_id'] ?? 0;
$current_status_assinatura = $_GET['status_assinatura'] ?? '';

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
if ($current_status_assinatura !== '') {
    $where[] = "a.assinado = :status_assinatura";
    $params[':status_assinatura'] = $current_status_assinatura;
}

$where_clause = $where ? " WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT 
            a.*, 
            l.processo as licitacao_processo, 
            f.nome as fornecedor_nome, 
            f.cnpj as fornecedor_cnpj, 
            f.contato as fornecedor_contato 
        FROM atas_registro_preco a 
        LEFT JOIN licitacoes l ON l.id = a.licitacao_id 
        LEFT JOIN fornecedores f ON f.id = a.fornecedor_id
        $where_clause 
        ORDER BY 
            CAST(SUBSTRING_INDEX(a.numero_ata, '/', -1) AS UNSIGNED) DESC, 
            CAST(SUBSTRING_INDEX(a.numero_ata, '/', 1) AS UNSIGNED) DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$atas = $stmt->fetchAll();

function render_atas_table_content($atas, $permissoes) {
    ob_start();
?>
    <table>
        <thead>
            <tr>
                <th>Nº Ata</th>
                <th>Licitação Vinculada</th>
                <th>Fornecedor</th>
                <th>Status Assinatura</th>
                <th>Vigência</th>
                <th>Tempo Restante</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($atas) > 0): foreach ($atas as $ata):
                $status_texto = '--'; $status_classe = '';
                if (!empty($ata['validade'])) {
                    $dias_restantes = (new DateTime())->diff(new DateTime($ata['validade']))->format('%r%a');
                    if ($dias_restantes < 0) { $status_texto = 'Expirado'; $status_classe = 'expirado'; }
                    elseif ($dias_restantes <= 30) { $status_texto = $dias_restantes . ' dia(s)'; $status_classe = 'atencao'; }
                    else { $status_texto = $dias_restantes . ' dia(s)'; $status_classe = 'ok'; }
                }
            ?>
                <tr>
                    <td><?= htmlspecialchars($ata['numero_ata']) ?></td>
                    <td><?= str_replace(' ', '<br>', htmlspecialchars($ata['licitacao_processo'])) ?></td>
                    <td><?= wordwrap(htmlspecialchars($ata['fornecedor_nome']), 35,"<br>\n", true) ?></td>
                    <td>
                        <?php if ($ata['assinado']): ?>
                            <span class="chip good">Assinada</span>
                        <?php else: ?>
                            <span class="chip warn">Pendente</span>
                        <?php endif; ?>
                    </td>
                    <td><?= !empty($ata['inicio_vigencia']) ? htmlspecialchars(date('d/m/Y', strtotime($ata['inicio_vigencia']))) : '--' ?> a <?= !empty($ata['validade']) ? htmlspecialchars(date('d/m/Y', strtotime($ata['validade']))) : '--' ?></td>
                    <td><span class="status-vencimento <?= $status_classe ?>"><?= $status_texto ?></span></td>
                    <td class="actions-cell">
                        <a href="#contato-fornecedor-<?= $ata['id'] ?>" class="btn btn-sm info">Contato</a>
                        <?php if ($permissoes['atas.editar']): ?>
                            <a href="#editar-ata-popup-<?= $ata['id'] ?>" class="btn btn-sm">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="7" style="text-align: center; padding: 2rem;">Nenhuma ata encontrada.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php
    return ob_get_clean();
}

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    echo render_atas_table_content($atas, ['atas.editar' => tem_permissao('atas.editar')]);
    exit;
}

$licitacoes = $pdo->query("SELECT id, processo, n_edital, objeto FROM licitacoes WHERE modalidade_id IN (4, 5, 8) ORDER BY processo DESC")->fetchAll();
$fornecedores = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome")->fetchAll();
$orgaos = $pdo->query("SELECT id, nome FROM orgaos ORDER BY nome")->fetchAll();

render_header('Atas de Registro de Preço');
?>

<div class="card">
    <?php display_flash_message(); ?>
    <div class="toolbar" style="flex-wrap: wrap; justify-content: flex-start;">
        <a href="/atas" class="btn btn-sm <?= empty($current_orgao_id) && $current_status_assinatura === '' ? 'primary' : '' ?>">Todas</a>
        <?php foreach ($orgaos as $orgao): ?>
            <a href="/atas?orgao_id=<?= $orgao['id'] ?>" class="btn btn-sm <?= (int)$current_orgao_id === $orgao['id'] ? 'primary' : '' ?>"><?= htmlspecialchars($orgao['nome']) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="toolbar">
        <form class="inline" method="get" id="atas-filter-form">
            <?php if (!empty($current_orgao_id)): ?><input type="hidden" name="orgao_id" value="<?= htmlspecialchars($current_orgao_id) ?>"><?php endif; ?>
            
            <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Buscar por Nº, Objeto, Processo ou Fornecedor...">
            
            <select name="status_assinatura" style="margin-left: 8px;">
                <option value="" <?= $current_status_assinatura === '' ? 'selected' : '' ?>>Status: Todas</option>
                <option value="1" <?= $current_status_assinatura === '1' ? 'selected' : '' ?>>Assinadas</option>
                <option value="0" <?= $current_status_assinatura === '0' ? 'selected' : '' ?>>Pendentes</option>
            </select>

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

    <div class="table-scroll-container" id="atas-table-container">
        <?php 
            echo render_atas_table_content($atas, ['atas.editar' => tem_permissao('atas.editar')]); 
        ?>
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
                 <div class="grid grid-2">
                    <div>
                        <label>Licitação Vinculada</label>
                        <select name="licitacao_id" required class="searchable-select">
                            <option value="">-- Selecione --</option>
                            <?php foreach($licitacoes as $l): ?>
                                <option value="<?= $l['id'] ?>" data-objeto="<?= htmlspecialchars($l['objeto']) ?>"><?= htmlspecialchars($l['n_edital'] . ' - ' . $l['processo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Fornecedor</label>
                        <select name="fornecedor_id" required class="searchable-select">
                            <option value="">-- Selecione --</option>
                            <?php foreach($fornecedores as $f) echo '<option value="'.$f['id'].'">'.htmlspecialchars($f['nome']).'</option>'; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-3">
                    <div><label>Nº da Ata (ex: 123/2025)</label><input name="numero_ata" required></div>
                    <div><label>Início da Vigência</label><input type="date" name="inicio_vigencia"></div>
                    <div>
                        <label>Fim da Vigência</label>
                        <strong class="texto-validade-calculada" style="display: block; padding-top: 8px; font-size: 1.1em;">--</strong>
                    </div>
                </div>
                <div>
                    <label>Objeto</label>
                    <textarea name="objeto" rows="4" readonly style="background: #1f2937; color: #9ca3af; cursor: not-allowed;"></textarea>
                </div>
                 <div class="form-checkbox" style="margin-top: 1rem;">
                    <input type="checkbox" id="assinado_novo" name="assinado" value="1">
                    <label for="assinado_novo">Marcar como assinada</label>
                </div>
            </div>
            <div class="form-actions"><button class="btn good" type="submit">Salvar Nova Ata</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php foreach ($atas as $ata): ?>
    <?php if (tem_permissao('atas.editar')): ?>
    <div id="editar-ata-popup-<?= $ata['id'] ?>" class="popup-overlay">
        <div class="popup-card card">
            <a href="#" class="popup-close">&times;</a>
            <h2>Editar Ata #<?= $ata['id'] ?></h2>
            <form method="post" class="form-popup">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $ata['id'] ?>">
                <div class="popup-content">
                    <div class="grid grid-2">
                        <div>
                            <label>Licitação Vinculada</label>
                            <select name="licitacao_id" required class="searchable-select">
                                <?php foreach($licitacoes as $l): ?>
                                    <option <?= ($ata['licitacao_id']==$l['id']?'selected':'') ?> value="<?= $l['id'] ?>" data-objeto="<?= htmlspecialchars($l['objeto']) ?>"><?= htmlspecialchars($l['n_edital'] . ' - ' . $l['processo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Fornecedor</label>
                            <select name="fornecedor_id" required class="searchable-select">
                                <?php foreach($fornecedores as $f) echo '<option '.($ata['fornecedor_id']==$f['id']?'selected':'').' value="'.$f['id'].'">'.htmlspecialchars($f['nome']).'</option>'; ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-3">
                        <div><label>Nº da Ata</label><input name="numero_ata" value="<?= htmlspecialchars($ata['numero_ata']) ?>" required></div>
                        <div><label>Início da Vigência</label><input type="date" name="inicio_vigencia" value="<?= htmlspecialchars($ata['inicio_vigencia']) ?>"></div>
                        <div>
                            <label>Fim da Vigência</label>
                            <strong class="texto-validade-calculada" style="display: block; padding-top: 8px; font-size: 1.1em;">
                                <?= !empty($ata['validade']) ? htmlspecialchars(date('d/m/Y', strtotime($ata['validade']))) : '--' ?>
                            </strong>
                        </div>
                    </div>
                    <div>
                        <label>Objeto</label>
                        <textarea name="objeto" rows="4" readonly style="background: #1f2937; color: #9ca3af; cursor: not-allowed;"><?= htmlspecialchars($ata['objeto']) ?></textarea>
                    </div>
                     <div class="form-checkbox" style="margin-top: 1rem;">
                        <input type="checkbox" id="assinado_edit_<?= $ata['id'] ?>" name="assinado" value="1" <?= $ata['assinado'] ? 'checked' : '' ?>>
                        <label for="assinado_edit_<?= $ata['id'] ?>">Marcar como assinada</label>
                    </div>
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
    <?php endif; ?>

    <div id="contato-fornecedor-<?= $ata['id'] ?>" class="popup-overlay">
        <div class="popup-card card">
            <a href="#" class="popup-close">&times;</a>
            <h2>Contato do Fornecedor</h2>
            <h3><?= htmlspecialchars($ata['fornecedor_nome']) ?></h3>
            <div class="popup-content">
                <p><strong>CNPJ:</strong> <?= htmlspecialchars($ata['fornecedor_cnpj'] ?? 'Não informado') ?></p>
                <p><strong>Contato:</strong> <?= htmlspecialchars($ata['fornecedor_contato'] ?? 'Não informado') ?></p>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php render_footer(); ?>