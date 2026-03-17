<?php
// Test PDF with both EN and AR values
define('K_PATH_FONTS', __DIR__ . '/vendor/tcpdf/fonts/');
require_once __DIR__ . '/vendor/tcpdf/tcpdf.php';

$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 12);

// Test row function
function testRow($pdf, $label, $value) {
    if ($value === null || $value === '' || trim($value) === '') {
        $value = 'N/A';
    }
    
    // Detect Arabic
    $hasArabic = preg_match('/[\x{0600}-\x{06FF}]/u', $value);
    
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(50, 8, $label . ':', 0, 0, 'L');
    
    $pdf->SetFont('dejavusans', '', 10);
    if ($hasArabic) {
        $pdf->setRTL(true);
    }
    $pdf->Cell(0, 8, $value, 0, 1, 'L');
    if ($hasArabic) {
        $pdf->setRTL(false);
    }
}

$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 15, 'Student Report Test', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('dejavusans', 'B', 12);
$pdf->SetFillColor(99, 102, 241);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'Personal Information', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(3);

testRow($pdf, 'Full Name (EN)', 'Yahia Mehdi KHERBOUCHE');
testRow($pdf, 'Full Name (AR)', 'يحيى مهدي خربوش');
testRow($pdf, 'Grade (EN)', 'Student');
testRow($pdf, 'Grade (AR)', 'طالب');
testRow($pdf, 'Section (EN)', 'Section A');
testRow($pdf, 'Section (AR)', 'القسم أ');

$pdf->Output('test_report.pdf', 'I');
