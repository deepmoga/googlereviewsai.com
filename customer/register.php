<?php
require_once __DIR__ . '/../config.php';

if (isCustomerLoggedIn()) {
    header('Location: ' . APP_URL . '/customer/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = normalizeIndianPhone($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || strlen($name) < 2) {
        $error = 'Please enter your full name.';
    } elseif (!isValidIndianPhone($phone)) {
        $error = 'Please enter a valid Indian WhatsApp number.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Password confirmation does not match.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM customers WHERE phone = ? OR (email IS NOT NULL AND email != '' AND email = ?)");
        $stmt->execute([$phone, $email]);

        if ($stmt->fetch()) {
            $error = 'An account already exists with this phone or email.';
        } else {
            $db->prepare("INSERT INTO customers (name, phone, email, password_hash, is_active, created_at)
                VALUES (?, ?, ?, ?, 1, NOW())")
                ->execute([$name, $phone, $email !== '' ? $email : null, password_hash($password, PASSWORD_DEFAULT)]);

            $customerId = $db->lastInsertId();
            $otp = createCustomerOtp($customerId, $phone, 'register');
            $send = sendWhatsAppOtp($phone, $otp);

            $_SESSION['pending_customer_id'] = $customerId;
            if (!$send['success']) {
                $_SESSION['otp_warning'] = 'Account created, but WhatsApp OTP could not be sent: ' . $send['message'];
            }

            header('Location: ' . APP_URL . '/customer/verify-otp.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Official AI Review</title>
  <style>
    :root{--primary:#058a36;--gold:#f0b400;--text:#102016;--muted:#667569;--line:#dce8df;--bg:#f6fbf7;--radius:8px}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,Helvetica,sans-serif;background:linear-gradient(135deg,#073f20,#058a36);min-height:100vh;display:grid;place-items:center;padding:24px;color:var(--text)}
    .card{width:min(100%,480px);background:#fff;border-radius:var(--radius);padding:30px;box-shadow:0 24px 70px rgba(0,0,0,.22)}
    .brand{display:flex;align-items:center;gap:12px;margin-bottom:22px}
    .mark{width:44px;height:44px;border:2px solid var(--gold);border-radius:var(--radius);display:grid;place-items:center;color:var(--primary);font-weight:900}
    h1{font-size:1.6rem}.lead{color:var(--muted);margin-top:5px}
    label{display:block;margin:14px 0 7px;color:var(--muted);font-size:.78rem;font-weight:800;text-transform:uppercase}
    input{width:100%;padding:12px 13px;border:1px solid var(--line);border-radius:var(--radius);font-size:1rem}
    input:focus{outline:2px solid rgba(5,138,54,.14);border-color:var(--primary)}
    .btn{width:100%;border:0;border-radius:var(--radius);padding:13px;background:var(--primary);color:#fff;font-weight:800;margin-top:18px;cursor:pointer}
    .alert{padding:12px;border-radius:var(--radius);margin:14px 0;font-size:.9rem}.alert-error{background:#fee2e2;color:#991b1b}.alert-success{background:#dcfce7;color:#166534}
    .links{text-align:center;margin-top:18px;color:var(--muted);font-size:.92rem}.links a{color:var(--primary);font-weight:800;text-decoration:none}
  </style>
</head>
<body>
  <div class="card">
    <div class="brand"><div class="mark">G★</div><div><h1>Create account</h1><p class="lead">Start your Google Review dashboard.</p></div></div>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <label>Name *</label>
      <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

      <label>WhatsApp Number *</label>
      <input type="tel" name="phone" required placeholder="9780551900" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

      <label>Password *</label>
      <input type="password" name="password" required minlength="8">

      <label>Confirm Password *</label>
      <input type="password" name="confirm_password" required minlength="8">

      <button class="btn" type="submit">Send WhatsApp OTP</button>
    </form>
    <div class="links">Already registered? <a href="<?= APP_URL ?>/customer/login.php">Login</a></div>
  </div>
</body>
</html>
