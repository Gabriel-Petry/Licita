<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['erro' => 'Nenhum arquivo enviado.']);
    exit;
}

$inputFileName = $_FILES['arquivo']['tmp_name'];

try {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    
    $itens = [];
    
    $idx = ['nr' => 0, 'desc' => 1, 'un' => 2, 'qtd' => 3];
    $headerFound = false;

    foreach ($rows as $index => $row) {
        if (!$headerFound) {
            $tempIdx = ['nr' => null, 'desc' => null, 'un' => null, 'qtd' => null];
            $foundInRow = false;
            
            foreach ($row as $k => $v) {
                $val = mb_strtolower(trim($v ?? ''), 'UTF-8');
                if ($val === 'item') $tempIdx['nr'] = $k;
                if (strpos($val, 'produto') !== false || strpos($val, 'descri') !== false || strpos($val, 'especifica') !== false) $tempIdx['desc'] = $k;
                if (strpos($val, 'un.') !== false || strpos($val, 'unidade') !== false || strpos($val, 'medida') !== false || in_array($val, ['un', 'kg', 'l'])) $tempIdx['un'] = $k;
                if (strpos($val, 'quantidade') !== false || strpos($val, 'quant.') !== false || strpos($val, 'qtd') !== false) $tempIdx['qtd'] = $k;
            }

            if (!is_null($tempIdx['desc']) && !is_null($tempIdx['qtd'])) {
                $idx = $tempIdx;
                $headerFound = true;
                continue;
            }
        }

        $desc = $row[$idx['desc']] ?? '';
        $valDesc = mb_strtolower(trim($desc), 'UTF-8');
        
        if (empty($desc) || $valDesc === 'produto' || $valDesc === 'descrição') {
            continue;
        }

        $nr  = $row[$idx['nr']] ?? ($index + 1);
        $un  = $row[$idx['un']] ?? 'UN';
        $qtd = $row[$idx['qtd']] ?? '1';

        if (is_string($qtd)) {
            $qtd = str_replace('.', '', $qtd);
            $qtd = str_replace(',', '.', $qtd);
        }

        $itens[] = [
            'nr'   => $nr,
            'un'   => $un,
            'desc' => $desc,
            'qtd'  => $qtd
        ];
    }

    echo json_encode(['sucesso' => true, 'itens' => $itens]);

} catch (Exception $e) {
    echo json_encode(['erro' => 'Erro ao processar: ' . $e->getMessage()]);
}