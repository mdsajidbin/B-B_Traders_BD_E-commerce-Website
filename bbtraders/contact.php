<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Contact Us';
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $sent = true; // In production: send email / store message
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Home</a> / Contact</div>
  <div class="shop-layout">
    <div class="account-card">
      <h2 style="margin-bottom:16px;">Get in Touch</h2>
      <?php if ($sent): ?><div class="alert alert-success">Thanks! Your message has been received.</div><?php endif; ?>
      <form method="post" action="<?= BASE_URL ?>contact.php">
        <?= csrf_field() ?>
        <div class="form-row">
          <div class="form-group"><label>Name</label><input class="form-control" name="name" required></div>
          <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div>
        </div>
        <div class="form-group"><label>Message</label><textarea class="form-control" name="message" rows="5" required></textarea></div>
        <button type="submit" class="btn btn-primary">Send Message</button>
      </form>
    </div>
    <div class="account-card">
      <h3 style="margin-bottom:14px;">Contact Information</h3>
      <p><strong>Address:</strong><br><?= clean(get_setting('site_address')) ?></p>
      <p><strong>Phone:</strong><br><?= clean(get_setting('site_phone')) ?></p>
      <p><strong>Email:</strong><br><?= clean(get_setting('site_email')) ?></p>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
