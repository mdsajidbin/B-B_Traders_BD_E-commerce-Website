<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Track Order';

$order = null;
$searched = false;
if (!empty($_GET['order_number'])) {
    $searched = true;
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
    $stmt->execute([trim($_GET['order_number'])]);
    $order = $stmt->fetch();
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="auth-wrap" style="max-width:520px;">
    <h2 style="margin-bottom:6px;">Track Your Order</h2>
    <p style="color:var(--text-soft); font-size:13.5px; margin-bottom:20px;">Enter your order number to check its status.</p>
    <form method="get" action="<?= BASE_URL ?>track-order.php">
      <div class="form-group">
        <label>Order Number</label>
        <input class="form-control" name="order_number" required value="<?= clean($_GET['order_number'] ?? '') ?>" placeholder="e.g. BB260904A1B2C">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Track Order</button>
    </form>

    <?php if ($searched): ?>
      <?php if ($order): ?>
        <div class="alert alert-success" style="margin-top:20px;">
          Order <strong><?= clean($order['order_number']) ?></strong> status:
          <span class="status-pill status-<?= clean($order['order_status']) ?>"><?= clean($order['order_status']) ?></span><br>
          Payment: <span class="status-pill status-<?= clean($order['payment_status']) ?>"><?= clean($order['payment_status']) ?></span><br>
          Placed on <?= date('M j, Y', strtotime($order['created_at'])) ?> &middot; Total <?= format_price($order['total_amount']) ?>
        </div>
      <?php else: ?>
        <div class="alert alert-danger" style="margin-top:20px;">No order found with that number.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
