<?php
require_once __DIR__ . '/../config.php';

if (isCustomerLoggedIn()) {
    header('Location: ' . APP_URL . '/customer/dashboard.php');
    exit;
}

$db = getDB();
$plans = $db->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
$addons = $db->query("SELECT * FROM addons WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
$rzpKey = getSetting('razorpay_key_id') ?: '';
$requestedPlanId = intval($_GET['plan'] ?? 0);
$planIds = array_map(function ($plan) {
    return (int) $plan['id'];
}, $plans);
$defaultPlanId = ($requestedPlanId && in_array($requestedPlanId, $planIds, true)) ? $requestedPlanId : ($plans ? (int) $plans[0]['id'] : 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - AI Google Reviews</title>
  <style>
    :root{--primary:#058a36;--primary-dark:#04662a;--gold:#f0b400;--text:#102016;--muted:#667569;--line:#dce8df;--bg:#f6fbf7;--radius:8px}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,Helvetica,sans-serif;background:linear-gradient(135deg,#073f20,#058a36);min-height:100vh;padding:28px;color:var(--text)}
    a{color:inherit;text-decoration:none}
    .shell{width:min(1120px,100%);margin:0 auto;display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr);gap:22px;align-items:start}
    .panel{background:#fff;border-radius:var(--radius);padding:28px;box-shadow:0 24px 70px rgba(0,0,0,.22)}
    .brand{display:flex;align-items:center;gap:12px;margin-bottom:22px}
    .mark{width:44px;height:44px;border:2px solid var(--gold);border-radius:var(--radius);display:grid;place-items:center;color:var(--primary);font-weight:900}
    h1{font-size:1.65rem;line-height:1.15}.lead{color:var(--muted);margin-top:6px;line-height:1.5}
    label{display:block;margin:14px 0 7px;color:var(--muted);font-size:.78rem;font-weight:800;text-transform:uppercase}
    input{width:100%;padding:12px 13px;border:1px solid var(--line);border-radius:var(--radius);font-size:1rem}
    input:focus{outline:2px solid rgba(5,138,54,.14);border-color:var(--primary)}
    .plans,.addons{display:grid;gap:12px;margin-top:16px}
    .plan-card,.addon-card{position:relative;border:1px solid var(--line);border-radius:var(--radius);padding:18px;background:#fbfff9;cursor:pointer}
    .plan-card input,.addon-card input{position:absolute;opacity:0;pointer-events:none}
    .plan-card.active,.addon-card.active{border-color:var(--primary);box-shadow:0 0 0 3px rgba(5,138,54,.12)}
    .plan-top,.addon-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
    .plan-card h2,.addon-card h3{font-size:1.05rem;line-height:1.25}
    .price{font-size:1.7rem;font-weight:900;color:var(--primary);white-space:nowrap}
    .desc{color:var(--muted);font-size:.92rem;margin-top:8px;line-height:1.45}
    .feature-list{display:grid;gap:7px;margin:10px 0 0;padding:0;list-style:none;color:var(--muted);font-size:.92rem;text-transform:none;font-weight:400}
    .feature-list li{position:relative;padding-left:19px;line-height:1.45}
    .feature-list li::before{content:"";position:absolute;left:0;top:.62em;width:7px;height:7px;border-radius:50%;background:var(--primary)}
    .buy-badge{display:inline-flex;margin-top:12px;border-radius:999px;padding:7px 10px;background:#fff4c5;color:#3a2b00;font-size:.78rem;font-weight:900}
    .summary{border:1px solid var(--line);border-radius:var(--radius);padding:16px;background:#f8fcf8;margin-top:18px}
    .summary-row{display:flex;justify-content:space-between;gap:12px;padding:8px 0;color:var(--muted);font-size:.95rem}
    .summary-row.total{border-top:1px solid var(--line);margin-top:6px;padding-top:12px;color:var(--text);font-weight:900;font-size:1.12rem}
    .btn{width:100%;border:0;border-radius:var(--radius);padding:14px;background:var(--primary);color:#fff;font-weight:900;margin-top:18px;cursor:pointer;font-size:1rem}
    .btn:hover{background:var(--primary-dark)}.btn:disabled{opacity:.58;cursor:not-allowed}
    .otp-panel{display:none;border:1px solid var(--line);border-radius:var(--radius);padding:16px;background:#f8fcf8;margin-top:18px}
    .otp-panel.active{display:block}.otp-input{text-align:center;letter-spacing:.2em;font-size:1.25rem}
    .alert{padding:12px;border-radius:var(--radius);margin:14px 0;font-size:.9rem}.alert-error{background:#fee2e2;color:#991b1b}.alert-info{background:#eff6ff;color:#1e40af}.alert-success{background:#dcfce7;color:#166534}
    .links{text-align:center;margin-top:18px;color:var(--muted);font-size:.92rem}.links a{color:var(--primary);font-weight:800}
    @media(max-width:860px){body{padding:16px}.shell{grid-template-columns:1fr}.panel{padding:22px}.plan-top,.addon-top{flex-direction:column}.price{font-size:1.45rem}}
  </style>
</head>
<body>
  <div class="shell">
    <section class="panel">
      <div class="brand"><div class="mark">G*</div><div><h1>Verify WhatsApp, then pay</h1><p class="lead">First verify your 10 digit WhatsApp number with OTP. After OTP verification, complete payment to create your account.</p></div></div>
      <?php if ($rzpKey === ''): ?>
        <div class="alert alert-info">Razorpay is not configured yet. Registration checkout will work after admin adds Razorpay keys.</div>
      <?php endif; ?>
      <form id="registerForm">
        <label>Name *</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

        <label>WhatsApp Number *</label>
        <input type="tel" name="phone" required inputmode="numeric" minlength="10" maxlength="10" pattern="[6-9][0-9]{9}" placeholder="9780551900" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label>Password *</label>
        <input type="password" name="password" required minlength="8">

        <label>Confirm Password *</label>
        <input type="password" name="confirm_password" required minlength="8">

        <div class="summary" aria-live="polite">
          <div class="summary-row"><span>Selected plan</span><strong id="summaryPlan">-</strong></div>
          <div class="summary-row"><span>Addons</span><strong id="summaryAddons">₹0</strong></div>
          <div class="summary-row total"><span>Total today</span><strong id="summaryTotal">₹0</strong></div>
        </div>

        <button class="btn" id="sendOtpButton" type="submit" <?= (!$plans) ? 'disabled' : '' ?>>Send OTP</button>
      </form>

      <div class="otp-panel" id="otpPanel">
        <label>WhatsApp OTP *</label>
        <input class="otp-input" type="text" id="otpInput" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" placeholder="000000">
        <button class="btn" id="verifyOtpButton" type="button">Verify OTP & Pay</button>
        <button class="btn" id="resendOtpButton" type="button" style="background:#fff;color:var(--primary);border:1px solid var(--line)">Resend OTP</button>
      </div>

      <div id="message"></div>
      <div class="links">
        Already registered? <a href="<?= APP_URL ?>/customer/login.php">Login</a><br>
        By registering, you agree to the <a href="<?= APP_URL ?>/terms-and-conditions.php" target="_blank" rel="noopener">Terms</a>
        and <a href="<?= APP_URL ?>/privacy-policy.php" target="_blank" rel="noopener">Privacy Policy</a>.
      </div>
    </section>

    <section class="panel">
      <h1>Choose pricing plan</h1>
      <p class="lead">OTP is verified first. Your account is created only after successful payment.</p>

      <div class="plans">
        <?php foreach ($plans as $plan): ?>
          <label class="plan-card" data-plan-card data-price="<?= htmlspecialchars($plan['price']) ?>" data-name="<?= htmlspecialchars($plan['name']) ?>">
            <input type="radio" name="plan_id" form="registerForm" value="<?= (int) $plan['id'] ?>" required <?= (int) $plan['id'] === $defaultPlanId ? 'checked' : '' ?>>
            <div class="plan-top">
              <div>
                <h2><?= htmlspecialchars($plan['name']) ?></h2>
                <ul class="feature-list">
                  <?php foreach (featureListFromText($plan['description'], $plan['duration_days'] . ' days access') as $feature): ?>
                    <li><?= htmlspecialchars($feature) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <div class="price"><?= (float) $plan['price'] <= 0 ? 'Free' : '₹' . number_format((float) $plan['price'], 0) ?></div>
            </div>
            <span class="buy-badge"><?= (float) $plan['price'] <= 0 ? 'Start free trial' : 'Buy this plan' ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <?php if ($addons): ?>
        <h1 style="margin-top:26px">Addons</h1>
        <p class="lead">Optional one-time items can be included with your first payment.</p>
        <div class="addons">
          <?php foreach ($addons as $addon): ?>
            <label class="addon-card" data-addon-card data-price="<?= htmlspecialchars($addon['price']) ?>">
              <input type="checkbox" name="addons[]" form="registerForm" value="<?= (int) $addon['id'] ?>">
              <div class="addon-top">
                <div>
                  <h3><?= htmlspecialchars($addon['name']) ?></h3>
                  <p class="desc"><?= htmlspecialchars($addon['description'] ?: 'One-time addon') ?></p>
                </div>
                <div class="price">₹<?= number_format((float) $addon['price'], 0) ?></div>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <script>
  const RAZORPAY_KEY = <?= json_encode($rzpKey) ?>;
  const form = document.getElementById('registerForm');
  const message = document.getElementById('message');
  const sendOtpButton = document.getElementById('sendOtpButton');
  const otpPanel = document.getElementById('otpPanel');
  const otpInput = document.getElementById('otpInput');
  const verifyOtpButton = document.getElementById('verifyOtpButton');
  const resendOtpButton = document.getElementById('resendOtpButton');
  let pendingRegistrationId = 0;
  let selectedTotalAmount = 0;

  function formatInr(value) {
    return '₹' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
  }

  function selectedPlanCard() {
    const selected = document.querySelector('input[name="plan_id"]:checked');
    return selected ? selected.closest('[data-plan-card]') : null;
  }

  function updateSelection() {
    document.querySelectorAll('[data-plan-card]').forEach(card => {
      card.classList.toggle('active', card.querySelector('input').checked);
    });
    document.querySelectorAll('[data-addon-card]').forEach(card => {
      card.classList.toggle('active', card.querySelector('input').checked);
    });

    const plan = selectedPlanCard();
    const planAmount = plan ? Number(plan.dataset.price) : 0;
    const isFreePlan = planAmount <= 0;
    document.querySelectorAll('[data-addon-card] input').forEach(input => {
      if (isFreePlan) {
        input.checked = false;
        input.closest('[data-addon-card]').classList.remove('active');
      }
      input.disabled = isFreePlan;
      input.closest('[data-addon-card]').style.opacity = isFreePlan ? '.55' : '1';
    });

    const addonAmount = Array.from(document.querySelectorAll('[data-addon-card] input:checked'))
      .reduce((sum, input) => sum + Number(input.closest('[data-addon-card]').dataset.price), 0);
    selectedTotalAmount = planAmount + addonAmount;

    document.getElementById('summaryPlan').textContent = plan ? `${plan.dataset.name} - ${formatInr(planAmount)}` : '-';
    document.getElementById('summaryAddons').textContent = formatInr(addonAmount);
    document.getElementById('summaryTotal').textContent = formatInr(selectedTotalAmount);
    verifyOtpButton.textContent = selectedTotalAmount > 0 ? 'Verify OTP & Pay' : 'Verify OTP & Start Trial';
  }

  document.querySelectorAll('[data-plan-card] input,[data-addon-card] input').forEach(input => {
    input.addEventListener('change', updateSelection);
  });
  updateSelection();

  function showError(text) {
    message.innerHTML = `<div class="alert alert-error">${text}</div>`;
  }

  function showSuccess(text) {
    message.innerHTML = `<div class="alert alert-success">${text}</div>`;
  }

  function validPhone() {
    return /^[6-9]\d{9}$/.test(form.elements['phone'].value.trim());
  }

  function setRegistrationLocked(locked) {
    form.querySelectorAll('input').forEach(input => {
      input.disabled = locked;
    });
    document.querySelectorAll('[data-plan-card] input,[data-addon-card] input').forEach(input => {
      input.disabled = locked;
    });
  }

  function openPayment(data) {
    if (!RAZORPAY_KEY) {
      showError('Razorpay keys are not configured. Please contact admin.');
      verifyOtpButton.disabled = false;
      verifyOtpButton.textContent = 'Verify OTP & Pay';
      return;
    }

    verifyOtpButton.textContent = 'Opening payment...';
    const options = {
      key: RAZORPAY_KEY,
      amount: data.amount,
      currency: 'INR',
      name: 'AI Google Reviews',
      description: data.description,
      order_id: data.razorpay_order_id,
      prefill: {
        name: form.elements['name'].value,
        email: form.elements['email'].value,
        contact: form.elements['phone'].value
      },
      modal: {
        ondismiss: function () {
          verifyOtpButton.disabled = false;
          verifyOtpButton.textContent = 'Verify OTP & Pay';
          if (!otpPanel.classList.contains('active')) {
            sendOtpButton.disabled = false;
            sendOtpButton.textContent = 'Continue Payment';
          }
        }
      },
      handler: async function (response) {
        verifyOtpButton.textContent = 'Verifying payment...';
        const verify = new FormData();
        verify.append('pending_registration_id', data.pending_registration_id);
        verify.append('razorpay_order_id', response.razorpay_order_id);
        verify.append('razorpay_payment_id', response.razorpay_payment_id);
        verify.append('razorpay_signature', response.razorpay_signature);
        const vr = await fetch('<?= APP_URL ?>/customer/verify-registration-payment.php', { method: 'POST', body: verify });
        const result = await vr.json();
        if (result.success) {
          window.location.href = result.redirect;
        } else {
          showError(result.message || 'Payment verification failed.');
          verifyOtpButton.disabled = false;
          verifyOtpButton.textContent = 'Verify OTP & Pay';
          if (!otpPanel.classList.contains('active')) {
            sendOtpButton.disabled = false;
            sendOtpButton.textContent = 'Continue Payment';
          }
        }
      }
    };
    new Razorpay(options).open();
  }

  form.elements['phone'].addEventListener('input', function () {
    this.value = this.value.replace(/\D+/g, '').slice(0, 10);
  });

  otpInput.addEventListener('input', function () {
    this.value = this.value.replace(/\D+/g, '').slice(0, 6);
  });

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    message.innerHTML = '';

    if (!form.reportValidity()) {
      return;
    }
    if (!validPhone()) {
      showError('Please enter a valid 10 digit Indian WhatsApp number.');
      return;
    }

    sendOtpButton.disabled = true;
    sendOtpButton.textContent = 'Sending OTP...';

    try {
      const res = await fetch('<?= APP_URL ?>/customer/create-registration-order.php', { method: 'POST', body: new FormData(form) });
      const data = await res.json();
      if (!data.success) {
        showError(data.message || 'Could not start registration.');
        sendOtpButton.disabled = false;
        sendOtpButton.textContent = 'Send OTP';
        return;
      }

      pendingRegistrationId = data.pending_registration_id;
      if (!data.requires_otp) {
        if (!data.requires_payment) {
          window.location.href = data.redirect;
          return;
        }
        setRegistrationLocked(true);
        sendOtpButton.textContent = 'Continue Payment';
        verifyOtpButton.disabled = true;
        openPayment(data);
        return;
      }

      setRegistrationLocked(true);
      sendOtpButton.textContent = 'OTP Sent';
      otpPanel.classList.add('active');
      otpInput.focus();
      showSuccess(data.message || 'OTP sent to WhatsApp. Verify OTP to continue to payment.');
    } catch (error) {
      showError('OTP could not be sent. Please try again.');
      sendOtpButton.disabled = false;
      sendOtpButton.textContent = 'Send OTP';
    }
  });

  verifyOtpButton.addEventListener('click', async function () {
    message.innerHTML = '';
    const otp = otpInput.value.trim();
    if (!pendingRegistrationId) {
      showError('Please send OTP first.');
      return;
    }
    if (!/^\d{6}$/.test(otp)) {
      showError('Please enter the 6 digit OTP.');
      return;
    }
    verifyOtpButton.disabled = true;
    verifyOtpButton.textContent = 'Verifying OTP...';

    try {
      const otpForm = new FormData();
      otpForm.append('pending_registration_id', pendingRegistrationId);
      otpForm.append('otp', otp);
      const otpRes = await fetch('<?= APP_URL ?>/customer/verify-registration-otp.php', { method: 'POST', body: otpForm });
      const data = await otpRes.json();
      if (!data.success) {
        showError(data.message || 'OTP verification failed.');
        verifyOtpButton.disabled = false;
        verifyOtpButton.textContent = selectedTotalAmount > 0 ? 'Verify OTP & Pay' : 'Verify OTP & Start Trial';
        return;
      }

      if (!data.requires_payment) {
        window.location.href = data.redirect;
        return;
      }

      openPayment(data);
    } catch (error) {
      showError('Checkout could not start. Please try again.');
      verifyOtpButton.disabled = false;
      verifyOtpButton.textContent = 'Verify OTP & Pay';
    }
  });

  resendOtpButton.addEventListener('click', async function () {
    message.innerHTML = '';
    if (!pendingRegistrationId) {
      showError('Please send OTP first.');
      return;
    }

    resendOtpButton.disabled = true;
    resendOtpButton.textContent = 'Resending...';
    try {
      const body = new FormData();
      body.append('pending_registration_id', pendingRegistrationId);
      const res = await fetch('<?= APP_URL ?>/customer/resend-registration-otp.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        otpInput.value = '';
        otpInput.focus();
        showSuccess(data.message || 'A new OTP has been sent.');
      } else {
        showError(data.message || 'Could not resend OTP.');
      }
    } catch (error) {
      showError('Could not resend OTP. Please try again.');
    }
    resendOtpButton.disabled = false;
    resendOtpButton.textContent = 'Resend OTP';
  });
  </script>
</body>
</html>
