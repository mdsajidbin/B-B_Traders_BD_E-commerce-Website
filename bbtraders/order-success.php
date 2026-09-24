<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Order Confirmed';

$orderId = (int) ($_GET['order'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || (!empty($order['user_id']) && (!current_user() || current_user()['id'] != $order['user_id']))) {
    redirect('index.php');
}

$itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="empty-state">
    <div class="icon">&#9989;</div>
    <h2>Thank you! Your order has been placed.</h2>
    <p>Order Number: <strong><?= clean($order['order_number']) ?></strong></p>
  </div>

  <div class="account-card" style="max-width:600px; margin:0 auto 40px;">
    <h4 style="margin-bottom:14px;">Order Summary</h4>
    <?php foreach ($orderItems as $item): ?>
      <div class="summary-row"><span><?= clean($item['product_name']) ?> &times;<?= $item['quantity'] ?></span><span><?= format_price($item['subtotal']) ?></span></div>
    <?php endforeach; ?>
    <div class="summary-row"><span>Shipping</span><span><?= format_price($order['shipping_cost']) ?></span></div>
    <?php if ($order['discount_amount'] > 0): ?><div class="summary-row"><span>Discount</span><span>-<?= format_price($order['discount_amount']) ?></span></div><?php endif; ?>
    <div class="summary-row total"><span>Total</span><span><?= format_price($order['total_amount']) ?></span></div>
    <div style="margin-top:14px; font-size:13.5px; color:var(--text-soft);">
      Payment method: <strong><?= strtoupper(clean($order['payment_method'])) ?></strong><br>
      Shipping to: <?= clean($order['shipping_address']) ?>, <?= clean($order['shipping_city']) ?>
    </div>
  </div>

  <div class="text-center" style="padding-bottom:40px;">
    <a href="<?= BASE_URL ?>shop.php" class="btn btn-primary">Continue Shopping</a>
    <a href="<?= BASE_URL ?>track-order.php?order_number=<?= urlencode($order['order_number']) ?>" class="btn btn-outline">Track Order</a>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
