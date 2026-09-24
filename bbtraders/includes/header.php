<?php
if (!defined('BASE_URL')) { require_once __DIR__ . '/../config/config.php'; }
$currentUser = current_user();
$cartCount   = cart_count();
$categories  = get_categories();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? clean($pageTitle) . ' | ' : '' ?><?= clean(get_setting('site_name', 'B&B TRADERS BD')) ?></title>
<meta name="description" content="<?= clean($pageDescription ?? 'Shop premium products across fashion, beauty, lifestyle, appliances and more.') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>

<div class="topbar">
  <div class="container">
    <span><?= clean(get_setting('announcement_text', 'Free Delivery on Selected Products')) ?></span>
    <span>
      <?php if ($currentUser): ?>
        Hi, <?= clean($currentUser['first_name']) ?> &nbsp;|&nbsp; <a href="<?= BASE_URL ?>logout.php">Logout</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>login.php">Login</a> / <a href="<?= BASE_URL ?>register.php">Sign Up</a>
      <?php endif; ?>
    </span>
  </div>
</div>

<header class="site-header">
  <div class="container header-main">
    <button class="mobile-toggle" id="mobileToggle" aria-label="Menu">&#9776;</button>
    <a href="<?= BASE_URL ?>index.php" class="brand">
      <span class="logo-badge">B&amp;B</span>
      <span>B&amp;B TRADERS BD<small>PREMIUM MARKETPLACE</small></span>
    </a>

    <div class="header-search">
      <form action="<?= BASE_URL ?>shop.php" method="get">
        <input type="text" name="q" placeholder="Search products, categories and more..." value="<?= clean($_GET['q'] ?? '') ?>">
        <button type="submit">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        </button>
      </form>
    </div>

    <div class="header-actions">
      <a href="<?= BASE_URL ?>track-order.php" class="header-action">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M3 3h18v18H3z" opacity="0"/><path d="M16 3h5v5M21 3l-9 9M8 21H3v-5M3 21l9-9"/></svg>
        Track
      </a>
      <a href="<?= BASE_URL . ($currentUser ? 'account/wishlist.php' : 'login.php') ?>" class="header-action">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.6l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 000-7.8z"/></svg>
        Wishlist
      </a>
      <a href="<?= BASE_URL . ($currentUser ? 'account/dashboard.php' : 'login.php') ?>" class="header-action">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
        Account
      </a>
      <a href="<?= BASE_URL ?>cart.php" class="header-action">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 002 1.6h9.7a2 2 0 002-1.6L23 6H6"/></svg>
        Cart
        <span class="badge-count" id="cartBadge" style="display:<?= $cartCount > 0 ? 'flex' : 'none' ?>"><?= $cartCount ?></span>
      </a>
    </div>
  </div>

  <nav class="main-nav" id="mainNav">
    <div class="container">
      <a href="<?= BASE_URL ?>shop.php" class="all-cat-btn">&#9776; All Categories</a>
      <a href="<?= BASE_URL ?>index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>
      <a href="<?= BASE_URL ?>shop.php" class="<?= $currentPage === 'shop.php' ? 'active' : '' ?>">Shop</a>
      <a href="<?= BASE_URL ?>best-sellers.php" class="<?= $currentPage === 'best-sellers.php' ? 'active' : '' ?>">Best Sellers</a>
      <a href="<?= BASE_URL ?>contact.php">Contact</a>
      <?php if (!$currentUser): ?><a href="<?= BASE_URL ?>login.php">Login / Sign Up</a><?php endif; ?>
    </div>
  </nav>
</header>
<main>
