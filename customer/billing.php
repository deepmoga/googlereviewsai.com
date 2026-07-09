<?php
require_once __DIR__ . '/../config.php';
$customer = requireCustomerLogin();
$db = getDB();
$plans = $db->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
$addons = $db->query("SELECT * FROM addons WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
$ordersStmt = $db->prepare("SELECT * FROM payment_orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 20");
$ordersStmt->execute([$customer['id']]);
$orders = $ordersStmt->fetchAll();
$subscription = activeCustomerSubscription($customer['id']);
$rzpKey = getSetting('razorpay_key_id') ?: '';

$pageTitle = 'Plans & Addons';
$activeNav = 'billing';
include __DIR__ . '/_layout.php';
?>

<?php if ($rzpKey === ''): ?>
  <div class="alert alert-info">Razorpay is not configured yet. You can view plans, but online payment will work after admin adds Razorpay keys.</div>
<?php endif; ?>

<div class="card">
  <h2>Current Plan</h2>
  <?php if ($subscription): ?>
    <p><span class="badge badge-green">Active</span></p>
    <p class="muted" style="margin-top:10px">Plan: <strong><?= htmlspecialchars($subscription['plan_name']) ?></strong> · Expires: <strong><?= date('d M Y', strtotime($subscription['expires_at'])) ?></strong></p>
  <?php else: ?>
    <p><span class="badge badge-red">No active plan</span></p>
    <p class="muted" style="margin-top:10px">Buy a plan to activate your public Google Review page.</p>
  <?php endif; ?>
</div>

<div class="grid grid-2">
  <?php foreach ($plans as $plan): ?>
    <div class="card">
      <h2><?= htmlspecialchars($plan['name']) ?></h2>
      <p style="font-size:2rem;font-weight:900;color:var(--primary);margin:10px 0">₹<?= number_format($plan['price'], 0) ?></p>
      <p class="muted" style="min-height:44px"><?= htmlspecialchars($plan['description'] ?: $plan['duration_days'] . ' days access') ?></p>
      <button class="btn btn-primary" type="button" onclick="buyItem('plan', <?= intval($plan['id']) ?>)">Buy Plan</button>
    </div>
  <?php endforeach; ?>
</div>

<div class="card" style="margin-top:18px">
  <h2>Addons</h2>
  <p class="muted" style="margin-bottom:14px">Addons do not expire and can be purchased multiple times.</p>
  <div class="grid grid-2">
    <?php foreach ($addons as $addon): ?>
      <div style="border:1px solid var(--line);border-radius:var(--radius);padding:16px">
        <h3><?= htmlspecialchars($addon['name']) ?></h3>
        <p style="font-size:1.5rem;font-weight:900;color:var(--primary);margin:8px 0">₹<?= number_format($addon['price'], 0) ?></p>
        <p class="muted" style="margin-bottom:12px"><?= htmlspecialchars($addon['description'] ?: 'One-time addon') ?></p>
        <button class="btn btn-light" type="button" onclick="buyItem('addon', <?= intval($addon['id']) ?>)">Buy Addon</button>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <h2>Payment History</h2>
  <?php if (!$orders): ?>
    <p class="muted">No payments yet.</p>
  <?php else: ?>
    <table>
      <thead><tr><th>Date</th><th>Item</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
          <td><?= htmlspecialchars(ucfirst($order['item_type'])) ?> #<?= intval($order['item_id']) ?></td>
          <td>₹<?= number_format($order['amount'], 2) ?></td>
          <td><span class="badge <?= $order['status'] === 'paid' ? 'badge-green' : 'badge-red' ?>"><?= htmlspecialchars($order['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const RAZORPAY_KEY = <?= json_encode($rzpKey) ?>;

async function buyItem(type, id) {
  if (!RAZORPAY_KEY) {
    alert('Razorpay keys are not configured. Please contact admin.');
    return;
  }

  const form = new FormData();
  form.append('item_type', type);
  form.append('item_id', id);

  const res = await fetch('<?= APP_URL ?>/customer/create-razorpay-order.php', { method: 'POST', body: form });
  const data = await res.json();
  if (!data.success) {
    alert(data.message || 'Could not create order.');
    return;
  }

  const options = {
    key: RAZORPAY_KEY,
    amount: data.amount,
    currency: 'INR',
    name: 'Official AI Review',
    description: data.description,
    order_id: data.razorpay_order_id,
    prefill: {
      name: <?= json_encode($customer['name']) ?>,
      email: <?= json_encode($customer['email'] ?? '') ?>,
      contact: <?= json_encode($customer['phone']) ?>
    },
    handler: async function (response) {
      const verify = new FormData();
      verify.append('local_order_id', data.local_order_id);
      verify.append('razorpay_order_id', response.razorpay_order_id);
      verify.append('razorpay_payment_id', response.razorpay_payment_id);
      verify.append('razorpay_signature', response.razorpay_signature);
      const vr = await fetch('<?= APP_URL ?>/customer/verify-payment.php', { method: 'POST', body: verify });
      const result = await vr.json();
      if (result.success) {
        window.location.reload();
      } else {
        alert(result.message || 'Payment verification failed.');
      }
    }
  };
  new Razorpay(options).open();
}
</script>

<?php include __DIR__ . '/_footer.php'; ?>
