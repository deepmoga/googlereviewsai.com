<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$db = getDB();
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = normalizeIndianPhone($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$planId = intval($_POST['plan_id'] ?? 0);
$addonIds = array_values(array_unique(array_filter(array_map('intval', $_POST['addons'] ?? []))));

if ($name === '' || strlen($name) < 2) {
    echo json_encode(['success' => false, 'message' => 'Please enter your full name.']);
    exit;
}
if (!isValidIndianPhone($phone)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid Indian WhatsApp number.']);
    exit;
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
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
$addonAmount = array_reduce($addons, function ($sum, $addon) {
    return $sum + (float) $addon['price'];
}, 0.0);
$totalAmount = $planAmount + $addonAmount;

if ($totalAmount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment amount.']);
    exit;
}

$db->prepare("INSERT INTO pending_registrations
    (name, phone, email, password_hash, plan_id, addon_ids, plan_amount, addon_amount, total_amount, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'created', NOW())")
    ->execute([
        $name,
        $phone,
        $email !== '' ? $email : null,
        password_hash($password, PASSWORD_DEFAULT),
        $planId,
        json_encode($addonIds),
        $planAmount,
        $addonAmount,
        $totalAmount
    ]);

$pendingId = $db->lastInsertId();
$receipt = 'reg_' . $pendingId . '_' . time();
$order = razorpayCreateOrder((int) round($totalAmount * 100), $receipt);

if (!$order['success']) {
    $db->prepare("UPDATE pending_registrations SET status = 'failed' WHERE id = ?")->execute([$pendingId]);
    echo json_encode(['success' => false, 'message' => $order['message']]);
    exit;
}

$rzp = $order['data'];
$db->prepare("UPDATE pending_registrations SET razorpay_order_id = ? WHERE id = ?")
    ->execute([$rzp['id'], $pendingId]);

echo json_encode([
    'success' => true,
    'pending_registration_id' => (int) $pendingId,
    'razorpay_order_id' => $rzp['id'],
    'amount' => (int) $rzp['amount'],
    'description' => 'Official AI Review - ' . $plan['name']
]);
