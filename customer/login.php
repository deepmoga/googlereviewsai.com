<?php
require_once __DIR__ . '/../config.php';

if (isCustomerLoggedIn()) {
    header('Location: ' . APP_URL . '/customer/dashboard.php');
    exit;
}

$error = isset($_GET['error']) && $_GET['error'] === 'inactive' ? 'Your account is inactive. Please contact support.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = normalizeIndianPhone($login);

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM customers WHERE phone = ? OR email = ? LIMIT 1");
    $stmt->execute([$phone, $login]);
    $customer = $stmt->fetch();

    if (!$customer || !password_verify($password, $customer['password_hash'])) {
        $error = 'Invalid login details.';
    } elseif (!$customer['is_active']) {
        $error = 'Your account is inactive. Please contact support.';
    } elseif (empty($customer['phone_verified_at'])) {
        $_SESSION['pending_customer_id'] = $customer['id'];
        $otp = createCustomerOtp($customer['id'], $customer['phone'], 'login');
        $send = sendWhatsAppOtp($customer['phone'], $otp);
        if (!$send['success']) {
            $_SESSION['otp_warning'] = 'OTP could not be sent: ' . $send['message'];
        }
        header('Location: ' . APP_URL . '/customer/verify-otp.php');
        exit;
    } else {
        $_SESSION['customer_id'] = $customer['id'];
        header('Location: ' . APP_URL . '/customer/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Official AI Review</title>
  <style>
    :root{--primary:#058a36;--gold:#f0b400;--muted:#667569;--line:#dce8df;--radius:8px}
    *{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,Helvetica,sans-serif;background:linear-gradient(135deg,#073f20,#058a36);min-height:100vh;display:grid;place-items:center;padding:24px}
    .card{width:min(100%,430px);background:#fff;border-radius:var(--radius);padding:30px;box-shadow:0 24px 70px rgba(0,0,0,.22)}.brand{display:flex;gap:12px;align-items:center;margin-bottom:22px}.mark{width:44px;height:44px;border:2px solid var(--gold);border-radius:var(--radius);display:grid;place-items:center;color:var(--primary);font-weight:900}
    h1{font-size:1.55rem}.lead{color:var(--muted);margin-top:5px}label{display:block;margin:14px 0 7px;color:var(--muted);font-size:.78rem;font-weight:800;text-transform:uppercase}input{width:100%;padding:12px 13px;border:1px solid var(--line);border-radius:var(--radius);font-size:1rem}
    .btn{width:100%;border:0;border-radius:var(--radius);padding:13px;background:var(--primary);color:#fff;font-weight:800;margin-top:18px;cursor:pointer}.alert{padding:12px;border-radius:var(--radius);margin:14px 0;font-size:.9rem;background:#fee2e2;color:#991b1b}.links{text-align:center;margin-top:18px;color:var(--muted);font-size:.92rem}.links a{color:var(--primary);font-weight:800;text-decoration:none}
  </style>
</head>
<body>
  <div class="card">
    <div class="brand"><div class="mark">G★</div><div><h1>Customer login</h1><p class="lead">Manage your Google Review page.</p></div></div>
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <label>Email or WhatsApp Number</label>
      <input type="text" name="login" required value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
      <label>Password</label>
      <input type="password" name="password" required>
      <button class="btn" type="submit">Login</button>
    </form>
    <div class="links">New customer? <a href="<?= APP_URL ?>/customer/register.php">Create account</a></div>
  </div>
</body>
</html>
