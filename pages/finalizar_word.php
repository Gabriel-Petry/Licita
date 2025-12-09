<?php
ini_set('display_errors', 0); 
ini_set('memory_limit', '1024M'); 
set_time_limit(300); 
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Language;

while (ob_get_level()) {
    ob_end_clean();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['edital_html'])) {
    die("Erro: Nenhum conteúdo recebido.");
}

$raw_html = $_POST['edital_html'];

class HtmlToWordConverter {
    private $phpWord;
    private $section;
    private $dom;

    public function __construct($html) {
        $this->phpWord = new PhpWord();
        
        $this->phpWord->setDefaultFontName('Arial');
        $this->phpWord->setDefaultFontSize(11);
        $this->phpWord->getSettings()->setThemeFontLang(new Language(Language::PT_BR));
        
        $this->phpWord->setDefaultParagraphStyle([
            'alignment' => Jc::BOTH,
            'spaceAfter' => 120, 
            'lineHeight' => 1.5,
        ]);

        $this->section = $this->phpWord->addSection([
            'marginLeft'   => 1134,
            'marginRight'  => 1134,
            'marginTop'    => 1134,
            'marginBottom' => 1134,
        ]);

        $this->dom = new DOMDocument();
        $html = str_replace(["\r\n", "\r", "\n"], '', $html);
        $html_wrapper = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
        
        $libxml_previous_state = libxml_use_internal_errors(true);
        $this->dom->loadHTML($html_wrapper, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($libxml_previous_state);
    }

    public function convert() {
        $body = $this->dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $this->processChildren($body, $this->section);
        }
        return $this->phpWord;
    }

    private function processChildren($node, $container) {
        if (!$node->hasChildNodes()) return;

        foreach ($node->childNodes as $child) {
            if ($this->isHidden($child)) continue;

            $tag = strtolower($child->nodeName);

            if ($tag === 'table') {
                $this->addTable($child, $container);
            }
            elseif (in_array($tag, ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'div'])) {
                if ($tag === 'div' && $this->hasBlockChildren($child)) {
                    $this->processChildren($child, $container);
                    continue;
                }
                $this->addParagraph($child, $container);
            }
            elseif (in_array($tag, ['ul', 'ol'])) {
                $this->processChildren($child, $container);
            }
            elseif ($child instanceof DOMText) {
                $text = trim($child->textContent);
                if (!empty($text)) {
                    $p = $container->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 120]);
                    $p->addText($text);
                }
            }
            else {
                $this->processChildren($child, $container);
            }
        }
    }

    private function isHidden($node) {
        if (!($node instanceof DOMElement)) return false;
        $style = strtolower($node->getAttribute('style'));
        $class = strtolower($node->getAttribute('class'));
        $style_clean = str_replace(' ', '', $style);
        
        if (strpos($style_clean, 'display:none') !== false || strpos($class, 'hidden') !== false) {
            return true;
        }
        return false;
    }

    private function addParagraph($node, $container) {
        if (trim($node->textContent) === '' && $node->getElementsByTagName('img')->length === 0) return;

        $pStyle = ['spaceAfter' => 120, 'alignment' => Jc::BOTH];

        $class = strtolower($node->getAttribute('class'));
        $style = strtolower($node->getAttribute('style'));
        $full_text = mb_strtoupper($node->textContent, 'UTF-8');

        if (strpos($class, 'center') !== false || strpos($style, 'text-align: center') !== false) {
            $pStyle['alignment'] = Jc::CENTER;
        } elseif (strpos($class, 'right') !== false || strpos($style, 'text-align: right') !== false) {
            $pStyle['alignment'] = Jc::RIGHT;
        } else {
            if (strpos($full_text, 'LIMITE PARA RECEBIMENTO') !== false || 
                strpos($full_text, 'INÍCIO DA SESSÃO') !== false) {
                $pStyle['alignment'] = Jc::LEFT;
            } else {
                $pStyle['alignment'] = Jc::BOTH;
            }
        }

        if (strpos($class, 'subitem-3') !== false) $pStyle['indentation'] = ['left' => 567];
        elseif (strpos($class, 'subitem-4') !== false) $pStyle['indentation'] = ['left' => 850];
        elseif (strpos($class, 'subitem-5') !== false) $pStyle['indentation'] = ['left' => 1134];

        $textRun = $container->addTextRun($pStyle);

        $globalFontStyle = [];
        $tag = strtolower($node->nodeName);
        if (in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']) || strpos($class, 'bold') !== false) {
            $globalFontStyle['bold'] = true;
        }

        $this->processInlineContent($node, $textRun, $globalFontStyle);
    }

    private function processInlineContent($node, $textRun, $currentStyle = []) {
        foreach ($node->childNodes as $child) {
            if ($this->isHidden($child)) continue;

            if ($child instanceof DOMText) {
                $text = $child->textContent;
                $text = str_replace(["\n", "\r", "\t"], ' ', $text);
                $text = preg_replace('/\s+/', ' ', $text);
                if ($text !== '') {
                    $textRun->addText($text, $currentStyle);
                }
            } elseif ($child instanceof DOMElement) {
                $tag = strtolower($child->nodeName);
                $newStyle = $currentStyle;

                if ($tag === 'b' || $tag === 'strong' || strpos($child->getAttribute('class'), 'bold') !== false) {
                    $newStyle['bold'] = true;
                }
                if ($tag === 'i' || $tag === 'em') $newStyle['italic'] = true;
                if ($tag === 'u') $newStyle['underline'] = 'single';
                
                if ($tag === 'br') {
                    $textRun->addTextBreak();
                    continue;
                }

                $this->processInlineContent($child, $textRun, $newStyle);
            }
        }
    }

    private function addTable($node, $container) {
        $colWidths = [];
        $rows = $node->getElementsByTagName('tr');
        $firstRow = $rows->item(0);
        $totalDefined = 0;
        $undefinedCols = [];

        if ($firstRow) {
            $colIndex = 0;
            foreach ($firstRow->childNodes as $child) {
                if ($child->nodeName !== 'td' && $child->nodeName !== 'th') continue;
                if ($this->isHidden($child)) continue;

                $style = strtolower($child->getAttribute('style'));
                if (preg_match('/width:\s*([\d\.]+)%/', $style, $matches)) {
                    $widthVal = (float)$matches[1] * 50; 
                    $colWidths[$colIndex] = $widthVal;
                    $totalDefined += $widthVal;
                } else {
                    $colWidths[$colIndex] = null;
                    $undefinedCols[] = $colIndex;
                }
                $colIndex++;
            }
        }

        $remainingSpace = 5000 - $totalDefined;
        if ($remainingSpace > 0 && count($undefinedCols) > 0) {
            $perCol = $remainingSpace / count($undefinedCols);
            foreach ($undefinedCols as $idx) {
                $colWidths[$idx] = $perCol;
            }
        }

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'alignment' => JcTable::CENTER,
            'unit' => TblWidth::PERCENT,
            'width' => 100 * 50
        ];
        
        $wordTable = $container->addTable($tableStyle);

        foreach ($rows as $row) {
            if ($this->isHidden($row)) continue;

            $wordRow = $wordTable->addRow();
            $colIndex = 0;
            
            foreach ($row->childNodes as $cell) {
                if ($cell->nodeName !== 'td' && $cell->nodeName !== 'th') continue;
                if ($this->isHidden($cell)) continue;

                $tag = strtolower($cell->nodeName);
                $isHeader = ($tag === 'th');
                $bgColor = $isHeader ? 'EEEEEE' : null;

                $cellWidth = isset($colWidths[$colIndex]) ? $colWidths[$colIndex] : null;

                $wordCell = $wordRow->addCell($cellWidth, [
                    'bgColor' => $bgColor, 
                    'valign' => 'center',
                    'borderSize' => 6,
                    'borderColor' => '000000'
                ]);

                $align = $isHeader ? Jc::CENTER : Jc::LEFT;
                $pStyle = ['alignment' => $align, 'spaceAfter' => 0];
                
                $cellRun = $wordCell->addTextRun($pStyle);
                $fontStyle = $isHeader ? ['bold' => true] : [];
                
                $this->processInlineContent($cell, $cellRun, $fontStyle);
                
                $colIndex++;
            }
        }
        $container->addTextBreak(1);
    }

    private function hasBlockChildren($node) {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                if (in_array(strtolower($child->nodeName), ['div', 'p', 'table', 'ul', 'ol', 'h1', 'h2', 'h3'])) {
                    return true;
                }
            }
        }
        return false;
    }
}

try {
    $converter = new HtmlToWordConverter($raw_html);
    $phpWord = $converter->convert();

    $filename = "Edital_" . date('d-m_Hi') . ".docx";
    $temp_file = tempnam(sys_get_temp_dir(), 'phpword');
    
    $xmlWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $xmlWriter->save($temp_file);

    if (!file_exists($temp_file) || filesize($temp_file) == 0) {
        throw new Exception("Falha ao gravar arquivo.");
    }

    ob_clean();

    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($temp_file));

    readfile($temp_file);
    unlink($temp_file);
    exit;

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: text/plain');
    echo "Erro Fatal: " . $e->getMessage();
    exit;
}