<?php
require_once __DIR__ . '/../config/config.php';
require_login();
$pageTitle = 'Order Details';
$user = current_user();

$orderId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch();
if (!$order) redirect('account/orders.php');

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    if ($order['order_status'] === 'delivered') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
        $text = trim($_POST['review_text'] ?? '');
        try {
            $ins = $pdo->prepare("INSERT INTO reviews (product_id, user_id, order_id, rating, review_text, status) VALUES (?,?,?,?,?,'pending')");
            $ins->execute([$productId, $user['id'], $orderId, $rating, $text]);
            $message = 'Thanks! Your review has been submitted for approval.';
        } catch (Exception $e) {
            $message = 'You have already reviewed this product for this order.';
        }
    }
}

$itemsStmt = $pdo->prepare("SELECT oi.*, p.slug, p.main_image FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$reviewedStmt = $pdo->prepare("SELECT product_id FROM reviews WHERE order_id = ? AND user_id = ?");
$reviewedStmt->execute([$orderId, $user['id']]);
$reviewedProductIds = array_column($reviewedStmt->fetchAll(), 'product_id');

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Home</a> / <a href="<?= BASE_URL ?>account/orders.php">My Orders</a> / <?= clean($order['order_number']) ?></div>
  <div class="account-layout">
    <?php include __DIR__ . '/includes-nav.php'; ?>
    <div>
      <?php if ($message): ?><div class="alert alert-success"><?= clean($message) ?></div><?php endif; ?>
      <div class="account-card" style="margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px;">
          <div>
            <h3>Order <?= clean($order['order_number']) ?></h3>
            <p style="color:var(--text-soft); font-size:13px;">Placed on <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></p>
          </div>
          <div>
            <span class="status-pill status-<?= clean($order['order_status']) ?>"><?= clean($order['order_status']) ?></span>
            <span class="status-pill status-<?= clean($order['payment_status']) ?>"><?= clean($order['payment_status']) ?></span>
          </div>
        </div>
        <hr style="border:none; border-top:1px solid var(--border); margin:16px 0;">
        <p style="font-size:13.5px;"><strong>Shipping to:</strong> <?= clean($order['shipping_name']) ?>, <?= clean($order['shipping_address']) ?>, <?= clean($order['shipping_city']) ?> <?= clean($order['shipping_postal_code']) ?><br>
        <strong>Phone:</strong> <?= clean($order['shipping_phone']) ?> &middot; <strong>Payment:</strong> <?= strtoupper(clean($order['payment_method'])) ?></p>
      </div>

      <div class="account-card">
        <h4 style="margin-bottom:14px;">Items</h4>
        <?php foreach ($items as $item): ?>
          <div style="display:flex; gap:14px; padding:14px 0; border-bottom:1px solid var(--border); align-items:center;">
            <img src="<?= clean($item['main_image']) ?>" style="width:56px; height:56px; object-fit:cover; border-radius:10px;">
            <div style="flex:1;">
              <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($item['slug']) ?>" style="font-weight:700; font-size:14px;"><?= clean($item['product_name']) ?></a>
              <?php if ($item['variant_name']): ?><div style="font-size:12px; color:var(--text-soft);"><?= clean($item['variant_name']) ?></div><?php endif; ?>
              <div style="font-size:13px; color:var(--text-soft);">Qty: <?= $item['quantity'] ?> &times; <?= format_price($item['unit_price']) ?></div>
            </div>
            <div style="font-weight:700;"><?= format_price($item['subtotal']) ?></div>
          </div>
          <?php if ($order['order_status'] === 'delivered' && !in_array($item['product_id'], $reviewedProductIds)): ?>
            <form method="post" style="background:#f9fbf9; padding:14px; border-radius:10px; margin:8px 0 16px;">
              <?= csrf_field() ?>
              <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
              <label style="font-size:13px; font-weight:700;">Rate this product</label>
              <select name="rating" class="form-control" style="max-width:120px; margin:8px 0;">
                <option value="5">5 - Excellent</option><option value="4">4 - Good</option><option value="3">3 - Average</option><option value="2">2 - Poor</option><option value="1">1 - Bad</option>
              </select>
              <textarea name="review_text" class="form-control" rows="2" placeholder="Write a review..."></textarea>
              <button type="submit" class="btn btn-primary btn-sm" style="margin-top:8px;">Submit Review</button>
            </form>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
