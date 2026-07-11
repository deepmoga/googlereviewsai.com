<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$db = getDB();
$totalBusinessProfiles = $db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$activeBusinessProfiles = $db->query("SELECT COUNT(*) FROM clients WHERE is_active = 1")->fetchColumn();
$totalCustomers = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$activeSubscriptions = $db->query("SELECT COUNT(*) FROM customer_subscriptions WHERE status = 'active' AND expires_at >= NOW()")->fetchColumn();
$paidRevenue = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payment_orders WHERE status = 'paid'")->fetchColumn();
$apiKey = getSetting('openai_api_key');

$recentCustomers = $db->query("SELECT c.*, cl.id AS business_id, cl.company_name, cl.slug, cl.logo_path, cl.tagline, cl.is_active AS business_active
  FROM customers c
  LEFT JOIN clients cl ON cl.id = (
    SELECT cl2.id FROM clients cl2
    WHERE cl2.customer_id = c.id
    ORDER BY cl2.created_at DESC, cl2.id DESC LIMIT 1
  )
  ORDER BY c.created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/_layout.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="val" style="color:#3b82f6"><?= $totalCustomers ?></div>
    <div class="lbl">Total Customers</div>
  </div>
  <div class="stat-card">
    <div class="val" style="color:#22c55e"><?= $activeBusinessProfiles ?></div>
    <div class="lbl">Active Business Profiles</div>
  </div>
  <div class="stat-card">
    <div class="val" style="color:#f59e0b"><?= $totalBusinessProfiles ?></div>
    <div class="lbl">Business Profiles</div>
  </div>
  <div class="stat-card">
    <div class="val" style="color:#22c55e"><?= $activeSubscriptions ?></div>
    <div class="lbl">Active Plans</div>
  </div>
  <div class="stat-card">
    <div class="val" style="color:#f59e0b">₹<?= number_format((float) $paidRevenue, 0) ?></div>
    <div class="lbl">Paid Revenue</div>
  </div>
  <div class="stat-card">
    <div class="val" style="color:<?= $apiKey ? '#22c55e' : '#ef4444' ?>"><?= $apiKey ? '✓' : '✗' ?></div>
    <div class="lbl">API Key <?= $apiKey ? 'Configured' : 'Missing' ?></div>
  </div>
</div>

<?php if (!$apiKey): ?>
<div class="alert alert-error">
  ⚠️ OpenAI API key not configured. <a href="<?= APP_URL ?>/admin/settings.php" style="color:inherit;font-weight:600;">Set it in Settings →</a>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <span class="card-title">Recent Customers</span>
    <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/admin/customers.php">+ Add Customer</a>
  </div>

  <?php if (empty($recentCustomers)): ?>
    <p style="color:var(--muted);text-align:center;padding:32px">No customers yet. <a href="<?= APP_URL ?>/admin/customers.php" style="color:var(--primary)">Add your first customer</a></p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Logo</th>
        <th>Company</th>
        <th>Status</th>
        <th>Review Link</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($recentCustomers as $c): ?>
      <tr>
        <td>
          <div class="logo-preview">
            <?php if ($c['logo_path']): ?>
              <img src="<?= htmlspecialchars(UPLOAD_URL . $c['logo_path']) ?>" alt="">
            <?php else: ?>
              <span style="font-size:1.5rem;color:var(--muted)"><?= strtoupper(substr($c['name'],0,1)) ?></span>
            <?php endif; ?>
          </div>
        </td>
        <td>
          <div style="font-weight:500"><?= htmlspecialchars($c['name']) ?></div>
          <div style="font-size:0.78rem;color:var(--muted)">+<?= htmlspecialchars($c['phone']) ?></div>
          <?php if ($c['company_name']): ?>
            <div style="font-size:0.78rem;color:var(--muted2);margin-top:4px"><?= htmlspecialchars($c['company_name']) ?></div>
          <?php elseif ($c['tagline']): ?>
            <div style="font-size:0.78rem;color:var(--muted)"><?= htmlspecialchars($c['tagline']) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge <?= $c['business_active'] ? 'badge-green' : 'badge-red' ?>">
            <?= $c['business_active'] ? 'Active' : 'No Business' ?>
          </span>
        </td>
        <td>
          <?php if ($c['slug']): ?>
            <div class="link-copy">
              <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(APP_URL . '/review.php?c=' . $c['slug']) ?></span>
              <button onclick="copyLink('<?= APP_URL . '/review.php?c=' . $c['slug'] ?>', this)" title="Copy link">📋</button>
              <a href="<?= APP_URL . '/review.php?c=' . $c['slug'] ?>" target="_blank" style="color:var(--muted);text-decoration:none" title="Open">↗</a>
            </div>
          <?php else: ?>
            <span style="color:var(--muted)">Not added</span>
          <?php endif; ?>
        </td>
        <td>
          <a class="btn btn-ghost btn-sm" href="<?= APP_URL ?>/admin/customers.php?action=business&id=<?= $c['id'] ?>">Business Profile</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<script>
function copyLink(url, btn) {
  navigator.clipboard.writeText(url).then(() => {
    btn.textContent = '✓';
    setTimeout(() => btn.textContent = '📋', 2000);
  });
}
</script>

  </div>
</div>
</body>
</html>
