<?php
require_once __DIR__ . '/config.php';

$siteName = 'Official AI Review';
$email = 'info@officialdigitalmarketing.in';
$phoneDisplay = '97805-51900';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Privacy Policy - <?= htmlspecialchars($siteName) ?></title>
  <style>
    :root{--primary:#058a36;--gold:#f0b400;--text:#102016;--muted:#667569;--line:#dce8df;--bg:#f6fbf7;--radius:8px}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,Helvetica,sans-serif;background:var(--bg);color:var(--text);line-height:1.7}
    a{color:var(--primary);font-weight:800;text-decoration:none}
    .top{background:#073f20;color:#fff;padding:22px 0}.wrap{width:min(920px,calc(100% - 40px));margin:0 auto}
    .brand{display:flex;align-items:center;gap:12px;color:#fff}.mark{width:42px;height:42px;border:2px solid var(--gold);border-radius:var(--radius);display:grid;place-items:center;background:#fff;color:var(--primary);font-weight:900}
    main{padding:48px 0}.legal{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:34px;box-shadow:0 14px 38px rgba(16,32,22,.06)}
    h1{font-size:2rem;line-height:1.15;margin-bottom:10px}h2{font-size:1.18rem;margin:28px 0 8px}.muted{color:var(--muted);margin-bottom:18px}
    ul{padding-left:22px}.footer{padding:24px 0;color:var(--muted);border-top:1px solid var(--line);background:#fff}
  </style>
</head>
<body>
  <header class="top">
    <div class="wrap">
      <a class="brand" href="<?= APP_URL ?>/"><span class="mark">G*</span><strong><?= htmlspecialchars($siteName) ?></strong></a>
    </div>
  </header>
  <main>
    <div class="wrap legal">
      <h1>Privacy Policy</h1>
      <p class="muted">Last updated: <?= date('d M Y') ?></p>
      <p><?= htmlspecialchars($siteName) ?> collects and uses information only to provide the AI Google Review dashboard, payment checkout, customer verification, and support.</p>

      <h2>Information We Collect</h2>
      <ul>
        <li>Name, WhatsApp number, email address, and password for customer accounts.</li>
        <li>Business profile information such as company name, logo, Google Review link, place ID, service options, and AI instructions.</li>
        <li>Payment order details including plan, addon, amount, Razorpay order ID, payment ID, and payment status.</li>
        <li>Technical information needed to keep the website secure and working correctly.</li>
      </ul>

      <h2>How We Use Information</h2>
      <ul>
        <li>To create and manage customer accounts after successful payment.</li>
        <li>To send WhatsApp OTP messages and verify customer access.</li>
        <li>To generate AI-assisted review suggestions based on business details and customer inputs.</li>
        <li>To process plan and addon purchases and maintain billing records.</li>
        <li>To provide support, improve the service, and prevent misuse.</li>
      </ul>

      <h2>Payments</h2>
      <p>Online payments are processed through Razorpay. We store payment identifiers and status for account activation and records, but we do not store card, UPI, net banking, or wallet credentials.</p>

      <h2>Sharing Of Information</h2>
      <p>We may share necessary information with service providers such as payment gateways, WhatsApp messaging providers, hosting providers, and AI providers only for operating this service. We do not sell customer personal information.</p>

      <h2>Data Security</h2>
      <p>Passwords are stored using secure hashing. Access to admin and customer areas is controlled by login and OTP verification. No internet system can be guaranteed perfectly secure, but we take reasonable steps to protect customer information.</p>

      <h2>Contact</h2>
      <p>For privacy requests, contact us at <a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a> or phone <?= htmlspecialchars($phoneDisplay) ?>.</p>
    </div>
  </main>
  <footer class="footer">
    <div class="wrap"><a href="<?= APP_URL ?>/terms-and-conditions.php">Terms & Conditions</a> | <a href="<?= APP_URL ?>/">Home</a></div>
  </footer>
</body>
</html>
