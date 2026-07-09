<?php
require_once __DIR__ . '/../config.php';

$customerId = intval($_SESSION['pending_customer_id'] ?? $_SESSION['customer_id'] ?? 0);
if (!$customerId) {
    header('Location: ' . APP_URL . '/customer/register.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();
if (!$customer) {
    header('Location: ' . APP_URL . '/customer/register.php');
    exit;
}

if (!empty($customer['phone_verified_at'])) {
    $_SESSION['customer_id'] = $customer['id'];
    unset($_SESSION['pending_customer_id']);
    header('Location: ' . APP_URL . '/customer/dashboard.php');
    exit;
}

$error = '';
$info = $_SESSION['otp_warning'] ?? '';
unset($_SESSION['otp_warning']);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['resend'])) {
    $otp = createCustomerOtp($customer['id'], $customer['phone'], 'resend');
    $send = sendWhatsAppOtp($customer['phone'], $otp);
    $info = $send['success'] ? 'A new OTP has been sent on WhatsApp.' : 'Could not send OTP: ' . $send['message'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    if (!preg_match('/^\d{6}$/', $otp)) {
        $error = 'Please enter the 6 digit OTP.';
    } elseif (verifyCustomerOtp($customer['id'], $otp)) {
        $_SESSION['customer_id'] = $customer['id'];
        unset($_SESSION['pending_customer_id']);
        header('Location: ' . APP_URL . '/customer/dashboard.php');
        exit;
    } else {
        $error = 'Invalid or expired OTP.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify OTP - Official AI Review</title>
  <style>
    :root{--primary:#058a36;--gold:#f0b400;--muted:#667569;--line:#dce8df;--radius:8px}
    *{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,Helvetica,sans-serif;background:linear-gradient(135deg,#073f20,#058a36);min-height:100vh;display:grid;place-items:center;padding:24px}
    .card{width:min(100%,430px);background:#fff;border-radius:var(--radius);padding:30px;box-shadow:0 24px 70px rgba(0,0,0,.22)}h1{font-size:1.55rem}.lead{color:var(--muted);margin:7px 0 18px;line-height:1.5}
    label{display:block;margin-bottom:7px;color:var(--muted);font-size:.78rem;font-weight:800;text-transform:uppercase}input{width:100%;padding:14px;border:1px solid var(--line);border-radius:var(--radius);font-size:1.4rem;text-align:center;letter-spacing:.2em}
    .btn{width:100%;border:0;border-radius:var(--radius);padding:13px;background:var(--primary);color:#fff;font-weight:800;margin-top:16px;cursor:pointer}.links{text-align:center;margin-top:16px;font-size:.92rem}.links a{color:var(--primary);font-weight:800;text-decoration:none}
    .alert{padding:12px;border-radius:var(--radius);margin:14px 0;font-size:.9rem}.alert-error{background:#fee2e2;color:#991b1b}.alert-info{background:#eff6ff;color:#1e40af}
  </style>
</head>
<body>
  <div class="card">
    <h1>Verify WhatsApp OTP</h1>
    <p class="lead">We sent a 6 digit OTP to +<?= htmlspecialchars($customer['phone']) ?> using WhatsApp.</p>
    <?php if ($info): ?><div class="alert alert-info"><?= htmlspecialchars($info) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" action="<?= APP_URL ?>/customer/verify-otp.php">
      <label>OTP Code</label>
      <input type="text" name="otp" inputmode="numeric" maxlength="6" required>
      <button class="btn" type="submit">Verify & Continue</button>
    </form>
    <div class="links"><a href="?resend=1">Resend OTP</a> · <a href="<?= APP_URL ?>/customer/login.php">Login</a></div>
  </div>
</body>
</html>
