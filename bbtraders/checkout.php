<?php
require_once __DIR__ . '/config/config.php';

$pageTitle = 'Checkout';
$cart = cart_details();
$items = $cart['items'];
$errors = [];

if (empty($items)) {
    redirect('cart.php');
}

$user = current_user();

/* -----------------------------------------------------------
   PROCESS POST BEFORE ANY HTML OUTPUT (prevents header errors)
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $name    = trim($_POST['shipping_name'] ?? '');
        $email   = trim($_POST['shipping_email'] ?? '');
        $phone   = trim($_POST['shipping_phone'] ?? '');
        $address = trim($_POST['shipping_address'] ?? '');
        $city    = trim($_POST['shipping_city'] ?? '');
        $postal  = trim($_POST['shipping_postal_code'] ?? '');
        $notes   = trim($_POST['notes'] ?? '');
        $paymentMethod = $_POST['payment_method'] ?? 'cod';

        if ($name === '') $errors[] = 'Full name is required.';
        if ($phone === '') $errors[] = 'Phone number is required.';
        if ($address === '') $errors[] = 'Shipping address is required.';
        if ($city === '') $errors[] = 'City is required.';
        if (!in_array($paymentMethod, ['cod', 'bkash', 'nagad', 'stripe'], true)) $errors[] = 'Invalid payment method.';

        // Re-fetch fresh cart/prices/stock right before placing the order
        $cart = cart_details();
        $items = $cart['items'];
        if (empty($items)) $errors[] = 'Your cart is empty.';

        $subtotal = $cart['subtotal'];
        $discount = 0;
        $couponRow = null;
        $couponCode = $_SESSION['applied_coupon'] ?? null;
        if ($couponCode) {
            $result = validate_coupon($couponCode, $subtotal);
            if ($result['valid']) { $discount = $result['discount']; $couponRow = $result['coupon']; }
        }
        $threshold = (float) get_setting('free_shipping_threshold', 0);
        $fee = (float) get_setting('shipping_fee', 0);
        $shippingFee = ($threshold > 0 && $subtotal >= $threshold) ? 0 : $fee;
        $total = max(0, $subtotal - $discount) + $shippingFee;

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Lock & verify stock for every item
                foreach ($items as $item) {
                    if ($item['variant_id']) {
                        $s = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ? FOR UPDATE");
                        $s->execute([$item['variant_id']]);
                    } else {
                        $s = $pdo->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
                        $s->execute([$item['product_id']]);
                    }
                    $stockRow = $s->fetch();
                    if (!$stockRow || (int) $stockRow['stock'] < $item['qty']) {
                        throw new Exception('"' . $item['name'] . '" no longer has enough stock.');
                    }
                }

                $orderNumber = generate_order_number();
                $ins = $pdo->prepare("INSERT INTO orders
                    (user_id, order_number, subtotal, discount_amount, shipping_cost, total_amount, currency,
                     coupon_id, coupon_code, payment_method, payment_status, order_status,
                     shipping_name, shipping_email, shipping_phone, shipping_address, shipping_city, shipping_postal_code, notes)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $ins->execute([
                    $user['id'] ?? null, $orderNumber, $subtotal, $discount, $shippingFee, $total, get_setting('active_currency', 'BDT'),
                    $couponRow['id'] ?? null, $couponRow['code'] ?? null, $paymentMethod, 'pending',
                    $paymentMethod === 'cod' ? 'confirmed' : 'pending',
                    $name, $email ?: null, $phone, $address, $city, $postal ?: null, $notes ?: null,
                ]);
                $orderId = $pdo->lastInsertId();

                foreach ($items as $item) {
                    $oi = $pdo->prepare("INSERT INTO order_items
                        (order_id, product_id, variant_id, product_name, variant_name, quantity, unit_price, subtotal)
                        VALUES (?,?,?,?,?,?,?,?)");
                    $oi->execute([
                        $orderId, $item['product_id'], $item['variant_id'], $item['name'], $item['variant_label'],
                        $item['qty'], $item['price'], $item['line_total'],
                    ]);

                    if ($item['variant_id']) {
                        $pdo->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?")->execute([$item['qty'], $item['variant_id']]);
                    } else {
                        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$item['qty'], $item['product_id']]);
                    }
                }

                $pay = $pdo->prepare("INSERT INTO payments (order_id, payment_method, amount, currency, status)
                                       VALUES (?,?,?,?, 'pending')");
                $pay->execute([$orderId, $paymentMethod, $total, get_setting('active_currency', 'BDT')]);

                if ($couponRow) {
                    $pdo->prepare("INSERT INTO coupon_usages (coupon_id, user_id, order_id, discount_amount) VALUES (?,?,?,?)")
                        ->execute([$couponRow['id'], $user['id'] ?? null, $orderId, $discount]);
                    $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$couponRow['id']]);
                }

                $pdo->commit();

                cart_clear();
                unset($_SESSION['applied_coupon']);
                $_SESSION['last_order_id'] = $orderId;

                redirect('order-success.php?order=' . $orderId);
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = $e->getMessage() ?: 'Could not place order. Please try again.';
            }
        }
    }
}

/* -----------------------------------------------------------
   RENDER PAGE
----------------------------------------------------------- */
$cart = cart_details();
$items = $cart['items'];
$subtotal = $cart['subtotal'];
$discount = 0;
$couponCode = $_SESSION['applied_coupon'] ?? null;
if ($couponCode) {
    $result = validate_coupon($couponCode, $subtotal);
    if ($result['valid']) $discount = $result['discount'];
}
$threshold = (float) get_setting('free_shipping_threshold', 0);
$fee = (float) get_setting('shipping_fee', 0);
$shippingFee = ($threshold > 0 && $subtotal >= $threshold) ? 0 : $fee;
$total = max(0, $subtotal - $discount) + $shippingFee;

require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Home</a> / Checkout</div>
  <h2 style="margin-bottom:20px;">Checkout</h2>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>checkout.php" class="cart-layout">
    <?= csrf_field() ?>
    <div>
      <div class="account-card" style="margin-bottom:20px;">
        <h4 style="margin-bottom:16px;">Shipping Information</h4>
        <div class="form-row">
          <div class="form-group"><label>Full Name *</label><input class="form-control" name="shipping_name" required value="<?= clean($user['first_name'] . ' ' . $user['last_name'] ?? '') ?>"></div>
          <div class="form-group"><label>Phone *</label><input class="form-control" name="shipping_phone" required value="<?= clean($user['phone'] ?? '') ?>"></div>
        </div>
        <div class="form-group"><label>Email</label><input class="form-control" type="email" name="shipping_email" value="<?= clean($user['email'] ?? '') ?>"></div>
        <div class="form-group"><label>Address *</label><textarea class="form-control" name="shipping_address" rows="3" required><?= clean($user['address'] ?? '') ?></textarea></div>
        <div class="form-row">
          <div class="form-group"><label>City *</label><input class="form-control" name="shipping_city" required value="<?= clean($user['city'] ?? '') ?>"></div>
          <div class="form-group"><label>Postal Code</label><input class="form-control" name="shipping_postal_code" value="<?= clean($user['postal_code'] ?? '') ?>"></div>
        </div>
        <div class="form-group"><label>Order Notes (optional)</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
      </div>

      <div class="account-card">
        <h4 style="margin-bottom:16px;">Payment Method</h4>
        <label style="display:flex; gap:10px; padding:10px 0; align-items:center;"><input type="radio" name="payment_method" value="cod" checked> Cash on Delivery</label>
        <label style="display:flex; gap:10px; padding:10px 0; align-items:center;"><input type="radio" name="payment_method" value="bkash"> bKash</label>
        <label style="display:flex; gap:10px; padding:10px 0; align-items:center;"><input type="radio" name="payment_method" value="nagad"> Nagad</label>
        <label style="display:flex; gap:10px; padding:10px 0; align-items:center;"><input type="radio" name="payment_method" value="stripe"> Card (Stripe)</label>
      </div>
    </div>

    <div>
      <div class="cart-summary">
        <h4 style="margin-bottom:14px;">Your Order</h4>
        <?php foreach ($items as $item): ?>
          <div class="summary-row"><span><?= clean($item['name']) ?> &times;<?= $item['qty'] ?></span><span><?= format_price($item['line_total']) ?></span></div>
        <?php endforeach; ?>
        <div class="summary-row"><span>Subtotal</span><span><?= format_price($subtotal) ?></span></div>
        <?php if ($discount > 0): ?><div class="summary-row"><span>Discount</span><span>-<?= format_price($discount) ?></span></div><?php endif; ?>
        <div class="summary-row"><span>Shipping</span><span><?= $shippingFee > 0 ? format_price($shippingFee) : 'Free' ?></span></div>
        <div class="summary-row total"><span>Total</span><span><?= format_price($total) ?></span></div>
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:14px;">Place Order</button>
      </div>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
