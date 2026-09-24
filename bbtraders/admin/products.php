<?php
$pageTitle = 'Products';
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['delete']) && verify_csrf($_GET['csrf_token'] ?? '')) {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([(int) $_GET['delete']]);
    redirect('admin/products.php?deleted=1');
}

$q = trim($_GET['q'] ?? '');
$where = '1=1'; $params = [];
if ($q !== '') { $where = "p.name LIKE ?"; $params[] = "%$q%"; }

$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p
                        JOIN categories c ON c.id = p.category_id
                        WHERE $where ORDER BY p.created_at DESC LIMIT 100");
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<?php if (!empty($_GET['deleted'])): ?><div class="alert alert-success">Product deleted.</div><?php endif; ?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Product saved successfully.</div><?php endif; ?>

<div class="admin-panel">
  <div class="admin-panel-head">
    <form method="get" style="display:flex; gap:8px;">
      <input class="form-control" name="q" placeholder="Search products..." value="<?= clean($q) ?>" style="width:240px;">
      <button class="btn btn-outline" type="submit">Search</button>
    </form>
    <a href="<?= BASE_URL ?>admin/product-form.php" class="btn btn-primary">+ Add Product</a>
  </div>
  <table class="data-table">
    <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><img src="<?= clean($p['main_image']) ?>" class="table-thumb"></td>
        <td><?= clean($p['name']) ?><br><small style="color:var(--text-soft);">SKU: <?= clean($p['sku']) ?></small></td>
        <td><?= clean($p['category_name']) ?></td>
        <td><?= format_price($p['price']) ?></td>
        <td><?= $p['stock'] ?></td>
        <td><span class="status-pill status-<?= clean($p['status']) ?>"><?= clean($p['status']) ?></span></td>
        <td>
          <a href="<?= BASE_URL ?>admin/product-form.php?id=<?= $p['id'] ?>" class="icon-btn">&#9998;</a>
          <a href="<?= BASE_URL ?>admin/products.php?delete=<?= $p['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="icon-btn" onclick="return confirm('Delete this product?')">&times;</a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$products): ?><tr><td colspan="7" style="text-align:center; color:var(--text-soft);">No products found</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
