<?php $accCurrent = basename($_SERVER['SCRIPT_NAME']); ?>
<nav class="account-nav">
  <a href="<?= BASE_URL ?>account/dashboard.php" class="<?= $accCurrent === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
  <a href="<?= BASE_URL ?>account/orders.php" class="<?= $accCurrent === 'orders.php' || $accCurrent === 'order-detail.php' ? 'active' : '' ?>">My Orders</a>
  <a href="<?= BASE_URL ?>account/wishlist.php" class="<?= $accCurrent === 'wishlist.php' ? 'active' : '' ?>">Wishlist</a>
  <a href="<?= BASE_URL ?>account/profile.php" class="<?= $accCurrent === 'profile.php' ? 'active' : '' ?>">Profile Settings</a>
  <a href="<?= BASE_URL ?>logout.php">Logout</a>
</nav>
