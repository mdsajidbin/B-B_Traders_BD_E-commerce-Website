<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

if (isset($_GET['toggle']) && verify_csrf($_GET['csrf_token'] ?? '')) {
    $cid = (int) $_GET['toggle'];
    $stmt = $pdo->prepare("SELECT status, role FROM users WHERE id = ?");
    $stmt->execute([$cid]);
    $u = $stmt->fetch();
    if ($u && $u['role'] === 'member') {
        $newStatus = $u['status'] === 'blocked' ? 'active' : 'blocked';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $cid]);
    }
    redirect('admin/customers.php?updated=1');
}

$pageTitle = 'Customers';
require_once __DIR__ . '/includes/header.php';

$q = trim($_GET['q'] ?? '');
$where = "role = 'member'"; $params = [];
if ($q !== '') { $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)"; $params = ["%$q%","%$q%","%$q%"]; }

$stmt = $pdo->prepare("SELECT u.*,
    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
    (SELECT COALESCE(SUM(total_amount),0) FROM orders o WHERE o.user_id = u.id) AS total_spent
    FROM users u WHERE $where ORDER BY u.created_at DESC LIMIT 200");
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success">Customer status updated.</div><?php endif; ?>

<div class="admin-panel">
  <div class="admin-panel-head">
    <h4>All Customers</h4>
    <form method="get"><input class="form-control" name="q" placeholder="Search name or email..." value="<?= clean($q) ?>"></form>
  </div>
  <table class="data-table">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Status</th><th>Joined</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($customers as $c): ?>
      <tr>
        <td><?= clean($c['first_name'] . ' ' . $c['last_name']) ?></td>
        <td><?= clean($c['email']) ?></td>
        <td><?= clean($c['phone']) ?></td>
        <td><?= $c['order_count'] ?></td>
        <td><?= format_price($c['total_spent']) ?></td>
        <td><span class="status-pill status-<?= clean($c['status']) ?>"><?= clean($c['status']) ?></span></td>
        <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
        <td><a href="<?= BASE_URL ?>admin/customers.php?toggle=<?= $c['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="icon-btn" onclick="return confirm('<?= $c['status'] === 'blocked' ? 'Unblock' : 'Block' ?> this customer?')"><?= $c['status'] === 'blocked' ? '&#10003;' : '&#128683;' ?></a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$customers): ?><tr><td colspan="8" style="text-align:center; color:var(--text-soft);">No customers found</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
