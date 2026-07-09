<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$db = getDB();
$msg = '';

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $db->prepare("UPDATE customers SET is_active = !is_active WHERE id = ?")->execute([$id]);
    header('Location: ' . APP_URL . '/admin/customers.php');
    exit;
}

$customers = $db->query("SELECT c.*,
    cl.company_name, cl.slug,
    s.expires_at, p.name AS plan_name,
    (SELECT COUNT(*) FROM addon_purchases ap WHERE ap.customer_id = c.id AND ap.status = 'paid') AS addon_count,
    (SELECT COALESCE(SUM(amount), 0) FROM payment_orders po WHERE po.customer_id = c.id AND po.status = 'paid') AS paid_total
  FROM customers c
  LEFT JOIN clients cl ON cl.customer_id = c.id
  LEFT JOIN customer_subscriptions s ON s.customer_id = c.id AND s.status = 'active' AND s.expires_at >= NOW()
  LEFT JOIN plans p ON p.id = s.plan_id
  ORDER BY c.created_at DESC")->fetchAll();

$pageTitle = 'Customers';
$activeNav = 'customers';
include __DIR__ . '/_layout.php';
?>

<div class="card">
  <div class="card-header">
    <span class="card-title">All Customers (<?= count($customers) ?>)</span>
  </div>

  <?php if (!$customers): ?>
    <p style="text-align:center;color:var(--muted);padding:40px">No customers yet.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Customer</th>
          <th>Business</th>
          <th>Plan</th>
          <th>Addons</th>
          <th>Total Paid</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars($c['name']) ?></strong><br>
            <span style="color:var(--muted);font-size:.8rem">+<?= htmlspecialchars($c['phone']) ?></span><br>
            <?php if ($c['email']): ?><span style="color:var(--muted);font-size:.8rem"><?= htmlspecialchars($c['email']) ?></span><?php endif; ?>
          </td>
          <td>
            <?php if ($c['company_name']): ?>
              <?= htmlspecialchars($c['company_name']) ?><br>
              <a style="color:var(--primary);font-size:.8rem" href="<?= APP_URL ?>/review.php?c=<?= htmlspecialchars($c['slug']) ?>" target="_blank">Open Review Page</a>
            <?php else: ?>
              <span style="color:var(--muted)">Not added</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($c['expires_at']): ?>
              <span class="badge badge-green"><?= htmlspecialchars($c['plan_name']) ?></span><br>
              <span style="color:var(--muted);font-size:.8rem">Expires <?= date('d M Y', strtotime($c['expires_at'])) ?></span>
            <?php else: ?>
              <span class="badge badge-red">No active plan</span>
            <?php endif; ?>
          </td>
          <td><?= intval($c['addon_count']) ?></td>
          <td>₹<?= number_format((float) $c['paid_total'], 2) ?></td>
          <td>
            <span class="badge <?= $c['is_active'] ? 'badge-green' : 'badge-red' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span><br>
            <span style="color:var(--muted);font-size:.8rem"><?= $c['phone_verified_at'] ? 'WhatsApp verified' : 'OTP pending' ?></span>
          </td>
          <td>
            <a class="btn btn-ghost btn-sm" href="?toggle=<?= intval($c['id']) ?>"><?= $c['is_active'] ? 'Deactivate' : 'Activate' ?></a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

  </div>
</div>
</body>
</html>
