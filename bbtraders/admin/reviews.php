<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

if (isset($_GET['action'], $_GET['id']) && verify_csrf($_GET['csrf_token'] ?? '')) {
    $rid = (int) $_GET['id'];
    if ($_GET['action'] === 'approve') {
        $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?")->execute([$rid]);
    } elseif ($_GET['action'] === 'reject') {
        $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?")->execute([$rid]);
    } elseif ($_GET['action'] === 'delete') {
        $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$rid]);
    }
    redirect('admin/reviews.php?updated=1');
}

$pageTitle = 'Reviews';
require_once __DIR__ . '/includes/header.php';

$filter = $_GET['status'] ?? '';
$where = '1=1'; $params = [];
if ($filter !== '') { $where = 'r.status = ?'; $params[] = $filter; }

$stmt = $pdo->prepare("SELECT r.*, p.name AS product_name, u.first_name, u.last_name
                        FROM reviews r JOIN products p ON p.id = r.product_id JOIN users u ON u.id = r.user_id
                        WHERE $where ORDER BY r.created_at DESC LIMIT 200");
$stmt->execute($params);
$reviews = $stmt->fetchAll();
?>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success">Review updated.</div><?php endif; ?>

<div class="admin-panel">
  <div class="admin-panel-head">
    <h4>Product Reviews</h4>
    <form method="get">
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="">All</option>
        <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="approved" <?= $filter === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="rejected" <?= $filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
    </form>
  </div>
  <table class="data-table">
    <thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Review</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($reviews as $r): ?>
      <tr>
        <td><?= clean($r['product_name']) ?></td>
        <td><?= clean($r['first_name'] . ' ' . $r['last_name']) ?></td>
        <td><?= str_repeat('★', (int) $r['rating']) ?></td>
        <td style="max-width:280px;"><?= clean(mb_substr($r['review_text'] ?? '', 0, 100)) ?></td>
        <td><span class="status-pill status-<?= clean($r['status']) ?>"><?= clean($r['status']) ?></span></td>
        <td>
          <?php if ($r['status'] !== 'approved'): ?><a href="<?= BASE_URL ?>admin/reviews.php?action=approve&id=<?= $r['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="icon-btn" title="Approve">&#10003;</a><?php endif; ?>
          <?php if ($r['status'] !== 'rejected'): ?><a href="<?= BASE_URL ?>admin/reviews.php?action=reject&id=<?= $r['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="icon-btn" title="Reject">&#10005;</a><?php endif; ?>
          <a href="<?= BASE_URL ?>admin/reviews.php?action=delete&id=<?= $r['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="icon-btn" onclick="return confirm('Delete this review?')" title="Delete">&#128465;</a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$reviews): ?><tr><td colspan="6" style="text-align:center; color:var(--text-soft);">No reviews found</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
