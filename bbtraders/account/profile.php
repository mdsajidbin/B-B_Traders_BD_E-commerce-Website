<?php
require_once __DIR__ . '/../config/config.php';
require_login();
$pageTitle = 'Profile Settings';
$user = current_user();
$message = null; $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postal = trim($_POST['postal_code'] ?? '');

        if ($first === '' || $last === '') $errors[] = 'Name is required.';

        if (empty($errors)) {
            $upd = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, address=?, city=?, postal_code=? WHERE id=?");
            $upd->execute([$first, $last, $phone, $address, $city, $postal, $user['id']]);
            $message = 'Profile updated successfully.';
            $user = current_user();
        }

        if (!empty($_POST['new_password'])) {
            if (!password_verify($_POST['current_password'] ?? '', $pdo->query("SELECT password FROM users WHERE id=" . (int)$user['id'])->fetchColumn())) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($_POST['new_password']) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } else {
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $user['id']]);
                $message = 'Password updated successfully.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Home</a> / Profile Settings</div>
  <div class="account-layout">
    <?php include __DIR__ . '/includes-nav.php'; ?>
    <div class="account-card">
      <h3 style="margin-bottom:14px;">Profile Settings</h3>
      <?php if ($message): ?><div class="alert alert-success"><?= clean($message) ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="form-row">
          <div class="form-group"><label>First Name</label><input class="form-control" name="first_name" value="<?= clean($user['first_name']) ?>"></div>
          <div class="form-group"><label>Last Name</label><input class="form-control" name="last_name" value="<?= clean($user['last_name']) ?>"></div>
        </div>
        <div class="form-group"><label>Email</label><input class="form-control" value="<?= clean($user['email']) ?>" disabled></div>
        <div class="form-group"><label>Phone</label><input class="form-control" name="phone" value="<?= clean($user['phone']) ?>"></div>
        <div class="form-group"><label>Address</label><textarea class="form-control" name="address" rows="2"><?= clean($user['address']) ?></textarea></div>
        <div class="form-row">
          <div class="form-group"><label>City</label><input class="form-control" name="city" value="<?= clean($user['city']) ?>"></div>
          <div class="form-group"><label>Postal Code</label><input class="form-control" name="postal_code" value="<?= clean($user['postal_code']) ?>"></div>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>

      <hr style="border:none; border-top:1px solid var(--border); margin:26px 0;">
      <h4 style="margin-bottom:14px;">Change Password</h4>
      <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label>Current Password</label><input class="form-control" type="password" name="current_password"></div>
        <div class="form-group"><label>New Password</label><input class="form-control" type="password" name="new_password"></div>
        <button type="submit" class="btn btn-outline">Update Password</button>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
