<?php
require_once __DIR__ . '/config/config.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug
                        FROM products p JOIN categories c ON c.id = p.category_id
                        WHERE p.slug = ? AND p.status = 'active' LIMIT 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="empty-state"><div class="icon">📦</div><h3>Product not found</h3><a href="' . BASE_URL . 'shop.php" class="btn btn-primary">Back to Shop</a></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $product['name'];
$pageDescription = $product['meta_description'] ?: mb_substr(strip_tags($product['description'] ?? ''), 0, 160);

// Gallery images
$imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
$imgStmt->execute([$product['id']]);
$gallery = $imgStmt->fetchAll();

// Variants grouped by variant_name — include out_of_stock too so customers see unavailable sizes
$varStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND status IN ('active','out_of_stock') ORDER BY id");
$varStmt->execute([$product['id']]);
$variants = $varStmt->fetchAll();
$variantGroups = [];
foreach ($variants as $v) $variantGroups[$v['variant_name']][] = $v;


// Reviews
$revStmt = $pdo->prepare("SELECT r.*, u.first_name, u.last_name FROM reviews r
                           JOIN users u ON u.id = r.user_id
                           WHERE r.product_id = ? AND r.status='approved' ORDER BY r.created_at DESC");
$revStmt->execute([$product['id']]);
$reviews = $revStmt->fetchAll();
$rating = product_rating($product['id']);

// Related products (same category)
$relStmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p
                           JOIN categories c ON c.id = p.category_id
                           WHERE p.category_id = ? AND p.id != ? AND p.status='active' LIMIT 5");
$relStmt->execute([$product['category_id'], $product['id']]);
$related = $relStmt->fetchAll();

$wishlisted = false;
if ($u = current_user()) {
    $wstmt = $pdo->prepare("SELECT 1 FROM wishlists WHERE user_id=? AND product_id=?");
    $wstmt->execute([$u['id'], $product['id']]);
    $wishlisted = (bool) $wstmt->fetchColumn();
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="breadcrumb">
    <a href="<?= BASE_URL ?>index.php">Home</a> /
    <a href="<?= BASE_URL ?>shop.php?category=<?= urlencode($product['category_slug']) ?>"><?= clean($product['category_name']) ?></a> /
    <?= clean($product['name']) ?>
  </div>

  <div class="product-detail">
    <div>
      <div class="gallery-main">
        <img src="<?= clean(product_main_image($product)) ?>" alt="<?= clean($product['name']) ?>">
      </div>
      <?php if ($gallery): ?>
      <div class="gallery-thumbs">
        <img src="<?= clean($product['main_image']) ?>" class="active">
        <?php foreach ($gallery as $g): ?>
          <img src="<?= clean($g['image_path']) ?>" alt="<?= clean($g['alt_text']) ?>">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div>
      <?php if (!empty($product['badge'])): ?><span class="product-badge badge-<?= clean($product['badge']) ?>" style="position:static; display:inline-block; margin-bottom:10px;"><?= clean($product['badge']) ?></span><?php endif; ?>
      <h1 class="pd-title"><?= clean($product['name']) ?></h1>
      <div class="pd-meta">
        <span>&#9733; <?= $rating['avg'] ?: '0.0' ?> (<?= $rating['total'] ?> reviews)</span>
        <span>SKU: <?= clean($product['sku']) ?></span>
        <span class="stock-note <?= $product['stock'] > 0 ? '' : 'out' ?>"><?= $product['stock'] > 0 ? $product['stock'] . ' in stock' : 'Out of stock' ?></span>
      </div>
      <div class="pd-price">
        <span class="price-current" id="pdCurrentPrice"><?= format_price($product['price']) ?></span>
        <?php if ($product['old_price'] > $product['price']): ?>
          <span class="price-old"><?= format_price($product['old_price']) ?></span>
          <span class="product-discount" style="position:static;">-<?= discount_percent($product['price'], $product['old_price']) ?>%</span>
        <?php endif; ?>
      </div>
      <p style="color:var(--text-soft); font-size:14.5px;"><?= nl2br(clean(mb_substr($product['description'] ?? '', 0, 220))) ?></p>

      <?php foreach ($variantGroups as $groupName => $opts): ?>
        <div class="variant-group">
          <h5><?= clean($groupName) ?></h5>
          <div class="variant-options">
            <?php foreach ($opts as $i => $opt): ?>
              <span class="variant-chip <?= $opt['is_default'] || $i === 0 ? 'active' : '' ?>"
                    data-variant-id="<?= $opt['id'] ?>"
                    data-price="<?= $opt['price'] ?? $product['price'] ?>"
                    data-price-formatted="<?= format_price($opt['price'] ?? $product['price']) ?>">
                <?= clean($opt['variant_value']) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if ($variants): ?>
        <input type="hidden" id="selectedVariantId" value="<?= $variants[0]['id'] ?>">
      <?php endif; ?>

      <div id="pdQtyWrap">
        <h5 style="font-size:13px; font-weight:700; margin-bottom:8px;">Quantity</h5>
        <div class="qty-selector">
          <button type="button" class="js-qty-btn" data-dir="down">−</button>
          <input type="text" id="pdQty" value="1" readonly>
          <button type="button" class="js-qty-btn" data-dir="up">+</button>
        </div>
      </div>

      <div class="pd-actions">
        <button class="btn btn-primary js-add-to-cart" data-product-id="<?= $product['id'] ?>" <?= $product['stock'] > 0 ? '' : 'disabled' ?>>
          <?= $product['stock'] > 0 ? 'Add to Cart' : 'Sold Out' ?>
        </button>
        <?php if ($product['stock'] > 0): ?>
        <button class="btn btn-gold js-buy-now" data-product-id="<?= $product['id'] ?>">Order Now</button>
        <?php endif; ?>
        <button class="btn btn-outline js-wishlist-toggle <?= $wishlisted ? 'active' : '' ?>" data-product-id="<?= $product['id'] ?>">&#9825; Wishlist</button>
      </div>

      <div class="pd-tabs">
        <div class="pd-tab active" data-target="tabDesc">Description</div>
        <div class="pd-tab" data-target="tabReviews">Reviews (<?= count($reviews) ?>)</div>
      </div>
      <div id="tabDesc" class="pd-tab-content">
        <p style="color:var(--text-soft); font-size:14.5px; line-height:1.7;"><?= nl2br(clean($product['description'])) ?></p>
      </div>
      <div id="tabReviews" class="pd-tab-content" style="display:none;">
        <?php if ($reviews): foreach ($reviews as $r): ?>
          <div class="review-card" style="margin-bottom:12px;">
            <div class="stars"><?= str_repeat('&#9733;', (int) $r['rating']) . str_repeat('&#9734;', 5 - (int) $r['rating']) ?></div>
            <p style="font-size:14px;"><?= clean($r['review_text']) ?></p>
            <div class="reviewer"><?= clean($r['first_name'] . ' ' . $r['last_name']) ?></div>
            <div class="rmeta"><?= time_ago($r['created_at']) ?></div>
          </div>
        <?php endforeach; else: ?>
          <p style="color:var(--text-soft);">No reviews yet for this product.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($related): ?>
  <section class="section">
    <div class="section-head"><h2>Related Products</h2></div>
    <div class="product-grid">
      <?php foreach ($related as $product): include __DIR__ . '/includes/product-card.php'; endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
