<?php
// ============================================
// review.php - Client Review Landing Page
// ============================================
require_once __DIR__ . '/config.php';

$slug = $_GET['c'] ?? '';
if (empty($slug)) {
    http_response_code(404);
    die('Client not found.');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM clients WHERE slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$client = $stmt->fetch();

if (!$client) {
    http_response_code(404);
    die('Client not found.');
}

$isExpired = false;
if (!empty($client['link_expire_at'])) {
  $isExpired = strtotime($client['link_expire_at']) < time();
}
$hasActivePlan = clientHasActiveSubscription($client);

$serviceOptions = [];
if (!empty($client['service_options'])) {
  $serviceOptions = array_values(array_filter(array_map('trim', explode(',', $client['service_options']))));
}

$logoUrl = $client['logo_path'] ? UPLOAD_URL . $client['logo_path'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Review <?= htmlspecialchars($client['company_name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --gold: #D4A853;
    --gold-light: #F2D08A;
    --gold-dark: #A07830;
    --bg: #0D0D0D;
    --surface: #161616;
    --surface2: #1E1E1E;
    --border: #2A2A2A;
    --text: #F0EDE8;
    --muted: #888;
    --radius: 16px;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 40px 20px 60px;
    position: relative;
    overflow-x: hidden;
  }

  /* Ambient background glow */
  body::before {
    content: '';
    position: fixed;
    top: -30%;
    left: 50%;
    transform: translateX(-50%);
    width: 800px;
    height: 600px;
    background: radial-gradient(ellipse, rgba(212,168,83,0.07) 0%, transparent 70%);
    pointer-events: none;
    z-index: 0;
  }

  .container {
    max-width: 600px;
    width: 100%;
    position: relative;
    z-index: 1;
  }

  /* Hero Card */
  .hero {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 48px 40px 40px;
    text-align: center;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
  }

  .hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
  }

  .logo-wrap {
    width: 100px;
    height: 100px;
    border-radius: 20px;
    overflow: hidden;
    margin: 0 auto 24px;
    border: 2px solid var(--border);
    background: var(--surface2);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .logo-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 8px;
  }

  .logo-placeholder {
    font-size: 2.5rem;
    font-family: 'Playfair Display', serif;
    color: var(--gold);
    font-weight: 700;
  }

  .company-name {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -0.5px;
    margin-bottom: 8px;
  }

  .tagline {
    font-size: 0.95rem;
    color: var(--muted);
    font-weight: 300;
    letter-spacing: 0.3px;
  }

  /* Star Rating Section */
  .rating-section {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 36px 40px;
    text-align: center;
    margin-bottom: 24px;
  }

  .service-section {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 28px 24px;
    text-align: center;
    margin-bottom: 24px;
  }

  .service-title {
    font-size: 0.8rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 16px;
    font-weight: 500;
  }

  .service-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
  }

  .service-btn {
    border: 1px solid var(--border);
    background: var(--surface2);
    color: var(--text);
    border-radius: 999px;
    padding: 9px 14px;
    font-size: 0.84rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .service-btn:hover {
    border-color: rgba(212,168,83,0.45);
  }

  .service-btn.active {
    border-color: var(--gold);
    background: rgba(212,168,83,0.16);
    color: var(--gold-light);
  }

  .service-hint {
    margin-top: 12px;
    color: var(--muted);
    font-size: 0.8rem;
    min-height: 18px;
  }

  .rating-label {
    font-size: 0.8rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 20px;
    font-weight: 500;
  }

  .stars {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 12px;
  }

  .star {
    font-size: 2.8rem;
    cursor: pointer;
    color: var(--border);
    transition: all 0.15s ease;
    line-height: 1;
    user-select: none;
    position: relative;
  }

  .star:hover,
  .star.active {
    color: var(--gold);
    transform: scale(1.15);
    filter: drop-shadow(0 0 8px rgba(212,168,83,0.5));
  }

  .star.hovered {
    color: var(--gold-light);
    transform: scale(1.1);
  }

  .star-hint {
    font-size: 0.85rem;
    color: var(--muted);
    height: 20px;
    transition: opacity 0.2s;
  }

  /* Loading state */
  .loading {
    display: none;
    text-align: center;
    padding: 40px;
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--border);
    border-top-color: var(--gold);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 16px;
  }

  @keyframes spin { to { transform: rotate(360deg); } }

  .loading-text {
    color: var(--muted);
    font-size: 0.9rem;
    animation: pulse 1.5s ease-in-out infinite;
  }

  @keyframes pulse { 0%,100% { opacity: 0.5; } 50% { opacity: 1; } }

  /* Reviews grid */
  .reviews-section {
    display: none;
  }

  .reviews-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding: 0 4px;
  }

  .reviews-header h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text);
  }

  .reviews-header .badge {
    background: rgba(212,168,83,0.15);
    color: var(--gold);
    border: 1px solid rgba(212,168,83,0.3);
    border-radius: 20px;
    font-size: 0.75rem;
    padding: 2px 10px;
    font-weight: 500;
  }

  .review-cards {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .review-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px 24px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    animation: slideIn 0.4s ease forwards;
    opacity: 0;
    transform: translateY(16px);
  }

  @keyframes slideIn {
    to { opacity: 1; transform: translateY(0); }
  }

  .review-card:nth-child(1) { animation-delay: 0.05s; }
  .review-card:nth-child(2) { animation-delay: 0.1s; }
  .review-card:nth-child(3) { animation-delay: 0.15s; }
  .review-card:nth-child(4) { animation-delay: 0.2s; }
  .review-card:nth-child(5) { animation-delay: 0.25s; }

  .review-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
    transform: scaleX(0);
    transition: transform 0.3s ease;
  }

  .review-card:hover {
    border-color: rgba(212,168,83,0.4);
    background: var(--surface2);
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
  }

  .review-card:hover::after {
    transform: scaleX(1);
  }

  .review-text {
    font-size: 0.92rem;
    line-height: 1.65;
    color: #D0CCC8;
    margin-bottom: 14px;
  }

  .review-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .tap-hint {
    font-size: 0.75rem;
    color: var(--muted);
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .tap-hint svg {
    width: 14px;
    height: 14px;
    opacity: 0.6;
  }

  .copy-badge {
    background: rgba(212,168,83,0.12);
    color: var(--gold);
    border: 1px solid rgba(212,168,83,0.25);
    border-radius: 8px;
    font-size: 0.72rem;
    padding: 3px 10px;
    font-weight: 500;
    transition: all 0.2s;
  }

  .review-card:hover .copy-badge {
    background: rgba(212,168,83,0.2);
  }

  /* Toast notification */
  .toast {
    position: fixed;
    bottom: 32px;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: #1a2e1a;
    border: 1px solid #2d5a2d;
    color: #7dca7d;
    padding: 14px 24px;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    z-index: 1000;
    white-space: nowrap;
  }

  .toast.show {
    transform: translateX(-50%) translateY(0);
  }

  /* Error state */
  .error-msg {
    background: rgba(220,60,60,0.1);
    border: 1px solid rgba(220,60,60,0.3);
    color: #ff8080;
    border-radius: var(--radius);
    padding: 16px 20px;
    font-size: 0.875rem;
    text-align: center;
    display: none;
  }

  .expired-msg {
    background: rgba(220,60,60,0.1);
    border: 1px solid rgba(220,60,60,0.4);
    color: #ff9a9a;
    border-radius: var(--radius);
    padding: 20px;
    text-align: center;
    margin-bottom: 24px;
  }

  .footer-note {
    text-align: center;
    color: var(--muted);
    font-size: 0.78rem;
    margin-top: 32px;
    opacity: 0.6;
  }

  @media (max-width: 480px) {
    .hero { padding: 36px 24px 32px; }
    .rating-section { padding: 28px 24px; }
    .company-name { font-size: 1.6rem; }
    .star { font-size: 2.4rem; }
  }
</style>
</head>
<body>

<div class="container">
  <!-- Hero Card -->
  <div class="hero">
    <div class="logo-wrap">
      <?php if ($logoUrl): ?>
        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($client['company_name']) ?> logo">
      <?php else: ?>
        <div class="logo-placeholder"><?= strtoupper(substr($client['company_name'], 0, 1)) ?></div>
      <?php endif; ?>
    </div>
    <h1 class="company-name"><?= htmlspecialchars($client['company_name']) ?></h1>
    <?php if ($client['tagline']): ?>
      <p class="tagline"><?= htmlspecialchars($client['tagline']) ?></p>
    <?php endif; ?>
  </div>

  <!-- Star Rating -->
  <?php if ($isExpired || !$hasActivePlan): ?>
    <div class="expired-msg">
      <strong><?= $isExpired ? 'Plan Expired' : 'Plan Not Active' ?></strong><br>
      This review link is not active. Please contact the business owner to reactivate it.
    </div>
  <?php else: ?>
    <?php if (!empty($serviceOptions)): ?>
      <div class="service-section">
        <p class="service-title">Select Service</p>
        <div class="service-buttons">
          <?php foreach ($serviceOptions as $service): ?>
            <button class="service-btn" type="button" onclick="selectService('<?= htmlspecialchars($service, ENT_QUOTES) ?>', this)"><?= htmlspecialchars($service) ?></button>
          <?php endforeach; ?>
        </div>
        <div class="service-hint" id="serviceHint">Pick your service first, then select star rating.</div>
      </div>
    <?php endif; ?>

    <div class="rating-section">
      <p class="rating-label">How was your experience?</p>
      <div class="stars" id="stars">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <span class="star" data-rating="<?= $i ?>" onclick="selectRating(<?= $i ?>)"
                onmouseenter="hoverStars(<?= $i ?>)"
                onmouseleave="resetHover()">★</span>
        <?php endfor; ?>
      </div>
      <div class="star-hint" id="starHint">Tap a star to get review suggestions</div>
    </div>
  <?php endif; ?>

  <!-- Loading -->
  <div class="loading" id="loading">
    <div class="spinner"></div>
    <p class="loading-text">Crafting your reviews...</p>
  </div>

  <!-- Error -->
  <div class="error-msg" id="errorMsg"></div>

  <!-- Reviews -->
  <div class="reviews-section" id="reviewsSection">
    <div class="reviews-header">
      <h3>Pick a review to share</h3>
      <span class="badge" id="ratingBadge"></span>
    </div>
    <div class="review-cards" id="reviewCards"></div>
  </div>

  <p class="footer-note">Tap any review to copy & open Google Reviews</p>
</div>

<div class="toast" id="toast">✓ Copied! Opening Google Reviews...</div>

<script>
const CLIENT_ID = <?= $client['id'] ?>;
const API_URL = '<?= APP_URL ?>/api/generate-reviews.php';
const GOOGLE_LINK = <?= json_encode($client['google_review_link']) ?>;
const HAS_SERVICE_OPTIONS = <?= !empty($serviceOptions) ? 'true' : 'false' ?>;
let selectedService = '';

const starLabels = {
  1: '⭐ Very Poor',
  2: '⭐⭐ Poor',
  3: '⭐⭐⭐ Average',
  4: '⭐⭐⭐⭐ Good',
  5: '⭐⭐⭐⭐⭐ Excellent'
};

const hintLabels = {
  1: 'Very Poor — 1 Star',
  2: 'Poor — 2 Stars',
  3: 'Average — 3 Stars',
  4: 'Good — 4 Stars',
  5: 'Excellent — 5 Stars'
};

function hoverStars(n) {
  if (HAS_SERVICE_OPTIONS && !selectedService) {
    return;
  }
  document.querySelectorAll('.star').forEach((s, i) => {
    s.classList.toggle('hovered', i < n);
  });
  document.getElementById('starHint').textContent = hintLabels[n];
}

function resetHover() {
  document.querySelectorAll('.star').forEach(s => s.classList.remove('hovered'));
  if (HAS_SERVICE_OPTIONS && !selectedService) {
    document.getElementById('starHint').textContent = 'Select service first to continue';
  } else {
    document.getElementById('starHint').textContent = 'Tap a star to get review suggestions';
  }
}

function selectService(service, btn) {
  selectedService = service;
  document.querySelectorAll('.service-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  const serviceHint = document.getElementById('serviceHint');
  if (serviceHint) {
    serviceHint.textContent = 'Selected: ' + service;
  }

  document.getElementById('starHint').textContent = 'Now tap a star to generate reviews';
}

async function selectRating(rating) {
  if (HAS_SERVICE_OPTIONS && !selectedService) {
    document.getElementById('starHint').textContent = 'Please select a service first';
    return;
  }

  // Update stars UI
  document.querySelectorAll('.star').forEach((s, i) => {
    s.classList.toggle('active', i < rating);
  });
  document.getElementById('starHint').textContent = `Generating ${rating}-star reviews...`;

  // Show loading
  document.getElementById('loading').style.display = 'block';
  document.getElementById('reviewsSection').style.display = 'none';
  document.getElementById('errorMsg').style.display = 'none';

  try {
    const res = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ client_id: CLIENT_ID, rating, service: selectedService })
    });

    const data = await res.json();

    if (data.error) throw new Error(data.error);

    renderReviews(data.reviews, rating);
  } catch (err) {
    document.getElementById('loading').style.display = 'none';
    const errEl = document.getElementById('errorMsg');
    errEl.style.display = 'block';
    errEl.textContent = 'Error: ' + (err.message || 'Failed to generate reviews. Please try again.');
  }
}

function renderReviews(reviews, rating) {
  document.getElementById('loading').style.display = 'none';

  const badge = document.getElementById('ratingBadge');
  badge.textContent = starLabels[rating];

  const container = document.getElementById('reviewCards');
  container.innerHTML = '';

  reviews.forEach(text => {
    const card = document.createElement('div');
    card.className = 'review-card';
    card.innerHTML = `
      <p class="review-text">${escapeHtml(text)}</p>
      <div class="review-footer">
        <span class="tap-hint">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
          </svg>
          Tap to copy & open
        </span>
        <span class="copy-badge">Copy & Go</span>
      </div>
    `;
    card.addEventListener('click', () => copyAndRedirect(text, card));
    container.appendChild(card);
  });

  document.getElementById('reviewsSection').style.display = 'block';
  document.getElementById('starHint').textContent = `${reviews.length} reviews generated — pick one!`;
}

function copyAndRedirect(text, card) {
  // Copy to clipboard
  navigator.clipboard.writeText(text).then(() => {
    // Visual feedback on card
    card.style.borderColor = 'rgba(212,168,83,0.7)';
    card.style.background = 'rgba(212,168,83,0.05)';

    // Show toast
    showToast();

    // Redirect after short delay
    setTimeout(() => {
      window.open(GOOGLE_LINK, '_blank');
    }, 800);
  }).catch(() => {
    // Fallback for older browsers
    const ta = document.createElement('textarea');
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showToast();
    setTimeout(() => window.open(GOOGLE_LINK, '_blank'), 800);
  });
}

function showToast() {
  const toast = document.getElementById('toast');
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

function escapeHtml(text) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(text));
  return d.innerHTML;
}
</script>
</body>
</html>
