<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Login';

if (is_logged_in()) redirect(is_admin() ? 'admin/index.php' : 'account/dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pass, $user['password'])) {
            $errors[] = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'Your account is not active. Please contact support.';
        } else {
            login_user($user['id']);
            $redirectTo = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);
            if ($redirectTo) redirect($redirectTo);
            redirect(in_array($user['role'], ['admin', 'super_admin'], true) ? 'admin/index.php' : 'account/dashboard.php');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="auth-wrap">
    <h2 style="margin-bottom:6px;">Welcome Back</h2>
    <p style="color:var(--text-soft); font-size:13.5px; margin-bottom:20px;">Login to your B&amp;B TRADERS BD account.</p>
    <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div><?php endif; ?>
    <form method="post" action="<?= BASE_URL ?>login.php">
      <?= csrf_field() ?>
      <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required value="<?= clean($_POST['email'] ?? '') ?>"></div>
      <div class="form-group"><label>Password</label><input class="form-control" type="password" name="password" required></div>
      <button type="submit" class="btn btn-primary btn-block">Login</button>
    </form>
    <p style="text-align:center; margin-top:16px; font-size:13.5px;">New here? <a href="<?= BASE_URL ?>register.php" style="color:var(--green); font-weight:700;">Create an account</a></p>
    <p style="text-align:center; margin-top:8px; font-size:12px; color:var(--text-soft);">Demo: demo@bbtradersbd.com / (your seeded password)</p>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
