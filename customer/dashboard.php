<?php
require_once __DIR__ . '/../config.php';
$customer = requireCustomerLogin();
$client = customerClient($customer['id']);
$subscription = activeCustomerSubscription($customer['id']);

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/_layout.php';
?>

<div class="grid grid-3">
  <div class="card stat">
    <strong><?= $subscription ? 'Active' : 'Inactive' ?></strong>
    <span>Plan status</span>
  </div>
  <div class="card stat">
    <strong><?= $subscription ? date('d M Y', strtotime($subscription['expires_at'])) : '-' ?></strong>
    <span>Plan expiry</span>
  </div>
  <div class="card stat">
    <strong><?= $client ? 'Ready' : 'Pending' ?></strong>
    <span>Business profile</span>
  </div>
</div>

<?php if (!$subscription): ?>
  <div class="alert alert-info">
    Your plan is not active. Buy a plan to activate your public Google Review page.
  </div>
<?php endif; ?>

<?php if (!$client): ?>
  <div class="card">
    <h2>Create your review page</h2>
    <p class="muted" style="margin-bottom:16px">Add your company name, Google Review link, AI instructions, and service buttons.</p>
    <a class="btn btn-primary" href="<?= APP_URL ?>/customer/profile.php">Create Business Profile</a>
  </div>
<?php else: ?>
  <?php $reviewUrl = APP_URL . '/review.php?c=' . $client['slug']; ?>
  <div class="card">
    <h2><?= htmlspecialchars($client['company_name']) ?></h2>
    <p class="muted" style="margin-bottom:16px"><?= htmlspecialchars($client['tagline'] ?: ($subscription ? 'Your Google Review page is ready.' : 'Buy a plan to unlock your review page preview and sharing tools.')) ?></p>
    <div class="actions">
      <a class="btn btn-light" href="<?= APP_URL ?>/customer/profile.php">Edit Profile</a>
      <?php if ($subscription): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars($reviewUrl) ?>" target="_blank">Open Review Page</a>
        <a class="btn btn-gold" href="<?= APP_URL ?>/customer/generate-pdf.php">Download PDF</a>
        <button class="btn btn-gold" type="button" onclick="copyText('<?= htmlspecialchars($reviewUrl, ENT_QUOTES) ?>')">Copy Link</button>
      <?php else: ?>
        <a class="btn btn-primary" href="<?= APP_URL ?>/customer/billing.php">Buy Plan</a>
      <?php endif; ?>
    </div>
    <?php if ($subscription): ?>
      <div style="margin-top:18px;padding:12px;border:1px solid var(--line);border-radius:var(--radius);word-break:break-all;color:var(--muted)">
        <?= htmlspecialchars($reviewUrl) ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="grid grid-2">
  <div class="card">
    <h2>Next steps</h2>
    <p class="muted">Share your QR code with customers after service. They can select a rating, generate AI review text, copy it, and post on Google Review.</p>
  </div>
  <div class="card">
    <h2>Need printed QR cards?</h2>
    <p class="muted" style="margin-bottom:14px">Buy the Print Card addon anytime. Addons do not expire and can be purchased multiple times.</p>
    <a class="btn btn-light" href="<?= APP_URL ?>/customer/billing.php">View Addons</a>
  </div>
</div>

<script>
function copyText(text) {
  navigator.clipboard.writeText(text).then(function(){ alert('Copied'); });
}
</script>

<?php include __DIR__ . '/_footer.php'; ?>
