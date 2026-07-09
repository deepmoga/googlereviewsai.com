<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$clientId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = '';
$msgType = 'success';

// ---- DELETE ----
if ($action === 'delete' && $clientId) {
  $client = $db->prepare("SELECT * FROM clients WHERE id = ?");
  $client->execute([$clientId]);
  $c = $client->fetch();
  if ($c) {
    if ($c['logo_path'] && file_exists(UPLOAD_DIR . $c['logo_path'])) {
      unlink(UPLOAD_DIR . $c['logo_path']);
    }
    $db->prepare("DELETE FROM clients WHERE id = ?")->execute([$clientId]);
  }
  header('Location: ' . APP_URL . '/admin/clients.php?msg=deleted');
  exit;
}

// ---- TOGGLE STATUS ----
if ($action === 'toggle' && $clientId) {
  $db->prepare("UPDATE clients SET is_active = !is_active WHERE id = ?")->execute([$clientId]);
  header('Location: ' . APP_URL . '/admin/clients.php');
  exit;
}

// ---- SAVE (new or edit) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $companyName = trim($_POST['company_name'] ?? '');
  $tagline = trim($_POST['tagline'] ?? '');
  $reviewLink = trim($_POST['google_review_link'] ?? '');
  $instructions = trim($_POST['chatgpt_instructions'] ?? '');
  $serviceOptions = trim($_POST['service_options'] ?? '');
  $linkExpireAt = trim($_POST['link_expire_at'] ?? '');
  $linkExpireAt = $linkExpireAt !== '' ? $linkExpireAt : null;
  $isActive = isset($_POST['is_active']) ? 1 : 0;
  $editId = intval($_POST['edit_id'] ?? 0);

  if (empty($companyName) || empty($reviewLink)) {
    $msg = 'Company name and Google Review link are required.';
    $msgType = 'error';
  } else {
    // Handle logo upload
    $logoPath = null;
    if (!empty($_FILES['logo']['name'])) {
      $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
        $msg = 'Invalid logo format. Use JPG, PNG, GIF, WEBP or SVG.';
        $msgType = 'error';
      } elseif ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
        $msg = 'Logo must be under 2MB.';
        $msgType = 'error';
      } else {
        $filename = 'logo_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
        if (move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . $filename)) {
          $logoPath = $filename;
        }
      }
    }

    if (empty($msg)) {
      if ($editId) {
        // Update existing
        $existing = $db->prepare("SELECT logo_path FROM clients WHERE id = ?");
        $existing->execute([$editId]);
        $ex = $existing->fetch();

        // If new logo uploaded, delete old one
        if ($logoPath && $ex && $ex['logo_path'] && file_exists(UPLOAD_DIR . $ex['logo_path'])) {
          unlink(UPLOAD_DIR . $ex['logo_path']);
        }

        $logoUpdate = $logoPath ? ", logo_path = ?" : "";
        $params = [$companyName, $tagline, $reviewLink, $instructions, $serviceOptions, $linkExpireAt, $isActive];
        if ($logoPath) $params[] = $logoPath;
        $params[] = $editId;

        $db->prepare("UPDATE clients SET company_name=?, tagline=?, google_review_link=?, chatgpt_instructions=?, service_options=?, link_expire_at=?, is_active=?{$logoUpdate} WHERE id=?")->execute($params);
        $msg = 'Client updated successfully!';
      } else {
        // Generate unique slug
        $baseSlug = generateSlug($companyName);
        $slug = $baseSlug;
        $i = 1;
        while ($db->prepare("SELECT id FROM clients WHERE slug=?")->execute([$slug]) && $db->query("SELECT id FROM clients WHERE slug='$slug'")->fetch()) {
          $slug = $baseSlug . '-' . $i++;
        }

        $db->prepare("INSERT INTO clients (company_name, tagline, slug, google_review_link, chatgpt_instructions, service_options, link_expire_at, logo_path, is_active) VALUES (?,?,?,?,?,?,?,?,?)")
          ->execute([$companyName, $tagline, $slug, $reviewLink, $instructions, $serviceOptions, $linkExpireAt, $logoPath, $isActive]);
        $newId = $db->lastInsertId();
        $msg = 'Client created!';
        $action = 'edit';
        $clientId = $newId;
      }
    }
  }
}

// Get flash msg from redirect
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
  $msg = 'Client deleted.';
}

// Load client for edit
$editClient = null;
if (($action === 'edit' || $action === 'new') && $clientId) {
  $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
  $stmt->execute([$clientId]);
  $editClient = $stmt->fetch();
}

// List all clients
$clients = $db->query("SELECT * FROM clients ORDER BY created_at DESC")->fetchAll();

$pageTitle = $action === 'list' ? 'Clients' : ($editClient ? 'Edit Client' : 'New Client');
$activeNav = 'clients';
include __DIR__ . '/_layout.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
  <!-- ========== CLIENT LIST ========== -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">All Clients (<?= count($clients) ?>)</span>
      <a class="btn btn-primary" href="?action=new">+ Add New Client</a>
    </div>

    <?php if (empty($clients)): ?>
      <p style="text-align:center;color:var(--muted);padding:40px">No clients yet. Add your first one!</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Logo</th>
            <th>Company</th>
            <th>Slug</th>
            <th>Status</th>
            <th>Review Page</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clients as $c): ?>
            <tr>
              <td>
                <div class="logo-preview">
                  <?php if ($c['logo_path']): ?>
                    <img src="<?= htmlspecialchars(UPLOAD_URL . $c['logo_path']) ?>" alt="">
                  <?php else: ?>
                    <span style="font-size:1.5rem;color:var(--muted)"><?= strtoupper(substr($c['company_name'], 0, 1)) ?></span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <div style="font-weight:500"><?= htmlspecialchars($c['company_name']) ?></div>
                <?php if ($c['tagline']): ?>
                  <div style="font-size:0.78rem;color:var(--muted);margin-top:2px"><?= htmlspecialchars($c['tagline']) ?></div>
                <?php endif; ?>
              </td>
              <td><code style="font-size:0.8rem;color:var(--muted2)"><?= htmlspecialchars($c['slug']) ?></code></td>
              <td>
                <a href="?action=toggle&id=<?= $c['id'] ?>" style="text-decoration:none">
                  <span class="badge <?= $c['is_active'] ? 'badge-green' : 'badge-red' ?>">
                    <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                  </span>
                </a>
              </td>
              <td>
                <?php $reviewUrl = APP_URL . '/review.php?c=' . $c['slug']; ?>
                <div class="link-copy" style="max-width:280px">
                  <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.75rem"><?= htmlspecialchars($reviewUrl) ?></span>
                  <button onclick="copyLink('<?= htmlspecialchars($reviewUrl) ?>', this)" title="Copy">📋</button>
                  <a href="<?= htmlspecialchars($reviewUrl) ?>" target="_blank" style="color:var(--muted);text-decoration:none" title="Open">↗</a>
                </div>
              </td>
              <td style="white-space:nowrap">
                <a class="btn btn-ghost btn-sm" href="?action=edit&id=<?= $c['id'] ?>">✏️ Edit</a>
                <button class="btn btn-ghost btn-sm" onclick="showQR('<?= htmlspecialchars(addslashes($c['company_name'])) ?>', '<?= APP_URL . '/review.php?c=' . $c['slug'] ?>')">📷 QR</button>
                <a class="btn btn-ghost btn-sm" href="generate-pdf.php?id=<?= $c['id'] ?>" title="Download review flyer as PDF">📄 PDF</a>
                <a class="btn btn-danger btn-sm" href="?action=delete&id=<?= $c['id'] ?>"
                  onclick="return confirm('Delete <?= addslashes(htmlspecialchars($c['company_name'])) ?>? This cannot be undone.')">🗑 Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- ========== ADD / EDIT FORM ========== -->
  <div style="margin-bottom:16px">
    <a class="btn btn-ghost btn-sm" href="<?= APP_URL ?>/admin/clients.php">← Back to Clients</a>
  </div>

  <?php if ($editClient && $msg === 'Client created!'): ?>
    <div class="alert alert-info">
      🎉 Client created! Share this review page link:<br>
      <div class="link-copy" style="margin-top:10px">
        <?php $newUrl = APP_URL . '/review.php?c=' . $editClient['slug']; ?>
        <span style="flex:1"><?= htmlspecialchars($newUrl) ?></span>
        <button onclick="copyLink('<?= htmlspecialchars($newUrl) ?>', this)">📋</button>
        <a href="<?= htmlspecialchars($newUrl) ?>" target="_blank" style="color:var(--primary);text-decoration:none">↗ Open</a>
      </div>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">
      <span class="card-title"><?= $editClient ? 'Edit: ' . htmlspecialchars($editClient['company_name']) : 'New Client' ?></span>
    </div>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="edit_id" value="<?= $editClient ? $editClient['id'] : 0 ?>">

      <div class="form-grid form-grid-2">
        <div class="form-group">
          <label>Company Name *</label>
          <input type="text" name="company_name" required value="<?= htmlspecialchars($editClient['company_name'] ?? '') ?>" placeholder="e.g. Sharma's Restaurant">
        </div>

        <div class="form-group">
          <label>Tagline</label>
          <input type="text" name="tagline" value="<?= htmlspecialchars($editClient['tagline'] ?? '') ?>" placeholder="e.g. Authentic North Indian Cuisine">
        </div>

        <div class="form-group full">
          <label>Google Review Link *</label>
          <input type="url" name="google_review_link" required value="<?= htmlspecialchars($editClient['google_review_link'] ?? '') ?>" placeholder="https://g.page/r/...">
          <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">Find this in your Google Business Profile → Get more reviews</small>
        </div>

        <div class="form-group full">
          <label>Logo (JPG, PNG, SVG — max 2MB)</label>
          <?php if ($editClient && $editClient['logo_path']): ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
              <div class="logo-preview" style="width:80px;height:80px">
                <img src="<?= htmlspecialchars(UPLOAD_URL . $editClient['logo_path']) ?>" alt="">
              </div>
              <span style="font-size:0.8rem;color:var(--muted)">Current logo — upload new to replace</span>
            </div>
          <?php endif; ?>
          <input type="file" name="logo" accept="image/*" style="padding:8px">
        </div>

        <div class="form-group full">
          <label>ChatGPT Instructions</label>
          <textarea name="chatgpt_instructions" rows="5" placeholder="Describe your business so AI can generate realistic reviews. Example:&#10;&#10;We are a pure vegetarian Indian restaurant in Delhi. We specialize in North Indian dal makhani, paneer dishes, and tandoor items. We are known for our family-friendly atmosphere and affordable pricing. Our customers are mostly local families and office workers."><?= htmlspecialchars($editClient['chatgpt_instructions'] ?? '') ?></textarea>
          <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">The more detail you give, the more authentic the reviews will be.</small>
        </div>

        <div class="form-group full">
          <label>Service Buttons (comma separated)</label>
          <input type="text" name="service_options" value="<?= htmlspecialchars($editClient['service_options'] ?? '') ?>" placeholder="Study Visa, Spouse Visa, Work Permit, Visitor Visa">
          <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">Shown before star selection so users can pick what service they used.</small>
        </div>

        <div class="form-group">
          <label>Link Expire Date &amp; Time</label>
          <input type="date" name="link_expire_at" value="<?= !empty($editClient['link_expire_at']) ? date('Y-m-d\\TH:i', strtotime($editClient['link_expire_at'])) : '' ?>">
          <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">After this date, review page will show Plan Expired.</small>
        </div>

        <div class="form-group">
          <label>Status</label>
          <label style="display:flex;align-items:center;gap:8px;text-transform:none;letter-spacing:0;font-size:0.875rem;cursor:pointer;padding-top:8px">
            <input type="checkbox" name="is_active" value="1" <?= (!$editClient || $editClient['is_active']) ? 'checked' : '' ?> style="width:auto;padding:0">
            Active (review page is publicly accessible)
          </label>
        </div>
      </div>

      <div style="margin-top:24px;display:flex;gap:12px;align-items:center">
        <button class="btn btn-primary" type="submit">
          <?= $editClient ? '💾 Save Changes' : '✅ Create Client' ?>
        </button>
        <?php if ($editClient): ?>
          <?php $previewUrl = APP_URL . '/review.php?c=' . $editClient['slug']; ?>
          <a class="btn btn-success" href="<?= htmlspecialchars($previewUrl) ?>" target="_blank">↗ Preview Page</a>
          <div class="link-copy" style="flex:1">
            <span style="flex:1;font-size:0.78rem"><?= htmlspecialchars($previewUrl) ?></span>
            <button type="button" onclick="copyLink('<?= htmlspecialchars($previewUrl) ?>', this)">📋</button>
          </div>
        <?php endif; ?>
      </div>
    </form>
  </div>
<?php endif; ?>

<!-- QR Code Modal -->
<div id="qrModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);align-items:center;justify-content:center">
  <div style="background:#1e293b;border:1px solid #334155;border-radius:20px;padding:36px 40px;max-width:380px;width:90%;text-align:center;position:relative">
    <button onclick="closeQR()" style="position:absolute;top:14px;right:16px;background:none;border:none;color:#64748b;font-size:1.4rem;cursor:pointer;line-height:1">×</button>
    <h3 id="qrTitle" style="font-size:1rem;font-weight:600;color:#f1f5f9;margin-bottom:6px"></h3>
    <p style="font-size:0.78rem;color:#64748b;margin-bottom:20px">Scan to open the review page</p>
    <div style="background:#fff;border-radius:12px;padding:12px;display:inline-block;margin-bottom:20px">
      <img id="qrImg" src="" alt="QR Code" style="width:200px;height:200px;display:block">
    </div>
    <div id="qrUrl" style="font-size:0.72rem;color:#64748b;word-break:break-all;margin-bottom:20px;padding:8px 12px;background:#0f172a;border-radius:8px"></div>
    <div style="display:flex;gap:10px;justify-content:center">
      <button class="btn btn-primary" onclick="downloadQR()">&#x2B07; Download PNG</button>
      <button class="btn btn-ghost" onclick="copyQrUrl()">&#x1F4CB; Copy Link</button>
    </div>
  </div>
</div>

<script>
  let currentQrUrl = '';

  function showQR(name, url) {
    currentQrUrl = url;
    document.getElementById('qrTitle').textContent = name;
    document.getElementById('qrUrl').textContent = url;

    // Encode the URL to handle special characters correctly
    const encoded = encodeURIComponent(url);

    // Use QuickChart with specific size and error correction parameters
    document.getElementById('qrImg').src = 'https://quickchart.io/qr?text=' + encoded + '&size=400&ecLevel=M&margin=2';

    const modal = document.getElementById('qrModal');
    modal.style.display = 'flex';
  }

  function closeQR() {
    document.getElementById('qrModal').style.display = 'none';
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('qrModal').addEventListener('click', function(e) {
      if (e.target === this) closeQR();
    });
  });

  function downloadQR() {
    const img = document.getElementById('qrImg');
    const link = document.createElement('a');
    link.href = img.src;
    link.download = 'qr-review.png';
    link.click();
  }

  function copyQrUrl() {
    navigator.clipboard.writeText(currentQrUrl).then(function() {
      const btns = document.querySelectorAll('#qrModal .btn-ghost');
      btns.forEach(function(btn) {
        if (btn.textContent.includes('Copy')) {
          btn.textContent = '\u2713 Copied!';
          setTimeout(function() {
            btn.innerHTML = '&#x1F4CB; Copy Link';
          }, 2000);
        }
      });
    });
  }

  function copyLink(url, btn) {
    navigator.clipboard.writeText(url).then(function() {
      const orig = btn.textContent;
      btn.textContent = '\u2713';
      setTimeout(function() {
        btn.textContent = orig;
      }, 2000);
    });
  }
</script>

</div>
</div>
</body>

</html>