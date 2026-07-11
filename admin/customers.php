<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$saved = $_GET['saved'] ?? '';
$msg = $saved === 'access' ? 'Customer access updated successfully.' : ($saved === 'created' ? 'Customer account created successfully.' : ($saved === 'business' ? 'Business profile saved successfully.' : ''));
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_business') {
    $customerId = intval($_POST['customer_id'] ?? 0);
    $businessId = intval($_POST['business_id'] ?? 0);
    $companyName = trim($_POST['company_name'] ?? '');
    $tagline = trim($_POST['tagline'] ?? '');
    $businessLocation = trim($_POST['business_location'] ?? '');
    $placeId = trim($_POST['google_place_id'] ?? '');
    $reviewLink = trim($_POST['google_review_link'] ?? '');
    $instructions = trim($_POST['chatgpt_instructions'] ?? '');
    $serviceOptions = trim($_POST['service_options'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($placeId === '') {
        $placeId = parseGooglePlaceId($reviewLink);
    }
    if ($placeId !== '') {
        $reviewLink = googleReviewLinkFromPlaceId($placeId);
    }

    try {
        if ($customerId <= 0) {
            throw new RuntimeException('Customer is required.');
        }
        if ($companyName === '' || $reviewLink === '') {
            throw new RuntimeException('Business name and Google Review link are required.');
        }

        $customerStmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
        $customerStmt->execute([$customerId]);
        if (!$customerStmt->fetch()) {
            throw new RuntimeException('Customer not found.');
        }

        $logoPath = null;
        if (!empty($_FILES['logo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
                throw new RuntimeException('Invalid logo format. Use JPG, PNG, GIF, WEBP or SVG.');
            }
            if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
                throw new RuntimeException('Logo must be under 2MB.');
            }
            $filename = 'logo_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            if (!move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . $filename)) {
                throw new RuntimeException('Logo upload failed.');
            }
            $logoPath = $filename;
        }

        $expiresAt = customerPlanExpiresAt($customerId);
        $db->beginTransaction();
        if ($businessId > 0) {
            $existingStmt = $db->prepare("SELECT * FROM clients WHERE id = ? AND customer_id = ?");
            $existingStmt->execute([$businessId, $customerId]);
            $existing = $existingStmt->fetch();
            if (!$existing) {
                throw new RuntimeException('Business profile not found.');
            }

            if ($logoPath && $existing['logo_path'] && file_exists(UPLOAD_DIR . $existing['logo_path'])) {
                unlink(UPLOAD_DIR . $existing['logo_path']);
            }

            $slug = generateUniqueClientSlug($companyName, $businessId);
            $logoSql = $logoPath ? ", logo_path = ?" : "";
            $params = [$companyName, $tagline, $slug, $reviewLink, $placeId, $businessLocation, $instructions, $serviceOptions, $expiresAt, $isActive];
            if ($logoPath) {
                $params[] = $logoPath;
            }
            $params[] = $businessId;
            $params[] = $customerId;

            $db->prepare("UPDATE clients SET company_name=?, tagline=?, slug=?, google_review_link=?, google_place_id=?, business_location=?, chatgpt_instructions=?, service_options=?, link_expire_at=?, is_active=?{$logoSql} WHERE id=? AND customer_id=?")
                ->execute($params);
        } else {
            $slug = generateUniqueClientSlug($companyName);
            $db->prepare("INSERT INTO clients (customer_id, company_name, tagline, slug, google_review_link, google_place_id, business_location, chatgpt_instructions, service_options, link_expire_at, logo_path, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())")
                ->execute([$customerId, $companyName, $tagline, $slug, $reviewLink, $placeId, $businessLocation, $instructions, $serviceOptions, $expiresAt, $logoPath, $isActive]);
        }
        $db->commit();

        header('Location: ' . APP_URL . '/admin/customers.php?action=business&id=' . $customerId . '&saved=business');
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = $e->getMessage();
        $action = 'business';
        $_GET['id'] = $customerId;
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

$selectedCustomer = null;
$selectedBusiness = null;
$selectedSubscription = null;
if ($action === 'business' && $customerId > 0) {
    $customerStmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $customerStmt->execute([$customerId]);
    $selectedCustomer = $customerStmt->fetch() ?: null;
    if (!$selectedCustomer) {
        $error = $error ?: 'Customer not found.';
        $action = 'list';
    } else {
        $businessStmt = $db->prepare("SELECT * FROM clients WHERE customer_id = ? ORDER BY created_at DESC, id DESC LIMIT 1");
        $businessStmt->execute([$customerId]);
        $selectedBusiness = $businessStmt->fetch() ?: null;
        $selectedSubscription = activeCustomerSubscription($customerId);
    }
}

$pageTitle = $action === 'business' && $selectedCustomer ? 'Customer Business Profile' : 'Customers';
$activeNav = 'customers';
$isCreateCustomerPost = ($_POST['action'] ?? '') === 'create_customer';
include __DIR__ . '/_layout.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($action === 'business' && $selectedCustomer): ?>
  <div style="margin-bottom:16px">
    <a class="btn btn-ghost btn-sm" href="<?= APP_URL ?>/admin/customers.php">Back to Customers</a>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Business Profile for <?= htmlspecialchars($selectedCustomer['name']) ?></span>
    </div>

    <?php if ($selectedSubscription): ?>
      <div class="alert alert-success">
        Active plan: <?= htmlspecialchars($selectedSubscription['plan_name']) ?>, expires <?= date('d M Y', strtotime($selectedSubscription['expires_at'])) ?>.
      </div>
    <?php else: ?>
      <div class="alert alert-info">
        No active plan assigned. Save customer access from the Customers list to unlock preview link, review generation, and PDF.
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save_business">
      <input type="hidden" name="customer_id" value="<?= intval($selectedCustomer['id']) ?>">
      <input type="hidden" name="business_id" value="<?= intval($selectedBusiness['id'] ?? 0) ?>">

      <div class="form-grid form-grid-2">
        <div class="form-group">
          <label>Business Name *</label>
          <input type="text" name="company_name" required value="<?= htmlspecialchars($selectedBusiness['company_name'] ?? '') ?>" placeholder="Business name">
        </div>

        <div class="form-group">
          <label>Tagline</label>
          <input type="text" name="tagline" value="<?= htmlspecialchars($selectedBusiness['tagline'] ?? '') ?>" placeholder="Short business tagline">
        </div>

        <div class="form-group full">
          <label>Find Google Place ID Automatically</label>
          <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <input type="text" id="placeSearch" placeholder="Example: AI Google Reviews Moga" value="<?= htmlspecialchars($selectedBusiness['business_location'] ?? '') ?>" style="flex:1;min-width:260px">
            <button class="btn btn-ghost" type="button" onclick="findPlace()">Find Place ID</button>
          </div>
          <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">Search by business name with city. The selected Place ID will auto-fill the Google Review link.</small>
          <div id="placeResults" style="margin-top:10px"></div>
        </div>

        <input type="hidden" name="google_place_id" id="googlePlaceId" value="<?= htmlspecialchars($selectedBusiness['google_place_id'] ?? '') ?>">

        <div class="form-group full">
          <label>Business Location / Search Text</label>
          <input type="text" name="business_location" id="businessLocation" value="<?= htmlspecialchars($selectedBusiness['business_location'] ?? '') ?>" placeholder="Business name, city, address">
        </div>

        <div class="form-group full">
          <label>Google Review Link *</label>
          <input type="text" name="google_review_link" id="googleReviewLink" required value="<?= htmlspecialchars($selectedBusiness['google_review_link'] ?? '') ?>" placeholder="https://search.google.com/local/writereview?placeid=...">
          <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">Use Place ID search above, paste a Google Review link, or paste a raw Place ID.</small>
        </div>

        <div class="form-group full">
          <label>Logo</label>
          <?php if ($selectedBusiness && $selectedBusiness['logo_path']): ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
              <div class="logo-preview" style="width:80px;height:80px">
                <img src="<?= htmlspecialchars(UPLOAD_URL . $selectedBusiness['logo_path']) ?>" alt="">
              </div>
              <span style="font-size:0.8rem;color:var(--muted)">Current logo. Upload a new file to replace it.</span>
            </div>
          <?php endif; ?>
          <input type="file" name="logo" accept="image/*" style="padding:8px">
        </div>

        <div class="form-group full">
          <label>AI Review Instructions</label>
          <textarea name="chatgpt_instructions" rows="5" placeholder="Describe the business, services, tone, and details for better reviews."><?= htmlspecialchars($selectedBusiness['chatgpt_instructions'] ?? '') ?></textarea>
        </div>

        <div class="form-group full">
          <label>Service Buttons</label>
          <input type="text" name="service_options" value="<?= htmlspecialchars($selectedBusiness['service_options'] ?? '') ?>" placeholder="Study Visa, Spouse Visa, Work Permit">
          <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">Comma separated options shown before star selection.</small>
        </div>

        <div class="form-group">
          <label>Business Status</label>
          <label style="text-transform:none;letter-spacing:0">
            <input type="checkbox" name="is_active" value="1" <?= (!$selectedBusiness || $selectedBusiness['is_active']) ? 'checked' : '' ?> style="width:auto;padding:0">
            Active review page
          </label>
        </div>
      </div>

      <div style="margin-top:24px;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <button class="btn btn-primary" type="submit">Save Business Profile</button>
        <?php if ($selectedBusiness): ?>
          <?php $previewUrl = APP_URL . '/review.php?c=' . $selectedBusiness['slug']; ?>
          <a class="btn btn-success" href="<?= htmlspecialchars($previewUrl) ?>" target="_blank">Open Review Page</a>
          <a class="btn btn-ghost" href="<?= APP_URL ?>/admin/generate-pdf.php?id=<?= intval($selectedBusiness['id']) ?>">PDF</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <script>
    async function findPlace() {
      const q = document.getElementById('placeSearch').value.trim();
      const out = document.getElementById('placeResults');
      if (q.length < 3) {
        out.innerHTML = '<div class="alert alert-error">Enter business name with city.</div>';
        return;
      }
      out.innerHTML = '<div class="alert alert-info">Searching Google Place ID...</div>';
      try {
        const res = await fetch('<?= APP_URL ?>/api/place-lookup.php?q=' + encodeURIComponent(q));
        const data = await res.json();
        if (!data.success) {
          throw new Error(data.message || 'No place found.');
        }
        out.innerHTML = data.places.map((p) => `
          <div style="border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:8px;background:#0f172a">
            <strong>${escapeHtml(p.name)}</strong>
            <div style="color:var(--muted);font-size:.88rem;margin:4px 0">${escapeHtml(p.address || '')}</div>
            <button class="btn btn-ghost btn-sm" type="button" onclick='selectPlace(${JSON.stringify(p)})'>Use this Google Review link</button>
          </div>
        `).join('');
      } catch (err) {
        out.innerHTML = '<div class="alert alert-error">' + escapeHtml(err.message) + '</div>';
      }
    }

    function selectPlace(place) {
      document.getElementById('googlePlaceId').value = place.place_id;
      document.getElementById('googleReviewLink').value = place.review_link;
      document.getElementById('businessLocation').value = [place.name, place.address].filter(Boolean).join(', ');
      document.getElementById('placeResults').innerHTML = '<div class="alert alert-success">Google Review link selected.</div>';
    }

    function escapeHtml(value) {
      const d = document.createElement('div');
      d.appendChild(document.createTextNode(value || ''));
      return d.innerHTML;
    }
  </script>
<?php else: ?>

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
            <a class="btn btn-ghost btn-sm" href="<?= APP_URL ?>/admin/customers.php?action=business&id=<?= intval($c['id']) ?>" style="margin-bottom:10px">Business Profile</a>
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

<?php endif; ?>

  </div>
</div>
</body>
</html>
