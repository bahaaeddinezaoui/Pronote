<?php
// Test Arabic text in TCPDF
define('K_PATH_FONTS', __DIR__ . '/vendor/tcpdf/fonts/');
require_once __DIR__ . '/vendor/tcpdf/tcpdf.php';

$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 14);

// Test Arabic text
$pdf->Cell(0, 10, 'English: Yahia Mehdi KHERBOUCHE', 0, 1);
$pdf->Cell(0, 10, 'Arabic: يحيى مهدي خربوش', 0, 1);
$pdf->Ln(10);

// Test with RTL enabled
$pdf->setRTL(true);
$pdf->Cell(0, 10, 'RTL Arabic: يحيى مهدي خربوش', 0, 1);
$pdf->setRTL(false);
$pdf->Cell(0, 10, 'Back to LTR: English text', 0, 1);

$pdf->Output('test_arabic.pdf', 'I');
