<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

require_login();
$pdo = db();

$demanda_id = (int)($_GET['demanda_id'] ?? 0);
if (empty($demanda_id)) {
    die('ID da demanda não fornecido.');
}

$sql = "SELECT d.*, o.nome as orgao_nome, u.nome as usuario_nome, u.cpf as usuario_cpf, u.cargo as usuario_cargo, u.setor as usuario_setor FROM demandas d JOIN orgaos o ON d.orgao_id = o.id JOIN usuarios u ON d.usuario_id = u.id WHERE d.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$demanda_id]);
$demanda = $stmt->fetch();

if (!$demanda || $demanda['status'] !== 'Aprovada') {
    die('Demanda não encontrada ou não está com o status "Aprovada".');
}

$itens_stmt = $pdo->prepare("SELECT * FROM demanda_itens WHERE demanda_id = ? ORDER BY id");
$itens_stmt->execute([$demanda_id]);
$itens = $itens_stmt->fetchAll();

try {
    $pathCabecalho = realpath(__DIR__ . '/../img/cabecalho.png');
    $pathRodape = realpath(__DIR__ . '/../img/rodape.png');

    if (!$pathCabecalho || !$pathRodape) {
        die('Erro: Não foi possível encontrar os arquivos de imagem do cabeçalho ou rodapé na pasta /img/.');
    }

    $data_prevista = $demanda['data_previsao_licitacao'] ? date('m/Y', strtotime($demanda['data_previsao_licitacao'])) : 'Não definida';
    $objeto_contratacao = htmlspecialchars($demanda['descricao_necessidade']);
    $grau_prioridade = htmlspecialchars($demanda['grau_prioridade']);
    $justificativa_prioridade = nl2br(htmlspecialchars($demanda['justificativa_prioridade']));
    $justificativa_contratacao = nl2br(htmlspecialchars($demanda['justificativa_contratacao']));
    $vinculacao_dependencia = nl2br(htmlspecialchars($demanda['vinculacao_dependencia'] ?? 'Nenhuma'));
    $valor_total_estimado = number_format($demanda['valor_total_estimado'], 2, ',', '.');
    $responsavel_nome = htmlspecialchars($demanda['usuario_nome']);
    $responsavel_cpf = htmlspecialchars($demanda['usuario_cpf'] ?? 'Não informado');
    $responsavel_cargo = htmlspecialchars($demanda['usuario_cargo'] ?? 'Não informado');
    $responsavel_setor = htmlspecialchars($demanda['usuario_setor'] ?? 'Não informado');
    $responsavel_orgao = htmlspecialchars($demanda['orgao_nome']);
    $objeto = htmlspecialchars($demanda['objeto_contratacao']);
    $data_criacao = date('d/m/Y', strtotime($demanda['data_criacao']));

    $itensHtml = '';
    if (count($itens) > 0) {
        foreach ($itens as $index => $item) {
            $item_num = $index + 1;
            $item_descricao = htmlspecialchars($item['descricao_item']);
            $item_unidade = htmlspecialchars($item['unidade_medida']);
            $item_quantidade = number_format($item['quantidade'], 2, ',', '.');
            $item_valor_unitario = number_format($item['valor_unitario_estimado'], 2, ',', '.');
            $item_valor_total = number_format($item['quantidade'] * $item['valor_unitario_estimado'], 2, ',', '.');

            $itensHtml .= "
                <tr>
                    <td>{$item_num}</td>
                    <td style='text-align: left;'>{$item_descricao}</td>
                    <td>{$item_unidade}</td>
                    <td>{$item_quantidade}</td>
                    <td>R$ {$item_valor_unitario}</td>
                    <td>R$ {$item_valor_total}</td>
                </tr>
            ";
        }
    } else {
        $itensHtml = '<tr><td colspan="6">Nenhum item cadastrado.</td></tr>';
    }

$htmlContent = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DFD</title>
    <style>
        @page {
            margin: 0;
        }
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 12px; 
            color: #000;
            margin-top: 120px;
            margin-bottom: 200px;
            margin-left: 30px;
            margin-right: 30px;
        }

        #header, #footer {
            position: fixed;
            left: 0;
            right: 0;
            width: 100%;
        }
        #header {
            top: 0;
        }
        #footer {
            bottom: 0;
        }
        #header img, #footer img {
            width: 100%;
        }

        .header-title { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 20px; }
        .section-title { font-size: 12px; font-weight: bold; background-color: #e6e6e6; text-align: center; padding: 4px; border: 1px solid #000; }
        .intro-text { text-align: justify; }
        .main-table, .items-table { width: 100%; border-collapse: collapse; border: 1px solid #000; margin-bottom: 10px; }
        .main-table td { border: 1px solid #000; padding: 5px; vertical-align: top; }
        .items-table th, .items-table td { border: 1px solid #000; padding: 4px; text-align: center; }
        .label { font-weight: bold; }
        .footer-date { text-align: right; margin-top: 20px; }
        .resp-title { background-color: #e6e6e6; font-weight: bold; }
    </style>
</head>
<body>
    <div id="header">
        <img src="{$pathCabecalho}">
    </div>

    <div id="footer">
        <img src="{$pathRodape}">
    </div>

    <main>
        <div class="header-title">DOCUMENTO DE FORMALIZAÇÃO DA DEMANDA – DFD {$objeto}</div>

        <div class="section-title">INTRODUÇÃO</div>
        <table class="main-table">
            <tr>
                <td class="intro-text">De acordo com o inciso IV do art. 2º do Decreto nº 10.947, de 25 de janeiro de 2022, o Documento de Formalização de Demanda (DFD) é o documento que fundamenta o plano de contratações anual, em que a área requisitante evidencia e detalha a necessidade de contratação. Adicionalmente, o art. 8º do Decreto nº 10.947, de 2022 e § 1º do art. 10 da Instrução Normativa SGD/ME n° 94, de 23 de dezembro de 2022, especificam as informações mínimas requeridas ao preenchimento do DFD no Sistema de Planejamento e Gerenciamento de Contratações (PGC), as quais serão detalhadas nos tópicos a seguir.</td>
            </tr>
        </table>

        <div class="section-title">1- INFORMAÇÕES GERAIS</div>
        <table class="main-table">
            <tr>
                <td>
                    <span class="label">1.1- Data prevista para conclusão do processo</span><br>
                    {$data_prevista}<br><br>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">1.2- Descrição sucinta do objeto</span><br>
                    {$objeto_contratacao}<br><br>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">1.3- Grau de prioridade da compra ou da contratação</span><br>
                    {$grau_prioridade}<br><br>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">1.4- Justificativa do Grau de Prioridade</span><br>
                    {$justificativa_prioridade}<br><br>
                </td>
            </tr>
        </table>

        <div class="section-title">2- JUSTIFICATIVA DA NECESSIDADE DA CONTRATAÇÃO</div>
        <table class="main-table">
            <tr>
                <td>
                    <span class="label">2.1- Justificativa da necessidade da contratação</span><br>
                    {$justificativa_contratacao}<br><br>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">2.2- Indicação de vinculação ou dependência com o objeto de outro documento de formalização de demanda.</span><br>
                    {$vinculacao_dependencia}<br><br>
                </td>
            </tr>
        </table>
        
        <div class="section-title">3- MATERIAIS/SERVIÇOS</div>
        <table class="items-table">
            <thead>
                <tr style="background-color: #e6e6e6; font-weight:bold;">
                    <th>Item</th>
                    <th style="text-align: left;">Descrição</th>
                    <th>Unidade</th>
                    <th>Qtde.</th>
                    <th>Valor Unitário</th>
                    <th>Valor Total</th>
                </tr>
            </thead>
            <tbody>
                {$itensHtml}
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right; font-weight:bold;">VALOR TOTAL</td>
                    <td style="font-weight:bold;">R$ {$valor_total_estimado}</td>
                </tr>
            </tfoot>
        </table>
        
        <div class="section-title">4- IDENTIFICAÇÃO DA ÁREA REQUISITANTE E RESPONSÁVEIS</div>
        <table class="main-table">
            <tr>
                <td colspan="2"><span class="label">Área Requisitante (Unidade/Setor/Depto):</span> {$responsavel_orgao} - {$responsavel_setor}</td>
            </tr>
            <tr>
                <td colspan="2" class="resp-title">Responsável pela demanda:</td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">Nome:</span> {$responsavel_nome}</td>
            </tr>
            <tr>
                <td style="width:50%;"><span class="label">CPF:</span> {$responsavel_cpf}</td>
                <td><span class="label">Cargo/Função:</span> {$responsavel_cargo}</td>
            </tr>
        </table>

        <div class="footer-date">Data da Demanda {$data_criacao}</div>
    </main>

</body>
</html>
HTML;

    $options = new Options();
    $options->set('isRemoteEnabled', true); 
    $options->set('chroot', realpath(__DIR__ . '/../'));
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($htmlContent);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $fileName = 'DFD_' . preg_replace('/[^a-zA-Z0-9-]/', '_', $demanda['descricao_necessidade']) . '_' . $demanda['id'] . '.pdf';
    $dompdf->stream($fileName, ["Attachment" => false]); 
    exit;

} catch (Exception $e) {
    die('Ocorreu um erro ao gerar o documento: ' . $e->getMessage());
}