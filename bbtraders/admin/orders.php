<?php
$pageTitle = 'Orders';
require_once __DIR__ . '/includes/header.php';

$statusFilter = $_GET['status'] ?? '';
$where = '1=1'; $params = [];
if ($statusFilter !== '') { $where = 'order_status = ?'; $params[] = $statusFilter; }

$stmt = $pdo->prepare("SELECT * FROM orders WHERE $where ORDER BY created_at DESC LIMIT 200");
$stmt->execute($params);
$orders = $stmt->fetchAll();
$statuses = ['pending','confirmed','processing','shipped','delivered','cancelled','returned'];
?>
<div class="admin-panel">
  <div class="admin-panel-head">
    <h4>All Orders</h4>
    <form method="get">
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
      </select>
    </form>
  </div>
  <table class="data-table">
    <thead><tr><th>Order #</th><th>Customer</th><th>Payment</th><th>Order Status</th><th>Total</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= clean($o['order_number']) ?></td>
        <td><?= clean($o['shipping_name']) ?><br><small style="color:var(--text-soft);"><?= clean($o['shipping_phone']) ?></small></td>
        <td><span class="status-pill status-<?= clean($o['payment_status']) ?>"><?= clean($o['payment_status']) ?></span> <?= strtoupper(clean($o['payment_method'])) ?></td>
        <td><span class="status-pill status-<?= clean($o['order_status']) ?>"><?= clean($o['order_status']) ?></span></td>
        <td><?= format_price($o['total_amount']) ?></td>
        <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
        <td><a href="<?= BASE_URL ?>admin/order-view.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">Manage</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$orders): ?><tr><td colspan="7" style="text-align:center; color:var(--text-soft);">No orders found</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
