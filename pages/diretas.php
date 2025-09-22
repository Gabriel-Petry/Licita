<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();
if (!tem_permissao('diretas.ver')) {
    header('Location: /dashboard');
    exit;
}

check_csrf();
$pdo = db();
$user = current_user();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            if (!tem_permissao('diretas.criar')) throw new Exception('Acesso negado.');
            $required_fields = ['processo', 'objeto', 'valor_adjudicado', 'orgao_id', 'modalidade_id', 'status_id'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Erro: O campo '" . ucfirst(str_replace(['_id', '_'], ['', ' '], $field)) . "' é obrigatório.");
                }
            }
            $params = [
                ':processo' => $_POST['processo'], ':objeto' => $_POST['objeto'], ':n_edital' => $_POST['n_edital'] ?? null,
                ':orgao_id' => $_POST['orgao_id'], ':modalidade_id' => $_POST['modalidade_id'], ':status_id' => $_POST['status_id'],
                ':valor_adjudicado' => $_POST['valor_adjudicado'], ':data_licitacao' => empty($_POST['data_licitacao']) ? null : $_POST['data_licitacao'],
                ':observacao' => $_POST['observacao'] ?? '', ':responsavel_elaboracao_id' => $_POST['responsavel_elaboracao_id'] ?? null
            ];
            $sql = "INSERT INTO licitacoes (processo, objeto, n_edital, orgao_id, modalidade_id, status_id, valor_adjudicado, data_licitacao, observacao, responsavel_elaboracao_id) 
                    VALUES (:processo, :objeto, :n_edital, :orgao_id, :modalidade_id, :status_id, :valor_adjudicado, :data_licitacao, :observacao, :responsavel_elaboracao_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Registro salvo com sucesso!'];

        } elseif ($action === 'update') {
            if (!tem_permissao('diretas.editar')) throw new Exception('Acesso negado.');
            $params = [
                ':id' => $_POST['id'], ':processo' => $_POST['processo'], ':objeto' => $_POST['objeto'], ':n_edital' => $_POST['n_edital'] ?? null,
                ':orgao_id' => $_POST['orgao_id'], ':modalidade_id' => $_POST['modalidade_id'], ':status_id' => $_POST['status_id'],
                ':valor_adjudicado' => $_POST['valor_adjudicado'], ':data_licitacao' => empty($_POST['data_licitacao']) ? null : $_POST['data_licitacao'],
                ':observacao' => $_POST['observacao'] ?? '', ':responsavel_elaboracao_id' => $_POST['responsavel_elaboracao_id'] ?? null
            ];
            $sql = "UPDATE licitacoes SET processo=:processo, objeto=:objeto, n_edital=:n_edital, orgao_id=:orgao_id, modalidade_id=:modalidade_id, status_id=:status_id, valor_adjudicado=:valor_adjudicado, data_licitacao=:data_licitacao, observacao=:observacao, responsavel_elaboracao_id=:responsavel_elaboracao_id WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Registro atualizado com sucesso!'];

        } elseif ($action === 'delete') {
            if (!tem_permissao('diretas.excluir')) throw new Exception('Acesso negado.');
            $stmt = $pdo->prepare("DELETE FROM licitacoes WHERE id = :id");
            $stmt->execute([':id' => $_POST['id']]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Registro excluído com sucesso!'];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => $e->getMessage()];
    }
    header('Location: /diretas');
    exit;
}

$items_per_page = 50;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;
$where = ["(m.nome LIKE '%Dispensa Direta%' OR m.nome LIKE '%Inexigibilidade%')"];
$params = [];

if ($user['orgao_id'] && !tem_permissao('dados.ver_todos_orgaos')) {
    $where[] = "l.orgao_id = :user_orgao_id";
    $params[':user_orgao_id'] = $user['orgao_id'];
}

if (!empty($_GET['q'])) { $where[] = "(l.processo LIKE :q OR l.objeto LIKE :q)"; $params[':q'] = '%' . $_GET['q'] . '%'; }
if (!empty($_GET['status_id'])) { $where[] = "l.status_id = :status_id"; $params[':status_id'] = $_GET['status_id']; }
if (!empty($_GET['orgao_id'])) { $where[] = "l.orgao_id = :orgao_id"; $params[':orgao_id'] = $_GET['orgao_id']; }

$where_clause = $where ? " WHERE " . implode(" AND ", $where) : "";

$count_sql = "SELECT COUNT(l.id) FROM licitacoes l LEFT JOIN modalidades m ON m.id=l.modalidade_id" . $where_clause;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();

$sql = "SELECT l.*, o.nome AS orgao, m.nome AS modalidade, s.nome AS status, re.nome AS responsavel_elaboracao FROM licitacoes l LEFT JOIN orgaos o ON o.id=l.orgao_id LEFT JOIN modalidades m ON m.id=l.modalidade_id LEFT JOIN status s ON s.id=l.status_id LEFT JOIN responsaveis_elaboracao re ON re.id=l.responsavel_elaboracao_id" . $where_clause . " ORDER BY l.id DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
$params_with_pagination = array_merge($params, [':limit' => $items_per_page, ':offset' => $offset]);
foreach ($params_with_pagination as $key => &$val) { is_int($val) ? $stmt->bindParam($key, $val, PDO::PARAM_INT) : $stmt->bindParam($key, $val, PDO::PARAM_STR); }
$stmt->execute();
$rows = $stmt->fetchAll();

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function render_diretas_table_content($rows, $total_items, $items_per_page, $current_page) {
    ob_start();
?>
    <table>
      <thead><tr><th>Nº PROCESSO</th><th>Nº EDITAL</th><th>OBJETO</th><th>STATUS</th><th>MODALIDADE</th><th>VALOR ADJUDICADO</th><th>DATA</th><th>ÓRGÃO</th><th>AÇÕES</th></tr></thead>
      <tbody>
        <?php if (count($rows) > 0): foreach ($rows as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['processo']) ?></td>
                <td><?= htmlspecialchars($r['n_edital']) ?></td>
                <td><?= htmlspecialchars($r['objeto']) ?></td>
                <td><span class="chip"><?= htmlspecialchars($r['status']) ?></span></td>
                <td><?= htmlspecialchars($r['modalidade']) ?></td>
                <td>R$ <?= number_format((float) ($r['valor_adjudicado'] ?? 0), 2, ',', '.') ?></td>
                <td><?= $r['data_licitacao'] ? htmlspecialchars(date('d/m/Y', strtotime($r['data_licitacao']))) : '--' ?></td>
                <td><?= htmlspecialchars($r['orgao']) ?></td>
                <td>
                    <?php if (tem_permissao('diretas.editar')): ?><a href="#editar-direta-popup-<?= $r['id'] ?>" class="btn btn-sm">Editar</a><?php endif; ?>
                </td>
              </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="9" style="text-align: center; padding: 2rem;">Nenhum registro encontrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?php
      $base_url = '/diretas?'.http_build_query(array_diff_key($_GET, ['page' => ''])).'&';
      render_pagination($total_items, $items_per_page, $current_page, $base_url);
    ?>
<?php
    return ob_get_clean();
}

if ($is_ajax) {
    echo render_diretas_table_content($rows, $total_items, $items_per_page, $current_page);
    exit;
}

$aux = fn ($t) => db()->query("SELECT id, nome FROM {$t} ORDER BY nome")->fetchAll();
$orgaos = $aux('orgaos');
$modalidades_diretas = $pdo->query("SELECT id, nome FROM modalidades WHERE nome = 'Inexigibilidade' OR nome = 'Dispensa Direta' ORDER BY nome")->fetchAll();
$responsaveis = $aux('responsaveis_elaboracao');
$status_diretas = $pdo->query("SELECT id, nome FROM status WHERE nome NOT LIKE 'Homologada%' AND nome NOT LIKE 'Fracassada%' AND nome NOT LIKE 'Aguardando Agendamento' AND nome NOT LIKE 'Análise Financeira' AND nome NOT LIKE 'Análise Técnica' AND nome NOT LIKE 'Elaboração de Edital' AND nome NOT LIKE 'Diligência' AND nome NOT LIKE 'Análise Docs' AND nome NOT LIKE 'Fase de Recurso'ORDER BY nome")->fetchAll();

render_header('Dispensas/Inexigibilidades - LicitAções', ['bodyClass' => 'page-licitacoes']);
?>
<div class="card">
  <?php display_flash_message(); ?>
  <div class="toolbar">
    <form class="inline" method="get" id="diretas-filter-form">
      <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Buscar por Processo ou Objeto...">
      <select name="status_id"><option value="">-- Status --</option><?php foreach ($status_diretas as $s): ?><option value="<?= $s['id'] ?>" <?= ($_GET['status_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nome']) ?></option><?php endforeach; ?></select>
      <select name="orgao_id"><option value="">-- Órgão --</option><?php foreach ($orgaos as $o): ?><option value="<?= $o['id'] ?>" <?= ($_GET['orgao_id'] ?? '') == $o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['nome']) ?></option><?php endforeach; ?></select>
      <?php if (tem_permissao('relatorios.gerar')): ?>
      <div class="dropdown-relatorio" style="margin-left: 8px;">
        <button type="button" class="btn">Gerar Relatório &#9662;</button>
        <div class="dropdown-relatorio-content">
          <a href="/gerar_relatorio?tipo=diretas&<?= http_build_query($_GET) ?>&formato=pdf" target="_blank">PDF</a>
          <a href="/gerar_relatorio?tipo=diretas&<?= http_build_query($_GET) ?>&formato=excel" target="_blank">Excel</a>
        </div>
      </div>      
      <?php endif; ?>
    </form>
    <?php if (tem_permissao('diretas.criar')): ?>
    <a href="#nova-direta-popup" class="btn primary right">Nova Dispensa/Inex.</a>
    <?php endif; ?>
  </div>

  <div class="table-scroll-container">
      <div id="diretas-table-container">
          <?php echo render_diretas_table_content($rows, $total_items, $items_per_page, $current_page); ?>
      </div>
  </div>
</div>

<?php if (tem_permissao('diretas.criar')): ?>
<div id="nova-direta-popup" class="popup-overlay">
    <div class="popup-card card">
        <a href="#" class="popup-close">&times;</a>
        <h2>Nova Dispensa ou Inexigibilidade</h2>
        <form method="post" class="form-popup">
            <div class="popup-content">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="create">
                <div class="grid grid-3">
                    <div><label>Nº Processo</label><input name="processo" required></div>
                    <div><label>Nº Edital</label><input name="n_edital"></div>
                    <div><label>Valor Adjudicado (R$)</label><input type="number" step="0.01" name="valor_adjudicado" required></div>
                    <div><label>Modalidade</label><select name="modalidade_id" required><option value="">--</option><?php foreach($modalidades_diretas as $m) echo '<option value="'.$m['id'].'">'.htmlspecialchars($m['nome']).'</option>'; ?></select></div>
                    <div><label>Órgão</label><select name="orgao_id" required><option value="">--</option><?php foreach($orgaos as $o) echo '<option value="'.$o['id'].'">'.htmlspecialchars($o['nome']).'</option>'; ?></select></div>
                    <div><label>Status</label><select name="status_id" required><option value="">--</option><?php foreach($status_diretas as $s) echo '<option value="'.$s['id'].'">'.htmlspecialchars($s['nome']).'</option>'; ?></select></div>
                    <div><label>Data</label><input type="date" name="data_licitacao"></div>
                    <div class="grid-span-2"><label>Responsável Elaboração</label><select name="responsavel_elaboracao_id"><option value="">--</option><?php foreach($responsaveis as $re) echo '<option value="'.$re['id'].'">'.htmlspecialchars($re['nome']).'</option>'; ?></select></div>
                </div>
                <div class="grid grid-2" style="margin-top: 1rem;">
                    <div><label>Objeto</label><textarea name="objeto" rows="4" required></textarea></div>
                    <div><label>Observação</label><textarea name="observacao" rows="4"></textarea></div>
                </div>
            </div>
            <div class="form-actions"><button class="btn good" type="submit">Salvar</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (tem_permissao('diretas.editar')): ?>
<?php foreach ($rows as $r): ?>
<div id="editar-direta-popup-<?= $r['id'] ?>" class="popup-overlay">
    <div class="popup-card card">
        <a href="#" class="popup-close">&times;</a>
        <h2>Editar Dispensa/Inex. #<?= $r['id'] ?></h2>
        <form method="post" class="form-popup">
            <div class="popup-content">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $r['id'] ?>">
                <div class="grid grid-3">
                    <div><label>Nº Processo</label><input name="processo" value="<?= htmlspecialchars($r['processo']) ?>" required></div>
                    <div><label>Nº Edital</label><input name="n_edital" value="<?= htmlspecialchars($r['n_edital']) ?>"></div>
                    <div><label>Valor Adjudicado (R$)</label><input type="number" step="0.01" name="valor_adjudicado" value="<?= htmlspecialchars($r['valor_adjudicado']) ?>" required></div>
                    <div><label>Modalidade</label><select name="modalidade_id" required><option value="">--</option><?php foreach($modalidades_diretas as $m) echo '<option '.($r['modalidade_id']==$m['id']?'selected':'').' value="'.$m['id'].'">'.htmlspecialchars($m['nome']).'</option>'; ?></select></div>
                    <div><label>Órgão</label><select name="orgao_id" required><option value="">--</option><?php foreach($orgaos as $o) echo '<option '.($r['orgao_id']==$o['id']?'selected':'').' value="'.$o['id'].'">'.htmlspecialchars($o['nome']).'</option>'; ?></select></div>
                    <div><label>Status</label><select name="status_id" required><option value="">--</option><?php foreach($status_diretas as $s) echo '<option '.($r['status_id']==$s['id']?'selected':'').' value="'.$s['id'].'">'.htmlspecialchars($s['nome']).'</option>'; ?></select></div>
                    <div><label>Data</label><input type="date" name="data_licitacao" value="<?= $r['data_licitacao'] ? htmlspecialchars(date('Y-m-d', strtotime($r['data_licitacao']))) : '' ?>"></div>
                    <div class="grid-span-2"><label>Responsável Elaboração</label><select name="responsavel_elaboracao_id"><option value="">--</option><?php foreach($responsaveis as $re) echo '<option '.($r['responsavel_elaboracao_id']==$re['id']?'selected':'').' value="'.$re['id'].'">'.htmlspecialchars($re['nome']).'</option>'; ?></select></div>
                </div>
                <div class="grid grid-2" style="margin-top: 1rem;">
                    <div><label>Objeto</label><textarea name="objeto" rows="4" required><?= htmlspecialchars($r['objeto']) ?></textarea></div>
                    <div><label>Observação</label><textarea name="observacao" rows="4"><?= htmlspecialchars($r['observacao']) ?></textarea></div>
                </div>
            </div>
            <div class="form-actions">
                <?php if (tem_permissao('diretas.excluir')): ?>
                <button class="btn warn btn-confirm-delete" type="submit" name="action" value="delete" data-confirm-message="Tem certeza que deseja excluir este registro?">Excluir</button>
                <?php endif; ?>
                <button class="btn good" type="submit" name="action" value="update">Atualizar</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php render_footer(); ?>