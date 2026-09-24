<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Create Account';

if (is_logged_in()) redirect('account/dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $pass  = $_POST['password'] ?? '';
        $pass2 = $_POST['confirm_password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');

        if ($first === '' || $last === '') $errors[] = 'First and last name are required.';
        if (!$email) $errors[] = 'A valid email is required.';
        if (strlen($pass) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($pass !== $pass2) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $errors[] = 'An account with this email already exists.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $ins = $pdo->prepare("INSERT INTO users (first_name,last_name,email,password,phone,role,status) VALUES (?,?,?,?,?, 'member','active')");
                $ins->execute([$first, $last, $email, $hash, $phone ?: null]);
                login_user($pdo->lastInsertId());
                redirect('account/dashboard.php');
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="auth-wrap">
    <h2 style="margin-bottom:6px;">Create Account</h2>
    <p style="color:var(--text-soft); font-size:13.5px; margin-bottom:20px;">Join B&amp;B TRADERS BD today.</p>
    <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div><?php endif; ?>
    <form method="post" action="<?= BASE_URL ?>register.php">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="form-group"><label>First Name</label><input class="form-control" name="first_name" required value="<?= clean($_POST['first_name'] ?? '') ?>"></div>
        <div class="form-group"><label>Last Name</label><input class="form-control" name="last_name" required value="<?= clean($_POST['last_name'] ?? '') ?>"></div>
      </div>
      <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required value="<?= clean($_POST['email'] ?? '') ?>"></div>
      <div class="form-group"><label>Phone</label><input class="form-control" name="phone" value="<?= clean($_POST['phone'] ?? '') ?>"></div>
      <div class="form-row">
        <div class="form-group"><label>Password</label><input class="form-control" type="password" name="password" required></div>
        <div class="form-group"><label>Confirm Password</label><input class="form-control" type="password" name="confirm_password" required></div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>
    <p style="text-align:center; margin-top:16px; font-size:13.5px;">Already have an account? <a href="<?= BASE_URL ?>login.php" style="color:var(--green); font-weight:700;">Login</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
