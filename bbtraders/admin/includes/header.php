<?php
require_once __DIR__ . '/../../config/config.php';
require_admin();
$admin = current_user();
$adminPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? clean($pageTitle) . ' | ' : '' ?>Admin - B&B TRADERS BD</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-wrap">
  <aside class="admin-sidebar">
    <div class="brand"><span class="logo-badge">B&amp;B</span> <span>Admin Panel</span></div>
    <a href="<?= BASE_URL ?>admin/index.php" class="<?= $adminPage === 'index.php' ? 'active' : '' ?>">&#128202; Dashboard</a>
    <a href="<?= BASE_URL ?>admin/products.php" class="<?= in_array($adminPage, ['products.php', 'product-form.php']) ? 'active' : '' ?>">&#128230; Products</a>
    <a href="<?= BASE_URL ?>admin/sliders.php" class="<?= $adminPage === 'sliders.php' ? 'active' : '' ?>">&#127916; Home Posters</a>
    <a href="<?= BASE_URL ?>admin/categories.php" class="<?= $adminPage === 'categories.php' ? 'active' : '' ?>">&#128193; Categories</a>
    <a href="<?= BASE_URL ?>admin/orders.php" class="<?= in_array($adminPage, ['orders.php', 'order-view.php']) ? 'active' : '' ?>">&#128179; Orders</a>
    <a href="<?= BASE_URL ?>admin/customers.php" class="<?= $adminPage === 'customers.php' ? 'active' : '' ?>">&#128101; Customers</a>
    <a href="<?= BASE_URL ?>admin/coupons.php" class="<?= $adminPage === 'coupons.php' ? 'active' : '' ?>">&#127991; Coupons</a>
    <a href="<?= BASE_URL ?>admin/reviews.php" class="<?= $adminPage === 'reviews.php' ? 'active' : '' ?>">&#11088; Reviews</a>
    <a href="<?= BASE_URL ?>admin/settings.php" class="<?= $adminPage === 'settings.php' ? 'active' : '' ?>">&#9881; Settings</a>
    <a href="<?= BASE_URL ?>index.php">&#8617; View Store</a>
    <a href="<?= BASE_URL ?>logout.php">&#128682; Logout</a>
  </aside>
  <div>
    <div class="admin-topbar">
      <h3 style="margin:0;"><?= clean($pageTitle ?? 'Dashboard') ?></h3>
      <span style="font-size:13.5px; color:var(--text-soft);">Hi, <?= clean($admin['first_name']) ?> (<?= clean($admin['role']) ?>)</span>
    </div>
    <div class="admin-main">
