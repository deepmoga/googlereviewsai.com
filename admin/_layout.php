<?php
// admin/_layout.php - shared admin layout
// Usage: include with $pageTitle and $activeNav set
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — Review System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0f172a;
      --sidebar: #1e293b;
      --surface: #1e293b;
      --surface2: #273349;
      --border: #334155;
      --primary: #3b82f6;
      --primary-hover: #2563eb;
      --danger: #ef4444;
      --success: #22c55e;
      --warning: #f59e0b;
      --text: #f1f5f9;
      --muted: #64748b;
      --muted2: #94a3b8;
      --gold: #f59e0b;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', sans-serif;
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 240px;
      background: var(--sidebar);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      z-index: 100;
    }

    .sidebar-brand {
      padding: 24px 20px;
      border-bottom: 1px solid var(--border);
    }

    .sidebar-brand h1 {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text);
    }

    .sidebar-brand p {
      font-size: 0.75rem;
      color: var(--muted);
      margin-top: 2px;
    }

    .sidebar-nav {
      flex: 1;
      padding: 16px 12px;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 8px;
      text-decoration: none;
      color: var(--muted2);
      font-size: 0.875rem;
      font-weight: 500;
      transition: all 0.15s;
    }

    .nav-item:hover {
      background: var(--surface2);
      color: var(--text);
    }

    .nav-item.active {
      background: rgba(59, 130, 246, 0.15);
      color: var(--primary);
    }

    .nav-icon {
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
    }

    .sidebar-footer {
      padding: 16px 12px;
      border-top: 1px solid var(--border);
    }

    .sidebar-footer a {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--muted);
      text-decoration: none;
      font-size: 0.8rem;
      padding: 8px 12px;
      border-radius: 8px;
      transition: all 0.15s;
    }

    .sidebar-footer a:hover {
      background: rgba(239, 68, 68, 0.1);
      color: #f87171;
    }

    /* Main content */
    .main {
      margin-left: 240px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .topbar {
      background: var(--sidebar);
      border-bottom: 1px solid var(--border);
      padding: 16px 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .topbar h2 {
      font-size: 1.1rem;
      font-weight: 600;
    }

    .topbar .admin-badge {
      font-size: 0.78rem;
      color: var(--muted);
    }

    .content {
      padding: 32px;
      flex: 1;
    }

    /* Cards */
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
    }

    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .card-title {
      font-size: 1rem;
      font-weight: 600;
    }

    /* Form styles */
    .form-grid {
      display: grid;
      gap: 20px;
    }

    .form-grid-2 {
      grid-template-columns: 1fr 1fr;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .form-group.full {
      grid-column: 1 / -1;
    }

    label {
      font-size: 0.78rem;
      font-weight: 500;
      color: var(--muted2);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    input[type=text],
    input[type=url],
    input[type=password],
    input[type=email],
    input[type=number],
    input[type=date],
    select,
    textarea {
      background: #0f172a;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 10px 14px;
      color: var(--text);
      font-size: 0.875rem;
      outline: none;
      transition: border-color 0.2s;
      font-family: 'Inter', sans-serif;
      width: 100%;
    }

    input:focus,
    select:focus,
    textarea:focus {
      border-color: var(--primary);
    }

    textarea {
      resize: vertical;
      min-height: 100px;
    }

    /* Buttons */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 9px 18px;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 500;
      cursor: pointer;
      border: none;
      text-decoration: none;
      transition: all 0.15s;
      font-family: 'Inter', sans-serif;
    }

    .btn-primary {
      background: var(--primary);
      color: #fff;
    }

    .btn-primary:hover {
      background: var(--primary-hover);
    }

    .btn-danger {
      background: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .btn-danger:hover {
      background: rgba(239, 68, 68, 0.25);
    }

    .btn-success {
      background: rgba(34, 197, 94, 0.15);
      color: #4ade80;
      border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .btn-ghost {
      background: transparent;
      color: var(--muted2);
      border: 1px solid var(--border);
    }

    .btn-ghost:hover {
      background: var(--surface2);
      color: var(--text);
    }

    .btn-sm {
      padding: 6px 12px;
      font-size: 0.78rem;
    }

    /* Tables */
    table {
      width: 100%;
      border-collapse: collapse;
    }

    th {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--muted);
      padding: 10px 12px;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }

    td {
      padding: 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      font-size: 0.875rem;
      vertical-align: middle;
    }

    tr:last-child td {
      border-bottom: none;
    }

    tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }

    /* Badges */
    .badge {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 0.72rem;
      font-weight: 500;
    }

    .badge-green {
      background: rgba(34, 197, 94, 0.15);
      color: #4ade80;
      border: 1px solid rgba(34, 197, 94, 0.25);
    }

    .badge-red {
      background: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.25);
    }

    /* Alert */
    .alert {
      border-radius: 8px;
      padding: 12px 16px;
      font-size: 0.85rem;
      margin-bottom: 20px;
    }

    .alert-success {
      background: rgba(34, 197, 94, 0.1);
      border: 1px solid rgba(34, 197, 94, 0.3);
      color: #4ade80;
    }

    .alert-error {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #f87171;
    }

    .alert-info {
      background: rgba(59, 130, 246, 0.1);
      border: 1px solid rgba(59, 130, 246, 0.3);
      color: #93c5fd;
    }

    /* Link copy */
    .link-copy {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #0f172a;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 0.8rem;
      color: var(--primary);
      word-break: break-all;
    }

    .link-copy button {
      background: none;
      border: none;
      cursor: pointer;
      color: var(--muted);
      flex-shrink: 0;
      padding: 2px;
      transition: color 0.15s;
    }

    .link-copy button:hover {
      color: var(--text);
    }

    /* Logo preview */
    .logo-preview {
      width: 60px;
      height: 60px;
      border-radius: 8px;
      background: var(--surface2);
      border: 1px solid var(--border);
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .logo-preview img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      padding: 4px;
    }

    /* Stats */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px 24px;
    }

    .stat-card .val {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .stat-card .lbl {
      font-size: 0.78rem;
      color: var(--muted);
    }

    @media (max-width: 768px) {
      .sidebar {
        display: none;
      }

      .main {
        margin-left: 0;
      }

      .form-grid-2 {
        grid-template-columns: 1fr;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>

  <aside class="sidebar">
    <div class="sidebar-brand">
      <h1>⭐ Review System</h1>
      <p>Admin Panel</p>
    </div>
    <nav class="sidebar-nav">
      <a class="nav-item <?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/dashboard.php">
        <span class="nav-icon">📊</span> Dashboard
      </a>
      <a class="nav-item <?= ($activeNav ?? '') === 'clients' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/clients.php">
        <span class="nav-icon">🏢</span> Clients
      </a>
      <a class="nav-item <?= ($activeNav ?? '') === 'customers' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/customers.php">
        <span class="nav-icon">👥</span> Customers
      </a>
      <a class="nav-item <?= ($activeNav ?? '') === 'plans' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/plans.php">
        <span class="nav-icon">₹</span> Plans & Addons
      </a>
      <a class="nav-item <?= ($activeNav ?? '') === 'settings' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/settings.php">
        <span class="nav-icon">⚙️</span> Settings
      </a>
      <a class="nav-item <?= ($activeNav ?? '') === 'password' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/change-password.php">
        <span class="nav-icon">🔑</span> Change Password
      </a>
    </nav>
    <div class="sidebar-footer">
      <a href="<?= APP_URL ?>/admin/logout.php">
        <span>🚪</span> Logout (<?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?>)
      </a>
    </div>
  </aside>

  <div class="main">
    <div class="topbar">
      <h2><?= htmlspecialchars($pageTitle ?? '') ?></h2>
      <span class="admin-badge">Logged in as <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?></span>
    </div>
    <div class="content">
