<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $sort = (int) ($_POST['sort_order'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $id = (int) ($_POST['id'] ?? 0);

    if ($name === '') {
        $errors[] = 'Category name is required.';
    } else {
        $slug = slugify($name);
        if ($id) {
            $pdo->prepare("UPDATE categories SET name=?, slug=?, icon=?, sort_order=?, status=? WHERE id=?")
                ->execute([$name, $slug, $icon, $sort, $status, $id]);
        } else {
            $pdo->prepare("INSERT INTO categories (name, slug, icon, sort_order, status) VALUES (?,?,?,?,?)")
                ->execute([$name, $slug, $icon, $sort, $status]);
        }
        redirect('admin/categories.php?saved=1');
    }
}

if (isset($_GET['delete']) && verify_csrf($_GET['csrf_token'] ?? '')) {
    try {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([(int) $_GET['delete']]);
    } catch (Exception $e) { /* has products, ignore */ }
    redirect('admin/categories.php?deleted=1');
}

$pageTitle = 'Categories';
require_once __DIR__ . '/includes/header.php';
$categories = get_categories(false);
?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Category saved.</div><?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?><div class="alert alert-success">Category removed.</div><?php endif; ?>

<div class="shop-layout" style="grid-template-columns:1fr 1.6fr;">
  <div class="admin-panel">
    <h4 style="margin-bottom:14px;">Add Category</h4>
    <form method="post">
      <?= csrf_field() ?>
      <div class="form-group"><label>Name</label><input class="form-control" name="name" required></div>
      <div class="form-group"><label>Icon (emoji)</label><input class="form-control" name="icon" placeholder="🛍️"></div>
      <div class="form-group"><label>Sort Order</label><input class="form-control" type="number" name="sort_order" value="0"></div>
      <div class="form-group">
        <label>Status</label>
        <select class="form-control" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Save Category</button>
    </form>
  </div>
  <div class="admin-panel">
    <table class="data-table">
      <thead><tr><th>Icon</th><th>Name</th><th>Products</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($categories as $c): ?>
        <tr>
          <td style="font-size:20px;"><?= $c['icon'] ?: '🛍️' ?></td>
          <td><?= clean($c['name']) ?></td>
          <td><?= $c['product_count'] ?></td>
          <td><span class="status-pill status-<?= clean($c['status']) ?>"><?= clean($c['status']) ?></span></td>
          <td><a href="<?= BASE_URL ?>admin/categories.php?delete=<?= $c['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="icon-btn" onclick="return confirm('Delete this category?')">&times;</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
