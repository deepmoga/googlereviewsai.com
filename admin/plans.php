<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$db = getDB();
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || $price < 0) {
        $msg = 'Name and valid price are required.';
        $msgType = 'error';
    } elseif ($type === 'plan') {
        $duration = intval($_POST['duration_days'] ?? 0);
        if ($duration < 1) {
            $msg = 'Plan duration is required.';
            $msgType = 'error';
        } elseif ($id) {
            $db->prepare("UPDATE plans SET name=?, price=?, duration_days=?, description=?, is_active=? WHERE id=?")
                ->execute([$name, $price, $duration, $description, $isActive, $id]);
            $msg = 'Plan updated.';
        } else {
            $db->prepare("INSERT INTO plans (name, price, duration_days, description, is_active) VALUES (?, ?, ?, ?, ?)")
                ->execute([$name, $price, $duration, $description, $isActive]);
            $msg = 'Plan added.';
        }
    } elseif ($type === 'addon') {
        if ($id) {
            $db->prepare("UPDATE addons SET name=?, price=?, description=?, is_active=? WHERE id=?")
                ->execute([$name, $price, $description, $isActive, $id]);
            $msg = 'Addon updated.';
        } else {
            $db->prepare("INSERT INTO addons (name, price, description, is_active) VALUES (?, ?, ?, ?)")
                ->execute([$name, $price, $description, $isActive]);
            $msg = 'Addon added.';
        }
    }
}

$plans = $db->query("SELECT * FROM plans ORDER BY id DESC")->fetchAll();
$addons = $db->query("SELECT * FROM addons ORDER BY id DESC")->fetchAll();

$pageTitle = 'Plans & Addons';
$activeNav = 'plans';
include __DIR__ . '/_layout.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><span class="card-title">Add Plan</span></div>
  <form method="POST">
    <input type="hidden" name="type" value="plan">
    <div class="form-grid form-grid-2">
      <div class="form-group"><label>Name</label><input type="text" name="name" required placeholder="Monthly Plan"></div>
      <div class="form-group"><label>Price ₹</label><input type="number" step="0.01" name="price" required placeholder="1000"></div>
      <div class="form-group"><label>Duration Days</label><input type="number" name="duration_days" required placeholder="30"></div>
      <div class="form-group"><label>Status</label><label style="text-transform:none;letter-spacing:0"><input type="checkbox" name="is_active" checked style="width:auto"> Active</label></div>
      <div class="form-group full"><label>Description</label><textarea name="description"></textarea></div>
    </div>
    <button class="btn btn-primary" type="submit">Add Plan</button>
  </form>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Plans</span></div>
  <table>
    <thead><tr><th>Name</th><th>Price</th><th>Days</th><th>Status</th><th>Quick Edit</th></tr></thead>
    <tbody>
    <?php foreach ($plans as $plan): ?>
      <tr>
        <form method="POST">
          <input type="hidden" name="type" value="plan">
          <input type="hidden" name="id" value="<?= $plan['id'] ?>">
          <td><input type="text" name="name" value="<?= htmlspecialchars($plan['name']) ?>"></td>
          <td><input type="number" step="0.01" name="price" value="<?= htmlspecialchars($plan['price']) ?>"></td>
          <td><input type="number" name="duration_days" value="<?= htmlspecialchars($plan['duration_days']) ?>"></td>
          <td><label style="text-transform:none;letter-spacing:0"><input type="checkbox" name="is_active" <?= $plan['is_active'] ? 'checked' : '' ?> style="width:auto"> Active</label></td>
          <td>
            <textarea name="description" style="min-height:60px"><?= htmlspecialchars($plan['description'] ?? '') ?></textarea>
            <button class="btn btn-primary btn-sm" type="submit">Save</button>
          </td>
        </form>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Add Addon</span></div>
  <form method="POST">
    <input type="hidden" name="type" value="addon">
    <div class="form-grid form-grid-2">
      <div class="form-group"><label>Name</label><input type="text" name="name" required placeholder="Print Card"></div>
      <div class="form-group"><label>Price ₹</label><input type="number" step="0.01" name="price" required placeholder="500"></div>
      <div class="form-group"><label>Status</label><label style="text-transform:none;letter-spacing:0"><input type="checkbox" name="is_active" checked style="width:auto"> Active</label></div>
      <div class="form-group full"><label>Description</label><textarea name="description"></textarea></div>
    </div>
    <button class="btn btn-primary" type="submit">Add Addon</button>
  </form>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Addons</span></div>
  <table>
    <thead><tr><th>Name</th><th>Price</th><th>Status</th><th>Quick Edit</th></tr></thead>
    <tbody>
    <?php foreach ($addons as $addon): ?>
      <tr>
        <form method="POST">
          <input type="hidden" name="type" value="addon">
          <input type="hidden" name="id" value="<?= $addon['id'] ?>">
          <td><input type="text" name="name" value="<?= htmlspecialchars($addon['name']) ?>"></td>
          <td><input type="number" step="0.01" name="price" value="<?= htmlspecialchars($addon['price']) ?>"></td>
          <td><label style="text-transform:none;letter-spacing:0"><input type="checkbox" name="is_active" <?= $addon['is_active'] ? 'checked' : '' ?> style="width:auto"> Active</label></td>
          <td>
            <textarea name="description" style="min-height:60px"><?= htmlspecialchars($addon['description'] ?? '') ?></textarea>
            <button class="btn btn-primary btn-sm" type="submit">Save</button>
          </td>
        </form>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

  </div>
</div>
</body>
</html>
