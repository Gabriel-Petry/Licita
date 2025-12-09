<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();
if (!tem_permissao('licitacoes.ver')) {
    header('Location: /dashboard');
    exit;
}

check_csrf();
$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            if (!tem_permissao('licitacoes.criar')) throw new Exception('Acesso negado.');
            $required_fields = ['processo', 'n_edital', 'valor_estimado', 'orgao_id', 'modalidade_id', 'status_id', 'responsavel_elaboracao_id', 'objeto', 'prioridade', 'complexidade'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Erro: O campo '" . ucfirst(str_replace(['_id', '_'], ['', ' '], $field)) . "' é obrigatório.");
                }
            }
            
            $stmt = $pdo->prepare(
                "INSERT INTO licitacoes (processo, objeto, orgao_id, modalidade_id, status_id, valor_estimado, data_licitacao, n_edital, observacao, responsavel_elaboracao_id, agente_contratacao_id, prioridade, complexidade)
                 VALUES (:processo, :objeto, :orgao_id, :modalidade_id, :status_id, :valor_estimado, :data_licitacao, :n_edital, :observacao, :responsavel_elaboracao_id, :agente_contratacao_id, :prioridade, :complexidade)"
            );
            $stmt->execute([
                ':processo' => $_POST['processo'], ':objeto' => $_POST['objeto'], ':orgao_id' => $_POST['orgao_id'],
                ':modalidade_id' => $_POST['modalidade_id'], ':status_id' => $_POST['status_id'], ':valor_estimado' => $_POST['valor_estimado'],
                ':data_licitacao' => empty($_POST['data_licitacao']) ? null : $_POST['data_licitacao'],
                ':n_edital' => $_POST['n_edital'], ':observacao' => $_POST['observacao'] ?? '',
                ':responsavel_elaboracao_id' => $_POST['responsavel_elaboracao_id'],
                ':agente_contratacao_id' => empty($_POST['agente_contratacao_id']) ? null : $_POST['agente_contratacao_id'],
                ':prioridade' => $_POST['prioridade'],
                ':complexidade' => $_POST['complexidade']
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Licitação criada com sucesso!'];

        } elseif ($action === 'update') {
            if (!tem_permissao('licitacoes.editar')) throw new Exception('Acesso negado.');
            $id_status_homologado_php = (int) $pdo->query("SELECT id FROM status WHERE nome LIKE '%homologad%' LIMIT 1")->fetchColumn();
            
            $stmt = $pdo->prepare(
                "UPDATE licitacoes SET processo=:processo, objeto=:objeto, orgao_id=:orgao_id, modalidade_id=:modalidade_id, status_id=:status_id, valor_estimado=:valor_estimado, data_licitacao=:data_licitacao, n_edital=:n_edital, observacao=:observacao, responsavel_elaboracao_id=:responsavel_elaboracao_id, agente_contratacao_id=:agente_contratacao_id, prioridade=:prioridade, complexidade=:complexidade WHERE id=:id"
            );
            $stmt->execute([
                ':id' => $_POST['id'], ':processo' => $_POST['processo'], ':objeto' => $_POST['objeto'], ':orgao_id' => $_POST['orgao_id'],
                ':modalidade_id' => $_POST['modalidade_id'], ':status_id' => $_POST['status_id'], ':valor_estimado' => $_POST['valor_estimado'],
                ':data_licitacao' => empty($_POST['data_licitacao']) ? null : $_POST['data_licitacao'],
                ':n_edital' => $_POST['n_edital'], ':observacao' => $_POST['observacao'] ?? '',
                ':responsavel_elaboracao_id' => $_POST['responsavel_elaboracao_id'],
                ':agente_contratacao_id' => empty($_POST['agente_contratacao_id']) ? null : $_POST['agente_contratacao_id'],
                ':prioridade' => $_POST['prioridade'],
                ':complexidade' => $_POST['complexidade']
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Licitação atualizada com sucesso!'];

            if ((int)$_POST['status_id'] === $id_status_homologado_php && tem_permissao('licitacoes.homologar')) {
                header('Location: /licitacoes?homologar=' . $_POST['id']);
                exit;
            }

        } elseif ($action === 'homologar') {
            if (!tem_permissao('licitacoes.homologar')) throw new Exception('Acesso negado.');
            $stmt = $pdo->prepare(
                "UPDATE licitacoes SET valor_adjudicado = :valor_adjudicado, data_homologacao = :data_homologacao, status_id = :status_id WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $_POST['id'], ':valor_adjudicado' => $_POST['valor_adjudicado'] ?: null,
                ':data_homologacao' => empty($_POST['data_homologacao']) ? null : $_POST['data_homologacao'],
                ':status_id' => $_POST['status_id_homologado']
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Licitação homologada com sucesso!'];

        } elseif ($action === 'delete') {
            if (!tem_permissao('licitacoes.excluir')) throw new Exception('Acesso negado.');
            $stmt = $pdo->prepare("DELETE FROM licitacoes WHERE id=:id");
            $stmt->execute([':id' => $_POST['id']]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Licitação excluída com sucesso!'];
        
        } elseif ($action === 'solicitar_correcao') {
            if (!tem_permissao('licitacoes.solicitar_correcao')) throw new Exception('Acesso negado.');

            $id = $_POST['id'] ?? 0;
            $mensagem = trim($_POST['mensagem_correcao'] ?? '');
            if (empty($id) || empty($mensagem)) {
                throw new Exception('A mensagem de correção é obrigatória.');
            }

            $status_id_correcao = (int) $pdo->query("SELECT id FROM status WHERE nome = 'Aguardando Correção' LIMIT 1")->fetchColumn();
            if (!$status_id_correcao) {
                throw new Exception('Status "Aguardando Correção" não encontrado no sistema.');
            }

            $stmt = $pdo->prepare(
                "UPDATE licitacoes SET status_id = :status_id, observacao = CONCAT(COALESCE(observacao, ''), '\n\n[SOLICITAÇÃO DE CORREÇÃO EM ', DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i'), ' POR ', :user_name, ']: ', :mensagem) WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $id,
                ':status_id' => $status_id_correcao,
                ':user_name' => $user['nome'],
                ':mensagem' => $mensagem
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Solicitação de correção enviada com sucesso!'];
        }

    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => $e->getMessage()];
    }
    header('Location: /licitacoes');
    exit;
}

$items_per_page = 50;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$where = [
    "(s.nome NOT LIKE 'Homologad%' AND s.nome NOT LIKE 'Fracassad%' AND s.nome NOT LIKE 'Desert%')",
    "(m.nome NOT LIKE '%Dispensa Direta%' AND m.nome NOT LIKE '%Inexigibilidade%')"
];
$params = [];

if ($user['orgao_id'] && !tem_permissao('dados.ver_todos_orgaos')) {
    $where[] = "l.orgao_id = :user_orgao_id";
    $params[':user_orgao_id'] = $user['orgao_id'];
}

$current_fase = $_GET['fase'] ?? 'todas';
if ($current_fase === 'interna') $where[] = "l.status_id IN (8, 11, 12, 19, 20)";
elseif ($current_fase === 'externa') $where[] = "l.status_id NOT IN (8, 11, 12, 19, 20)";

if (!empty($_GET['q'])) { $where[] = "(l.processo LIKE :q OR l.objeto LIKE :q)"; $params[':q'] = '%' . $_GET['q'] . '%'; }
if (!empty($_GET['status_id'])) { $where[] = "l.status_id = :status_id"; $params[':status_id'] = $_GET['status_id']; }
if (!empty($_GET['modalidade_id'])) { $where[] = "l.modalidade_id = :modalidade_id"; $params[':modalidade_id'] = $_GET['modalidade_id']; }
if (!empty($_GET['responsavel_id'])) { $where[] = "l.responsavel_elaboracao_id = :responsavel_id"; $params[':responsavel_id'] = $_GET['responsavel_id']; }
if (!empty($_GET['agente_id'])) { $where[] = "l.agente_contratacao_id = :agente_id"; $params[':agente_id'] = $_GET['agente_id']; }
if (!empty($_GET['prioridade'])) { $where[] = "l.prioridade = :prioridade"; $params[':prioridade'] = $_GET['prioridade']; }

$count_sql = "SELECT COUNT(l.id) FROM licitacoes l LEFT JOIN modalidades m ON m.id=l.modalidade_id LEFT JOIN status s ON s.id=l.status_id WHERE " . implode(" AND ", $where);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();

$sql = "SELECT l.*, o.nome AS orgao, m.nome AS modalidade, s.nome AS status, re.nome AS responsavel_elaboracao, ac.nome AS agente_contratacao
        FROM licitacoes l
        LEFT JOIN orgaos o ON o.id=l.orgao_id
        LEFT JOIN modalidades m ON m.id=l.modalidade_id
        LEFT JOIN status s ON s.id=l.status_id
        LEFT JOIN responsaveis_elaboracao re ON re.id=l.responsavel_elaboracao_id
        LEFT JOIN agentes_contratacao ac ON ac.id=l.agente_contratacao_id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY l.prioridade DESC, CAST(SUBSTRING_INDEX(l.n_edital, '/', 1) AS UNSIGNED) ASC, l.id DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
$params_with_pagination = array_merge($params, [':limit' => $items_per_page, ':offset' => $offset]);
foreach ($params_with_pagination as $key => &$val) { is_int($val) ? $stmt->bindParam($key, $val, PDO::PARAM_INT) : $stmt->bindParam($key, $val, PDO::PARAM_STR); }
$stmt->execute();
$rows = $stmt->fetchAll();

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function render_licitacoes_table_content($rows, $total_items, $items_per_page, $current_page) {
    ob_start();
?>
    <table>
      <thead><tr><th>Nº PROCESSO</th><th class="text-wrap">OBJETO</th><th>STATUS</th><th>MODALIDADE</th><th>N° EDITAL</th><th class="text-wrap">OBSERVAÇÃO</th><th>VALOR ESTIMADO</th><th>DATA</th><th>ÓRGÃO</th><th>RESPONSÁVEL</th><th>AGENTE</th><th>AÇÕES</th></tr></thead>
      <tbody>
        <?php if (count($rows) > 0): foreach ($rows as $r): ?>
            <tr class="<?= ['1' => 'priority-low', '2' => 'priority-medium', '3' => 'priority-high'][$r['prioridade']] ?? 'priority-low' ?>">
              <td><?= wordwrap(htmlspecialchars($r['processo']), 10, "<br>\n", true) ?></td>
              <td class="text-wrap"><?= htmlspecialchars($r['objeto']) ?></td>
              <td><span class="chip"><?= htmlspecialchars($r['status']) ?></span></td>
              <td><?= htmlspecialchars($r['modalidade']) ?></td>
              <td><?= htmlspecialchars($r['n_edital']) ?></td>
              <td class="text-wrap"><?= htmlspecialchars($r['observacao']) ?></td>
              <td>R$ <?= number_format((float) $r['valor_estimado'], 2, ',', '.') ?></td>
              <td><?= $r['data_licitacao'] ? htmlspecialchars(date('d/m/Y', strtotime($r['data_licitacao']))) : '--' ?></td>
              <td><?= htmlspecialchars($r['orgao']) ?></td>
              <td><?= htmlspecialchars($r['responsavel_elaboracao']) ?></td>
              <td><?= htmlspecialchars($r['agente_contratacao']) ?></td>
              <td>
                <?php if (tem_permissao('licitacoes.editar')): ?>
                    <a href="#editar-licitacao-popup-<?= $r['id'] ?>" class="btn">Editar</a>
                <?php elseif (tem_permissao('licitacoes.solicitar_correcao')): ?>
                    <a href="#solicitar-correcao-popup-<?= $r['id'] ?>" class="btn">Solicitar Correção</a>
                <?php endif; ?>
              </td>
            </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="12" style="text-align: center; padding: 2rem;">Nenhum registro encontrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?php
      $base_url = '/licitacoes?'.http_build_query(array_diff_key($_GET, ['page' => ''])).'&';
      render_pagination($total_items, $items_per_page, $current_page, $base_url);
    ?>
<?php
    return ob_get_clean();
}

if ($is_ajax) {
    echo render_licitacoes_table_content($rows, $total_items, $items_per_page, $current_page);
    exit;
}

$aux = fn ($t) => db()->query("SELECT id, nome FROM {$t} ORDER BY nome")->fetchAll();
$orgaos = $aux('orgaos');
$modalidades = $aux('modalidades');
$status = $aux('status');
$responsaveis = $aux('responsaveis_elaboracao');
$agentes = $aux('agentes_contratacao');
$modalidades_nova_licitacao = array_filter($modalidades, fn($m) => stripos($m['nome'], 'Dispensa Direta') === false && stripos($m['nome'], 'Inexigibilidade') === false);
$id_status_homologado = (int) $pdo->query("SELECT id FROM status WHERE nome LIKE '%homologad%' LIMIT 1")->fetchColumn();
render_header('Licitações - LicitAções', ['bodyClass' => 'page-licitacoes']);
?>
<div class="card">
  <?php display_flash_message(); ?>

  <div class="toolbar" style="flex-wrap: wrap; justify-content: flex-start; gap: 8px;">
    <a href="/licitacoes" class="btn btn-sm <?= ($current_fase === 'todas') ? 'primary' : '' ?>">Todas</a>
    <a href="/licitacoes?fase=interna" class="btn btn-sm <?= ($current_fase === 'interna') ? 'primary' : '' ?>">Fase Interna</a>
    <a href="/licitacoes?fase=externa" class="btn btn-sm <?= ($current_fase === 'externa') ? 'primary' : '' ?>">Fase Externa</a>
  </div>
  
  <div class="toolbar">
      <form class="inline" method="get" id="filter-form">
      <?php if (isset($_GET['fase'])): ?><input type="hidden" name="fase" value="<?= htmlspecialchars($_GET['fase']) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Buscar por Processo ou Objeto...">
      <select name="prioridade"><option value="">-- Prioridade --</option><option value="3" <?= ($_GET['prioridade'] ?? '') == '3' ? 'selected' : '' ?>>Alta</option><option value="2" <?= ($_GET['prioridade'] ?? '') == '2' ? 'selected' : '' ?>>Média</option><option value="1" <?= ($_GET['prioridade'] ?? '') == '1' ? 'selected' : '' ?>>Baixa</option></select>
      <select name="status_id"><option value="">-- Status --</option><?php foreach ($status as $s): ?><option value="<?= $s['id'] ?>" <?= ($_GET['status_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nome']) ?></option><?php endforeach; ?></select>
      <select name="modalidade_id"><option value="">-- Modalidade --</option><?php foreach ($modalidades as $m): ?><option value="<?= $m['id'] ?>" <?= ($_GET['modalidade_id'] ?? '') == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nome']) ?></option><?php endforeach; ?></select>
      <select name="responsavel_id"><option value="">-- Responsável --</option><?php foreach ($responsaveis as $r): ?><option value="<?= $r['id'] ?>" <?= ($_GET['responsavel_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nome']) ?></option><?php endforeach; ?></select>
      <select name="agente_id"><option value="">-- Agente --</option><?php foreach ($agentes as $a): ?><option value="<?= $a['id'] ?>" <?= ($_GET['agente_id'] ?? '') == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['nome']) ?></option><?php endforeach; ?></select>
      
      <?php if (tem_permissao('relatorios.gerar')): ?>
      <div class="dropdown-relatorio">
        <button type="button" class="btn">Gerar Relatório &#9662;</button>
        <div class="dropdown-relatorio-content">
          <a href="/gerar_relatorio?tipo=licitacoes&<?= http_build_query($_GET) ?>&formato=pdf" target="_blank">PDF</a>
          <a href="/gerar_relatorio?tipo=licitacoes&<?= http_build_query($_GET) ?>&formato=excel" target="_blank">Excel</a>
        </div>
      </div>
      <?php endif; ?>
    </form>
    <?php if (tem_permissao('licitacoes.criar')): ?>
    <a href="#nova-licitacao-popup" class="btn primary right">Nova licitação</a>
    <?php endif; ?>
  </div>

  <div class="table-scroll-container">
    <div id="licitacoes-table-container">
        <?php echo render_licitacoes_table_content($rows, $total_items, $items_per_page, $current_page); ?>
    </div>
  </div>
</div>

<?php if (tem_permissao('licitacoes.editar')): ?>
<?php foreach ($rows as $r): ?>
<div id="editar-licitacao-popup-<?= $r['id'] ?>" class="popup-overlay">
  <div class="popup-card card">
    <div class="popup-header"><h2>Editar Licitação #<?= $r['id'] ?></h2><a href="#" class="popup-close">&times;</a></div>
    <form method="post" class="form-popup">
        <div class="popup-content">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <label style="margin-bottom: 0;">Prioridade:</label>
                    <div class="priority-selector">
                        <label class="priority-option"><input type="radio" name="prioridade" value="1" <?= ($r['prioridade'] ?? 1) == 1 ? 'checked' : '' ?> required><span class="radio-custom radio-low"></span></label>
                        <label class="priority-option"><input type="radio" name="prioridade" value="2" <?= ($r['prioridade'] ?? 1) == 2 ? 'checked' : '' ?> required><span class="radio-custom radio-medium"></span></label>
                        <label class="priority-option"><input type="radio" name="prioridade" value="3" <?= ($r['prioridade'] ?? 1) == 3 ? 'checked' : '' ?> required><span class="radio-custom radio-high"></span></label>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 15px;">
                    <label style="margin-bottom: 0;">Nível (Pts):</label>
                    <div class="priority-selector">
                        <label class="priority-option" title="Simples (1 pt)">
                            <input type="radio" name="complexidade" value="1" <?= ($r['complexidade'] ?? 1) == 1 ? 'checked' : '' ?> required>
                            <span class="radio-custom radio-low"></span>
                        </label>
                        <label class="priority-option" title="Médio (2 pts)">
                            <input type="radio" name="complexidade" value="2" <?= ($r['complexidade'] ?? 1) == 2 ? 'checked' : '' ?> required>
                            <span class="radio-custom radio-medium"></span>
                        </label>
                        <label class="priority-option" title="Complexo (3 pts)">
                            <input type="radio" name="complexidade" value="3" <?= ($r['complexidade'] ?? 1) == 3 ? 'checked' : '' ?> required>
                            <span class="radio-custom radio-high"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="grid grid-3">
                <div><label>Nº Processo</label><input name="processo" value="<?= htmlspecialchars($r['processo']) ?>" required></div>
                <div><label>Nº Edital</label><input name="n_edital" value="<?= htmlspecialchars($r['n_edital']) ?>" required></div>
                <div><label>Valor Estimado (R$)</label><input type="number" step="0.01" name="valor_estimado" value="<?= htmlspecialchars($r['valor_estimado']) ?>" required></div>
                <div><label>Órgão</label><select name="orgao_id" required><option value="">--</option><?php foreach($orgaos as $o) echo '<option '.($r['orgao_id']==$o['id']?'selected':'').' value="'.$o['id'].'">'.htmlspecialchars($o['nome']).'</option>'; ?></select></div>
                <div><label>Modalidade</label><select name="modalidade_id" required><option value="">--</option><?php foreach($modalidades as $m) echo '<option '.($r['modalidade_id']==$m['id']?'selected':'').' value="'.$m['id'].'">'.htmlspecialchars($m['nome']).'</option>'; ?></select></div>
                <div><label>Status</label><select name="status_id" required><option value="">--</option><?php foreach($status as $s) echo '<option '.($r['status_id']==$s['id']?'selected':'').' value="'.$s['id'].'">'.htmlspecialchars($s['nome']).'</option>'; ?></select></div>
                <div><label>Responsável Elaboração</label><select name="responsavel_elaboracao_id" required><option value="">--</option><?php foreach($responsaveis as $re) echo '<option '.($r['responsavel_elaboracao_id']==$re['id']?'selected':'').' value="'.$re['id'].'">'.htmlspecialchars($re['nome']).'</option>'; ?></select></div>
                <div><label>Agente Contratação</label><select name="agente_contratacao_id"><option value="">--</option><?php foreach($agentes as $ac) echo '<option '.($r['agente_contratacao_id']==$ac['id']?'selected':'').' value="'.$ac['id'].'">'.htmlspecialchars($ac['nome']).'</option>'; ?></select></div>
                <div><label>Data da Licitação</label><input type="date" name="data_licitacao" value="<?= $r['data_licitacao'] ? htmlspecialchars(date('Y-m-d', strtotime($r['data_licitacao']))) : '' ?>"></div>
            </div>
            <div class="grid grid-2" style="margin-top: 1rem;">
                <div><label>Objeto</label><textarea name="objeto" rows="3" required><?= htmlspecialchars($r['objeto']) ?></textarea></div>
                <div><label>Observação</label><textarea name="observacao" rows="3"><?= htmlspecialchars($r['observacao']) ?></textarea></div>
            </div>
        </div>
        <div class="form-actions">
            <?php if (tem_permissao('licitacoes.excluir')): ?>
            <button class="btn warn btn-confirm-delete" type="submit" name="action" value="delete" data-confirm-message="Excluir esta licitação?">Excluir</button>
            <?php endif; ?>
            <button class="btn good" type="submit" name="action" value="update">Atualizar Licitação</button>
        </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (tem_permissao('licitacoes.criar')): ?>
<div id="nova-licitacao-popup" class="popup-overlay">
    <div class="popup-card card">
        <div class="popup-header"><h2>Nova Licitação</h2><a href="#" class="popup-close">&times;</a></div>
        <form method="post" class="form-popup">
            <div class="popup-content">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="create">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <label style="margin-bottom: 0;">Prioridade:</label>
                        <div class="priority-selector">
                            <label class="priority-option"><input type="radio" name="prioridade" value="1" checked required><span class="radio-custom radio-low"></span></label>
                            <label class="priority-option"><input type="radio" name="prioridade" value="2" required><span class="radio-custom radio-medium"></span></label>
                            <label class="priority-option"><input type="radio" name="prioridade" value="3" required><span class="radio-custom radio-high"></span></label>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 15px;">
                        <label style="margin-bottom: 0;">Nível (Pts):</label>
                        <div class="priority-selector">
                            <label class="priority-option" title="Simples (1 pt)">
                                <input type="radio" name="complexidade" value="1" checked required>
                                <span class="radio-custom radio-low"></span>
                                <span class="muted" style="font-size: 0.8rem; margin-left: 2px;">1</span>
                            </label>
                            <label class="priority-option" title="Médio (2 pts)">
                                <input type="radio" name="complexidade" value="2" required>
                                <span class="radio-custom radio-medium"></span>
                                <span class="muted" style="font-size: 0.8rem; margin-left: 2px;">2</span>
                            </label>
                            <label class="priority-option" title="Complexo (3 pts)">
                                <input type="radio" name="complexidade" value="3" required>
                                <span class="radio-custom radio-high"></span>
                                <span class="muted" style="font-size: 0.8rem; margin-left: 2px;">3</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="grid grid-3">
                    <div><label>Nº Processo</label><input name="processo" required></div>
                    <div><label>Nº Edital</label><input name="n_edital" required></div>
                    <div><label>Valor Estimado (R$)</label><input type="number" step="0.01" name="valor_estimado" required></div>
                    <div><label>Órgão</label><select name="orgao_id" required><option value="">--</option><?php foreach($orgaos as $o) echo '<option value="'.$o['id'].'">'.htmlspecialchars($o['nome']).'</option>'; ?></select></div>
                    <div><label>Modalidade</label><select name="modalidade_id" required><option value="">--</option><?php foreach($modalidades_nova_licitacao as $m) echo '<option value="'.$m['id'].'">'.htmlspecialchars($m['nome']).'</option>'; ?></select></div>
                    <div><label>Status</label><select name="status_id" required><option value="">--</option><?php foreach($status as $s) echo '<option value="'.$s['id'].'">'.htmlspecialchars($s['nome']).'</option>'; ?></select></div>
                    <div><label>Responsável Elaboração</label><select name="responsavel_elaboracao_id" required><option value="">--</option><?php foreach($responsaveis as $re) echo '<option value="'.$re['id'].'">'.htmlspecialchars($re['nome']).'</option>'; ?></select></div>
                    <div><label>Agente Contratação</label><select name="agente_contratacao_id"><option value="">--</option><?php foreach($agentes as $ac) echo '<option value="'.$ac['id'].'">'.htmlspecialchars($ac['nome']).'</option>'; ?></select></div>
                    <div><label>Data da Licitação</label><input type="date" name="data_licitacao"></div>
                </div>
                <div class="grid grid-2" style="margin-top: 1rem;">
                    <div><label>Objeto</label><textarea name="objeto" rows="3" required></textarea></div>
                    <div><label>Observação</label><textarea name="observacao" rows="3"></textarea></div>
                </div>
            </div>
            <div class="form-actions"><button class="btn good" type="submit">Salvar Nova Licitação</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
foreach ($rows as $r): ?>
<div id="solicitar-correcao-popup-<?= $r['id'] ?>" class="popup-overlay">
  <div class="popup-card card popup-small">
    <a href="#" class="popup-close">&times;</a>
    <h2>Solicitar Correção</h2>
    <p class="muted">Descreva a alteração necessária para o processo <strong><?= htmlspecialchars($r['processo']) ?></strong>. A equipe de Compras será notificada.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="action" value="solicitar_correcao">
        <input type="hidden" name="id" value="<?= $r['id'] ?>">
        <label>Mensagem da Solicitação</label>
        <textarea name="mensagem_correcao" rows="4" required placeholder="Ex: Por favor, alterar o valor estimado para R$ 5.000,00."></textarea>
        <button class="btn good" type="submit" style="width: 100%; margin-top: 1rem;">Enviar Solicitação</button>
    </form>
  </div>
</div>
<?php endforeach; ?>

<?php if (tem_permissao('licitacoes.homologar')): ?>
<div id="homologar-popup" class="popup-overlay">
  <div class="popup-card card popup-small">
    <a href="#" class="popup-close">&times;</a>
    <h2>Homologar Licitação</h2>
    <p class="muted">Preencha as informações finais para homologar.</p>
    <form method="post" id="form-homologar">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="homologar">
        <input type="hidden" name="id" id="homologar-licitacao-id"><input type="hidden" name="status_id_homologado" value="<?= $id_status_homologado ?>">
        <label>Valor Adjudicado (R$)</label><input type="number" step="0.01" name="valor_adjudicado" required>
        <label>Data da Homologação</label><input type="date" name="data_homologacao" required>
        <button class="btn good login" type="submit" style="width: 100%; margin-top: 1rem;">Confirmar Homologação</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php render_footer(); ?>