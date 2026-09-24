<?php
require_once __DIR__ . '/config/config.php';

$pageTitle = 'Best Sellers & Top Verified Stores';
$pageDescription = 'Discover top-rated sellers, verified store owners, and best-selling products at B&B TRADERS BD.';

$sellers = get_best_sellers_list();
$selectedSellerId = trim($_GET['seller'] ?? '');
$activeSeller = $selectedSellerId ? get_seller_by_id($selectedSellerId) : null;

// Overall bestselling products from the DB
$topSellingProducts = $pdo->query("SELECT p.*, c.name AS category_name, COALESCE(SUM(oi.quantity),0) AS sold
                                    FROM products p
                                    JOIN categories c ON c.id = p.category_id
                                    LEFT JOIN order_items oi ON oi.product_id = p.id
                                    WHERE p.status='active'
                                    GROUP BY p.id
                                    ORDER BY sold DESC, p.created_at DESC LIMIT 8")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ---------- Best Sellers Page Styling ---------- */
.sellers-hero {
  background: linear-gradient(120deg, var(--green-deep) 0%, var(--green-dark) 55%, var(--green) 100%);
  color: #fff;
  padding: 48px 0;
  margin-bottom: 36px;
  border-radius: 0 0 var(--radius) var(--radius);
}
.sellers-hero h1 {
  color: #fff;
  font-size: 34px;
  margin-bottom: 10px;
}
.sellers-hero p {
  color: rgba(255,255,255,.85);
  font-size: 15.5px;
  max-width: 650px;
  margin: 0;
}
.sellers-stat-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(212,167,44,.18);
  color: var(--gold);
  border: 1px solid rgba(212,167,44,.35);
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 12.5px;
  font-weight: 700;
  margin-bottom: 14px;
}

.seller-filter-strip {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding-bottom: 12px;
  margin-bottom: 28px;
}
.seller-tab-btn {
  padding: 9px 18px;
  border-radius: 999px;
  background: #fff;
  border: 1px solid var(--border);
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text);
  white-space: nowrap;
  transition: all .2s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.seller-tab-btn:hover, .seller-tab-btn.active {
  background: var(--green);
  color: #fff;
  border-color: var(--green);
  box-shadow: var(--shadow-sm);
}

.seller-card-full {
  background: #fff;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  margin-bottom: 32px;
  overflow: hidden;
  transition: box-shadow .25s ease;
}
.seller-card-full:hover {
  box-shadow: var(--shadow-md);
}

.seller-header {
  background: linear-gradient(135deg, #f8fbf9 0%, #edf7f1 100%);
  padding: 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
  border-bottom: 1px solid var(--border);
}
.seller-profile-group {
  display: flex;
  align-items: center;
  gap: 18px;
}
.seller-avatar-wrap {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid #fff;
  box-shadow: var(--shadow-sm);
  flex-shrink: 0;
  background: #e2e8f0;
}
.seller-avatar-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.seller-info h3 {
  font-size: 20px;
  margin: 0 0 4px;
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.seller-badge-tag {
  font-size: 11.5px;
  padding: 3px 10px;
  border-radius: 999px;
  color: #fff;
  font-weight: 700;
  letter-spacing: .02em;
}
.seller-meta {
  font-size: 13px;
  color: var(--text-soft);
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}
.seller-meta span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.seller-action-group {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.btn-whatsapp {
  background: #25D366;
  color: #fff;
  font-weight: 600;
}
.btn-whatsapp:hover {
  background: #1ebc59;
  color: #fff;
}

.seller-body {
  padding: 24px;
}
.seller-stats-strip {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  background: #fbfcfb;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 16px 20px;
  margin-bottom: 20px;
}
.seller-stat-box {
  text-align: center;
}
.seller-stat-box .val {
  font-size: 18px;
  font-weight: 800;
  color: var(--green-deep);
  font-family: var(--font-heading);
}
.seller-stat-box .lbl {
  font-size: 12px;
  color: var(--text-soft);
  font-weight: 600;
  margin-top: 2px;
}

.seller-details-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
}
.seller-bio-box {
  font-size: 14px;
  line-height: 1.6;
  color: #374151;
}
.seller-bio-box strong {
  color: var(--green-deep);
}
.seller-contact-box {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 16px;
  font-size: 13px;
}
.seller-contact-box h5 {
  font-size: 13px;
  margin: 0 0 10px;
  color: var(--green-deep);
  text-transform: uppercase;
  letter-spacing: .05em;
}
.seller-contact-item {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  color: #4b5563;
}
.seller-contact-item:last-child {
  margin-bottom: 0;
}

.seller-products-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-top: 8px;
  border-top: 1px solid var(--border);
}
.seller-products-heading h4 {
  font-size: 16px;
  margin: 0;
  color: var(--green-deep);
}

@media (max-width: 900px) {
  .seller-stats-strip { grid-template-columns: repeat(2, 1fr); }
  .seller-details-row { grid-template-columns: 1fr; }
  .seller-header { flex-direction: column; align-items: flex-start; }
}
</style>

<div class="sellers-hero">
  <div class="container">
    <div class="sellers-stat-pill">&#10004; Verified Merchant Program</div>
    <h1>Top Best Sellers &amp; Certified Stores</h1>
    <p>Shop with confidence from our highest-rated merchant partners across Bangladesh. Genuine quality, transparent contact, and fast nationwide delivery.</p>
  </div>
</div>

<div class="container">

  <!-- Quick Filter Strip -->
  <div class="seller-filter-strip">
    <a href="<?= BASE_URL ?>best-sellers.php" class="seller-tab-btn <?= empty($selectedSellerId) ? 'active' : '' ?>">
      &#127942; All Top Sellers (<?= count($sellers) ?>)
    </a>
    <?php foreach ($sellers as $s): ?>
      <a href="<?= BASE_URL ?>best-sellers.php?seller=<?= urlencode($s['id']) ?>"
         class="seller-tab-btn <?= $selectedSellerId === $s['id'] ? 'active' : '' ?>">
        <?= clean($s['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php
  // Determine list of sellers to display
  $displaySellers = $activeSeller ? [$activeSeller] : $sellers;
  ?>

  <!-- Sellers Cards Loop -->
  <?php foreach ($displaySellers as $seller):
    // Fetch products belonging to this seller's category
    $sellerCategory = get_category_by_slug($seller['category_slug']);
    $sellerProducts = [];
    if ($sellerCategory) {
      $spStmt = $pdo->prepare("SELECT p.*, c.name AS category_name
                                FROM products p
                                JOIN categories c ON c.id = p.category_id
                                WHERE p.category_id = ? AND p.status = 'active'
                                ORDER BY p.featured DESC, p.created_at DESC
                                LIMIT 4");
      $spStmt->execute([$sellerCategory['id']]);
      $sellerProducts = $spStmt->fetchAll();
    }
  ?>
  <div class="seller-card-full" id="<?= clean($seller['id']) ?>">
    <div class="seller-header">
      <div class="seller-profile-group">
        <div class="seller-avatar-wrap">
          <img src="<?= clean($seller['avatar']) ?>" alt="<?= clean($seller['name']) ?>">
        </div>
        <div class="seller-info">
          <h3>
            <?= clean($seller['name']) ?>
            <span class="seller-badge-tag" style="background:<?= clean($seller['badge_color']) ?>;">
              <?= clean($seller['badge']) ?>
            </span>
          </h3>
          <div class="seller-meta">
            <span>&#128100; Owner: <strong><?= clean($seller['owner']) ?></strong></span>
            <span>&#128205; <?= clean($seller['location']) ?></span>
            <span>&#128197; <?= clean($seller['joined']) ?></span>
            <span style="color:var(--gold); font-weight:700;">&#9733; <?= $seller['rating'] ?> (<?= $seller['reviews_count'] ?> reviews)</span>
          </div>
        </div>
      </div>
      <div class="seller-action-group">
        <a href="<?= BASE_URL ?>shop.php?category=<?= urlencode($seller['category_slug']) ?>" class="btn btn-outline btn-sm">
          Browse Store Catalog &rarr;
        </a>
        <a href="https://wa.me/<?= clean($seller['whatsapp']) ?>?text=<?= urlencode('Hello ' . $seller['name'] . ', I saw your store on B&B TRADERS BD and would like to inquire about products.') ?>"
           target="_blank"
           class="btn btn-whatsapp btn-sm">
          &#128172; Chat on WhatsApp
        </a>
      </div>
    </div>

    <div class="seller-body">
      <!-- Performance Stats -->
      <div class="seller-stats-strip">
        <div class="seller-stat-box">
          <div class="val">&#9733; <?= $seller['rating'] ?> / 5.0</div>
          <div class="lbl">Customer Rating</div>
        </div>
        <div class="seller-stat-box">
          <div class="val"><?= clean($seller['orders_count']) ?></div>
          <div class="lbl">Orders Fulfilled</div>
        </div>
        <div class="seller-stat-box">
          <div class="val"><?= clean($seller['response_rate']) ?></div>
          <div class="lbl">Customer Response</div>
        </div>
        <div class="seller-stat-box">
          <div class="val"><?= clean($seller['response_time']) ?></div>
          <div class="lbl">Avg Response Time</div>
        </div>
      </div>

      <!-- Bio and Direct Contact -->
      <div class="seller-details-row">
        <div class="seller-bio-box">
          <p style="margin:0 0 8px;"><strong>About the Store:</strong> <?= clean($seller['bio']) ?></p>
          <p style="margin:0;"><strong>Specialty:</strong> <?= clean($seller['specialty']) ?></p>
        </div>
        <div class="seller-contact-box">
          <h5>Direct Contact Details</h5>
          <div class="seller-contact-item">
            <span>&#128222;</span>
            <a href="tel:<?= clean($seller['phone']) ?>" style="font-weight:600; color:var(--green);"><?= clean($seller['phone']) ?></a>
          </div>
          <div class="seller-contact-item">
            <span>&#9993;</span>
            <a href="mailto:<?= clean($seller['email']) ?>"><?= clean($seller['email']) ?></a>
          </div>
          <div class="seller-contact-item">
            <span>&#128205;</span>
            <span><?= clean($seller['location']) ?></span>
          </div>
        </div>
      </div>

      <!-- Featured Store Products -->
      <?php if (!empty($sellerProducts)): ?>
        <div class="seller-products-heading">
          <h4>Top Selling Products from <?= clean($seller['name']) ?></h4>
          <a href="<?= BASE_URL ?>shop.php?category=<?= urlencode($seller['category_slug']) ?>" class="view-all" style="font-size:13.5px; font-weight:700; color:var(--green);">
            View all <?= clean($seller['category_name']) ?> &rarr;
          </a>
        </div>
        <div class="product-grid cols-4">
          <?php foreach ($sellerProducts as $product):
            include __DIR__ . '/includes/product-card.php';
          endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Marketplace-Wide Best Selling Products Section -->
  <section class="section" style="padding-top:10px;">
    <div class="section-head">
      <div>
        <h2>Marketplace Best Selling Products</h2>
        <p>Most purchased items by customers across all certified stores</p>
      </div>
      <a href="<?= BASE_URL ?>shop.php?sort=bestselling" class="view-all">See All in Shop &rarr;</a>
    </div>
    <div class="product-grid">
      <?php foreach ($topSellingProducts as $product):
        include __DIR__ . '/includes/product-card.php';
      endforeach; ?>
    </div>
  </section>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
