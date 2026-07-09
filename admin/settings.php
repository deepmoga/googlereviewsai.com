<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$db = getDB();
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aiProvider = trim($_POST['ai_provider'] ?? 'openai');
    $apiKey = trim($_POST['openai_api_key'] ?? '');
    $model = trim($_POST['openai_model'] ?? 'gpt-4o-mini');
    $geminiKey = trim($_POST['gemini_api_key'] ?? '');
    $geminiModel = trim($_POST['gemini_model'] ?? 'gemini-2.0-flash');
    $reviews = intval($_POST['reviews_per_click'] ?? 5);
    $wordLimit = intval($_POST['review_word_limit'] ?? 60);
    $whatsappBaseUrl = trim($_POST['whatsapp_api_base_url'] ?? '');
    $whatsappToken = trim($_POST['whatsapp_api_token'] ?? '');
    $whatsappTemplate = trim($_POST['whatsapp_otp_template'] ?? 'test_demo');
    $whatsappLanguage = trim($_POST['whatsapp_otp_language'] ?? 'en_US');
    $razorpayKeyId = trim($_POST['razorpay_key_id'] ?? '');
    $razorpayKeySecret = trim($_POST['razorpay_key_secret'] ?? '');
    $googleMapsApiKey = trim($_POST['google_maps_api_key'] ?? '');

    if ($reviews < 1 || $reviews > 10) $reviews = 5;
    if ($wordLimit < 10 || $wordLimit > 500) $wordLimit = 60;
    if (!in_array($aiProvider, ['openai', 'gemini'])) $aiProvider = 'openai';

    $updates = [
        'ai_provider' => $aiProvider,
        'openai_api_key' => $apiKey,
        'openai_model' => $model,
        'gemini_api_key' => $geminiKey,
        'gemini_model' => $geminiModel,
        'reviews_per_click' => $reviews,
        'review_word_limit' => $wordLimit,
        'whatsapp_api_base_url' => $whatsappBaseUrl ?: 'https://site10.officialdigitalmarketing.in/api',
        'whatsapp_api_token' => $whatsappToken,
        'whatsapp_otp_template' => $whatsappTemplate ?: 'test_demo',
        'whatsapp_otp_language' => $whatsappLanguage ?: 'en_US',
        'razorpay_key_id' => $razorpayKeyId,
        'razorpay_key_secret' => $razorpayKeySecret,
        'google_maps_api_key' => $googleMapsApiKey
    ];

    foreach ($updates as $k => $v) {
        $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$k, $v, $v]);
    }

    $msg = 'Settings saved!';
}

$pageTitle = 'Global Settings';
$activeNav = 'settings';
include __DIR__ . '/_layout.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <span class="card-title">AI Provider Settings</span>
  </div>

  <form method="POST">
    <div class="form-grid" style="max-width:600px">

      <div class="form-group">
        <label>AI Provider</label>
        <?php $currentProvider = getSetting('ai_provider') ?: 'openai'; ?>
        <select name="ai_provider" id="ai_provider" onchange="toggleProviderFields()">
          <option value="openai" <?= $currentProvider === 'openai' ? 'selected' : '' ?>>OpenAI (ChatGPT)</option>
          <option value="gemini" <?= $currentProvider === 'gemini' ? 'selected' : '' ?>>Google Gemini</option>
        </select>
        <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">Choose which AI provider to use for generating reviews.</small>
      </div>

      <!-- OpenAI Fields -->
      <div id="openai_fields">
        <div class="form-group">
          <label>OpenAI API Key *</label>
          <input type="password" name="openai_api_key"
                 value="<?= htmlspecialchars(getSetting('openai_api_key') ?? '') ?>"
                 placeholder="sk-...">
          <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">
            Get your key from <a href="https://platform.openai.com/api-keys" target="_blank" style="color:var(--primary)">platform.openai.com/api-keys</a>
          </small>
        </div>

        <div class="form-group">
          <label>OpenAI Model</label>
          <select name="openai_model">
            <?php
            $currentModel = getSetting('openai_model') ?: 'gpt-4o-mini';
            $models = ['gpt-4o-mini' => 'GPT-4o Mini (Fast & Cheap)', 'gpt-4o' => 'GPT-4o (Best Quality)', 'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Budget)'];
            foreach ($models as $v => $l): ?>
              <option value="<?= $v ?>" <?= $currentModel === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Gemini Fields -->
      <div id="gemini_fields">
        <div class="form-group">
          <label>Gemini API Key *</label>
          <input type="password" name="gemini_api_key"
                 value="<?= htmlspecialchars(getSetting('gemini_api_key') ?? '') ?>"
                 placeholder="AIza...">
          <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">
            Get your key from <a href="https://aistudio.google.com/apikey" target="_blank" style="color:var(--primary)">aistudio.google.com/apikey</a>
          </small>
        </div>

        <div class="form-group">
          <label>Gemini Model</label>
          <select name="gemini_model">
            <?php
            $currentGeminiModel = getSetting('gemini_model') ?: 'gemini-2.0-flash';
            $geminiModels = ['gemini-2.0-flash' => 'Gemini 2.0 Flash (Fast & Cheap)', 'gemini-2.0-flash-lite' => 'Gemini 2.0 Flash Lite (Budget)', 'gemini-2.5-flash' => 'Gemini 2.5 Flash (Best Quality)'];
            foreach ($geminiModels as $v => $l): ?>
              <option value="<?= $v ?>" <?= $currentGeminiModel === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Review Word Limit</label>
        <input type="number" name="review_word_limit" min="10" max="500"
               value="<?= htmlspecialchars(getSetting('review_word_limit') ?? '60') ?>">
        <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">Maximum words per review (10–500). Controls how long each generated review will be. Default: 60</small>
      </div>

      <div class="form-group">
        <label>Reviews per Star Click</label>
        <input type="number" name="reviews_per_click" min="1" max="10"
               value="<?= htmlspecialchars(getSetting('reviews_per_click') ?? '5') ?>">
        <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">How many review options to show when a star is clicked (1–10). Default: 5</small>
      </div>

      <div>
        <button class="btn btn-primary" type="submit">💾 Save Settings</button>
      </div>

      <hr style="border:0;border-top:1px solid var(--border);width:100%;margin:6px 0">

      <div class="form-group">
        <label>WhatsApp API Base URL</label>
        <input type="url" name="whatsapp_api_base_url"
               value="<?= htmlspecialchars(getSetting('whatsapp_api_base_url') ?: 'https://site10.officialdigitalmarketing.in/api') ?>">
        <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">Used for customer OTP verification.</small>
      </div>

      <div class="form-group">
        <label>WhatsApp API Token</label>
        <input type="password" name="whatsapp_api_token"
               value="<?= htmlspecialchars(getSetting('whatsapp_api_token') ?? '') ?>">
      </div>

      <div class="form-group">
        <label>WhatsApp OTP Template</label>
        <input type="text" name="whatsapp_otp_template"
               value="<?= htmlspecialchars(getSetting('whatsapp_otp_template') ?: 'test_demo') ?>">
      </div>

      <div class="form-group">
        <label>WhatsApp OTP Language</label>
        <input type="text" name="whatsapp_otp_language"
               value="<?= htmlspecialchars(getSetting('whatsapp_otp_language') ?: 'en_US') ?>">
      </div>

      <hr style="border:0;border-top:1px solid var(--border);width:100%;margin:6px 0">

      <div class="form-group">
        <label>Razorpay Key ID</label>
        <input type="text" name="razorpay_key_id"
               value="<?= htmlspecialchars(getSetting('razorpay_key_id') ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Razorpay Key Secret</label>
        <input type="password" name="razorpay_key_secret"
               value="<?= htmlspecialchars(getSetting('razorpay_key_secret') ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Google Maps API Key</label>
        <input type="password" name="google_maps_api_key"
               value="<?= htmlspecialchars(getSetting('google_maps_api_key') ?? '') ?>">
        <small style="color:var(--muted);font-size:0.75rem;margin-top:4px">Required for automatic Place ID lookup in customer dashboard.</small>
      </div>

      <div>
        <button class="btn btn-primary" type="submit">Save All Settings</button>
      </div>

    </div>
  </form>
</div>

<script>
function toggleProviderFields() {
  var provider = document.getElementById('ai_provider').value;
  document.getElementById('openai_fields').style.display = provider === 'openai' ? 'block' : 'none';
  document.getElementById('gemini_fields').style.display = provider === 'gemini' ? 'block' : 'none';
}
toggleProviderFields();
</script>

<div class="card">
  <div class="card-header">
    <span class="card-title">System Info</span>
  </div>
  <table>
    <tr>
      <td style="color:var(--muted);width:200px">App URL</td>
      <td><code><?= APP_URL ?></code></td>
    </tr>
    <tr>
      <td style="color:var(--muted)">Upload Directory</td>
      <td><code><?= UPLOAD_DIR ?></code> <?= is_writable(UPLOAD_DIR) ? '<span style="color:#4ade80">✓ Writable</span>' : '<span style="color:#f87171">✗ Not writable</span>' ?></td>
    </tr>
    <tr>
      <td style="color:var(--muted)">PHP Version</td>
      <td><?= PHP_VERSION ?></td>
    </tr>
    <tr>
      <td style="color:var(--muted)">cURL</td>
      <td><?= function_exists('curl_init') ? '<span style="color:#4ade80">✓ Available</span>' : '<span style="color:#f87171">✗ Missing — required for API calls</span>' ?></td>
    </tr>
    <tr>
      <td style="color:var(--muted)">AI Provider</td>
      <td><strong><?= (getSetting('ai_provider') ?: 'openai') === 'gemini' ? 'Google Gemini' : 'OpenAI (ChatGPT)' ?></strong></td>
    </tr>
    <tr>
      <td style="color:var(--muted)">API Key Set</td>
      <td><?php
        $prov = getSetting('ai_provider') ?: 'openai';
        $keySet = $prov === 'gemini' ? getSetting('gemini_api_key') : getSetting('openai_api_key');
        echo $keySet ? '<span style="color:#4ade80">✓ Yes</span>' : '<span style="color:#f87171">✗ Not set</span>';
      ?></td>
    </tr>
    <tr>
      <td style="color:var(--muted)">Review Word Limit</td>
      <td><?= getSetting('review_word_limit') ?: '60' ?> words</td>
    </tr>
  </table>
</div>

  </div>
</div>
</body>
</html>
