<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
$pdo = db();

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$params = [':year' => $year];

try {
    $year_filter_sql = "SUBSTRING_INDEX(l.n_edital, '/', -1) = :year";

    $totalLicitacoesAno_stmt = $pdo->prepare("SELECT COUNT(l.id) FROM licitacoes l JOIN modalidades m ON l.modalidade_id = m.id WHERE {$year_filter_sql} AND (m.nome NOT LIKE '%Dispensa Direta%' AND m.nome NOT LIKE '%Inexigibilidade%')");
    $totalLicitacoesAno_stmt->execute($params);
    $totalLicitacoesAno = (int)$totalLicitacoesAno_stmt->fetchColumn();

    $totalDiretasAno_stmt = $pdo->prepare("SELECT COUNT(l.id) FROM licitacoes l JOIN modalidades m ON l.modalidade_id = m.id WHERE {$year_filter_sql} AND (m.nome LIKE '%Dispensa Direta%' OR m.nome LIKE '%Inexigibilidade%')");
    $totalDiretasAno_stmt->execute($params);
    $totalDiretasAno = (int)$totalDiretasAno_stmt->fetchColumn();

    $totalHomologadas_stmt = $pdo->prepare("SELECT COUNT(l.id) FROM licitacoes l JOIN status s ON l.status_id = s.id WHERE {$year_filter_sql} AND s.nome LIKE 'Homologad%'");
    $totalHomologadas_stmt->execute($params);
    $totalHomologadasAno = (int)$totalHomologadas_stmt->fetchColumn();

    $totalFracassadas_stmt = $pdo->prepare("SELECT COUNT(l.id) FROM licitacoes l JOIN status s ON l.status_id = s.id WHERE {$year_filter_sql} AND (s.nome LIKE 'Fracassad%' OR s.nome LIKE 'Desert%')");
    $totalFracassadas_stmt->execute($params);
    $totalFracassadasDesertasAno = (int)$totalFracassadas_stmt->fetchColumn();

    $valorAno_stmt = $pdo->prepare("SELECT COALESCE(SUM(valor_estimado),0) FROM licitacoes l WHERE " . $year_filter_sql);
    $valorAno_stmt->execute($params);
    $valorAno = (float)$valorAno_stmt->fetchColumn();

    $valorAdjudicadoAno_stmt = $pdo->prepare("SELECT COALESCE(SUM(l.valor_adjudicado),0) FROM licitacoes l JOIN modalidades m ON l.modalidade_id = m.id WHERE {$year_filter_sql} AND (m.nome NOT LIKE '%Dispensa Direta%' AND m.nome NOT LIKE '%Inexigibilidade%')");
    $valorAdjudicadoAno_stmt->execute($params);
    $valorAdjudicadoAno = (float)$valorAdjudicadoAno_stmt->fetchColumn();

    $economiaTotalAno_stmt = $pdo->prepare("SELECT COALESCE(SUM(l.valor_estimado - l.valor_adjudicado), 0) FROM licitacoes l WHERE {$year_filter_sql} AND l.valor_estimado > 0 AND l.valor_adjudicado IS NOT NULL");
    $economiaTotalAno_stmt->execute($params);
    $economiaTotalAno = (float)$economiaTotalAno_stmt->fetchColumn();
    
    $valorTotalDiretasAno_stmt = $pdo->prepare("SELECT COALESCE(SUM(l.valor_adjudicado),0) FROM licitacoes l JOIN modalidades m ON l.modalidade_id = m.id WHERE {$year_filter_sql} AND (m.nome LIKE '%Dispensa Direta%' OR m.nome LIKE '%Inexigibilidade%')");
    $valorTotalDiretasAno_stmt->execute($params);
    $valorTotalDiretasAno = (float)$valorTotalDiretasAno_stmt->fetchColumn();

    $status_stmt = $pdo->prepare("SELECT COALESCE(s.nome, 'Sem Status') AS nome, COUNT(l.id) AS qtd FROM licitacoes l LEFT JOIN status s ON s.id = l.status_id WHERE {$year_filter_sql} AND s.nome NOT LIKE 'Homologada%' AND s.nome NOT LIKE 'Fracassada%' AND s.nome NOT LIKE 'Desert%' AND s.nome NOT LIKE 'Publicada' GROUP BY s.nome ORDER BY s.nome");
    $status_stmt->execute($params);
    $status = $status_stmt->fetchAll();
    
    $contagem_modalidade_stmt = $pdo->prepare("SELECT COALESCE(m.nome, 'Não Definida') as nome, COUNT(l.id) as total FROM licitacoes l LEFT JOIN modalidades m ON m.id = l.modalidade_id WHERE {$year_filter_sql} GROUP BY m.nome ORDER BY total DESC");
    $contagem_modalidade_stmt->execute($params);
    $contagem_modalidade = $contagem_modalidade_stmt->fetchAll();

    $gasto_por_orgao_stmt = $pdo->prepare("SELECT o.nome, SUM(l.valor_adjudicado) as total FROM orgaos o JOIN licitacoes l ON o.id = l.orgao_id WHERE {$year_filter_sql} AND l.valor_adjudicado > 0 GROUP BY o.nome ORDER BY total DESC");
    $gasto_por_orgao_stmt->execute($params);
    $gasto_por_orgao = $gasto_por_orgao_stmt->fetchAll();

    $top_agentes_stmt = $pdo->prepare("
        SELECT ac.nome, SUM(COALESCE(l.complexidade, 1)) as total_pontos
        FROM agentes_contratacao ac
        LEFT JOIN licitacoes l ON ac.id = l.agente_contratacao_id
        WHERE {$year_filter_sql}
        GROUP BY ac.nome
        HAVING COUNT(l.id) > 0
        ORDER BY total_pontos DESC
        LIMIT 10
    ");
    $top_agentes_stmt->execute($params);
    $top_agentes_nomes = $top_agentes_stmt->fetchAll(PDO::FETCH_COLUMN);

    $contagem_por_agente = [];

    if (!empty($top_agentes_nomes)) {
        $in_params_ag = [];
        $in_names_ag = [];
        foreach ($top_agentes_nomes as $k => $name) {
            $pname = ":ag_$k";
            $in_params_ag[$pname] = $name;
            $in_names_ag[] = $pname;
        }
        $placeholders_ag = implode(',', $in_names_ag);

        $sql_detalhe_ag = "
            SELECT ac.nome, COALESCE(l.complexidade, 1) as nivel, COUNT(l.id) as qtd
            FROM licitacoes l
            JOIN agentes_contratacao ac ON l.agente_contratacao_id = ac.id
            WHERE {$year_filter_sql}
            AND ac.nome IN ($placeholders_ag)
            GROUP BY ac.nome, nivel
        ";
        
        $params_detalhe_ag = array_merge($params, $in_params_ag);
        $stmt_detalhe_ag = $pdo->prepare($sql_detalhe_ag);
        $stmt_detalhe_ag->execute($params_detalhe_ag);
        $dados_ag_raw = $stmt_detalhe_ag->fetchAll(PDO::FETCH_ASSOC);

        $dados_ag_organizados = [];
        foreach ($dados_ag_raw as $row) {
            $nivel = (int)$row['nivel'];
            $pontos = (int)$row['qtd'] * $nivel;
            $dados_ag_organizados[$nivel][$row['nome']] = $pontos;
        }

        $datasetsAgentes = [];
        
        $data_n1 = [];
        foreach ($top_agentes_nomes as $nome) $data_n1[] = $dados_ag_organizados[1][$nome] ?? 0;
        $datasetsAgentes[] = ['label' => 'Simples (1 pt)', 'data' => $data_n1, 'backgroundColor' => '#22c55e']; // Verde

        $data_n2 = [];
        foreach ($top_agentes_nomes as $nome) $data_n2[] = $dados_ag_organizados[2][$nome] ?? 0;
        $datasetsAgentes[] = ['label' => 'Médio (2 pts)', 'data' => $data_n2, 'backgroundColor' => '#f59e0b']; // Laranja

        $data_n3 = [];
        foreach ($top_agentes_nomes as $nome) $data_n3[] = $dados_ag_organizados[3][$nome] ?? 0;
        $datasetsAgentes[] = ['label' => 'Complexo (3 pts)', 'data' => $data_n3, 'backgroundColor' => '#ef4444']; // Vermelho

        $contagem_por_agente = [
            'labels' => $top_agentes_nomes,
            'datasets' => $datasetsAgentes
        ];
    }

    $top_resp_stmt = $pdo->prepare("
        SELECT re.nome, SUM(COALESCE(l.complexidade, 1)) as total_pontos
        FROM responsaveis_elaboracao re 
        LEFT JOIN licitacoes l ON re.id = l.responsavel_elaboracao_id 
        WHERE {$year_filter_sql} 
        GROUP BY re.nome 
        HAVING COUNT(l.id) > 0 
        ORDER BY total_pontos DESC 
        LIMIT 10
    ");
    $top_resp_stmt->execute($params);
    $top_responsaveis_dados = $top_resp_stmt->fetchAll(PDO::FETCH_ASSOC);
    $top_responsaveis_nomes = array_column($top_responsaveis_dados, 'nome');

    $contagem_por_responsavel = [];
    
    if (!empty($top_responsaveis_nomes)) {
        $in_params = [];
        $in_names = [];
        foreach ($top_responsaveis_nomes as $k => $name) {
            $pname = ":resp_$k";
            $in_params[$pname] = $name;
            $in_names[] = $pname;
        }
        $placeholders = implode(',', $in_names);
        
        $sql_detalhe = "
            SELECT re.nome as responsavel, m.nome as modalidade, SUM(COALESCE(l.complexidade, 1)) as total_pontos
            FROM licitacoes l 
            JOIN responsaveis_elaboracao re ON l.responsavel_elaboracao_id = re.id
            JOIN modalidades m ON l.modalidade_id = m.id
            WHERE {$year_filter_sql} 
            AND re.nome IN ($placeholders)
            GROUP BY re.nome, m.nome
        ";
        
        $params_detalhe = array_merge($params, $in_params);
        $stmt_detalhe = $pdo->prepare($sql_detalhe);
        $stmt_detalhe->execute($params_detalhe);
        $dados_raw = $stmt_detalhe->fetchAll(PDO::FETCH_ASSOC);

        $modalidades_encontradas = [];
        $dados_organizados = [];

        foreach ($dados_raw as $row) {
            $modalidades_encontradas[$row['modalidade']] = true;
            $dados_organizados[$row['modalidade']][$row['responsavel']] = (int)$row['total_pontos'];
        }

        $chartColors = ['#7c3aed', '#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#64748b', '#ec4899', '#8b5cf6'];
        $datasetsResponsaveis = [];
        $cor_idx = 0;

        foreach (array_keys($modalidades_encontradas) as $mod) {
            $data_series = [];
            foreach ($top_responsaveis_nomes as $resp) {
                $data_series[] = isset($dados_organizados[$mod][$resp]) ? $dados_organizados[$mod][$resp] : 0;
            }

            $datasetsResponsaveis[] = [
                'label' => $mod,
                'data' => $data_series,
                'backgroundColor' => $chartColors[$cor_idx % count($chartColors)]
            ];
            $cor_idx++;
        }

        $contagem_por_responsavel = [
            'labels' => $top_responsaveis_nomes,
            'datasets' => $datasetsResponsaveis
        ];
    }
    
    $desempenho_stmt = $pdo->prepare("SELECT o.nome AS orgao, s.nome AS status, COUNT(l.id) AS qtd FROM licitacoes l JOIN orgaos o ON l.orgao_id = o.id JOIN status s ON l.status_id = s.id WHERE {$year_filter_sql} AND (s.nome LIKE 'Homologad%' OR s.nome LIKE 'Fracassad%' OR s.nome LIKE 'Desert%') GROUP BY o.nome, s.nome ORDER BY o.nome, s.nome");
    $desempenho_stmt->execute($params);
    $desempenho_raw = $desempenho_stmt->fetchAll();
    
    $labelsDesempenho = [];
    $datasetsDesempenho = ['Homologada' => ['label' => 'Homologada', 'data' => [], 'backgroundColor' => 'rgba(34, 197, 94, 0.7)'],'Fracassada' => ['label' => 'Fracassada', 'data' => [], 'backgroundColor' => 'rgba(239, 68, 68, 0.7)'],'Deserta' => ['label' => 'Deserta', 'data' => [], 'backgroundColor' => 'rgba(100, 116, 139, 0.7)']];
    $orgaos_map = [];
    foreach ($desempenho_raw as $row) {
        if (!in_array($row['orgao'], $labelsDesempenho)) {
            $labelsDesempenho[] = $row['orgao'];
            $orgaos_map[$row['orgao']] = count($labelsDesempenho) - 1;
            foreach ($datasetsDesempenho as &$dataset) { $dataset['data'][count($labelsDesempenho) - 1] = 0; }
        }
    }
    foreach ($desempenho_raw as $row) {
        $orgao_idx = $orgaos_map[$row['orgao']];
        $status_key = stripos($row['status'], 'homologad') !== false ? 'Homologada' : (stripos($row['status'], 'fracassad') !== false ? 'Fracassada' : 'Deserta');
        $datasetsDesempenho[$status_key]['data'][$orgao_idx] = (int)$row['qtd'];
    }

    $tempo_medio_homologacao_stmt = $pdo->prepare("SELECT o.nome, ROUND(AVG(DATEDIFF(l.data_homologacao, l.data_licitacao))) as dias FROM licitacoes l JOIN orgaos o ON l.orgao_id = o.id WHERE {$year_filter_sql} AND l.data_homologacao IS NOT NULL AND l.data_licitacao IS NOT NULL GROUP BY o.nome HAVING COUNT(l.id) > 0 ORDER BY dias DESC");
    $tempo_medio_homologacao_stmt->execute($params);
    $tempo_medio_homologacao = $tempo_medio_homologacao_stmt->fetchAll();

    $heatmap_stmt = $pdo->prepare("SELECT o.nome as orgao, MONTH(l.data_licitacao) as mes, COUNT(l.id) as qtd FROM licitacoes l JOIN orgaos o ON l.orgao_id = o.id WHERE {$year_filter_sql} AND l.data_licitacao IS NOT NULL GROUP BY o.nome, mes ORDER BY o.nome, mes");
    $heatmap_stmt->execute($params);
    $heatmap_data = $heatmap_stmt->fetchAll();

    $labelsHeatmapOrgaos = array_values(array_unique(array_column($heatmap_data, 'orgao')));
    $labelsHeatmapMeses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $seriesHeatmap = [];
    foreach($heatmap_data as $item) {
        $seriesHeatmap[] = ['x' => $labelsHeatmapMeses[(int)$item['mes'] - 1], 'y' => $item['orgao'], 'v' => (int)$item['qtd']];
    }
    
    echo json_encode([
        'kpis' => [
            'gerais' => [
                'licitacoes_ano' => $totalLicitacoesAno,
                'diretas_ano' => $totalDiretasAno,
                'homologadas_ano' => $totalHomologadasAno,
                'fracassadas_desertas_ano' => $totalFracassadasDesertasAno
            ],
            'financeiros' => [
                'valor_estimado_ano' => $valorAno,
                'valor_adjudicado_ano' => $valorAdjudicadoAno,
                'economia_ano' => $economiaTotalAno,
                'valor_total_diretas_ano' => $valorTotalDiretasAno
            ]
        ],
        'status' => ['labels' => array_column($status, 'nome'), 'series' => array_column($status, 'qtd')],
        'contagem_por_modalidade' => ['labels' => array_column($contagem_modalidade, 'nome'), 'series' => array_map('intval', array_column($contagem_modalidade, 'total'))],
        'gasto_por_orgao' => ['labels' => array_column($gasto_por_orgao, 'nome'), 'series' => array_map('floatval', array_column($gasto_por_orgao, 'total'))],
        
        'contagem_por_agente' => $contagem_por_agente,
        
        'contagem_por_responsavel' => $contagem_por_responsavel, 
        'desempenho_por_orgao' => ['labels' => $labelsDesempenho, 'datasets' => array_values($datasetsDesempenho)],
        'tempo_medio_homologacao' => ['labels' => array_column($tempo_medio_homologacao, 'nome'), 'series' => array_map('intval', array_column($tempo_medio_homologacao, 'dias'))],
        'mapa_calor' => ['labels_orgaos' => $labelsHeatmapOrgaos, 'labels_meses' => $labelsHeatmapMeses, 'series' => $seriesHeatmap]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de banco de dados: ' . $e->getMessage()]);
}
?>