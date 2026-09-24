<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$csrf   = $_POST['csrf_token'] ?? '';

if (!verify_csrf($csrf)) {
    json_response(['success' => false, 'message' => 'Session expired, please refresh the page.']);
}

switch ($action) {
    case 'add': {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $variantId = !empty($_POST['variant_id']) ? (int) $_POST['variant_id'] : null;
        $qty       = max(1, (int) ($_POST['qty'] ?? 1));

        $stmt = $pdo->prepare("SELECT id, stock, status FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product || $product['status'] !== 'active') {
            json_response(['success' => false, 'message' => 'Product not available.']);
        }

        $stock = (int) $product['stock'];
        if ($variantId) {
            $vstmt = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ? AND product_id = ? AND status='active'");
            $vstmt->execute([$variantId, $productId]);
            $variant = $vstmt->fetch();
            if (!$variant) json_response(['success' => false, 'message' => 'Selected variant not available.']);
            $stock = (int) $variant['stock'];
        }
        if ($stock <= 0) json_response(['success' => false, 'message' => 'This product is out of stock.']);

        cart_add($productId, $qty, $variantId);
        json_response(['success' => true, 'message' => 'Added to cart', 'cart_count' => cart_count()]);
    }

    case 'update': {
        $key = $_POST['key'] ?? '';
        $qty = max(0, (int) ($_POST['qty'] ?? 1));
        cart_update($key, $qty);
        json_response(['success' => true, 'cart_count' => cart_count()]);
    }

    case 'remove': {
        $key = $_POST['key'] ?? '';
        cart_remove($key);
        json_response(['success' => true, 'cart_count' => cart_count()]);
    }

    case 'apply_coupon': {
        $code = trim($_POST['coupon_code'] ?? '');
        if ($code === '') json_response(['success' => false, 'message' => 'Enter a coupon code.']);
        $cart = cart_details();
        $result = validate_coupon($code, $cart['subtotal']);
        if (!$result['valid']) json_response(['success' => false, 'message' => $result['message']]);
        $_SESSION['applied_coupon'] = strtoupper($code);
        json_response(['success' => true, 'message' => 'Coupon applied!', 'discount' => $result['discount']]);
    }

    case 'remove_coupon': {
        unset($_SESSION['applied_coupon']);
        json_response(['success' => true]);
    }

    default:
        json_response(['success' => false, 'message' => 'Unknown action.']);
}
