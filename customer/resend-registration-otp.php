<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!registrationOtpEnabled()) {
    echo json_encode(['success' => false, 'message' => 'OTP verification is disabled.']);
    exit;
}

$pendingId = intval($_POST['pending_registration_id'] ?? 0);
if (!$pendingId) {
    echo json_encode(['success' => false, 'message' => 'Registration session not found.']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT id, phone, status, otp_verified_at FROM pending_registrations WHERE id = ? LIMIT 1");
$stmt->execute([$pendingId]);
$pending = $stmt->fetch();

if (!$pending || $pending['status'] !== 'created') {
    echo json_encode(['success' => false, 'message' => 'Registration session expired. Please start again.']);
    exit;
}
if (!empty($pending['otp_verified_at'])) {
    echo json_encode(['success' => false, 'message' => 'OTP is already verified.']);
    exit;
}

$otp = (string) random_int(100000, 999999);
$hash = password_hash($otp, PASSWORD_DEFAULT);
$db->prepare("UPDATE pending_registrations
    SET otp_hash = ?, otp_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE), otp_attempts = 0
    WHERE id = ?")
    ->execute([$hash, $pendingId]);

$send = sendWhatsAppOtp($pending['phone'], $otp);
if (!$send['success']) {
    echo json_encode(['success' => false, 'message' => 'Could not resend OTP: ' . $send['message']]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'A new OTP has been sent on WhatsApp.']);
