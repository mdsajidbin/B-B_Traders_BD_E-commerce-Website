<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    json_response(['success' => false, 'message' => 'Session expired.']);
}

$user = current_user();
if (!$user) {
    json_response(['success' => false, 'login_required' => true, 'message' => 'Please login to use wishlist.']);
}

$productId = (int) ($_POST['product_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user['id'], $productId]);
$existing = $stmt->fetch();

if ($existing) {
    $del = $pdo->prepare("DELETE FROM wishlists WHERE id = ?");
    $del->execute([$existing['id']]);
    json_response(['success' => true, 'wishlisted' => false, 'message' => 'Removed from wishlist']);
} else {
    $ins = $pdo->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
    $ins->execute([$user['id'], $productId]);
    json_response(['success' => true, 'wishlisted' => true, 'message' => 'Added to wishlist']);
}
