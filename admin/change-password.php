<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$db = getDB();
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$_SESSION['admin_username']]);
    $user = $stmt->fetch();

    if ($user['password_hash'] !== md5($current)) {
        $msg = 'Current password is incorrect.';
        $msgType = 'error';
    } elseif (strlen($new) < 6) {
        $msg = 'New password must be at least 6 characters.';
        $msgType = 'error';
    } elseif ($new !== $confirm) {
        $msg = 'Passwords do not match.';
        $msgType = 'error';
    } else {
        $hash = md5($new);
        $db->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?")->execute([$hash, $_SESSION['admin_username']]);
        $msg = 'Password changed successfully!';
    }
}

$pageTitle = 'Change Password';
$activeNav = 'password';
include __DIR__ . '/_layout.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card" style="max-width:480px">
  <div class="card-header">
    <span class="card-title">Change Password</span>
  </div>
  <form method="POST">
    <div class="form-grid">
      <div class="form-group">
        <label>Current Password</label>
        <input type="password" name="current_password" required>
      </div>
      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="new_password" required minlength="6">
      </div>
      <div class="form-group">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required minlength="6">
      </div>
      <div>
        <button class="btn btn-primary" type="submit">🔑 Change Password</button>
      </div>
    </div>
  </form>
</div>

  </div>
</div>
</body>
</html>
