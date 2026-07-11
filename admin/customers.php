<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$db = getDB();
$saved = $_GET['saved'] ?? '';
$msg = $saved === 'access' ? 'Customer access updated successfully.' : ($saved === 'created' ? 'Customer account created successfully.' : '');
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
        $db->beginTransaction();
        saveManualCustomerAccess($customerId, $planId, $expiresInput, $isActive, $phoneVerified);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_customer') {
    $name = trim($_POST['name'] ?? '');
    $phone = normalizeIndianPhone($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $phoneVerified = isset($_POST['phone_verified']) ? 1 : 0;
    $planId = intval($_POST['plan_id'] ?? 0);
    $expiresInput = trim($_POST['expires_at'] ?? '');

    try {
        if ($name === '' || $phone === '' || $password === '') {
            throw new RuntimeException('Name, WhatsApp number, and password are required.');
        }
        if (!isValidIndianPhone($phone)) {
            throw new RuntimeException('Please enter a valid Indian WhatsApp number.');
        }
        if (strlen($password) < 6) {
            throw new RuntimeException('Password must be at least 6 characters.');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid email address.');
        }

        $exists = $db->prepare("SELECT id FROM customers WHERE phone = ? OR (? <> '' AND email = ?) LIMIT 1");
        $exists->execute([$phone, $email, $email]);
        if ($exists->fetch()) {
            throw new RuntimeException('A customer with this phone or email already exists.');
        }

        $db->beginTransaction();
        $verifiedAt = $phoneVerified ? date('Y-m-d H:i:s') : null;
        $db->prepare("INSERT INTO customers (name, phone, email, password_hash, phone_verified_at, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())")
            ->execute([$name, $phone, $email !== '' ? $email : null, password_hash($password, PASSWORD_DEFAULT), $verifiedAt, $isActive]);
        $customerId = (int) $db->lastInsertId();
        saveManualCustomerAccess($customerId, $planId, $expiresInput, $isActive, $phoneVerified);
        $db->commit();

        header('Location: ' . APP_URL . '/admin/customers.php?saved=created');
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
  LEFT JOIN clients cl ON cl.id = (
    SELECT cl2.id FROM clients cl2
    WHERE cl2.customer_id = c.id
    ORDER BY cl2.created_at DESC, cl2.id DESC LIMIT 1
  )
  LEFT JOIN customer_subscriptions s ON s.id = (
    SELECT s2.id FROM customer_subscriptions s2
    WHERE s2.customer_id = c.id AND s2.status = 'active' AND s2.expires_at >= NOW()
    ORDER BY s2.expires_at DESC LIMIT 1
  )
  LEFT JOIN plans p ON p.id = s.plan_id
  ORDER BY c.created_at DESC")->fetchAll();

$pageTitle = 'Customers';
$activeNav = 'customers';
$isCreateCustomerPost = ($_POST['action'] ?? '') === 'create_customer';
include __DIR__ . '/_layout.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <span class="card-title">Create Customer Account</span>
  </div>
  <form method="post">
    <input type="hidden" name="action" value="create_customer">
    <div class="form-grid form-grid-2">
      <div class="form-group">
        <label>Customer Name *</label>
        <input type="text" name="name" required value="<?= $isCreateCustomerPost ? htmlspecialchars($_POST['name'] ?? '') : '' ?>">
      </div>
      <div class="form-group">
        <label>WhatsApp Number *</label>
        <input type="text" name="phone" required placeholder="9876543210" value="<?= $isCreateCustomerPost ? htmlspecialchars($_POST['phone'] ?? '') : '' ?>">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= $isCreateCustomerPost ? htmlspecialchars($_POST['email'] ?? '') : '' ?>">
      </div>
      <div class="form-group">
        <label>Password *</label>
        <input type="password" name="password" required minlength="6">
      </div>
      <div class="form-group">
        <label>Assign Plan</label>
        <select name="plan_id">
          <option value="">No active plan</option>
          <?php foreach ($plans as $plan): ?>
            <option value="<?= intval($plan['id']) ?>"><?= htmlspecialchars($plan['name']) ?> - &#8377;<?= number_format((float) $plan['price'], 0) ?><?= $plan['is_active'] ? '' : ' (inactive)' ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Plan Expiry</label>
        <input type="datetime-local" name="expires_at">
        <small style="color:var(--muted);font-size:0.75rem">Leave blank to use the selected plan duration.</small>
      </div>
      <div class="form-group">
        <label>Status</label>
        <label style="text-transform:none;letter-spacing:0"><input type="checkbox" name="is_active" value="1" checked style="width:auto;padding:0"> Active customer</label>
      </div>
      <div class="form-group">
        <label>Verification</label>
        <label style="text-transform:none;letter-spacing:0"><input type="checkbox" name="phone_verified" value="1" checked style="width:auto;padding:0"> Mark OTP verified</label>
      </div>
    </div>
    <div style="margin-top:18px">
      <button class="btn btn-primary" type="submit">Create Customer</button>
    </div>
  </form>
</div>

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
