<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();
if (!tem_permissao('concluidos.ver')) {
    header('Location: /dashboard');
    exit;
}

check_csrf();
$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'desomologar') {
    if (!tem_permissao('concluidos.desomologar')) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Acesso negado.'];
        header('Location: /homologadas');
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $observacao = trim($_POST['observacao_desomologacao'] ?? '');
    $novo_status_id = (int) ($_POST['novo_status_id'] ?? 0);

    if (!empty($id) && !empty($observacao) && !empty($novo_status_id)) {
        $stmt = $pdo->prepare(
            "UPDATE licitacoes SET valor_adjudicado = NULL, data_homologacao = NULL, status_id = :status_id,
             observacao = CONCAT(COALESCE(observacao, ''), '\n\n[DESOMOLOGADO EM ', DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i'), ' POR ', :user_name, ']: ', :observacao)
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id, ':status_id' => $novo_status_id, ':observacao' => $observacao, ':user_name' => $user['nome']]);
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Processo retornado com sucesso!'];
    }
    header('Location: /homologadas');
    exit;
}

$items_per_page = 50;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$where = ["(s.nome LIKE '%Homologada%' OR s.nome LIKE '%Homologado%' OR s.nome LIKE '%Fracassada%' OR s.nome LIKE '%Deserta%')"];
$params = [];

if ($user['orgao_id'] && !tem_permissao('dados.ver_todos_orgaos')) {
    $where[] = "l.orgao_id = :user_orgao_id";
    $params[':user_orgao_id'] = $user['orgao_id'];
}

if (!empty($_GET['q'])) { $where[] = "(l.processo LIKE :q OR l.objeto LIKE :q)"; $params[':q'] = '%' . $_GET['q'] . '%'; }
if (!empty($_GET['modalidade_id'])) { $where[] = "l.modalidade_id = :modalidade_id"; $params[':modalidade_id'] = $_GET['modalidade_id']; }
if (!empty($_GET['orgao_id'])) { $where[] = "l.orgao_id = :orgao_id"; $params[':orgao_id'] = $_GET['orgao_id']; }

$where_clause = $where ? " WHERE " . implode(" AND ", $where) : "";

$count_sql = "SELECT COUNT(l.id) FROM licitacoes l LEFT JOIN status s ON s.id=l.status_id" . $where_clause;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();

$sql = "SELECT l.*, o.nome AS orgao, m.nome AS modalidade, s.nome AS status, re.nome AS responsavel_elaboracao, ac.nome AS agente_contratacao
        FROM licitacoes l 
        LEFT JOIN orgaos o ON o.id=l.orgao_id 
        LEFT JOIN modalidades m ON m.id=l.modalidade_id 
        LEFT JOIN status s ON s.id=l.status_id
        LEFT JOIN responsaveis_elaboracao re ON re.id = l.responsavel_elaboracao_id
        LEFT JOIN agentes_contratacao ac ON ac.id = l.agente_contratacao_id"
        . $where_clause . " ORDER BY l.data_homologacao DESC, l.id DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
$params_with_pagination = array_merge($params, [':limit' => $items_per_page, ':offset' => $offset]);
foreach ($params_with_pagination as $key => &$val) { is_int($val) ? $stmt->bindParam($key, $val, PDO::PARAM_INT) : $stmt->bindParam($key, $val, PDO::PARAM_STR); }
$stmt->execute();
$rows = $stmt->fetchAll();

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function render_homologadas_table_content($rows, $total_items, $items_per_page, $current_page) {
    ob_start();
?>
    <table>
      <thead><tr><th>Nº PROCESSO</th><th>OBJETO</th><th>STATUS</th><th>MODALIDADE</th><th>VALOR ESTIMADO</th><th>VALOR ADJUDICADO</th><th>DATA CONCLUSÃO</th><th>AÇÕES</th></tr></thead>
      <tbody>
        <?php if (count($rows) > 0): foreach ($rows as $r): ?>
              <tr>
                <td><?= wordwrap(htmlspecialchars($r['processo']), 10, "<br>\n", true) ?></td>
                <td><?= htmlspecialchars($r['objeto']) ?></td>
                <td><span class="chip"><?= htmlspecialchars($r['status']) ?></span></td>
                <td><?= htmlspecialchars($r['modalidade']) ?></td>
                <td>R$ <?= number_format((float) $r['valor_estimado'], 2, ',', '.') ?></td>
                <td><?= $r['valor_adjudicado'] ? 'R$ ' . number_format((float) $r['valor_adjudicado'], 2, ',', '.') : '--' ?></td>
                <td><?= $r['data_homologacao'] ? htmlspecialchars(date('d/m/Y', strtotime($r['data_homologacao']))) : '--' ?></td>
                <td class="actions-cell" style="white-space: nowrap;">
                  <a href="#consultar-popup" class="btn btn-sm btn-consultar" data-licitacao='<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>'>Consultar</a>
                  <?php if (stripos($r['status'], 'homologad') !== false && tem_permissao('concluidos.desomologar')): ?>
                    <a href="#desomologar-popup" class="btn btn-sm warn btn-desomologar" data-id="<?= $r['id'] ?>" data-processo="<?= htmlspecialchars($r['processo']) ?>">Desomologar</a>
                  <?php endif; ?>
                </td>
              </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="8" style="text-align: center; padding: 2rem;">Nenhum registro encontrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?php
      $base_url = '/homologadas?'.http_build_query(array_diff_key($_GET, ['page' => ''])).'&';
      render_pagination($total_items, $items_per_page, $current_page, $base_url);
    ?>
<?php
    return ob_get_clean();
}

if ($is_ajax) {
    echo render_homologadas_table_content($rows, $total_items, $items_per_page, $current_page);
    exit;
}

$aux = fn ($t) => db()->query("SELECT id, nome FROM {$t} ORDER BY nome")->fetchAll();
$orgaos = $aux('orgaos');
$modalidades = $aux('modalidades');
$status_para_retorno = $pdo->query("SELECT id, nome FROM status WHERE nome NOT LIKE '%Homologad%' ORDER BY nome")->fetchAll();

render_header('Processos Concluídos - LicitAções', ['bodyClass' => 'page-licitacoes']);
?>
<div class="card">
  <?php display_flash_message(); ?>
  <div class="toolbar">
    <form class="inline" method="get" id="homologadas-filter-form">
      <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Buscar por Processo ou Objeto...">
      <select name="modalidade_id"><option value="">-- Modalidade --</option><?php foreach ($modalidades as $m): ?><option value="<?= $m['id'] ?>" <?= ($_GET['modalidade_id'] ?? '') == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nome']) ?></option><?php endforeach; ?></select>
      <select name="orgao_id"><option value="">-- Órgão --</option><?php foreach ($orgaos as $o): ?><option value="<?= $o['id'] ?>" <?= ($_GET['orgao_id'] ?? '') == $o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['nome']) ?></option><?php endforeach; ?></select>
      <?php if (tem_permissao('relatorios.gerar')): ?>
      <div class="dropdown-relatorio" style="margin-left: 8px;">
        <button type="button" class="btn">Gerar Relatório &#9662;</button>
        <div class="dropdown-relatorio-content">
          <a href="/gerar_relatorio?tipo=homologadas&<?= http_build_query($_GET) ?>&formato=pdf" target="_blank">PDF</a>
          <a href="/gerar_relatorio?tipo=homologadas&<?= http_build_query($_GET) ?>&formato=excel" target="_blank">Excel</a>
        </div>
      </div>
      <?php endif; ?>
    </form>
  </div>
  <div class="table-scroll-container">
      <div id="homologadas-table-container">
        <?php echo render_homologadas_table_content($rows, $total_items, $items_per_page, $current_page); ?>
      </div>
  </div>
</div>

<div id="consultar-popup" class="popup-overlay">
  <div class="popup-card card">
    <a href="#" class="popup-close">&times;</a>
    <h2 class="consultar-title">Detalhes da Licitação</h2>
    <div class="popup-content consultar-content" style="font-size: 0.95rem;">
        </div>
  </div>
</div>

<?php if (tem_permissao('concluidos.desomologar')): ?>
<div id="desomologar-popup" class="popup-overlay">
  <div class="popup-card card popup-small">
    <a href="#" class="popup-close">&times;</a>
    <h2 id="desomologar-title">Desomologar Licitação</h2>
    <form method="post" id="form-desomologar">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="desomologar"><input type="hidden" name="id" id="desomologar-licitacao-id">
        <label for="novo_status_id">Retornar para o Status</label>
        <select name="novo_status_id" required><option value="">-- Selecione --</option><?php foreach ($status_para_retorno as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nome']) ?></option><?php endforeach; ?></select>
        <label for="observacao_desomologacao">Observação (Obrigatório)</label>
        <textarea name="observacao_desomologacao" rows="4" required placeholder="Insira o motivo..."></textarea>
        <button class="btn warn" type="submit" style="width: 100%; margin-top: 1rem;">Confirmar</button>
    </form>
  </div>
</div>
<?php endif; ?>
<?php render_footer(); ?>