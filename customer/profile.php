<?php
require_once __DIR__ . '/../config.php';
$customer = requireCustomerLogin();
$db = getDB();
$client = customerClient($customer['id']);
$msg = '';
$msgType = 'success';

if (postBodyExceededLimit()) {
    $msg = 'Uploaded form data is too large. Please upload a logo under ' . LOGO_UPLOAD_MAX_MB . 'MB.';
    $msgType = 'error';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = trim($_POST['company_name'] ?? '');
    $tagline = trim($_POST['tagline'] ?? '');
    $businessLocation = trim($_POST['business_location'] ?? '');
    $placeId = trim($_POST['google_place_id'] ?? '');
    $reviewLink = trim($_POST['google_review_link'] ?? '');
    $instructions = trim($_POST['chatgpt_instructions'] ?? '');
    $serviceOptions = trim($_POST['service_options'] ?? '');

    if ($placeId === '') {
        $placeId = parseGooglePlaceId($reviewLink);
    }
    if ($placeId !== '') {
        $reviewLink = googleReviewLinkFromPlaceId($placeId);
    }

    if ($companyName === '') {
        $msg = 'Company name is required.';
        $msgType = 'error';
    } elseif ($reviewLink === '' || !filter_var($reviewLink, FILTER_VALIDATE_URL)) {
        $msg = 'Google Review link is required. Use the Place ID search or paste your Google Review link.';
        $msgType = 'error';
    } elseif ($instructions === '') {
        $msg = 'ChatGPT instructions are required so AI can write accurate reviews.';
        $msgType = 'error';
    } else {
        try {
            $logoPath = saveUploadedLogo('logo');
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $msgType = 'error';
            $logoPath = null;
        }

        if ($msg === '') {
            $expiresAt = customerPlanExpiresAt($customer['id']);
            if ($client) {
                if ($logoPath && $client['logo_path'] && file_exists(UPLOAD_DIR . $client['logo_path'])) {
                    @unlink(UPLOAD_DIR . $client['logo_path']);
                }
                $slug = generateUniqueClientSlug($companyName, $client['id']);
                $logoSql = $logoPath ? ', logo_path = ?' : '';
                $params = [$companyName, $tagline, $slug, $reviewLink, $placeId, $businessLocation, $instructions, $serviceOptions, $expiresAt, $client['id'], $customer['id']];
                if ($logoPath) {
                    array_splice($params, 9, 0, [$logoPath]);
                }
                $db->prepare("UPDATE clients SET company_name=?, tagline=?, slug=?, google_review_link=?, google_place_id=?, business_location=?, chatgpt_instructions=?, service_options=?, link_expire_at=?{$logoSql} WHERE id=? AND customer_id=?")
                    ->execute($params);
            } else {
                $slug = generateUniqueClientSlug($companyName);
                $db->prepare("INSERT INTO clients (customer_id, company_name, tagline, slug, google_review_link, google_place_id, business_location, chatgpt_instructions, service_options, link_expire_at, logo_path, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())")
                    ->execute([$customer['id'], $companyName, $tagline, $slug, $reviewLink, $placeId, $businessLocation, $instructions, $serviceOptions, $expiresAt, $logoPath]);
            }
            $msg = 'Business profile saved.';
            $client = customerClient($customer['id']);
        }
    }
}

$pageTitle = 'Business Profile';
$activeNav = 'profile';
include __DIR__ . '/_layout.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
  <h2>Google Review Business Details</h2>
  <p class="muted" style="margin-bottom:18px">These details power your customer-facing AI review page.</p>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= LOGO_UPLOAD_MAX_BYTES ?>">
    <div class="grid grid-2">
      <div class="form-group">
        <label>Company Name *</label>
        <input type="text" name="company_name" required value="<?= htmlspecialchars($client['company_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Tagline</label>
        <input type="text" name="tagline" value="<?= htmlspecialchars($client['tagline'] ?? '') ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Find Google Place ID Automatically</label>
      <div class="actions">
        <input type="text" id="placeSearch" placeholder="Example: Official Digital Marketing Ludhiana" value="<?= htmlspecialchars($client['business_location'] ?? '') ?>" style="flex:1;min-width:260px">
        <button class="btn btn-light" type="button" onclick="findPlace()">Find Place ID</button>
      </div>
      <span class="help">Search by business name with city. If Google API key is configured, we will generate the Google Review link automatically.</span>
      <div id="placeResults" style="margin-top:10px"></div>
    </div>

    <input type="hidden" name="google_place_id" id="googlePlaceId" value="<?= htmlspecialchars($client['google_place_id'] ?? '') ?>">

    <div class="form-group">
      <label>Business Location / Search Text</label>
      <input type="text" name="business_location" id="businessLocation" value="<?= htmlspecialchars($client['business_location'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Google Review Link *</label>
      <input type="url" name="google_review_link" id="googleReviewLink" required value="<?= htmlspecialchars($client['google_review_link'] ?? '') ?>" placeholder="https://search.google.com/local/writereview?placeid=...">
      <span class="help">You can also paste a Google Review link or raw Place ID here.</span>
    </div>

    <div class="form-group">
      <label>Logo</label>
      <?php if ($client && $client['logo_path']): ?>
        <div style="margin-bottom:10px"><img src="<?= htmlspecialchars(UPLOAD_URL . $client['logo_path']) ?>" alt="" style="max-width:90px;max-height:90px;border:1px solid var(--line);border-radius:var(--radius);padding:6px;background:#fff"></div>
      <?php endif; ?>
      <input type="file" name="logo" accept=".jpg,.jpeg,.png,.gif,.webp,.svg,image/jpeg,image/png,image/gif,image/webp,image/svg+xml">
      <span class="help">JPG, PNG, GIF, WEBP or SVG. Max <?= LOGO_UPLOAD_MAX_MB ?>MB.</span>
    </div>

    <div class="form-group">
      <label>ChatGPT Instructions *</label>
      <textarea name="chatgpt_instructions" required placeholder="Describe your business, tone, popular services, city, customer type, and anything reviews should mention."><?= htmlspecialchars($client['chatgpt_instructions'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label>Service Buttons (comma separated)</label>
      <input type="text" name="service_options" value="<?= htmlspecialchars($client['service_options'] ?? '') ?>" placeholder="Study Visa, Spouse Visa, Work Permit, Visitor Visa">
      <span class="help">Shown to customers before star selection.</span>
    </div>

    <button class="btn btn-primary" type="submit">Save Business Profile</button>
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
    if (!data.success) throw new Error(data.message || 'No place found.');
    out.innerHTML = data.places.map((p) => `
      <div style="border:1px solid var(--line);border-radius:var(--radius);padding:12px;margin-bottom:8px;background:#fff">
        <strong>${escapeHtml(p.name)}</strong>
        <div class="muted" style="font-size:.88rem;margin:4px 0">${escapeHtml(p.address || '')}</div>
        <button class="btn btn-light" type="button" onclick='selectPlace(${JSON.stringify(p)})'>Use this Google Review link</button>
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

<?php include __DIR__ . '/_footer.php'; ?>
