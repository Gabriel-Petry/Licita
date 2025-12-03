<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\IOFactory;

if (ob_get_level()) ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['edital_html'])) {
    die("Erro: Conteúdo vazio.");
}

$html = $_POST['edital_html'];
$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

$html = strip_tags($html, '<p><table><thead><tbody><tr><th><td><b><strong><i><u><br><span><h1><h2><h3><ul><ol><li>');

$phpWord = new PhpWord();
$section = $phpWord->addSection();

try {
    Html::addHtml($section, $html, false, false);
} catch (Exception $e) {
    $section->addText("Erro visual. Texto recuperado:");
    $section->addText(strip_tags($_POST['edital_html']));
}

$filename = "Edital_" . date('d-m_Hi') . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$xmlWriter = IOFactory::createWriter($phpWord, 'Word2007');
$xmlWriter->save("php://output");
exit;