<?php
require_once __DIR__ . '/../config/config.php';
require_login();
$pageTitle = 'My Wishlist';
$user = current_user();

$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM wishlists w
                        JOIN products p ON p.id = w.product_id
                        JOIN categories c ON c.id = p.category_id
                        WHERE w.user_id = ? ORDER BY w.created_at DESC");
$stmt->execute([$user['id']]);
$products = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Home</a> / Wishlist</div>
  <div class="account-layout">
    <?php include __DIR__ . '/includes-nav.php'; ?>
    <div>
      <h3 style="margin-bottom:14px;">My Wishlist</h3>
      <?php if ($products): ?>
        <div class="product-grid cols-4">
          <?php foreach ($products as $product): include __DIR__ . '/../includes/product-card.php'; endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state"><div class="icon">&#9825;</div><h3>Your wishlist is empty</h3><a href="<?= BASE_URL ?>shop.php" class="btn btn-primary">Browse Products</a></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
