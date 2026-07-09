<?php
// ============================================
// config.php - Database & App Configuration
// ============================================

$httpHost = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
$hostOnly = preg_replace('/:\d+$/', '', $httpHost);
$isLocal = in_array($hostOnly, ['localhost', '127.0.0.1', '::1'], true);

$appConfig = [
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_NAME' => $isLocal ? 'review_system' : 'aigooglereviews',
    'APP_URL' => $isLocal
        ? 'http://' . ($httpHost ?: 'localhost') . '/github/review-system'
        : 'https://aigooglereviews.in',
];

$overrideFile = __DIR__ . '/config.env.php';
if (is_file($overrideFile)) {
    $overrides = require $overrideFile;
    if (is_array($overrides)) {
        $appConfig = array_merge($appConfig, $overrides);
    }
}

define('DB_HOST', $appConfig['DB_HOST']);
define('DB_USER', $appConfig['DB_USER']);
define('DB_PASS', $appConfig['DB_PASS']);
define('DB_NAME', $appConfig['DB_NAME']);

define('APP_URL', rtrim($appConfig['APP_URL'], '/'));
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB Connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function getSetting($key) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : null;
}

function setSetting($key, $value) {
    $db = getDB();
    $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
        ->execute([$key, $value]);
}

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/admin/index.php');
        exit;
    }
}

function generateSlug($name) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    return rtrim($slug, '-');
}

function generateUniqueClientSlug($name, $ignoreClientId = 0) {
    $db = getDB();
    $baseSlug = generateSlug($name);
    if ($baseSlug === '') {
        $baseSlug = 'client';
    }

    $slug = $baseSlug;
    $i = 1;
    while (true) {
        $stmt = $db->prepare("SELECT id FROM clients WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, intval($ignoreClientId)]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $baseSlug . '-' . $i++;
    }
}

function normalizeIndianPhone($phone) {
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) === 10) {
        $digits = '91' . $digits;
    }
    if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
        return $digits;
    }
    return $digits;
}

function isValidIndianPhone($phone) {
    return (bool) preg_match('/^91[6-9]\d{9}$/', normalizeIndianPhone($phone));
}

function isCustomerLoggedIn() {
    return isset($_SESSION['customer_id']) && intval($_SESSION['customer_id']) > 0;
}

function currentCustomer() {
    if (!isCustomerLoggedIn()) {
        return null;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([intval($_SESSION['customer_id'])]);
    return $stmt->fetch() ?: null;
}

function requireCustomerLogin() {
    if (!isCustomerLoggedIn()) {
        header('Location: ' . APP_URL . '/customer/login.php');
        exit;
    }

    $customer = currentCustomer();
    if (!$customer || !$customer['is_active']) {
        unset($_SESSION['customer_id']);
        header('Location: ' . APP_URL . '/customer/login.php?error=inactive');
        exit;
    }

    if (empty($customer['phone_verified_at'])) {
        $_SESSION['pending_customer_id'] = $customer['id'];
        header('Location: ' . APP_URL . '/customer/verify-otp.php');
        exit;
    }

    return $customer;
}

function customerClient($customerId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM clients WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([intval($customerId)]);
    return $stmt->fetch() ?: null;
}

function activeCustomerSubscription($customerId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, p.name AS plan_name, p.duration_days
        FROM customer_subscriptions s
        LEFT JOIN plans p ON p.id = s.plan_id
        WHERE s.customer_id = ? AND s.status = 'active' AND s.expires_at >= NOW()
        ORDER BY s.expires_at DESC LIMIT 1");
    $stmt->execute([intval($customerId)]);
    return $stmt->fetch() ?: null;
}

function customerPlanExpiresAt($customerId) {
    $subscription = activeCustomerSubscription($customerId);
    return $subscription ? $subscription['expires_at'] : null;
}

function createCustomerOtp($customerId, $phone, $purpose = 'register') {
    $db = getDB();
    $otp = (string) random_int(100000, 999999);
    $hash = password_hash($otp, PASSWORD_DEFAULT);

    $db->prepare("INSERT INTO customer_otps (customer_id, phone, otp_hash, purpose, expires_at, created_at)
        VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())")
        ->execute([intval($customerId), normalizeIndianPhone($phone), $hash, $purpose]);

    return $otp;
}

function verifyCustomerOtp($customerId, $otp) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM customer_otps
        WHERE customer_id = ? AND verified_at IS NULL AND expires_at >= NOW()
        ORDER BY id DESC LIMIT 1");
    $stmt->execute([intval($customerId)]);
    $row = $stmt->fetch();

    if (!$row || $row['attempts'] >= 5) {
        return false;
    }

    $db->prepare("UPDATE customer_otps SET attempts = attempts + 1 WHERE id = ?")->execute([$row['id']]);

    if (!password_verify(trim($otp), $row['otp_hash'])) {
        return false;
    }

    $db->prepare("UPDATE customer_otps SET verified_at = NOW() WHERE id = ?")->execute([$row['id']]);
    $db->prepare("UPDATE customers SET phone_verified_at = NOW() WHERE id = ?")->execute([intval($customerId)]);
    return true;
}

function sendWhatsAppOtp($phone, $otp) {
    $baseUrl = rtrim(getSetting('whatsapp_api_base_url') ?: 'https://site10.officialdigitalmarketing.in/api', '/');
    $token = trim(getSetting('whatsapp_api_token') ?: '');
    $template = trim(getSetting('whatsapp_otp_template') ?: 'test_demo');
    $language = trim(getSetting('whatsapp_otp_language') ?: 'en_US');

    if ($token === '') {
        return ['success' => false, 'message' => 'WhatsApp API token is not configured.'];
    }

    $authToken = $token;
    $tokenResponse = whatsappApiRequest($baseUrl . '/token', 'GET', null, $token);
    if ($tokenResponse['success'] && !empty($tokenResponse['data']['token'])) {
        $authToken = $tokenResponse['data']['token'];
    }

    $payload = [
        'to' => normalizeIndianPhone($phone),
        'template_name' => $template,
        'language' => $language,
        'variables' => [$otp,$otp]
    ];

    return whatsappApiRequest($baseUrl . '/send-template', 'POST', $payload, $authToken);
}

function whatsappApiRequest($url, $method, $payload, $token) {
    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'cURL is not available on this server.'];
    }

    $ch = curl_init($url);
    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ];
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return ['success' => false, 'message' => $error ?: 'WhatsApp API request failed.'];
    }

    $data = json_decode($body, true);
    if ($httpCode < 200 || $httpCode >= 300) {
        return ['success' => false, 'message' => $data['message'] ?? $data['error'] ?? 'WhatsApp API error.', 'data' => $data];
    }

    return ['success' => true, 'message' => 'Sent', 'data' => is_array($data) ? $data : ['raw' => $body]];
}

function parseGooglePlaceId($value) {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (preg_match('/[?&]placeid=([^&]+)/i', $value, $m)) {
        return urldecode($m[1]);
    }
    if (preg_match('/place_id[:=]([A-Za-z0-9_-]+)/i', $value, $m)) {
        return $m[1];
    }
    if (preg_match('/^ChI[A-Za-z0-9_-]{10,}$/', $value)) {
        return $value;
    }
    return '';
}

function googleReviewLinkFromPlaceId($placeId) {
    return 'https://search.google.com/local/writereview?placeid=' . rawurlencode($placeId);
}

function razorpayCreateOrder($amountPaise, $receipt) {
    $keyId = trim(getSetting('razorpay_key_id') ?: '');
    $keySecret = trim(getSetting('razorpay_key_secret') ?: '');
    if ($keyId === '' || $keySecret === '') {
        return ['success' => false, 'message' => 'Razorpay keys are not configured.'];
    }

    $payload = [
        'amount' => intval($amountPaise),
        'currency' => 'INR',
        'receipt' => $receipt,
        'payment_capture' => 1
    ];

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_USERPWD => $keyId . ':' . $keySecret,
        CURLOPT_TIMEOUT => 20
    ]);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($body, true);
    if ($body === false || $httpCode < 200 || $httpCode >= 300) {
        return ['success' => false, 'message' => $data['error']['description'] ?? $error ?? 'Razorpay order creation failed.', 'data' => $data];
    }

    return ['success' => true, 'data' => $data];
}

function verifyRazorpaySignature($orderId, $paymentId, $signature) {
    $secret = trim(getSetting('razorpay_key_secret') ?: '');
    if ($secret === '') {
        return false;
    }
    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);
    return hash_equals($expected, $signature);
}
?>
