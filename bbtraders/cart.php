<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Shopping Cart';

$cart = cart_details();
$items = $cart['items'];
$subtotal = $cart['subtotal'];

$discount = 0;
$couponCode = $_SESSION['applied_coupon'] ?? null;
$couponMessage = null;
if ($couponCode) {
    $result = validate_coupon($couponCode, $subtotal);
    if ($result['valid']) {
        $discount = $result['discount'];
    } else {
        unset($_SESSION['applied_coupon']);
        $couponCode = null;
    }
}

$shippingFee = 0;
if (!empty($items)) {
    $threshold = (float) get_setting('free_shipping_threshold', 0);
    $fee = (float) get_setting('shipping_fee', 0);
    $shippingFee = ($threshold > 0 && $subtotal >= $threshold) ? 0 : $fee;
}
$total = max(0, $subtotal - $discount) + $shippingFee;

require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Home</a> / Cart</div>
  <h2 style="margin-bottom:20px;">Shopping Cart</h2>

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <div class="icon">&#128722;</div>
      <h3>Your cart is empty</h3>
      <p>Looks like you haven't added anything yet.</p>
      <a href="<?= BASE_URL ?>shop.php" class="btn btn-primary">Start Shopping</a>
    </div>
  <?php else: ?>
  <div class="cart-layout">
    <div>
      <table class="cart-table">
        <thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <div class="cart-product">
                <img src="<?= clean($item['image']) ?>" alt="">
                <div>
                  <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($item['slug']) ?>" style="font-weight:700; font-size:13.5px;"><?= clean($item['name']) ?></a>
                  <?php if ($item['variant_label']): ?><div style="font-size:12px; color:var(--text-soft);"><?= clean($item['variant_label']) ?></div><?php endif; ?>
                </div>
              </div>
            </td>
            <td><?= format_price($item['price']) ?></td>
            <td>
              <div class="qty-selector">
                <button type="button" class="js-qty-btn" data-dir="down">−</button>
                <input type="text" value="<?= $item['qty'] ?>" data-cart-key="<?= clean($item['key']) ?>" readonly>
                <button type="button" class="js-qty-btn" data-dir="up">+</button>
              </div>
              <?php if ($item['qty'] >= $item['stock']): ?><small style="color:var(--danger);">Max stock reached</small><?php endif; ?>
            </td>
            <td style="font-weight:700;"><?= format_price($item['line_total']) ?></td>
            <td><button class="icon-btn js-cart-remove" data-key="<?= clean($item['key']) ?>" title="Remove">&times;</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div>
      <div class="cart-summary">
        <h4 style="margin-bottom:14px;">Order Summary</h4>
        <div class="summary-row"><span>Subtotal</span><span><?= format_price($subtotal) ?></span></div>
        <?php if ($discount > 0): ?><div class="summary-row"><span>Discount (<?= clean($couponCode) ?>)</span><span>-<?= format_price($discount) ?></span></div><?php endif; ?>
        <div class="summary-row"><span>Shipping</span><span><?= $shippingFee > 0 ? format_price($shippingFee) : 'Free' ?></span></div>
        <div class="summary-row total"><span>Total</span><span><?= format_price($total) ?></span></div>

        <form id="couponForm">
          <div class="coupon-box">
            <input type="text" name="coupon_code" placeholder="Coupon code" value="<?= clean($couponCode ?? '') ?>">
            <button type="submit" class="btn btn-outline">Apply</button>
          </div>
        </form>

        <a href="<?= BASE_URL ?>checkout.php" class="btn btn-primary btn-block">Proceed to Checkout</a>
        <a href="<?= BASE_URL ?>shop.php" class="btn btn-outline btn-block" style="margin-top:10px;">Continue Shopping</a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
