<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$pendingId = intval($_POST['pending_registration_id'] ?? 0);
$otp = trim($_POST['otp'] ?? '');

if (!$pendingId || !preg_match('/^\d{6}$/', $otp)) {
    echo json_encode(['success' => false, 'message' => 'Please enter the 6 digit OTP.']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();
    $stmt = $db->prepare("SELECT pr.*, p.name AS plan_name, p.duration_days
        FROM pending_registrations pr
        INNER JOIN plans p ON p.id = pr.plan_id
        WHERE pr.id = ? AND pr.status = 'created'
        FOR UPDATE");
    $stmt->execute([$pendingId]);
    $pending = $stmt->fetch();

    if (!$pending) {
        throw new RuntimeException('Registration session expired. Please start again.');
    }
    $isAlreadyVerified = !empty($pending['otp_verified_at']);
    if (!$isAlreadyVerified) {
        if (empty($pending['otp_hash']) || empty($pending['otp_expires_at']) || strtotime($pending['otp_expires_at']) < time()) {
            throw new RuntimeException('OTP expired. Please start registration again.');
        }
        if ((int) $pending['otp_attempts'] >= 5) {
            throw new RuntimeException('Too many wrong OTP attempts. Please start registration again.');
        }

        $db->prepare("UPDATE pending_registrations SET otp_attempts = otp_attempts + 1 WHERE id = ?")->execute([$pendingId]);
        if (!password_verify($otp, $pending['otp_hash'])) {
            $db->commit();
            echo json_encode(['success' => false, 'message' => 'Invalid OTP.']);
            exit;
        }
    }

    $dupe = $db->prepare("SELECT id FROM customers WHERE phone = ? OR (email IS NOT NULL AND email != '' AND email = ?) LIMIT 1");
    $dupe->execute([$pending['phone'], $pending['email']]);
    if ($dupe->fetch()) {
        throw new RuntimeException('An account already exists with this phone or email.');
    }

    if ((float) $pending['total_amount'] <= 0) {
        if ((float) $pending['addon_amount'] > 0) {
            throw new RuntimeException('Invalid trial registration.');
        }

        $db->prepare("INSERT INTO customers (name, phone, email, password_hash, phone_verified_at, is_active, created_at)
            VALUES (?, ?, ?, ?, NOW(), 1, NOW())")
            ->execute([$pending['name'], $pending['phone'], $pending['email'], $pending['password_hash']]);
        $customerId = (int) $db->lastInsertId();

        $startsAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + intval($pending['duration_days']) * 86400);
        $db->prepare("INSERT INTO customer_subscriptions (customer_id, plan_id, order_id, starts_at, expires_at, amount, status, created_at)
            VALUES (?, ?, NULL, ?, ?, 0, 'active', NOW())")
            ->execute([$customerId, $pending['plan_id'], $startsAt, $expiresAt]);

        $db->prepare("UPDATE pending_registrations
            SET status = 'paid', customer_id = ?, otp_verified_at = COALESCE(otp_verified_at, NOW()), paid_at = NOW()
            WHERE id = ?")
            ->execute([$customerId, $pendingId]);

        $_SESSION['customer_id'] = $customerId;
        unset($_SESSION['pending_customer_id']);
        $db->commit();

        echo json_encode([
            'success' => true,
            'requires_payment' => false,
            'redirect' => APP_URL . '/customer/dashboard.php'
        ]);
        exit;
    }

    $receipt = 'reg_' . $pendingId . '_' . time();
    $order = razorpayCreateOrder((int) round((float) $pending['total_amount'] * 100), $receipt);
    if (!$order['success']) {
        throw new RuntimeException($order['message']);
    }

    $rzp = $order['data'];
    if ($isAlreadyVerified) {
        $db->prepare("UPDATE pending_registrations SET razorpay_order_id = ? WHERE id = ?")
            ->execute([$rzp['id'], $pendingId]);
    } else {
        $db->prepare("UPDATE pending_registrations SET otp_verified_at = NOW(), razorpay_order_id = ? WHERE id = ?")
            ->execute([$rzp['id'], $pendingId]);
    }
    $db->commit();

    echo json_encode([
        'success' => true,
        'pending_registration_id' => $pendingId,
        'razorpay_order_id' => $rzp['id'],
        'amount' => (int) $rzp['amount'],
        'description' => 'AI Google Reviews - ' . $pending['plan_name']
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
