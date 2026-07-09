<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$db = getDB();
$totalClients = $db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$activeClients = $db->query("SELECT COUNT(*) FROM clients WHERE is_active = 1")->fetchColumn();
$totalCustomers = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$activeSubscriptions = $db->query("SELECT COUNT(*) FROM customer_subscriptions WHERE status = 'active' AND expires_at >= NOW()")->fetchColumn();
$paidRevenue = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payment_orders WHERE status = 'paid'")->fetchColumn();
$apiKey = getSetting('openai_api_key');

$recentClients = $db->query("SELECT * FROM clients ORDER BY created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/_layout.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="val" style="color:#3b82f6"><?= $totalClients ?></div>
    <div class="lbl">Total Clients</div>
  </div>
  <div class="stat-card">
    <div class="val" style="color:#22c55e"><?= $activeClients ?></div>
    <div class="lbl">Active Clients</div>
  </div>
  <div class="stat-card">
    <div class="val" style="color:#f59e0b"><?= $totalCustomers ?></div>
    <div class="lbl">Customers</div>
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
    <span class="card-title">Recent Clients</span>
    <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/admin/clients.php?action=new">+ Add Client</a>
  </div>

  <?php if (empty($recentClients)): ?>
    <p style="color:var(--muted);text-align:center;padding:32px">No clients yet. <a href="<?= APP_URL ?>/admin/clients.php?action=new" style="color:var(--primary)">Add your first client →</a></p>
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
      <?php foreach ($recentClients as $c): ?>
      <tr>
        <td>
          <div class="logo-preview">
            <?php if ($c['logo_path']): ?>
              <img src="<?= htmlspecialchars(UPLOAD_URL . $c['logo_path']) ?>" alt="">
            <?php else: ?>
              <span style="font-size:1.5rem;color:var(--muted)"><?= strtoupper(substr($c['company_name'],0,1)) ?></span>
            <?php endif; ?>
          </div>
        </td>
        <td>
          <div style="font-weight:500"><?= htmlspecialchars($c['company_name']) ?></div>
          <?php if ($c['tagline']): ?>
            <div style="font-size:0.78rem;color:var(--muted)"><?= htmlspecialchars($c['tagline']) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge <?= $c['is_active'] ? 'badge-green' : 'badge-red' ?>">
            <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
          </span>
        </td>
        <td>
          <div class="link-copy">
            <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(APP_URL . '/review.php?c=' . $c['slug']) ?></span>
            <button onclick="copyLink('<?= APP_URL . '/review.php?c=' . $c['slug'] ?>', this)" title="Copy link">📋</button>
            <a href="<?= APP_URL . '/review.php?c=' . $c['slug'] ?>" target="_blank" style="color:var(--muted);text-decoration:none" title="Open">↗</a>
          </div>
        </td>
        <td>
          <a class="btn btn-ghost btn-sm" href="<?= APP_URL ?>/admin/clients.php?action=edit&id=<?= $c['id'] ?>">Edit</a>
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
