<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) redirect('admin/orders.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $orderStatus = $_POST['order_status'] ?? $order['order_status'];
    $paymentStatus = $_POST['payment_status'] ?? $order['payment_status'];
    $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?")
        ->execute([$orderStatus, $paymentStatus, $id]);
    $pdo->prepare("UPDATE payments SET status = ? WHERE order_id = ?")->execute([$paymentStatus, $id]);
    redirect('admin/order-view.php?id=' . $id . '&updated=1');
}

$pageTitle = 'Order ' . $order['order_number'];
require_once __DIR__ . '/includes/header.php';

$itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

$statuses = ['pending','confirmed','processing','shipped','delivered','cancelled','returned'];
$payStatuses = ['pending','paid','failed','refunded'];
?>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success">Order updated.</div><?php endif; ?>

<div class="shop-layout" style="grid-template-columns:1.6fr 1fr;">
  <div class="admin-panel">
    <h4 style="margin-bottom:14px;">Items</h4>
    <table class="data-table">
      <thead><tr><th>Product</th><th>Variant</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= clean($it['product_name']) ?></td>
          <td><?= clean($it['variant_name'] ?: '-') ?></td>
          <td><?= $it['quantity'] ?></td>
          <td><?= format_price($it['unit_price']) ?></td>
          <td><?= format_price($it['subtotal']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div style="margin-top:16px; text-align:right; font-size:14px;">
      <div>Subtotal: <?= format_price($order['subtotal']) ?></div>
      <div>Discount: -<?= format_price($order['discount_amount']) ?></div>
      <div>Shipping: <?= format_price($order['shipping_cost']) ?></div>
      <div style="font-size:18px; font-weight:800; margin-top:6px;">Total: <?= format_price($order['total_amount']) ?></div>
    </div>
  </div>

  <div>
    <div class="admin-panel" style="margin-bottom:16px;">
      <h4 style="margin-bottom:10px;">Customer</h4>
      <p style="font-size:13.5px;"><?= clean($order['shipping_name']) ?><br>
      <?= clean($order['shipping_phone']) ?><?= $order['shipping_email'] ? ' &middot; ' . clean($order['shipping_email']) : '' ?><br>
      <?= clean($order['shipping_address']) ?>, <?= clean($order['shipping_city']) ?> <?= clean($order['shipping_postal_code']) ?></p>
      <?php if ($order['notes']): ?><p style="font-size:13px; color:var(--text-soft);"><strong>Notes:</strong> <?= clean($order['notes']) ?></p><?php endif; ?>
    </div>
    <div class="admin-panel">
      <h4 style="margin-bottom:10px;">Update Status</h4>
      <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>Order Status</label>
          <select name="order_status" class="form-control">
            <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $order['order_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Payment Status</label>
          <select name="payment_status" class="form-control">
            <?php foreach ($payStatuses as $s): ?><option value="<?= $s ?>" <?= $order['payment_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Update Order</button>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
