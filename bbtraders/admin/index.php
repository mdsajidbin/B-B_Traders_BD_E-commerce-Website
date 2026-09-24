<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$totalProducts  = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders    = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalCustomers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='member'")->fetchColumn();
$totalRevenue   = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status='paid' OR payment_method='cod'")->fetchColumn();
$pendingOrders  = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn();
$lowStock       = $pdo->query("SELECT * FROM products WHERE stock <= 5 AND status='active' ORDER BY stock ASC LIMIT 5")->fetchAll();
$recentOrders   = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 8")->fetchAll();
?>
<div class="admin-cards">
  <div class="admin-stat-card"><div><div class="num"><?= number_format($totalProducts) ?></div><div style="color:var(--text-soft); font-size:13px;">Products</div></div><div class="icon">&#128230;</div></div>
  <div class="admin-stat-card"><div><div class="num"><?= number_format($totalOrders) ?></div><div style="color:var(--text-soft); font-size:13px;">Orders</div></div><div class="icon">&#128179;</div></div>
  <div class="admin-stat-card"><div><div class="num"><?= number_format($totalCustomers) ?></div><div style="color:var(--text-soft); font-size:13px;">Customers</div></div><div class="icon">&#128101;</div></div>
  <div class="admin-stat-card"><div><div class="num"><?= format_price($totalRevenue) ?></div><div style="color:var(--text-soft); font-size:13px;">Revenue</div></div><div class="icon">&#128176;</div></div>
</div>

<div class="shop-layout" style="grid-template-columns:2fr 1fr;">
  <div class="admin-panel">
    <div class="admin-panel-head"><h4>Recent Orders</h4><a href="<?= BASE_URL ?>admin/orders.php" class="btn btn-outline btn-sm">View All</a></div>
    <table class="data-table">
      <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($recentOrders as $o): ?>
        <tr>
          <td><?= clean($o['order_number']) ?></td>
          <td><?= clean($o['shipping_name']) ?></td>
          <td><span class="status-pill status-<?= clean($o['order_status']) ?>"><?= clean($o['order_status']) ?></span></td>
          <td><?= format_price($o['total_amount']) ?></td>
          <td><a href="<?= BASE_URL ?>admin/order-view.php?id=<?= $o['id'] ?>" class="icon-btn">&#128065;</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recentOrders): ?><tr><td colspan="5" style="text-align:center; color:var(--text-soft);">No orders yet</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="admin-panel">
    <div class="admin-panel-head"><h4>Low Stock Alert</h4></div>
    <?php if ($lowStock): foreach ($lowStock as $p): ?>
      <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border); font-size:13.5px;">
        <span><?= clean($p['name']) ?></span><strong style="color:var(--danger);"><?= $p['stock'] ?> left</strong>
      </div>
    <?php endforeach; else: ?>
      <p style="color:var(--text-soft); font-size:13.5px;">Stock levels look healthy.</p>
    <?php endif; ?>
    <p style="margin-top:16px; font-size:13.5px;"><strong><?= $pendingOrders ?></strong> orders pending confirmation.</p>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
