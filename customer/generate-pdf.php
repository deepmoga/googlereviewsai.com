<?php
require_once __DIR__ . '/../config.php';

$customer = requireCustomerLogin();
$subscription = activeCustomerSubscription($customer['id']);
if (!$subscription) {
    die('Buy a plan to generate your review PDF.');
}

$client = customerClient($customer['id']);
if (!$client) {
    die('Create your business profile first.');
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die('TCPDF is not installed.');
}
require_once $autoload;

$reviewUrl = APP_URL . '/review.php?c=' . $client['slug'];
$qrApiUrl = 'https://quickchart.io/qr?text=' . urlencode($reviewUrl) . '&size=500&ecLevel=M&margin=1';
$qrTempFile = sys_get_temp_dir() . '/review_qr_customer_' . $client['id'] . '_' . time() . '.png';

$ctx = stream_context_create(['http' => ['timeout' => 10]]);
$qrData = @file_get_contents($qrApiUrl, false, $ctx);
if ($qrData === false) {
    die('Could not fetch QR code. Check your internet connection.');
}
file_put_contents($qrTempFile, $qrData);

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('AI Google Reviews');
$pdf->SetAuthor('AI Google Reviews');
$pdf->SetTitle('Review Flyer - ' . $client['company_name']);
$pdf->SetSubject('Google Review Flyer');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0, true);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

$pdf->SetFillColor(0, 0, 0);
$pdf->Rect(0, 0, 210, 297, 'F');

$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 34);
$pdf->SetXY(0, 16);
$pdf->Cell(210, 18, 'Review us on...', 0, 1, 'C');

$qrSize = 120;
$border = 5;
$qrX = (210 - $qrSize) / 2;
$qrY = 44;

$pdf->SetFillColor(212, 170, 50);
$pdf->Rect($qrX - $border, $qrY - $border, $qrSize + $border * 2, $qrSize + $border * 2, 'F');
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect($qrX - 2, $qrY - 2, $qrSize + 4, $qrSize + 4, 'F');
$pdf->Image($qrTempFile, $qrX, $qrY, $qrSize, $qrSize, 'PNG');

$pdf->SetFont('helvetica', 'B', 38);
$letters = ['G', 'o', 'o', 'g', 'l', 'e'];
$colors = [[66,133,244], [234,67,53], [251,188,5], [66,133,244], [52,168,83], [234,67,53]];
$totalW = 0;
foreach ($letters as $letter) {
    $totalW += $pdf->GetStringWidth($letter);
}
$x = (210 - $totalW) / 2;
foreach ($letters as $i => $letter) {
    $pdf->SetTextColor($colors[$i][0], $colors[$i][1], $colors[$i][2]);
    $w = $pdf->GetStringWidth($letter);
    $pdf->SetXY($x, 178);
    $pdf->Cell($w, 16, $letter, 0, 0, 'L');
    $x += $w;
}

$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY(15, 198);
$pdf->Cell(180, 9, 'Your reviews are greatly appreciated!', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 15);
$pdf->SetXY(15, 208);
$pdf->Cell(180, 9, 'We hope you enjoyed your visit.', 0, 1, 'C');

$pdf->SetTextColor(251, 188, 5);
$pdf->SetFont('dejavusans', '', 26);
$pdf->SetXY(0, 222);
$pdf->Cell(210, 14, "\xe2\x98\x85 \xe2\x98\x85 \xe2\x98\x85 \xe2\x98\x85 \xe2\x98\x85", 0, 1, 'C');

$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('times', 'BI', 30);
$pdf->SetXY(10, 255);
$pdf->Cell(90, 20, 'Thank You', 0, 0, 'L');

if ($client['logo_path'] && file_exists(UPLOAD_DIR . $client['logo_path'])) {
    $logoFile = UPLOAD_DIR . $client['logo_path'];
    $ext = strtolower(pathinfo($logoFile, PATHINFO_EXTENSION));
    if ($ext === 'svg') {
        $pdf->ImageSVG($logoFile, 130, 252, 68, 30, '', '', 'M');
    } else {
        $pdf->Image($logoFile, 130, 252, 68, 30, '', '', '', false, 300, '', false, false, 0, 'CM');
    }
} else {
    $pdf->SetTextColor(212, 170, 50);
    $pdf->SetFont('helvetica', 'B', 22);
    $pdf->SetXY(130, 255);
    $pdf->Cell(68, 20, strtoupper(substr($client['company_name'], 0, 3)), 0, 0, 'C');
}

@unlink($qrTempFile);
$filename = preg_replace('/[^a-z0-9_-]/i', '-', $client['slug']) . '-review-flyer.pdf';
$pdf->Output($filename, 'D');
exit;
