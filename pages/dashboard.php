<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();
if (!tem_permissao('dashboard.ver')) {
    header('Location: /licitacoes'); 
    exit;
}

render_header('Dashboard - LicitAções', [
    'bodyClass' => 'page-dashboard',
    'scripts' => ['/js/dashboard.js']
]);

$pdo = db();
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$years_stmt = $pdo->query("SELECT DISTINCT SUBSTRING_INDEX(n_edital, '/', -1) as ano FROM licitacoes WHERE n_edital LIKE '%/%' ORDER BY ano DESC");
$available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
if (!in_array(date('Y'), $available_years)) {
    array_unshift($available_years, date('Y'));
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$current_calendar_year = isset($_GET['year_cal']) ? (int)$_GET['year_cal'] : date('Y');

$stmt_calendario = $pdo->prepare("
    SELECT EXTRACT(DAY FROM data_licitacao) AS dia, processo, objeto 
    FROM licitacoes 
    WHERE EXTRACT(MONTH FROM data_licitacao) = ? AND EXTRACT(YEAR FROM data_licitacao) = ?
    ORDER BY dia
");
$stmt_calendario->execute([$month, $current_calendar_year]);
$licitacoes_mes = $stmt_calendario->fetchAll(PDO::FETCH_GROUP); 

function render_calendar($year, $month, $events_by_day = []) {
    setlocale(LC_TIME, 'pt_BR.utf-8', 'pt_BR', 'portuguese');
    $month_name = strftime('%B', mktime(0, 0, 0, $month, 1, $year));
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $first_day_of_month = date('w', mktime(0, 0, 0, $month, 1, $year));
    $today = date('j');
    $current_month_today = date('m');
    $current_year_today = date('Y');
    
    $prev_month = $month - 1; $prev_year = $year;
    if ($prev_month == 0) { $prev_month = 12; $prev_year--; }
    $next_month = $month + 1; $next_year = $year;
    if ($next_month == 13) { $next_month = 1; $next_year++; }

    echo '<div class="calendario-header">';
    echo '<a href="?month=' . $prev_month . '&year_cal=' . $prev_year . '" class="nav-arrow" title="Mês Anterior">&lt;</a>';
    echo '<h3>' . ucfirst($month_name) . ' ' . $year . '</h3>';
    echo '<a href="?month=' . $next_month . '&year_cal=' . $next_year . '" class="nav-arrow" title="Próximo Mês">&gt;</a>';
    echo '</div>';
    echo '<table class="calendario"><thead><tr><th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th></tr></thead><tbody><tr>';
    for ($i = 0; $i < $first_day_of_month; $i++) { echo '<td></td>'; }
    $day_counter = $first_day_of_month;
    for ($day = 1; $day <= $days_in_month; $day++, $day_counter++) {
        if ($day_counter % 7 == 0 && $day > 1) { echo '</tr><tr>'; }
        $is_today = ($day == $today && $month == $current_month_today && $year == $current_year_today);
        $has_event = isset($events_by_day[$day]);
        $class = $is_today ? ' hoje' : '';
        $tooltip_content = '';
        if ($has_event) {
            $class .= ' evento';
            $tooltip_html = '<ul>';
            foreach ($events_by_day[$day] as $event) { $tooltip_html .= '<li><strong>' . htmlspecialchars($event['processo']) . ':</strong> ' . htmlspecialchars(substr($event['objeto'], 0, 100)) . '...</li>'; }
            $tooltip_html .= '</ul>';
            $tooltip_content = 'data-tooltip="' . htmlspecialchars($tooltip_html, ENT_QUOTES) . '"';
        }
        echo "<td class='" . trim($class) . "' " . $tooltip_content . " data-day='" . $day . "'><span class='day-number'>" . $day . "</span></td>";
    }
    while ($day_counter % 7 != 0) { echo '<td></td>'; $day_counter++; }
    echo '</tr></tbody></table>';
}
?>

<div class="dashboard-header">
    <h2>Dashboard Geral</h2>
    <div class="dashboard-filter">
        <form id="dashboard-filter-form" class="inline">
            <label for="year-filter">Ano:</label>
            <select name="year" id="year-filter">
                <?php foreach ($available_years as $y): ?>
                    <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<div class="kpi-container">
    <div class="card"><div class="muted">Licitações (Ano)</div><div class="kpi" id="kpiLicitacoesAno">--</div></div>
    <div class="card"><div class="muted">Contratações Diretas (Ano)</div><div class="kpi" id="kpiDiretasAno">--</div></div>
    <div class="card"><div class="muted">Homologadas (Ano)</div><div class="kpi" id="kpiHomologadasAno">--</div></div>
    <div class="card"><div class="muted">Fracassadas/Desertas (Ano)</div><div class="kpi" id="kpiFracassadasDesertasAno">--</div></div>
</div>

<div class="kpi-container">
    <div class="card"><div class="muted">Valor Estimado (Ano)</div><div class="kpi" id="kpiValorEstimadoAno">--</div></div>
    <div class="card"><div class="muted">Valor Adjudicado (Ano)</div><div class="kpi" id="kpiValorAdjudicadoAno">--</div></div>
    <div class="card"><div class="muted">Economia Gerada (Ano)</div><div class="kpi" id="kpiEconomiaAno">--</div></div>
    <div class="card"><div class="muted">Valor Contrat. Diretas (Ano)</div><div class="kpi" id="kpiValorDiretasAno">--</div></div>
</div>

<div class="dashboard-grid-container">
    <div class="card"><h3>Status (Em andamento)</h3><div class="chart-container"><canvas id="chartStatus"></canvas></div></div>
    <div class="card card-calendario">
        <?php render_calendar($current_calendar_year, $month, $licitacoes_mes); ?>
    </div>
    <div class="card"><h3>Modalidades (Qtd)</h3><div class="chart-container"><canvas id="chartContagemModalidade"></canvas></div></div>
    <div class="card"><h3>Gasto por Órgão (R$)</h3><div class="chart-container"><canvas id="chartGastoOrgao"></canvas></div></div>
    <div class="card"><h3>Desempenho por Órgão</h3><div class="chart-container"><canvas id="chartDesempenhoOrgao"></canvas></div></div>
    <div class="card"><h3>Top Agentes (Qtd)</h3><div class="chart-container"><canvas id="chartAgentes"></canvas></div></div>
    <div class="card"><h3>Top Responsáveis (Qtd)</h3><div class="chart-container"><canvas id="chartResponsaveis"></canvas></div></div>
    <div class="card card-full-height"><h3>Tempo Médio de Homologação (Dias)</h3><div class="chart-container"><canvas id="chartTempoMedio"></canvas></div></div>
    <div class="card card-full-height"><h3>Concentração de Licitações</h3><div class="chart-container"><canvas id="chartMapaCalor"></canvas></div></div>
</div>

<div id="calendar-event-popup" class="popup-overlay">
  <div class="popup-card card popup-small">
    <a href="#" class="popup-close">&times;</a>
    <h2 id="popup-title">Licitações do Dia</h2>
    <div id="popup-content" class="popup-content" style="padding-top: 1rem;"></div>
  </div>
</div>
<div id="calendar-tooltip" class="calendar-tooltip"></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@^1.1.1"></script>

<?php
render_footer(); 
?>