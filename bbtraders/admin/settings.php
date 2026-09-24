<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$fields = [
    'site_name' => 'Site Name', 'site_email' => 'Site Email', 'site_phone' => 'Site Phone', 'site_address' => 'Site Address',
    'active_currency' => 'Currency Code', 'currency_symbol' => 'Currency Symbol',
    'shipping_fee' => 'Shipping Fee', 'free_shipping_threshold' => 'Free Shipping Threshold',
    'announcement_text' => 'Announcement Bar Text',
    'facebook_url' => 'Facebook URL', 'instagram_url' => 'Instagram URL', 'youtube_url' => 'YouTube URL', 'whatsapp_url' => 'WhatsApp URL',
    'stripe_publishable_key' => 'Stripe Publishable Key', 'stripe_secret_key' => 'Stripe Secret Key', 'stripe_webhook_secret' => 'Stripe Webhook Secret',
    'bkash_app_key' => 'bKash App Key', 'bkash_app_secret' => 'bKash App Secret', 'bkash_username' => 'bKash Username', 'bkash_password' => 'bKash Password',
    'nagad_merchant_id' => 'Nagad Merchant ID', 'nagad_merchant_number' => 'Nagad Merchant Number', 'nagad_public_key' => 'Nagad Public Key', 'nagad_private_key' => 'Nagad Private Key',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    foreach ($fields as $key => $label) {
        $val = trim($_POST[$key] ?? '');
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $val]);
    }
    redirect('admin/settings.php?saved=1');
}

$pageTitle = 'Settings';
require_once __DIR__ . '/includes/header.php';
$settings = get_settings();
?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Settings saved.</div><?php endif; ?>

<div class="admin-panel">
  <form method="post">
    <?= csrf_field() ?>
    <h4 style="margin-bottom:14px;">General</h4>
    <div class="form-row">
      <?php foreach (['site_name','site_email','site_phone','site_address','active_currency','currency_symbol'] as $k): ?>
        <div class="form-group"><label><?= $fields[$k] ?></label><input class="form-control" name="<?= $k ?>" value="<?= clean($settings[$k] ?? '') ?>"></div>
      <?php endforeach; ?>
    </div>
    <div class="form-row">
      <div class="form-group"><label><?= $fields['shipping_fee'] ?></label><input class="form-control" type="number" step="0.01" name="shipping_fee" value="<?= clean($settings['shipping_fee'] ?? '') ?>"></div>
      <div class="form-group"><label><?= $fields['free_shipping_threshold'] ?></label><input class="form-control" type="number" step="0.01" name="free_shipping_threshold" value="<?= clean($settings['free_shipping_threshold'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label><?= $fields['announcement_text'] ?></label><input class="form-control" name="announcement_text" value="<?= clean($settings['announcement_text'] ?? '') ?>"></div>

    <h4 style="margin:22px 0 14px;">Social Links</h4>
    <div class="form-row">
      <?php foreach (['facebook_url','instagram_url','youtube_url','whatsapp_url'] as $k): ?>
        <div class="form-group"><label><?= $fields[$k] ?></label><input class="form-control" name="<?= $k ?>" value="<?= clean($settings[$k] ?? '') ?>"></div>
      <?php endforeach; ?>
    </div>

    <h4 style="margin:22px 0 14px;">Payment Gateways (architecture only — add real keys before going live)</h4>
    <div class="form-row">
      <?php foreach (['stripe_publishable_key','stripe_secret_key','stripe_webhook_secret'] as $k): ?>
        <div class="form-group"><label><?= $fields[$k] ?></label><input class="form-control" name="<?= $k ?>" value="<?= clean($settings[$k] ?? '') ?>"></div>
      <?php endforeach; ?>
    </div>
    <div class="form-row">
      <?php foreach (['bkash_app_key','bkash_app_secret','bkash_username','bkash_password'] as $k): ?>
        <div class="form-group"><label><?= $fields[$k] ?></label><input class="form-control" name="<?= $k ?>" value="<?= clean($settings[$k] ?? '') ?>"></div>
      <?php endforeach; ?>
    </div>
    <div class="form-row">
      <?php foreach (['nagad_merchant_id','nagad_merchant_number','nagad_public_key','nagad_private_key'] as $k): ?>
        <div class="form-group"><label><?= $fields[$k] ?></label><input class="form-control" name="<?= $k ?>" value="<?= clean($settings[$k] ?? '') ?>"></div>
      <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-primary">Save Settings</button>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
