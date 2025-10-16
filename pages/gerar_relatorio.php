<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_login();
if (!tem_permissao('relatorios.gerar')) {
    die('Acesso negado.');
}
$user = current_user();
$pdo = db();

$tipo_relatorio = $_GET['tipo'] ?? 'licitacoes';
$formato = $_GET['formato'] ?? 'pdf';
$filtros = $_GET;
$pca_id = (int)($_GET['pca_id'] ?? 0);

$sql = "";
$params = [];
$titulo = "Relatório";
$colunas = [];
$filtros_where = [];
$total_geral = 0;

$alias_tabela_principal = 'l';
if (strpos($tipo_relatorio, 'pca_') === 0 || $tipo_relatorio === 'contratos' || $tipo_relatorio === 'atas') {
    $alias_tabela_principal = 'l'; 
    if ($tipo_relatorio === 'pca_calendario' || $tipo_relatorio === 'pca_dfd') {
        $alias_tabela_principal = 'd';
    }
}

if ($user['orgao_id'] && !tem_permissao('dados.ver_todos_orgaos')) {
    if ($tipo_relatorio === 'pca_itens') {
        $filtros_where[] = "d.orgao_id = :user_orgao_id";
    } else {
        $filtros_where[] = "{$alias_tabela_principal}.orgao_id = :user_orgao_id";
    }
    $params[':user_orgao_id'] = $user['orgao_id'];
}

switch ($tipo_relatorio) {
    case 'licitacoes':
        $titulo = "Relatório de Licitações";
        $colunas = ['Nº PROCESSO', 'OBJETO', 'STATUS', 'MODALIDADE', 'VALOR ESTIMADO', 'DATA', 'OBSERVAÇÃO'];
        $sql = "SELECT l.processo, l.objeto, s.nome as status, m.nome as modalidade, l.valor_estimado, l.data_licitacao, l.observacao FROM licitacoes l LEFT JOIN status s ON s.id = l.status_id LEFT JOIN modalidades m ON m.id = l.modalidade_id WHERE (s.nome NOT LIKE 'Homologad%' AND s.nome NOT LIKE 'Fracassad%' AND s.nome NOT LIKE 'Desert%') AND (m.nome NOT LIKE '%Dispensa Direta%' AND m.nome NOT LIKE '%Inexigibilidade%')";
        break;
    case 'diretas':
        $titulo = "Relatório de Dispensas/Inexigibilidades";
        $colunas = ['Nº PROCESSO', 'OBJETO', 'STATUS', 'MODALIDADE', 'VALOR ADJUDICADO', 'DATA', 'OBSERVAÇÃO'];
        $sql = "SELECT l.processo, l.objeto, s.nome as status, m.nome as modalidade, l.valor_adjudicado, l.data_licitacao, l.observacao FROM licitacoes l LEFT JOIN status s ON s.id = l.status_id LEFT JOIN modalidades m ON m.id = l.modalidade_id WHERE (m.nome LIKE '%Dispensa%' OR m.nome LIKE '%Inexigibilidade%')";
        break;
    case 'homologadas':
        $titulo = "Relatório de Homologadas";
        $colunas = ['Nº PROCESSO', 'OBJETO', 'STATUS', 'MODALIDADE', 'VALOR ESTIMADO', 'VALOR ADJUDICADO', 'DATA CONCLUSÃO', 'OBSERVAÇÃO'];
        $sql = "SELECT l.processo, l.objeto, s.nome as status, m.nome as modalidade, l.valor_estimado, l.valor_adjudicado, l.data_homologacao as data_conclusao, l.observacao FROM licitacoes l LEFT JOIN status s ON s.id = l.status_id LEFT JOIN modalidades m ON m.id = l.modalidade_id WHERE (s.nome LIKE '%Homologada%' OR s.nome LIKE '%Homologado%' OR s.nome LIKE '%Fracassada%' OR s.nome LIKE '%Deserta%')";
        break;
    case 'atas':
         $titulo = "Relatório de Atas de Registro de Preço";
         $colunas = ['Nº ATA', 'LICITAÇÃO', 'FORNECEDOR', 'OBJETO', 'INÍCIO VIGÊNCIA', 'FIM VIGÊNCIA'];
         $sql = "SELECT a.numero_ata, l.processo as licitacao_processo, f.nome as fornecedor_nome, a.objeto, a.inicio_vigencia, a.validade FROM atas_registro_preco a LEFT JOIN licitacoes l ON l.id = a.licitacao_id LEFT JOIN fornecedores f ON f.id = a.fornecedor_id";
        break;
    case 'contratos':
        $titulo = "Relatório de Contratos";
        $colunas = ['Nº CONTRATO/ANO', 'FORNECEDOR', 'OBJETO', 'VALOR', 'ASSINATURA', 'VIGÊNCIA'];
        $sql = "SELECT CONCAT(c.numero_contrato, '/', c.ano_contrato) as num_contrato, f.nome as fornecedor_nome, c.objeto, c.valor_contrato, c.data_assinatura, CONCAT(c.vigencia_inicio, ' a ', c.vigencia_fim) as vigencia FROM contratos c LEFT JOIN fornecedores f ON f.id = c.fornecedor_id LEFT JOIN licitacoes l ON c.licitacao_id = l.id";
        break;
    case 'pca_calendario':
        $titulo = "Relatório de Calendário de Contratações PCA " . ($pca_id > 0 ? $pdo->query("SELECT ano_vigencia FROM plano_contratacoes_anual WHERE id=$pca_id")->fetchColumn() : '');
        $colunas = ['Órgão', 'Objeto Resumido', 'Previsão da Contratação'];
        $sql = "SELECT o.nome as orgao_nome, d.objeto_contratacao, d.data_previsao_licitacao FROM demandas d JOIN orgaos o ON d.orgao_id = o.id WHERE d.pca_id = :pca_id ORDER BY o.nome, d.data_previsao_licitacao ASC";
        $params[':pca_id'] = $pca_id;
        break;
    case 'pca_dfd':
        $titulo = "Relatório de DFDs por Secretaria PCA " . ($pca_id > 0 ? $pdo->query("SELECT ano_vigencia FROM plano_contratacoes_anual WHERE id=$pca_id")->fetchColumn() : '');
        $colunas = ['Órgão', 'Objeto', 'Justificativa', 'Valor Estimado'];
        $sql = "SELECT o.nome as orgao_nome, d.objeto_contratacao, d.justificativa_contratacao, d.valor_total_estimado FROM demandas d JOIN orgaos o ON d.orgao_id = o.id WHERE d.pca_id = :pca_id ORDER BY o.nome, d.objeto_contratacao ASC";
        $params[':pca_id'] = $pca_id;
        break;
    case 'pca_itens':
        $titulo = "Relatório Consolidado de Itens por Tipo PCA " . ($pca_id > 0 ? $pdo->query("SELECT ano_vigencia FROM plano_contratacoes_anual WHERE id=$pca_id")->fetchColumn() : '');
        $colunas = ['Tipo de Objeto', 'Item', 'Quantidade Total', 'Unidade', 'Valor Unitário', 'Valor Total'];
        $sql = "SELECT d.tipo_objeto, di.descricao_item, SUM(di.quantidade) as quantidade, di.unidade_medida, di.valor_unitario_estimado, SUM(di.quantidade * di.valor_unitario_estimado) AS valor_total FROM demanda_itens di JOIN demandas d ON di.demanda_id = d.id WHERE d.pca_id = :pca_id GROUP BY d.tipo_objeto, di.descricao_item, di.unidade_medida, di.valor_unitario_estimado ORDER BY d.tipo_objeto, di.descricao_item ASC";
        $params[':pca_id'] = $pca_id;
        break;
}

if (!empty($filtros['q'])) {
    if ($tipo_relatorio === 'atas') $filtros_where[] = "(a.numero_ata LIKE :q OR a.objeto LIKE :q)";
    elseif ($tipo_relatorio === 'contratos') $filtros_where[] = "(c.numero_contrato LIKE :q OR c.objeto LIKE :q)";
    else $filtros_where[] = "(l.processo LIKE :q OR l.objeto LIKE :q)";
    $params[':q'] = '%' . $filtros['q'] . '%';
}
if (!empty($filtros['orgao_id'])) { $filtros_where[] = "l.orgao_id = :orgao_id"; $params[':orgao_id'] = $filtros['orgao_id']; }
if (!empty($filtros['modalidade_id'])) { $filtros_where[] = "l.modalidade_id = :modalidade_id"; $params[':modalidade_id'] = $filtros['modalidade_id']; }
if (!empty($filtros['status_id'])) { $filtros_where[] = "l.status_id = :status_id"; $params[':status_id'] = $filtros['status_id']; }

if (!empty($filtros_where)) {
    $sql .= (strpos($sql, 'WHERE') === false ? ' WHERE ' : ' AND ') . implode(" AND ", $filtros_where);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($tipo_relatorio === 'pca_itens') {
    foreach ($dados as $linha) {
        $total_geral += $linha['valor_total'];
    }
}

if ($formato === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(substr($titulo, 0, 30));

    $colunaLetra = 'A';
    foreach ($colunas as $coluna) {
        $sheet->setCellValue($colunaLetra . '1', $coluna);
        $sheet->getStyle($colunaLetra . '1')->getFont()->setBold(true);
        $colunaLetra++;
    }

    $linhaNumero = 2;
    foreach ($dados as $linha) {
        $colunaLetra = 'A';
        foreach ($linha as $key => $valor) {
            $sheet->setCellValue($colunaLetra . $linhaNumero, $valor);
             if (strpos($key, 'valor') !== false && is_numeric($valor)) {
                $sheet->getStyle($colunaLetra . $linhaNumero)->getNumberFormat()->setFormatCode('R$ #,##0.00');
            }
            $colunaLetra++;
        }
        $linhaNumero++;
    }

    if ($tipo_relatorio === 'pca_itens') {
        $sheet->setCellValue('E' . $linhaNumero, 'TOTAL GERAL:');
        $sheet->getStyle('E' . $linhaNumero)->getFont()->setBold(true);
        $sheet->setCellValue('F' . $linhaNumero, $total_geral);
        $sheet->getStyle('F' . $linhaNumero)->getFont()->setBold(true);
        $sheet->getStyle('F' . $linhaNumero)->getNumberFormat()->setFormatCode('R$ #,##0.00');
    }

    $colunaLetra--;
    foreach (range('A', $colunaLetra) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $filename = strtolower(str_replace([' ', '/'], '_', $titulo)) . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} else { 
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Sistema LicitAções');
    $pdf->SetTitle($titulo);
    $pdf->SetSubject($titulo);
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 25);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    $pdf->AddPage('L', 'A4');
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 15, $titulo, 0, 1, 'C', 0, '', 0, false, 'M', 'M');
    
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(230, 230, 230);
    $num_colunas = count($colunas);
    $w = array_fill(0, $num_colunas, 277 / $num_colunas);
    for ($i = 0; $i < $num_colunas; $i++) {
        $pdf->Cell($w[$i], 7, $colunas[$i], 1, 0, 'C', 1);
    }
    $pdf->Ln();
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255);

    $baseLineHeight = 5; 
    $minCellHeight = 6; 

    foreach ($dados as $linha) {
        $maxLines = 0;
        $colIndexForCalc = 0;
        foreach($linha as $key => $valor) {
            $valor = $valor ?? '';
            if (strpos($key, 'valor') !== false && is_numeric($valor)) {
                $valor = 'R$ ' . number_format((float)$valor, 2, ',', '.');
            } elseif (is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
                $valor = date('d/m/Y', strtotime($valor));
            }
            
            $lines = $pdf->getNumLines($valor, $w[$colIndexForCalc]);
            if ($lines > $maxLines) {
                $maxLines = $lines;
            }
            $colIndexForCalc++;
        }
        $rowHeight = max($minCellHeight, $maxLines * $baseLineHeight);

        $colIndexForDraw = 0;
        foreach ($linha as $key => $valor) {
            $valor = $valor ?? '';
            if (strpos($key, 'valor') !== false && is_numeric($valor)) {
                $valor = 'R$ ' . number_format((float)$valor, 2, ',', '.');
            } elseif (is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
                $valor = date('d/m/Y', strtotime($valor));
            }
            
            $nextPos = ($colIndexForDraw === count($linha) - 1) ? 1 : 0;
            
            $pdf->MultiCell($w[$colIndexForDraw], $rowHeight, $valor, 1, 'L', 1, $nextPos, '', '', true, 0, false, true, $rowHeight, 'M');
            $colIndexForDraw++;
        }
    }

    if ($tipo_relatorio === 'pca_itens') {
        $pdf->SetFont('helvetica', 'B', 9);
        $total_width = $w[0] + $w[1] + $w[2] + $w[3];
        $pdf->Cell($total_width, 7, 'TOTAL GERAL:', 1, 0, 'R', 1);
        $pdf->Cell($w[5], 7, 'R$ ' . number_format($total_geral, 2, ',', '.'), 1, 1, 'L', 1);
    }
    
    $pdf->Output('relatorio.pdf', 'I');
    exit;
}