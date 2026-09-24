<?php
require_once __DIR__ . '/../config/config.php';
require_login();
$pageTitle = 'My Account';
$user = current_user();

$orderCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$orderCount->execute([$user['id']]);
$totalOrders = (int) $orderCount->fetchColumn();

$wishCount = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ?");
$wishCount->execute([$user['id']]);
$totalWishlist = (int) $wishCount->fetchColumn();

$recentOrders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$recentOrders->execute([$user['id']]);
$recentOrders = $recentOrders->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Home</a> / My Account</div>
  <div class="account-layout">
    <?php include __DIR__ . '/includes-nav.php'; ?>
    <div>
      <div class="admin-cards" style="grid-template-columns:repeat(2,1fr); margin-bottom:20px;">
        <div class="admin-stat-card"><div><div class="num"><?= $totalOrders ?></div><div style="color:var(--text-soft); font-size:13px;">Total Orders</div></div><div class="icon">&#128230;</div></div>
        <div class="admin-stat-card"><div><div class="num"><?= $totalWishlist ?></div><div style="color:var(--text-soft); font-size:13px;">Wishlist Items</div></div><div class="icon">&#9825;</div></div>
      </div>
      <div class="account-card">
        <h3 style="margin-bottom:8px;">Welcome back, <?= clean($user['first_name']) ?>!</h3>
        <p style="color:var(--text-soft); font-size:14px;">Here's a quick look at your recent orders.</p>
        <?php if ($recentOrders): ?>
          <table class="data-table" style="margin-top:14px;">
            <thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recentOrders as $o): ?>
              <tr>
                <td><?= clean($o['order_number']) ?></td>
                <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                <td><span class="status-pill status-<?= clean($o['order_status']) ?>"><?= clean($o['order_status']) ?></span></td>
                <td><?= format_price($o['total_amount']) ?></td>
                <td><a href="<?= BASE_URL ?>account/order-detail.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p style="color:var(--text-soft);">You haven't placed any orders yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
