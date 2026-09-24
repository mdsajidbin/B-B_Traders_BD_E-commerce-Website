<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    json_response(['success' => false, 'message' => 'Session expired.']);
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) json_response(['success' => false, 'message' => 'Please enter a valid email.']);

try {
    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, status) VALUES (?, 'active')
                            ON DUPLICATE KEY UPDATE status = 'active', unsubscribed_at = NULL");
    $stmt->execute([$email]);
    json_response(['success' => true, 'message' => 'Subscribed successfully!']);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Could not subscribe. Try again later.']);
}
