<?php
require_once __DIR__ . '/config/config.php';

$pageTitle = 'Home';

// --- Stats (real, from DB) ---
$totalProducts  = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn();
$totalCustomers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='member'")->fetchColumn();
$totalOrders    = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$avgRatingRow   = $pdo->query("SELECT ROUND(AVG(rating),1) AS r FROM reviews WHERE status='approved'")->fetch();
$avgRating      = $avgRatingRow['r'] ?? 0;

// --- Featured products ---
$featured = $pdo->query("SELECT p.*, c.name AS category_name FROM products p
                          JOIN categories c ON c.id = p.category_id
                          WHERE p.status='active' AND p.featured = 1
                          ORDER BY p.created_at DESC LIMIT 10")->fetchAll();

// --- New arrivals ---
$newArrivals = $pdo->query("SELECT p.*, c.name AS category_name FROM products p
                             JOIN categories c ON c.id = p.category_id
                             WHERE p.status='active'
                             ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

// --- Best sellers (by order_items qty sold) ---
$bestSellers = $pdo->query("SELECT p.*, c.name AS category_name, COALESCE(SUM(oi.quantity),0) AS sold
                             FROM products p
                             JOIN categories c ON c.id = p.category_id
                             LEFT JOIN order_items oi ON oi.product_id = p.id
                             WHERE p.status='active'
                             GROUP BY p.id
                             ORDER BY sold DESC, p.created_at DESC LIMIT 5")->fetchAll();

// --- Deal of the day: biggest discount ---
$deal = $pdo->query("SELECT p.*, c.name AS category_name FROM products p
                      JOIN categories c ON c.id = p.category_id
                      WHERE p.status='active' AND p.old_price IS NOT NULL AND p.old_price > p.price
                      ORDER BY (p.old_price - p.price) / p.old_price DESC LIMIT 1")->fetch();

// --- Recent approved reviews ---
$reviews = $pdo->query("SELECT r.*, p.name AS product_name, u.first_name, u.last_name
                         FROM reviews r
                         JOIN products p ON p.id = r.product_id
                         JOIN users u ON u.id = r.user_id
                         WHERE r.status = 'approved'
                         ORDER BY r.created_at DESC LIMIT 3")->fetchAll();

require_once __DIR__ . '/includes/header.php';
$homePosters = get_home_posters();
$mainPoster = $homePosters[0] ?? [
    'eyebrow' => 'B&B TRADERS BD',
    'title' => 'Quality Products. Better Everyday.',
    'subtitle' => 'Shop premium products across fashion, beauty, lifestyle, appliances and more — all in one place.',
    'button_text' => 'Shop Now',
    'button_link' => BASE_URL . 'shop.php',
    'image_url' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=900&q=85',
];
?>

<section class="hero">
  <div class="container">
    <div>
      <span class="hero-label"><?= clean($mainPoster['eyebrow'] ?? 'B&amp;B TRADERS BD') ?></span>
      <h1><?= clean($mainPoster['title'] ?? 'Quality Products. Better Everyday.') ?></h1>
      <p><?= clean($mainPoster['subtitle'] ?? 'Shop premium products across fashion, beauty, lifestyle, appliances and more — all in one place.') ?></p>
      <div class="hero-cta">
        <?php if (!empty($mainPoster['button_link'])): ?>
          <a href="<?= clean(normalize_frontend_link($mainPoster['button_link'], BASE_URL . 'shop.php')) ?>" class="btn btn-gold"><?= clean($mainPoster['button_text'] ?: 'Shop Now') ?></a>
        <?php endif; ?>
      </div>
    </div>
    <div class="hero-image-wrap">
      <img src="<?= clean($mainPoster['image_url'] ?? 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=900&q=85') ?>" alt="<?= clean($mainPoster['title'] ?? 'Featured products') ?>">
      <?php if (!empty($homePosters[1])): ?>
        <div class="hero-badge b1"><?= clean($homePosters[1]['title']) ?></div>
      <?php elseif (!empty($mainPoster['badge_1'])): ?>
        <div class="hero-badge b1"><?= clean($mainPoster['badge_1']) ?></div>
      <?php else: ?>
        <div class="hero-badge b1">&#10003; Exclusive Deals</div>
      <?php endif; ?>
      <?php if (!empty($homePosters[2])): ?>
        <div class="hero-badge b2"><?= clean($homePosters[2]['title']) ?></div>
      <?php elseif (!empty($mainPoster['badge_2'])): ?>
        <div class="hero-badge b2"><?= clean($mainPoster['badge_2']) ?></div>
      <?php else: ?>
        <div class="hero-badge b2">Up to 40% Off</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="stats-strip">
  <div class="container">
    <div class="stat-item"><div class="num"><?= number_format($totalProducts) ?>+</div><div class="label">Products</div></div>
    <div class="stat-item"><div class="num"><?= number_format($totalCustomers) ?>+</div><div class="label">Customers</div></div>
    <div class="stat-item"><div class="num"><?= number_format($totalOrders) ?>+</div><div class="label">Orders Delivered</div></div>
    <div class="stat-item"><div class="num"><?= $avgRating ?: '—' ?></div><div class="label">Average Rating</div></div>
  </div>
</section>

<div class="container">
  <section class="trust-strip">
    <div class="container" style="padding:0;">
      <div class="trust-item"><div class="icon">&#128666;</div><div><div class="title">Free Delivery</div><div class="sub">On selected products</div></div></div>
      <div class="trust-item"><div class="icon">&#8635;</div><div><div class="title">7-Day Returns</div><div class="sub">Easy return policy</div></div></div>
      <div class="trust-item"><div class="icon">&#128274;</div><div><div class="title">Secure Payment</div><div class="sub">Safe &amp; protected checkout</div></div></div>
      <div class="trust-item"><div class="icon">&#11088;</div><div><div class="title">Quality Products</div><div class="sub">Verified &amp; trusted</div></div></div>
      <div class="trust-item"><div class="icon">&#127911;</div><div><div class="title">Customer Support</div><div class="sub">Always here to help</div></div></div>
    </div>
  </section>

  <section class="section">
    <div class="section-head">
      <div><h2>Shop by Category</h2><p>Explore our full range of categories</p></div>
      <a href="<?= BASE_URL ?>shop.php" class="view-all">View All &rarr;</a>
    </div>
    <div class="category-grid">
      <?php foreach (get_categories() as $cat): ?>
        <a href="<?= BASE_URL ?>shop.php?category=<?= urlencode($cat['slug']) ?>" class="category-card">
          <div class="cicon"><?= $cat['icon'] ?: '🛍️' ?></div>
          <h4><?= clean($cat['name']) ?></h4>
          <div class="ccount"><?= $cat['product_count'] ?> Products</div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ($deal): $dr = product_rating($deal['id']); ?>
  <section class="section" style="padding-top:0;">
    <div class="deal-banner">
      <div>
        <span class="hero-label" style="background:rgba(255,255,255,.35); color:#3a2c05;">Deal of the Day</span>
        <h3 style="margin:10px 0 6px;"><?= clean($deal['name']) ?></h3>
        <p style="margin:0;">Now <strong><?= format_price($deal['price']) ?></strong> instead of <s><?= format_price($deal['old_price']) ?></s> — save <?= discount_percent($deal['price'], $deal['old_price']) ?>%!</p>
      </div>
      <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($deal['slug']) ?>" class="btn btn-primary">Grab This Deal</a>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($featured): ?>
  <section class="section">
    <div class="section-head">
      <div><h2>Featured Products</h2><p>Hand-picked premium picks for you</p></div>
      <a href="<?= BASE_URL ?>shop.php" class="view-all">View All &rarr;</a>
    </div>
    <div class="product-grid">
      <?php foreach ($featured as $product): include __DIR__ . '/includes/product-card.php'; endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="section">
    <div class="section-head">
      <div><h2>New Arrivals</h2><p>Fresh additions to our marketplace</p></div>
      <a href="<?= BASE_URL ?>shop.php?sort=newest" class="view-all">View All &rarr;</a>
    </div>
    <div class="product-grid">
      <?php foreach ($newArrivals as $product): include __DIR__ . '/includes/product-card.php'; endforeach; ?>
    </div>
  </section>

  <!-- Top Verified Sellers Section -->
  <section class="section" style="padding-bottom:10px;">
    <div class="section-head">
      <div><h2>Top Verified Best Sellers</h2><p>Top rated stores &amp; verified merchants</p></div>
      <a href="<?= BASE_URL ?>best-sellers.php" class="view-all">All Sellers &rarr;</a>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
      <?php foreach (array_slice(get_best_sellers_list(), 0, 4) as $s): ?>
        <a href="<?= BASE_URL ?>best-sellers.php?seller=<?= urlencode($s['id']) ?>" class="category-card" style="text-align:left; display:flex; align-items:center; gap:14px; padding:16px;">
          <img src="<?= clean($s['avatar']) ?>" alt="<?= clean($s['name']) ?>" style="width:48px; height:48px; border-radius:50%; object-fit:cover; border:2px solid var(--border); flex-shrink:0;">
          <div style="overflow:hidden;">
            <div style="font-size:11px; font-weight:700; color:<?= clean($s['badge_color']) ?>;"><?= clean($s['badge']) ?></div>
            <h4 style="font-size:14px; margin:2px 0 3px; white-space:nowrap; text-overflow:ellipsis; overflow:hidden;"><?= clean($s['name']) ?></h4>
            <div style="font-size:12px; color:var(--text-soft);">&#9733; <?= $s['rating'] ?> &middot; <?= clean($s['orders_count']) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ($bestSellers): ?>
  <section class="section">
    <div class="section-head">
      <div><h2>Best Selling Products</h2><p>Most loved by our customers</p></div>
      <a href="<?= BASE_URL ?>best-sellers.php" class="view-all">View All Best Sellers &rarr;</a>
    </div>
    <div class="product-grid">
      <?php foreach ($bestSellers as $product): include __DIR__ . '/includes/product-card.php'; endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($reviews): ?>
  <section class="section">
    <div class="section-head">
      <div><h2>What Our Customers Say</h2></div>
    </div>
    <div class="product-grid cols-4" style="grid-template-columns:repeat(3,1fr);">
      <?php foreach ($reviews as $r): ?>
        <div class="review-card">
          <div class="stars"><?= str_repeat('&#9733;', (int) $r['rating']) . str_repeat('&#9734;', 5 - (int) $r['rating']) ?></div>
          <p style="font-size:14px; color:var(--text-soft);">"<?= clean($r['review_text']) ?>"</p>
          <div class="reviewer"><?= clean($r['first_name'] . ' ' . $r['last_name']) ?></div>
          <div class="rmeta">on <?= clean($r['product_name']) ?> &middot; <?= time_ago($r['created_at']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
