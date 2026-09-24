<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = $_POST['discount_type'] ?? 'percentage';
    $value = (float) ($_POST['discount_value'] ?? 0);
    $minOrder = (float) ($_POST['minimum_order'] ?? 0);
    $maxDiscount = $_POST['maximum_discount'] !== '' ? (float) $_POST['maximum_discount'] : null;
    $usageLimit = $_POST['usage_limit'] !== '' ? (int) $_POST['usage_limit'] : null;
    $perUserLimit = $_POST['per_user_limit'] !== '' ? (int) $_POST['per_user_limit'] : null;
    $startsAt = $_POST['starts_at'] !== '' ? $_POST['starts_at'] : null;
    $expiresAt = $_POST['expires_at'] !== '' ? $_POST['expires_at'] : null;
    $status = $_POST['status'] ?? 'active';

    if ($code === '') $errors[] = 'Coupon code is required.';
    if ($value <= 0) $errors[] = 'Discount value must be greater than 0.';

    if (empty($errors)) {
        try {
            $ins = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, minimum_order, maximum_discount, usage_limit, per_user_limit, starts_at, expires_at, status)
                                   VALUES (?,?,?,?,?,?,?,?,?,?)");
            $ins->execute([$code, $type, $value, $minOrder, $maxDiscount, $usageLimit, $perUserLimit, $startsAt, $expiresAt, $status]);
            redirect('admin/coupons.php?saved=1');
        } catch (Exception $e) {
            $errors[] = 'That coupon code already exists.';
        }
    }
}

if (isset($_GET['delete']) && verify_csrf($_GET['csrf_token'] ?? '')) {
    $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([(int) $_GET['delete']]);
    redirect('admin/coupons.php?deleted=1');
}

$pageTitle = 'Coupons';
require_once __DIR__ . '/includes/header.php';
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Coupon created.</div><?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?><div class="alert alert-success">Coupon removed.</div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div><?php endif; ?>

<div class="shop-layout" style="grid-template-columns:1fr 1.6fr;">
  <div class="admin-panel">
    <h4 style="margin-bottom:14px;">Create Coupon</h4>
    <form method="post">
      <?= csrf_field() ?>
      <div class="form-group"><label>Code</label><input class="form-control" name="code" required placeholder="e.g. WELCOME10"></div>
      <div class="form-row">
        <div class="form-group">
          <label>Type</label>
          <select name="discount_type" class="form-control"><option value="percentage">Percentage %</option><option value="fixed">Fixed Amount</option></select>
        </div>
        <div class="form-group"><label>Value</label><input class="form-control" type="number" step="0.01" name="discount_value" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Minimum Order</label><input class="form-control" type="number" step="0.01" name="minimum_order" value="0"></div>
        <div class="form-group"><label>Max Discount</label><input class="form-control" type="number" step="0.01" name="maximum_discount"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Usage Limit</label><input class="form-control" type="number" name="usage_limit"></div>
        <div class="form-group"><label>Per-User Limit</label><input class="form-control" type="number" name="per_user_limit"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Starts At</label><input class="form-control" type="datetime-local" name="starts_at"></div>
        <div class="form-group"><label>Expires At</label><input class="form-control" type="datetime-local" name="expires_at"></div>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create Coupon</button>
    </form>
  </div>
  <div class="admin-panel">
    <table class="data-table">
      <thead><tr><th>Code</th><th>Discount</th><th>Min Order</th><th>Used</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($coupons as $c): ?>
        <tr>
          <td><strong><?= clean($c['code']) ?></strong></td>
          <td><?= $c['discount_type'] === 'percentage' ? $c['discount_value'] . '%' : format_price($c['discount_value']) ?></td>
          <td><?= format_price($c['minimum_order']) ?></td>
          <td><?= $c['used_count'] ?><?= $c['usage_limit'] ? ' / ' . $c['usage_limit'] : '' ?></td>
          <td><span class="status-pill status-<?= clean($c['status']) ?>"><?= clean($c['status']) ?></span></td>
          <td><a href="<?= BASE_URL ?>admin/coupons.php?delete=<?= $c['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="icon-btn" onclick="return confirm('Delete this coupon?')">&times;</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$coupons): ?><tr><td colspan="6" style="text-align:center; color:var(--text-soft);">No coupons yet</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
