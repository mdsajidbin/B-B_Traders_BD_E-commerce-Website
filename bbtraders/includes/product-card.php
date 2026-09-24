<?php
/** Expects $product array in scope. */
$rating = product_rating($product['id']);
$discount = discount_percent($product['price'], $product['old_price']);
$inStock = (int) $product['stock'] > 0;
$catName = $product['category_name'] ?? '';
?>
<div class="product-card">
  <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($product['slug']) ?>" class="product-thumb">
    <?php if (!empty($product['badge'])): ?>
      <span class="product-badge badge-<?= clean($product['badge']) ?>"><?= clean($product['badge']) ?></span>
    <?php endif; ?>
    <?php if ($discount > 0): ?><span class="product-discount">-<?= $discount ?>%</span><?php endif; ?>
    <img src="<?= clean(product_main_image($product)) ?>" alt="<?= clean($product['name']) ?>" loading="lazy">
  </a>
  <button class="wishlist-btn js-wishlist-toggle" data-product-id="<?= $product['id'] ?>" title="Add to wishlist">&#9825;</button>
  <div class="product-info">
    <?php if ($catName): ?><span class="product-cat"><?= clean($catName) ?></span><?php endif; ?>
    <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($product['slug']) ?>" class="product-title"><?= clean($product['name']) ?></a>
    <div class="product-rating">
      <span class="stars">&#9733;</span> <?= $rating['avg'] ?: '0.0' ?> <span>(<?= $rating['total'] ?>)</span>
    </div>
    <div class="product-price">
      <span class="price-current"><?= format_price($product['price']) ?></span>
      <?php if ($product['old_price'] > $product['price']): ?><span class="price-old"><?= format_price($product['old_price']) ?></span><?php endif; ?>
    </div>
    <span class="stock-note <?= $inStock ? '' : 'out' ?>"><?= $inStock ? 'In Stock' : 'Out of Stock' ?></span>
  </div>
  <div class="product-actions">
    <button class="btn btn-primary js-add-to-cart" data-product-id="<?= $product['id'] ?>" <?= $inStock ? '' : 'disabled' ?>>
      <?= $inStock ? 'Add to Cart' : 'Sold Out' ?>
    </button>
    <?php if ($inStock): ?>
    <button class="btn btn-gold js-buy-now" data-product-id="<?= $product['id'] ?>">Order Now</button>
    <?php endif; ?>
  </div>
</div>
