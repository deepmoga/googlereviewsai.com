<?php
/**
 * generate-pdf.php
 * Generates a "Review us on Google" A4 flyer PDF for a client.
 * Matches the black-background poster design with QR code, Google logo,
 * 5-star rating, "Thank You" script text, and the client's logo.
 *
 * Usage: /admin/generate-pdf.php?id=<client_id>
 *
 * Requires TCPDF:  composer require tecnickcom/tcpdf
 */

require_once __DIR__ . '/../config.php';
requireLogin();

// ------------------------------------------------------------------ vendor
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die('<b>TCPDF not installed.</b><br>Run: <code>composer require tecnickcom/tcpdf</code> in the project root.');
}
require_once $autoload;

// ------------------------------------------------------------------ client
$db       = getDB();
$clientId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$clientId) {
    die('Invalid client ID.');
}

$stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch();
if (!$client) {
    die('Client not found.');
}

// ------------------------------------------------------------------ QR code
$reviewUrl  = APP_URL . '/review.php?c=' . $client['slug'];
$qrApiUrl   = 'https://quickchart.io/qr?text=' . urlencode($reviewUrl) . '&size=500&ecLevel=M&margin=1';
$qrTempFile = sys_get_temp_dir() . '/review_qr_' . $client['id'] . '_' . time() . '.png';

$ctx = stream_context_create(['http' => ['timeout' => 10]]);
$qrData = @file_get_contents($qrApiUrl, false, $ctx);
if ($qrData === false) {
    die('Could not fetch QR code. Check your internet connection.');
}
file_put_contents($qrTempFile, $qrData);

// ------------------------------------------------------------------ PDF setup
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Review System');
$pdf->SetAuthor('Review System');
$pdf->SetTitle('Review Flyer – ' . $client['company_name']);
$pdf->SetSubject('Google Review Flyer');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0, true);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

// ================================================================== DESIGN
// A4 = 210 mm wide × 297 mm tall

// ------ 1. Black background
$pdf->SetFillColor(0, 0, 0);
$pdf->Rect(0, 0, 210, 297, 'F');

// ------ 2. "Review us on..." title
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 34);
$pdf->SetXY(0, 16);
$pdf->Cell(210, 18, 'Review us on...', 0, 1, 'C');

// ------ 3. QR code with gold border
$qrSize     = 120;  // mm – size of the QR image itself
$border     = 5;    // mm – gold border thickness
$qrX        = (210 - $qrSize) / 2;   // 45 mm (centered)
$qrY        = 44;

// Gold border rectangle
$pdf->SetFillColor(212, 170, 50);     // gold
$pdf->Rect($qrX - $border, $qrY - $border, $qrSize + $border * 2, $qrSize + $border * 2, 'F');

// White inner padding (2 mm)
$pad = 2;
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect($qrX - $pad, $qrY - $pad, $qrSize + $pad * 2, $qrSize + $pad * 2, 'F');

// QR image
$pdf->Image($qrTempFile, $qrX, $qrY, $qrSize, $qrSize, 'PNG');

// ------ 4. "Google" coloured logo
$googleY     = 178;   // mm – top of Google text row
$fontSize    = 38;
$pdf->SetFont('helvetica', 'B', $fontSize);

$googleLetters = ['G', 'o', 'o', 'g', 'l', 'e'];
$googleColors  = [
    [66,  133, 244],  // G – blue
    [234, 67,  53 ],  // o – red
    [251, 188, 5  ],  // o – yellow
    [66,  133, 244],  // g – blue
    [52,  168, 83 ],  // l – green
    [234, 67,  53 ],  // e – red
];

// Calculate total width for centering
$totalGoogleW = 0;
foreach ($googleLetters as $letter) {
    $totalGoogleW += $pdf->GetStringWidth($letter);
}
$curX = (210 - $totalGoogleW) / 2;

foreach ($googleLetters as $i => $letter) {
    $pdf->SetTextColor($googleColors[$i][0], $googleColors[$i][1], $googleColors[$i][2]);
    $w = $pdf->GetStringWidth($letter);
    $pdf->SetXY($curX, $googleY);
    $pdf->Cell($w, 16, $letter, 0, 0, 'L');
    $curX += $w;
}

// ------ 5. Appreciation text
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(15, 200);
$pdf->Cell(180, 8, 'Your reviews are greatly appreciated!', 0, 1, 'C');
$pdf->SetXY(15, 209);
$pdf->Cell(180, 8, 'We hope you enjoyed your visit.', 0, 1, 'C');

// ------ 6. Five gold stars  ★★★★★
$pdf->SetTextColor(251, 188, 5);  // gold
$pdf->SetFont('dejavusans', '', 26);
$pdf->SetXY(0, 222);
$pdf->Cell(210, 14, "\xe2\x98\x85 \xe2\x98\x85 \xe2\x98\x85 \xe2\x98\x85 \xe2\x98\x85", 0, 1, 'C');
// ★ = U+2605 → UTF-8: 0xE2 0x98 0x85

// ------ 7. "Thank You" script-style text (bottom left)
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('times', 'BI', 30);
$pdf->SetXY(10, 255);
$pdf->Cell(90, 20, 'Thank You', 0, 0, 'L');

// ------ 8. Client logo (bottom right)
if ($client['logo_path'] && file_exists(UPLOAD_DIR . $client['logo_path'])) {
    $logoFile = UPLOAD_DIR . $client['logo_path'];
    $ext      = strtolower(pathinfo($logoFile, PATHINFO_EXTENSION));

    $logoX = 130;
    $logoY = 252;
    $logoW = 68;
    $logoH = 30;

    if ($ext === 'svg') {
        // TCPDF has ImageSVG for SVG files
        $pdf->ImageSVG($logoFile, $logoX, $logoY, $logoW, $logoH, '', '', 'M');
    } else {
        // PNG, JPG, GIF, WEBP – fitbox 'CM' = centre + maintain aspect ratio
        $pdf->Image($logoFile, $logoX, $logoY, $logoW, $logoH, '', '', '', false, 300, '', false, false, 0, 'CM');
    }
} else {
    // Fallback: company initials in a circle area
    $pdf->SetTextColor(212, 170, 50);
    $pdf->SetFont('helvetica', 'B', 22);
    $pdf->SetXY(130, 255);
    $pdf->Cell(68, 20, strtoupper(substr($client['company_name'], 0, 3)), 0, 0, 'C');
}

// ------------------------------------------------------------------ cleanup
@unlink($qrTempFile);

// ------------------------------------------------------------------ output
$filename = preg_replace('/[^a-z0-9_-]/i', '-', $client['slug']) . '-review-flyer.pdf';
$pdf->Output($filename, 'D');   // 'D' = force download
exit;
