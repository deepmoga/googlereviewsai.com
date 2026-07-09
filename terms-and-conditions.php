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
  <title>Terms & Conditions - <?= htmlspecialchars($siteName) ?></title>
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
      <h1>Terms & Conditions</h1>
      <p class="muted">Last updated: <?= date('d M Y') ?></p>
      <p>These terms apply to your use of <?= htmlspecialchars($siteName) ?>, including the website, customer dashboard, AI review generation features, plans, addons, and support services.</p>

      <h2>Account Creation And Payment</h2>
      <ul>
        <li>A customer account is created only after successful payment for a selected plan.</li>
        <li>After payment, the customer must verify the WhatsApp OTP to access the dashboard.</li>
        <li>You are responsible for providing accurate name, phone, email, and business details.</li>
      </ul>

      <h2>Plans And Addons</h2>
      <ul>
        <li>Plans activate access for the stated duration shown at checkout.</li>
        <li>Addons are optional one-time purchases and may be purchased during registration or later from billing.</li>
        <li>Prices, plan duration, addon availability, and features may be updated from time to time.</li>
      </ul>

      <h2>Use Of AI Review Suggestions</h2>
      <p>The service provides AI-assisted review text suggestions. Customers should post only truthful reviews based on their real experience. You must not use the service for fake, misleading, abusive, illegal, or spam review activity.</p>

      <h2>Business Information</h2>
      <p>You confirm that you have permission to use any business name, logo, Google Review link, place ID, instructions, and service details submitted in the dashboard.</p>

      <h2>Payments And Refunds</h2>
      <p>Payments are processed by Razorpay. Payment success, failure, or refund handling may depend on Razorpay and banking systems. For billing questions, contact support with your registered phone number and payment reference.</p>

      <h2>Availability</h2>
      <p>We aim to keep the service available, but we do not guarantee uninterrupted access. Features that depend on third parties, including payment gateways, WhatsApp services, Google services, hosting, or AI providers, may be affected by those providers.</p>

      <h2>Limitation Of Liability</h2>
      <p>To the maximum extent permitted by law, <?= htmlspecialchars($siteName) ?> is not liable for indirect losses, lost profits, business interruption, third-party platform changes, or customer review outcomes.</p>

      <h2>Contact</h2>
      <p>For terms, billing, or support questions, contact us at <a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a> or phone <?= htmlspecialchars($phoneDisplay) ?>.</p>
    </div>
  </main>
  <footer class="footer">
    <div class="wrap"><a href="<?= APP_URL ?>/privacy-policy.php">Privacy Policy</a> | <a href="<?= APP_URL ?>/">Home</a></div>
  </footer>
</body>
</html>
