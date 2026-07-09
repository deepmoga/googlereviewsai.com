<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
$customer = requireCustomerLogin();
$db = getDB();

$localOrderId = intval($_POST['local_order_id'] ?? 0);
$razorpayOrderId = trim($_POST['razorpay_order_id'] ?? '');
$paymentId = trim($_POST['razorpay_payment_id'] ?? '');
$signature = trim($_POST['razorpay_signature'] ?? '');

if (!$localOrderId || $razorpayOrderId === '' || $paymentId === '' || $signature === '') {
    echo json_encode(['success' => false, 'message' => 'Missing payment details.']);
    exit;
}

$stmt = $db->prepare("SELECT * FROM payment_orders WHERE id = ? AND customer_id = ? AND status = 'created'");
$stmt->execute([$localOrderId, $customer['id']]);
$order = $stmt->fetch();

if (!$order || $order['razorpay_order_id'] !== $razorpayOrderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order.']);
    exit;
}

if (!verifyRazorpaySignature($razorpayOrderId, $paymentId, $signature)) {
    $db->prepare("UPDATE payment_orders SET status = 'failed', razorpay_payment_id = ?, razorpay_signature = ? WHERE id = ?")
        ->execute([$paymentId, $signature, $localOrderId]);
    echo json_encode(['success' => false, 'message' => 'Payment signature verification failed.']);
    exit;
}

$db->beginTransaction();
try {
    $db->prepare("UPDATE payment_orders SET status = 'paid', razorpay_payment_id = ?, razorpay_signature = ?, paid_at = NOW() WHERE id = ?")
        ->execute([$paymentId, $signature, $localOrderId]);

    if ($order['item_type'] === 'plan') {
        $planStmt = $db->prepare("SELECT * FROM plans WHERE id = ?");
        $planStmt->execute([$order['item_id']]);
        $plan = $planStmt->fetch();
        if (!$plan) {
            throw new RuntimeException('Plan not found.');
        }

        $active = activeCustomerSubscription($customer['id']);
        $startTime = time();
        if ($active && strtotime($active['expires_at']) > $startTime) {
            $startTime = strtotime($active['expires_at']);
        }
        $startsAt = date('Y-m-d H:i:s', $startTime);
        $expiresAt = date('Y-m-d H:i:s', $startTime + intval($plan['duration_days']) * 86400);

        $db->prepare("UPDATE customer_subscriptions SET status = 'expired' WHERE customer_id = ? AND status = 'active'")
            ->execute([$customer['id']]);
        $db->prepare("INSERT INTO customer_subscriptions (customer_id, plan_id, order_id, starts_at, expires_at, amount, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())")
            ->execute([$customer['id'], $plan['id'], $localOrderId, $startsAt, $expiresAt, $order['amount']]);

        $db->prepare("UPDATE clients SET link_expire_at = ? WHERE customer_id = ?")
            ->execute([$expiresAt, $customer['id']]);
    } else {
        $db->prepare("INSERT INTO addon_purchases (customer_id, addon_id, order_id, amount, status, purchased_at)
            VALUES (?, ?, ?, ?, 'paid', NOW())")
            ->execute([$customer['id'], $order['item_id'], $localOrderId, $order['amount']]);
    }

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
