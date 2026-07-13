<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$pendingId = intval($_POST['pending_registration_id'] ?? 0);
$razorpayOrderId = trim($_POST['razorpay_order_id'] ?? '');
$paymentId = trim($_POST['razorpay_payment_id'] ?? '');
$signature = trim($_POST['razorpay_signature'] ?? '');

if (!$pendingId || $razorpayOrderId === '' || $paymentId === '' || $signature === '') {
    echo json_encode(['success' => false, 'message' => 'Missing payment details.']);
    exit;
}

if (!verifyRazorpaySignature($razorpayOrderId, $paymentId, $signature)) {
    echo json_encode(['success' => false, 'message' => 'Payment signature verification failed.']);
    exit;
}

$db = getDB();
$customerId = 0;

$db->beginTransaction();
try {
    $stmt = $db->prepare("SELECT pr.*, p.duration_days, p.name AS plan_name
        FROM pending_registrations pr
        INNER JOIN plans p ON p.id = pr.plan_id
        WHERE pr.id = ? AND pr.status = 'created' AND pr.otp_verified_at IS NOT NULL
        FOR UPDATE");
    $stmt->execute([$pendingId]);
    $pending = $stmt->fetch();

    if (!$pending || $pending['razorpay_order_id'] !== $razorpayOrderId) {
        throw new RuntimeException('Invalid, unverified, or already processed registration payment.');
    }

    $dupe = $db->prepare("SELECT id FROM customers WHERE phone = ? OR (email IS NOT NULL AND email != '' AND email = ?) LIMIT 1");
    $dupe->execute([$pending['phone'], $pending['email']]);
    if ($dupe->fetch()) {
        throw new RuntimeException('An account already exists with this phone or email.');
    }

    $db->prepare("INSERT INTO customers (name, phone, email, password_hash, phone_verified_at, is_active, created_at)
        VALUES (?, ?, ?, ?, NOW(), 1, NOW())")
        ->execute([$pending['name'], $pending['phone'], $pending['email'], $pending['password_hash']]);
    $customerId = (int) $db->lastInsertId();

    $db->prepare("INSERT INTO payment_orders
        (customer_id, item_type, item_id, amount, razorpay_order_id, razorpay_payment_id, razorpay_signature, status, created_at, paid_at)
        VALUES (?, 'plan', ?, ?, ?, ?, ?, 'paid', NOW(), NOW())")
        ->execute([$customerId, $pending['plan_id'], $pending['plan_amount'], $razorpayOrderId, $paymentId, $signature]);
    $planOrderId = (int) $db->lastInsertId();

    $startsAt = date('Y-m-d H:i:s');
    $expiresAt = date('Y-m-d H:i:s', time() + intval($pending['duration_days']) * 86400);
    $db->prepare("INSERT INTO customer_subscriptions (customer_id, plan_id, order_id, starts_at, expires_at, amount, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())")
        ->execute([$customerId, $pending['plan_id'], $planOrderId, $startsAt, $expiresAt, $pending['plan_amount']]);

    $addonIds = json_decode($pending['addon_ids'] ?: '[]', true);
    if (!is_array($addonIds)) {
        $addonIds = [];
    }

    if ($addonIds) {
        $placeholders = implode(',', array_fill(0, count($addonIds), '?'));
        $addonStmt = $db->prepare("SELECT * FROM addons WHERE id IN ($placeholders) AND is_active = 1");
        $addonStmt->execute($addonIds);
        $addons = $addonStmt->fetchAll();

        foreach ($addons as $addon) {
            $db->prepare("INSERT INTO payment_orders
                (customer_id, item_type, item_id, amount, razorpay_order_id, razorpay_payment_id, razorpay_signature, status, created_at, paid_at)
                VALUES (?, 'addon', ?, ?, ?, ?, ?, 'paid', NOW(), NOW())")
                ->execute([$customerId, $addon['id'], $addon['price'], $razorpayOrderId, $paymentId, $signature]);
            $addonOrderId = (int) $db->lastInsertId();

            $db->prepare("INSERT INTO addon_purchases (customer_id, addon_id, order_id, amount, status, purchased_at)
                VALUES (?, ?, ?, ?, 'paid', NOW())")
                ->execute([$customerId, $addon['id'], $addonOrderId, $addon['price']]);
        }
    }

    $db->prepare("UPDATE pending_registrations
        SET status = 'paid', customer_id = ?, razorpay_payment_id = ?, razorpay_signature = ?, paid_at = NOW()
        WHERE id = ?")
        ->execute([$customerId, $paymentId, $signature, $pendingId]);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

$_SESSION['customer_id'] = $customerId;
unset($_SESSION['pending_customer_id']);

echo json_encode([
    'success' => true,
    'redirect' => APP_URL . '/customer/dashboard.php'
]);
