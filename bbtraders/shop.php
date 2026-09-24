<?php
require_once __DIR__ . '/config/config.php';

$pageTitle = 'Shop';

$q          = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$minPrice   = $_GET['min_price'] ?? '';
$maxPrice   = $_GET['max_price'] ?? '';
$sort       = $_GET['sort'] ?? 'latest';
$page       = max(1, (int) ($_GET['page'] ?? 1));
$perPage    = 12;

$where  = ["p.status = 'active'"];
$params = [];

if ($q !== '') {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%";
}
$activeCategory = null;
if ($categorySlug !== '') {
    $activeCategory = get_category_by_slug($categorySlug);
    if ($activeCategory) { $where[] = "p.category_id = ?"; $params[] = $activeCategory['id']; }
}
if ($minPrice !== '' && is_numeric($minPrice)) { $where[] = "p.price >= ?"; $params[] = $minPrice; }
if ($maxPrice !== '' && is_numeric($maxPrice)) { $where[] = "p.price <= ?"; $params[] = $maxPrice; }
if ($sort === 'deals') { $where[] = "p.old_price IS NOT NULL AND p.old_price > p.price"; }

$whereSql = implode(' AND ', $where);

$orderBy = "p.created_at DESC";
$joinBestseller = "";
switch ($sort) {
    case 'price_low':  $orderBy = "p.price ASC"; break;
    case 'price_high': $orderBy = "p.price DESC"; break;
    case 'newest':     $orderBy = "p.created_at DESC"; break;
    case 'bestselling':
        $joinBestseller = "LEFT JOIN order_items oi ON oi.product_id = p.id";
        $orderBy = "sold DESC";
        break;
    case 'rating':     $orderBy = "avg_rating DESC"; break;
    default:           $orderBy = "p.created_at DESC";
}

$selectSold = $joinBestseller ? ", COALESCE(SUM(oi.quantity),0) AS sold" : "";
$groupBy    = $joinBestseller ? "GROUP BY p.id" : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();
$pg = paginate($totalItems, $perPage, $page);

$sql = "SELECT p.*, c.name AS category_name,
        (SELECT ROUND(AVG(rating),1) FROM reviews r WHERE r.product_id = p.id AND r.status='approved') AS avg_rating
        $selectSold
        FROM products p
        JOIN categories c ON c.id = p.category_id
        $joinBestseller
        WHERE $whereSql
        $groupBy
        ORDER BY $orderBy
        LIMIT $perPage OFFSET {$pg['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = get_categories();

require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Home</a> / Shop<?= $activeCategory ? ' / ' . clean($activeCategory['name']) : '' ?></div>

  <div class="shop-layout">
    <aside>
      <form method="get" action="<?= BASE_URL ?>shop.php">
        <?php if ($q): ?><input type="hidden" name="q" value="<?= clean($q) ?>"><?php endif; ?>
        <div class="filter-box">
          <h4>Categories</h4>
          <label><input type="radio" name="category" value="" <?= $categorySlug === '' ? 'checked' : '' ?> onchange="this.form.submit()"> All Categories</label>
          <?php foreach ($categories as $cat): ?>
            <label><input type="radio" name="category" value="<?= clean($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'checked' : '' ?> onchange="this.form.submit()"> <?= clean($cat['name']) ?> (<?= $cat['product_count'] ?>)</label>
          <?php endforeach; ?>
        </div>
        <div class="filter-box">
          <h4>Price Range</h4>
          <div style="display:flex; gap:8px;">
            <input type="number" name="min_price" placeholder="Min" value="<?= clean($minPrice) ?>" class="form-control" style="padding:8px;">
            <input type="number" name="max_price" placeholder="Max" value="<?= clean($maxPrice) ?>" class="form-control" style="padding:8px;">
          </div>
          <button type="submit" class="btn btn-outline btn-block" style="margin-top:12px;">Apply Filter</button>
        </div>
      </form>
    </aside>

    <div>
      <div class="shop-toolbar">
        <span><?= $totalItems ?> products found<?= $q ? ' for "' . clean($q) . '"' : '' ?></span>
        <form method="get" action="<?= BASE_URL ?>shop.php" id="sortForm">
          <?php foreach (['q' => $q, 'category' => $categorySlug, 'min_price' => $minPrice, 'max_price' => $maxPrice] as $k => $v): ?>
            <?php if ($v !== ''): ?><input type="hidden" name="<?= $k ?>" value="<?= clean($v) ?>"><?php endif; ?>
          <?php endforeach; ?>
          <select name="sort" onchange="this.form.submit()">
            <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Latest</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="bestselling" <?= $sort === 'bestselling' ? 'selected' : '' ?>>Best Selling</option>
            <option value="deals" <?= $sort === 'deals' ? 'selected' : '' ?>>Deals</option>
            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top Rated</option>
          </select>
        </form>
      </div>

      <?php if ($products): ?>
        <div class="product-grid cols-4">
          <?php foreach ($products as $product): include __DIR__ . '/includes/product-card.php'; endforeach; ?>
        </div>

        <?php if ($pg['total_pages'] > 1): ?>
        <div class="pagination">
          <?php
            $qs = $_GET; unset($qs['page']);
            for ($i = 1; $i <= $pg['total_pages']; $i++):
              $qs['page'] = $i;
          ?>
            <a href="?<?= http_build_query($qs) ?>" class="<?= $i === $pg['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="empty-state">
          <div class="icon">&#128269;</div>
          <h3>No products found</h3>
          <p>Try adjusting your filters or search terms.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
