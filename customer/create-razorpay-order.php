<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
$customer = requireCustomerLogin();
$db = getDB();

$type = $_POST['item_type'] ?? '';
$itemId = intval($_POST['item_id'] ?? 0);

if (!in_array($type, ['plan', 'addon'], true) || !$itemId) {
    echo json_encode(['success' => false, 'message' => 'Invalid item.']);
    exit;
}

if ($type === 'plan') {
    $stmt = $db->prepare("SELECT * FROM plans WHERE id = ? AND is_active = 1");
} else {
    $stmt = $db->prepare("SELECT * FROM addons WHERE id = ? AND is_active = 1");
}
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found or inactive.']);
    exit;
}

$amount = (float) $item['price'];
if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount.']);
    exit;
}

$db->prepare("INSERT INTO payment_orders (customer_id, item_type, item_id, amount, status, created_at)
    VALUES (?, ?, ?, ?, 'created', NOW())")
    ->execute([$customer['id'], $type, $itemId, $amount]);
$localOrderId = $db->lastInsertId();
$receipt = 'oar_' . $localOrderId . '_' . time();

$order = razorpayCreateOrder((int) round($amount * 100), $receipt);
if (!$order['success']) {
    $db->prepare("UPDATE payment_orders SET status = 'failed' WHERE id = ?")->execute([$localOrderId]);
    echo json_encode(['success' => false, 'message' => $order['message']]);
    exit;
}

$rzp = $order['data'];
$db->prepare("UPDATE payment_orders SET razorpay_order_id = ? WHERE id = ?")
    ->execute([$rzp['id'], $localOrderId]);

echo json_encode([
    'success' => true,
    'local_order_id' => $localOrderId,
    'razorpay_order_id' => $rzp['id'],
    'amount' => intval($rzp['amount']),
    'description' => $item['name']
]);
