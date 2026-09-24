<?php
require_once __DIR__ . '/../config/config.php';
require_login();
$pageTitle = 'My Orders';
$user = current_user();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Home</a> / My Orders</div>
  <div class="account-layout">
    <?php include __DIR__ . '/includes-nav.php'; ?>
    <div class="account-card">
      <h3 style="margin-bottom:14px;">My Orders</h3>
      <?php if ($orders): ?>
        <table class="data-table">
          <thead><tr><th>Order #</th><th>Date</th><th>Payment</th><th>Status</th><th>Total</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><?= clean($o['order_number']) ?></td>
              <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
              <td><span class="status-pill status-<?= clean($o['payment_status']) ?>"><?= clean($o['payment_status']) ?></span></td>
              <td><span class="status-pill status-<?= clean($o['order_status']) ?>"><?= clean($o['order_status']) ?></span></td>
              <td><?= format_price($o['total_amount']) ?></td>
              <td><a href="<?= BASE_URL ?>account/order-detail.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state"><div class="icon">&#128230;</div><h3>No orders yet</h3><a href="<?= BASE_URL ?>shop.php" class="btn btn-primary">Start Shopping</a></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
