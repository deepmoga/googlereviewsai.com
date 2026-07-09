<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && $user['password_hash'] === md5($password)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $user['username'];
        header('Location: ' . APP_URL . '/admin/dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Review System</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  * { margin:0;padding:0;box-sizing:border-box; }
  body { background:#0f172a; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Inter',sans-serif; }
  .card { background:#1e293b; border:1px solid #334155; border-radius:16px; padding:48px 40px; width:100%; max-width:400px; }
  .logo { text-align:center; margin-bottom:32px; }
  .logo h1 { color:#f1f5f9; font-size:1.5rem; font-weight:600; }
  .logo p { color:#64748b; font-size:0.85rem; margin-top:4px; }
  .form-group { margin-bottom:20px; }
  label { display:block; font-size:0.8rem; font-weight:500; color:#94a3b8; letter-spacing:0.5px; margin-bottom:8px; text-transform:uppercase; }
  input { width:100%; background:#0f172a; border:1px solid #334155; border-radius:8px; padding:12px 16px; color:#f1f5f9; font-size:0.9rem; outline:none; transition:border-color 0.2s; font-family:'Inter',sans-serif; }
  input:focus { border-color:#3b82f6; }
  .btn { width:100%; background:#3b82f6; color:#fff; border:none; border-radius:8px; padding:13px; font-size:0.9rem; font-weight:500; cursor:pointer; transition:background 0.2s; font-family:'Inter',sans-serif; }
  .btn:hover { background:#2563eb; }
  .error { background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#f87171; border-radius:8px; padding:12px 16px; font-size:0.85rem; margin-bottom:20px; }
  .hint { text-align:center; margin-top:16px; font-size:0.78rem; color:#475569; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h1>⭐ Review Admin</h1>
    <p>Sign in to manage your clients</p>
  </div>
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" required autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" required autocomplete="current-password">
    </div>
    <button class="btn" type="submit">Sign In</button>
  </form>
</div>
</body>
</html>
