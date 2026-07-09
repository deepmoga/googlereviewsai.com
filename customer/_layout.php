<?php
// customer/_layout.php - shared customer dashboard layout
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> - Official AI Review</title>
  <style>
    :root {
      --primary:#058a36;
      --primary-dark:#04662a;
      --gold:#f0b400;
      --bg:#f6fbf7;
      --surface:#ffffff;
      --line:#dce8df;
      --text:#122018;
      --muted:#667569;
      --danger:#dc2626;
      --success:#16a34a;
      --radius:8px;
      --shadow:0 18px 48px rgba(5, 88, 38, .12);
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,Helvetica,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
    a{text-decoration:none;color:inherit}
    .app{display:flex;min-height:100vh}
    .sidebar{width:260px;background:#073f20;color:#fff;padding:22px 14px;position:fixed;inset:0 auto 0 0}
    .brand{display:flex;align-items:center;gap:12px;padding:4px 8px 22px;border-bottom:1px solid rgba(255,255,255,.14);margin-bottom:16px;font-weight:800}
    .brand-icon{width:42px;height:42px;border:2px solid var(--gold);border-radius:var(--radius);display:grid;place-items:center;background:#fff;color:var(--primary);font-weight:900}
    .nav{display:grid;gap:6px}
    .nav a{padding:11px 12px;border-radius:var(--radius);color:rgba(255,255,255,.82);font-weight:700;font-size:.92rem}
    .nav a.active,.nav a:hover{background:rgba(255,255,255,.12);color:#fff}
    .main{margin-left:260px;width:calc(100% - 260px)}
    .topbar{height:72px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:5}
    .topbar h1{font-size:1.25rem}
    .topbar span{color:var(--muted);font-size:.9rem}
    .content{padding:28px;max-width:1180px}
    .grid{display:grid;gap:18px}
    .grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
    .grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
    .card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:22px;box-shadow:0 10px 28px rgba(18,32,24,.04)}
    .card h2{font-size:1.05rem;margin-bottom:12px}
    .muted{color:var(--muted)}
    .stat strong{display:block;font-size:1.7rem;color:var(--primary);line-height:1.1}
    .stat span{display:block;color:var(--muted);margin-top:6px;font-size:.9rem}
    label{display:block;font-size:.78rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:7px}
    input,textarea,select{width:100%;border:1px solid var(--line);border-radius:var(--radius);padding:12px 13px;font-size:.95rem;font-family:Arial,Helvetica,sans-serif;background:#fff;color:var(--text)}
    textarea{min-height:110px;resize:vertical}
    input:focus,textarea:focus,select:focus{outline:2px solid rgba(5,138,54,.16);border-color:var(--primary)}
    .form-group{margin-bottom:16px}
    .help{display:block;margin-top:6px;font-size:.82rem;color:var(--muted)}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:var(--radius);padding:11px 16px;font-weight:800;cursor:pointer;font-size:.92rem}
    .btn-primary{background:var(--primary);color:#fff}
    .btn-primary:hover{background:var(--primary-dark)}
    .btn-gold{background:var(--gold);color:#1d1700}
    .btn-light{background:#eef7f1;color:var(--primary);border:1px solid rgba(5,138,54,.18)}
    .btn-danger{background:#fee2e2;color:var(--danger)}
    .actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .alert{padding:12px 14px;border-radius:var(--radius);margin-bottom:16px;font-size:.92rem}
    .alert-success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
    .alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
    .alert-info{background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe}
    .badge{display:inline-flex;border-radius:999px;padding:5px 10px;font-size:.78rem;font-weight:800}
    .badge-green{background:#dcfce7;color:#166534}
    .badge-red{background:#fee2e2;color:#991b1b}
    table{width:100%;border-collapse:collapse}
    th,td{text-align:left;padding:11px;border-bottom:1px solid var(--line);font-size:.9rem;vertical-align:top}
    th{font-size:.75rem;text-transform:uppercase;color:var(--muted);letter-spacing:.05em}
    .mobile-nav{display:none}
    @media(max-width:860px){
      .sidebar{display:none}
      .main{margin-left:0;width:100%}
      .mobile-nav{display:flex;gap:8px;overflow:auto;padding:10px 14px;background:#073f20}
      .mobile-nav a{white-space:nowrap;color:#fff;background:rgba(255,255,255,.12);padding:9px 12px;border-radius:999px;font-size:.84rem;font-weight:800}
      .topbar{height:auto;align-items:flex-start;gap:6px;flex-direction:column;padding:16px}
      .content{padding:16px}
      .grid-2,.grid-3{grid-template-columns:1fr}
      .actions .btn{width:100%}
    }
  </style>
</head>
<body>
<?php $activeNav = $activeNav ?? ''; ?>
<div class="app">
  <aside class="sidebar">
    <div class="brand"><span class="brand-icon">G★</span><span>Official AI Review</span></div>
    <nav class="nav">
      <a class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/dashboard.php">Dashboard</a>
      <a class="<?= $activeNav === 'profile' ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/profile.php">Business Profile</a>
      <a class="<?= $activeNav === 'billing' ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/billing.php">Plans & Addons</a>
      <a href="<?= APP_URL ?>/customer/logout.php">Logout</a>
    </nav>
  </aside>
  <main class="main">
    <nav class="mobile-nav">
      <a href="<?= APP_URL ?>/customer/dashboard.php">Dashboard</a>
      <a href="<?= APP_URL ?>/customer/profile.php">Profile</a>
      <a href="<?= APP_URL ?>/customer/billing.php">Plans</a>
      <a href="<?= APP_URL ?>/customer/logout.php">Logout</a>
    </nav>
    <div class="topbar">
      <h1><?= htmlspecialchars($pageTitle ?? '') ?></h1>
      <span><?= htmlspecialchars($customer['name'] ?? '') ?> <?= !empty($customer['phone']) ? '• +' . htmlspecialchars($customer['phone']) : '' ?></span>
    </div>
    <div class="content">
