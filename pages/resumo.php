<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();
if (!tem_permissao('licitacoes.ver')) {
    header('Location: /dashboard');
    exit;
}
$user = current_user();
$pdo = db();

$where = ["(s.nome IS NULL OR s.nome NOT LIKE 'Homologad%' AND s.nome NOT LIKE 'Fracassad%' AND s.nome NOT LIKE 'Desert%')", "(m.nome NOT LIKE '%Dispensa Direta%' AND m.nome NOT LIKE '%Inexigibilidade%')"];
$params = [];

if ($user['orgao_id'] && !tem_permissao('dados.ver_todos_orgaos')) {
    $where[] = "l.orgao_id = :user_orgao_id";
    $params[':user_orgao_id'] = $user['orgao_id'];
}

$current_fase = $_GET['fase'] ?? 'todas';
if ($current_fase === 'interna') {
    $where[] = "l.status_id IN (8, 11, 12, 19, 20)"; 
} elseif ($current_fase === 'externa') {
    $where[] = "l.status_id NOT IN (8, 11, 12, 19, 20)";
}

if (!empty($_GET['q'])) {
    $where[] = "(l.processo LIKE :q OR l.objeto LIKE :q)";
    $params[':q'] = '%' . $_GET['q'] . '%';
}

if (!empty($_GET['prioridade'])) {
    $where[] = "l.prioridade = :prioridade";
    $params[':prioridade'] = $_GET['prioridade'];
}

$stmt_prazos_sql = "
  SELECT l.processo, m.nome AS modalidade, l.n_edital, l.objeto, l.observacao, l.data_licitacao, l.prioridade
  FROM licitacoes l
  LEFT JOIN modalidades m ON m.id = l.modalidade_id
  LEFT JOIN status s ON s.id = l.status_id
  WHERE " . implode(" AND ", $where) . "
  ORDER BY l.prioridade DESC, CAST(SUBSTRING_INDEX(l.n_edital, '/', 1) AS UNSIGNED) ASC, l.id DESC
";

$stmt_prazos = $pdo->prepare($stmt_prazos_sql);
$stmt_prazos->execute($params);
$rows = $stmt_prazos->fetchAll();

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function render_resumo_table_content($rows) {
    ob_start();
?>
    <table class="table-prazos">
      <thead>
        <tr>
          <th>Nº PROCESSO</th><th>MODALIDADE</th><th>Nº EDITAL</th><th class="text-wrap">OBJETO</th><th class="text-wrap">OBSERVAÇÃO</th><th style="text-align: right;">DATA DA LICITAÇÃO</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($rows) > 0): ?>
          <?php foreach ($rows as $row):
                $priority_class = '';
                switch ($row['prioridade']) {
                    case 3: $priority_class = 'priority-high'; break;
                    case 2: $priority_class = 'priority-medium'; break;
                    case 1: default: $priority_class = 'priority-low'; break;
                }
          ?>
            <tr class="<?= $priority_class ?>">
              <td><?= wordwrap(htmlspecialchars($row['processo']), 10, "<br>\n", true) ?></td>
              <td><?= htmlspecialchars($row['modalidade']) ?></td>
              <td><?= htmlspecialchars($row['n_edital']) ?></td>
              <td><?= wordwrap(htmlspecialchars($row['objeto']), 40, "<br>\n", true) ?></td>
              <td><?= wordwrap(htmlspecialchars($row['observacao']), 60, "<br>\n", true) ?></td>
              <td style="text-align: right;"><?= ($row['data_licitacao'] ? htmlspecialchars(date('d/m/Y', strtotime($row['data_licitacao']))) : '--/--/----') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" style="text-align: center; padding: 2rem;">Nenhum registro encontrado para os filtros selecionados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
<?php
    return ob_get_clean();
}

if ($is_ajax) {
    echo render_resumo_table_content($rows);
    exit;
}

render_header('Resumo - LicitAções', ['bodyClass' => 'page-licitacoes page-resumo']);
?>

<div class="card">
  <div class="toolbar">
    <a href="/resumo" class="btn btn-sm <?= ($current_fase === 'todas') ? 'primary' : '' ?>">Todas as Fases</a>
    <a href="/resumo?fase=interna" class="btn btn-sm <?= ($current_fase === 'interna') ? 'primary' : '' ?>">Fase Interna</a>
    <a href="/resumo?fase=externa" class="btn btn-sm <?= ($current_fase === 'externa') ? 'primary' : '' ?>">Fase Externa</a>
  </div>
  
  <div class="toolbar">
      <form class="inline" method="get" id="resumo-filter-form" style="width: 100%;">
          <?php if (isset($_GET['fase'])): ?>
            <input type="hidden" name="fase" value="<?= htmlspecialchars($_GET['fase']) ?>">
          <?php endif; ?>
          <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Buscar por Processo ou Objeto..." style="flex-grow: 1;">
          <select name="prioridade">
            <option value="">-- Prioridade --</option>
            <option value="3" <?= ($_GET['prioridade'] ?? '') == '3' ? 'selected' : '' ?>>Alta</option>
            <option value="2" <?= ($_GET['prioridade'] ?? '') == '2' ? 'selected' : '' ?>>Média</option>
            <option value="1" <?= ($_GET['prioridade'] ?? '') == '1' ? 'selected' : '' ?>>Baixa</option>
          </select>
      </form>
  </div>

  <div class="table-scroll-container">
    <div id="resumo-table-container">
        <?php echo render_resumo_table_content($rows); ?>
    </div>
  </div>
</div>

<?php 
render_footer(); 
?>