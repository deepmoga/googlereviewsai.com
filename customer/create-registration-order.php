<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$db = getDB();
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phoneDigits = indianMobile10Digits($_POST['phone'] ?? '');
$phone = normalizeIndianPhone($phoneDigits);
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$planId = intval($_POST['plan_id'] ?? 0);
$addonIds = array_values(array_unique(array_filter(array_map('intval', $_POST['addons'] ?? []))));
$otpEnabled = registrationOtpEnabled();

if ($name === '' || strlen($name) < 2 || strlen($name) > 150) {
    echo json_encode(['success' => false, 'message' => 'Please enter your full name.']);
    exit;
}
if (!isValidIndianMobile10($phoneDigits)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 10 digit Indian WhatsApp number.']);
    exit;
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}
if (strlen($email) > 190) {
    echo json_encode(['success' => false, 'message' => 'Email address is too long.']);
    exit;
}
if (strlen($password) < 8 || strlen($password) > 72) {
    echo json_encode(['success' => false, 'message' => 'Password must be 8 to 72 characters.']);
    exit;
}
if ($password !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'Password confirmation does not match.']);
    exit;
}
if (!$planId) {
    echo json_encode(['success' => false, 'message' => 'Please choose a pricing plan.']);
    exit;
}

$stmt = $db->prepare("SELECT id FROM customers WHERE phone = ? OR (email IS NOT NULL AND email != '' AND email = ?) LIMIT 1");
$stmt->execute([$phone, $email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'An account already exists with this phone or email.']);
    exit;
}

$planStmt = $db->prepare("SELECT * FROM plans WHERE id = ? AND is_active = 1");
$planStmt->execute([$planId]);
$plan = $planStmt->fetch();
if (!$plan) {
    echo json_encode(['success' => false, 'message' => 'Selected plan is not available.']);
    exit;
}

$addons = [];
if ($addonIds) {
    $placeholders = implode(',', array_fill(0, count($addonIds), '?'));
    $addonStmt = $db->prepare("SELECT * FROM addons WHERE id IN ($placeholders) AND is_active = 1");
    $addonStmt->execute($addonIds);
    $addons = $addonStmt->fetchAll();
    if (count($addons) !== count($addonIds)) {
        echo json_encode(['success' => false, 'message' => 'One selected addon is not available.']);
        exit;
    }
}

$planAmount = (float) $plan['price'];
if ($planAmount <= 0 && $addonIds) {
    echo json_encode(['success' => false, 'message' => 'Addons can be purchased after your free trial is active.']);
    exit;
}

$addonAmount = array_reduce($addons, function ($sum, $addon) {
    return $sum + (float) $addon['price'];
}, 0.0);
$totalAmount = $planAmount + $addonAmount;

if ($totalAmount < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment amount.']);
    exit;
}

$otp = (string) random_int(100000, 999999);
$otpHash = password_hash($otp, PASSWORD_DEFAULT);

try {
    $db->beginTransaction();
    $db->prepare("UPDATE pending_registrations SET status = 'failed'
        WHERE status = 'created' AND (phone = ? OR (? <> '' AND email = ?))")
        ->execute([$phone, $email, $email]);

    $db->prepare("INSERT INTO pending_registrations
        (name, phone, email, password_hash, plan_id, addon_ids, plan_amount, addon_amount, total_amount, otp_hash, otp_expires_at, otp_attempts, otp_verified_at, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0, ?, 'created', NOW())")
        ->execute([
            $name,
            $phone,
            $email !== '' ? $email : null,
            password_hash($password, PASSWORD_DEFAULT),
            $planId,
            json_encode($addonIds),
            $planAmount,
            $addonAmount,
            $totalAmount,
            $otpEnabled ? $otpHash : null,
            $otpEnabled ? null : date('Y-m-d H:i:s')
        ]);
    $pendingId = (int) $db->lastInsertId();

    if (!$otpEnabled && $totalAmount <= 0) {
        $db->prepare("INSERT INTO customers (name, phone, email, password_hash, phone_verified_at, is_active, created_at)
            VALUES (?, ?, ?, ?, NOW(), 1, NOW())")
            ->execute([$name, $phone, $email !== '' ? $email : null, password_hash($password, PASSWORD_DEFAULT)]);
        $customerId = (int) $db->lastInsertId();

        $startsAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + intval($plan['duration_days']) * 86400);
        $db->prepare("INSERT INTO customer_subscriptions (customer_id, plan_id, order_id, starts_at, expires_at, amount, status, created_at)
            VALUES (?, ?, NULL, ?, ?, 0, 'active', NOW())")
            ->execute([$customerId, $plan['id'], $startsAt, $expiresAt]);
        $db->prepare("UPDATE pending_registrations SET status = 'paid', customer_id = ?, paid_at = NOW() WHERE id = ?")
            ->execute([$customerId, $pendingId]);
        $_SESSION['customer_id'] = $customerId;
        $db->commit();

        echo json_encode([
            'success' => true,
            'requires_otp' => false,
            'requires_payment' => false,
            'redirect' => APP_URL . '/customer/dashboard.php',
            'message' => 'Free trial account created.'
        ]);
        exit;
    }

    $paymentOrder = null;
    if (!$otpEnabled && $totalAmount > 0) {
        $receipt = 'reg_' . $pendingId . '_' . time();
        $paymentOrder = razorpayCreateOrder((int) round($totalAmount * 100), $receipt);
        if (!$paymentOrder['success']) {
            throw new RuntimeException($paymentOrder['message']);
        }
        $db->prepare("UPDATE pending_registrations SET razorpay_order_id = ? WHERE id = ?")
            ->execute([$paymentOrder['data']['id'], $pendingId]);
    }

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage() ?: 'Could not start registration. Please try again.']);
    exit;
}

if (!$otpEnabled && $totalAmount > 0) {
    echo json_encode([
        'success' => true,
        'pending_registration_id' => $pendingId,
        'requires_otp' => false,
        'requires_payment' => true,
        'razorpay_order_id' => $paymentOrder['data']['id'],
        'amount' => (int) $paymentOrder['data']['amount'],
        'description' => 'AI Google Reviews - ' . $plan['name'],
        'message' => 'Continue to payment.'
    ]);
    exit;
}

$send = sendWhatsAppOtp($phone, $otp);
if (!$send['success']) {
    $db->prepare("UPDATE pending_registrations SET status = 'failed' WHERE id = ?")->execute([$pendingId]);
    echo json_encode(['success' => false, 'message' => 'Could not send WhatsApp OTP: ' . $send['message']]);
    exit;
}

echo json_encode([
    'success' => true,
    'pending_registration_id' => $pendingId,
    'phone' => $phone,
    'requires_otp' => true,
    'requires_payment' => $totalAmount > 0,
    'message' => $totalAmount > 0
        ? 'OTP sent to WhatsApp. Verify OTP to continue to payment.'
        : 'OTP sent to WhatsApp. Verify OTP to activate your free trial.'
]);
