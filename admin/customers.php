<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$db = getDB();
$msg = ($_GET['saved'] ?? '') === 'access' ? 'Customer access updated successfully.' : '';
$error = '';

$plans = $db->query("SELECT * FROM plans ORDER BY is_active DESC, price ASC, id ASC")->fetchAll();

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $db->prepare("UPDATE customers SET is_active = !is_active WHERE id = ?")->execute([$id]);
    header('Location: ' . APP_URL . '/admin/customers.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_access') {
    $customerId = intval($_POST['customer_id'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $phoneVerified = isset($_POST['phone_verified']) ? 1 : 0;
    $planId = intval($_POST['plan_id'] ?? 0);
    $expiresInput = trim($_POST['expires_at'] ?? '');

    try {
        $customerStmt = $db->prepare("SELECT id FROM customers WHERE id = ?");
        $customerStmt->execute([$customerId]);
        if (!$customerStmt->fetch()) {
            throw new RuntimeException('Customer not found.');
        }

        $plan = null;
        if ($planId > 0) {
            $planStmt = $db->prepare("SELECT * FROM plans WHERE id = ?");
            $planStmt->execute([$planId]);
            $plan = $planStmt->fetch();
            if (!$plan) {
                throw new RuntimeException('Selected plan was not found.');
            }
        }

        $expiresAt = null;
        if ($plan) {
            if ($expiresInput !== '') {
                $expiryTime = strtotime($expiresInput);
                if (!$expiryTime) {
                    throw new RuntimeException('Please enter a valid expiry date.');
                }
            } else {
                $expiryTime = time() + (intval($plan['duration_days']) * 86400);
            }

            if ($expiryTime <= time()) {
                throw new RuntimeException('Expiry date must be in the future to activate the plan.');
            }
            $expiresAt = date('Y-m-d H:i:s', $expiryTime);
        }

        $db->beginTransaction();
        $verifiedAt = $phoneVerified ? date('Y-m-d H:i:s') : null;
        $db->prepare("UPDATE customers SET is_active = ?, phone_verified_at = ? WHERE id = ?")
            ->execute([$isActive, $verifiedAt, $customerId]);

        $db->prepare("UPDATE customer_subscriptions SET status = 'expired' WHERE customer_id = ? AND status = 'active'")
            ->execute([$customerId]);

        if ($plan) {
            $db->prepare("INSERT INTO customer_subscriptions (customer_id, plan_id, order_id, starts_at, expires_at, amount, status, created_at)
                VALUES (?, ?, NULL, NOW(), ?, ?, 'active', NOW())")
                ->execute([$customerId, $plan['id'], $expiresAt, $plan['price']]);
            $db->prepare("UPDATE clients SET link_expire_at = ?, is_active = 1 WHERE customer_id = ?")
                ->execute([$expiresAt, $customerId]);
        } else {
            $db->prepare("UPDATE clients SET link_expire_at = NULL WHERE customer_id = ?")
                ->execute([$customerId]);
        }

        $db->commit();
        header('Location: ' . APP_URL . '/admin/customers.php?saved=access');
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = $e->getMessage();
    }
}

$customers = $db->query("SELECT c.*,
    cl.company_name, cl.slug,
    s.id AS subscription_id, s.plan_id, s.expires_at, p.name AS plan_name,
    (SELECT COUNT(*) FROM addon_purchases ap WHERE ap.customer_id = c.id AND ap.status = 'paid') AS addon_count,
    (SELECT COALESCE(SUM(amount), 0) FROM payment_orders po WHERE po.customer_id = c.id AND po.status = 'paid') AS paid_total
  FROM customers c
  LEFT JOIN clients cl ON cl.customer_id = c.id
  LEFT JOIN customer_subscriptions s ON s.id = (
    SELECT s2.id FROM customer_subscriptions s2
    WHERE s2.customer_id = c.id AND s2.status = 'active' AND s2.expires_at >= NOW()
    ORDER BY s2.expires_at DESC LIMIT 1
  )
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

  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

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
          <td>&#8377;<?= number_format((float) $c['paid_total'], 2) ?></td>
          <td>
            <span class="badge <?= $c['is_active'] ? 'badge-green' : 'badge-red' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span><br>
            <span style="color:var(--muted);font-size:.8rem"><?= $c['phone_verified_at'] ? 'WhatsApp verified' : 'OTP pending' ?></span>
          </td>
          <td>
            <form method="post" style="min-width:260px;display:grid;gap:10px">
              <input type="hidden" name="action" value="save_access">
              <input type="hidden" name="customer_id" value="<?= intval($c['id']) ?>">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <label style="text-transform:none;letter-spacing:0;font-size:.8rem">
                  <input type="checkbox" name="is_active" value="1" <?= $c['is_active'] ? 'checked' : '' ?> style="width:auto;padding:0">
                  Active
                </label>
                <label style="text-transform:none;letter-spacing:0;font-size:.8rem">
                  <input type="checkbox" name="phone_verified" value="1" <?= $c['phone_verified_at'] ? 'checked' : '' ?> style="width:auto;padding:0">
                  OTP verified
                </label>
              </div>
              <select name="plan_id">
                <option value="">No active plan</option>
                <?php foreach ($plans as $plan): ?>
                  <option value="<?= intval($plan['id']) ?>" <?= intval($c['plan_id'] ?? 0) === intval($plan['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($plan['name']) ?> - &#8377;<?= number_format((float) $plan['price'], 0) ?><?= $plan['is_active'] ? '' : ' (inactive)' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="datetime-local" name="expires_at" value="<?= !empty($c['expires_at']) ? date('Y-m-d\\TH:i', strtotime($c['expires_at'])) : '' ?>">
              <button class="btn btn-primary btn-sm" type="submit">Save Access</button>
            </form>
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
