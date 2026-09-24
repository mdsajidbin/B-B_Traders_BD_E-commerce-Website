<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$product = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) redirect('admin/products.php');
}

$errors = [];

/* Process POST before any HTML output */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $sku = trim($_POST['sku'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $oldPrice = $_POST['old_price'] !== '' ? (float) $_POST['old_price'] : null;
        $stock = (int) ($_POST['stock'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $badge = $_POST['badge'] ?: null;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $status = $_POST['status'] ?? 'active';
        $productType = $_POST['product_type'] ?? 'Physical';
        $imageUrl = trim($_POST['main_image_url'] ?? '');

        if ($name === '') $errors[] = 'Product name is required.';
        if (!$categoryId) $errors[] = 'Please select a category.';
        if ($sku === '') $errors[] = 'SKU is required.';
        if ($price <= 0) $errors[] = 'Price must be greater than 0.';

        // Handle file upload (optional, overrides URL)
        $finalImage = $product['main_image'] ?? $imageUrl;
        if (!empty($_FILES['main_image_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['main_image_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Only jpg, png, webp, gif images are allowed.';
            } else {
                $newName = uniqid('prod_') . '.' . $ext;
                if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);
                if (move_uploaded_file($_FILES['main_image_file']['tmp_name'], UPLOAD_PATH . $newName)) {
                    $finalImage = UPLOAD_URL . $newName;
                } else {
                    $errors[] = 'Image upload failed.';
                }
            }
        } elseif ($imageUrl !== '') {
            $finalImage = $imageUrl;
        }

        if (empty($errors)) {
            $slug = slugify($name);
            // Ensure slug uniqueness
            $baseSlug = $slug; $i = 1;
            while (true) {
                $chk = $pdo->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
                $chk->execute([$slug, $id]);
                if (!$chk->fetch()) break;
                $slug = $baseSlug . '-' . (++$i);
            }

            if ($product) {
                $upd = $pdo->prepare("UPDATE products SET category_id=?, name=?, slug=?, sku=?, product_type=?, description=?, price=?, old_price=?, main_image=?, badge=?, stock=?, featured=?, status=? WHERE id=?");
                $upd->execute([$categoryId, $name, $slug, $sku, $productType, $description, $price, $oldPrice, $finalImage, $badge, $stock, $featured, $status, $id]);
                $productId = $id;
            } else {
                $ins = $pdo->prepare("INSERT INTO products (category_id, name, slug, sku, product_type, description, price, old_price, main_image, badge, stock, featured, status)
                                       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $ins->execute([$categoryId, $name, $slug, $sku, $productType, $description, $price, $oldPrice, $finalImage, $badge, $stock, $featured, $status]);
                $productId = $pdo->lastInsertId();
            }

            // Variants — smart upsert: UPDATE existing rows by ID, INSERT new ones, DELETE removed ones
            $vIds    = $_POST['variant_id']    ?? [];
            $vNames  = $_POST['variant_name']  ?? [];
            $vValues = $_POST['variant_value'] ?? [];
            $vPrices = $_POST['variant_price'] ?? [];
            $vStocks = $_POST['variant_stock'] ?? [];
            $submittedIds = [];
            for ($vi = 0; $vi < count($vNames); $vi++) {
                if (trim($vNames[$vi]) === '' && trim($vValues[$vi]) === '') continue;
                $vp  = ($vPrices[$vi] !== '') ? (float) $vPrices[$vi] : null;
                $vs  = (int) ($vStocks[$vi] ?? 0);
                $vid = (int) ($vIds[$vi] ?? 0);
                $vStatus = ($vs <= 0) ? 'out_of_stock' : 'active';
                if ($vid > 0) {
                    // Update existing variant
                    $vupd = $pdo->prepare("UPDATE product_variants SET variant_name=?, variant_value=?, price=?, stock=?, status=? WHERE id=? AND product_id=?");
                    $vupd->execute([trim($vNames[$vi]), trim($vValues[$vi]), $vp, $vs, $vStatus, $vid, $productId]);
                    $submittedIds[] = $vid;
                } else {
                    // Insert new variant
                    $vsku = 'BB-' . strtoupper(substr(md5($productId . $vNames[$vi] . $vValues[$vi] . microtime()), 0, 8));
                    $vins = $pdo->prepare("INSERT INTO product_variants (product_id, variant_name, variant_value, price, stock, sku, is_default, status) VALUES (?,?,?,?,?,?,?,?)");
                    $vins->execute([$productId, trim($vNames[$vi]), trim($vValues[$vi]), $vp, $vs, $vsku, (empty($submittedIds) ? 1 : 0), $vStatus]);
                    $submittedIds[] = (int) $pdo->lastInsertId();
                }
            }
            // Delete variant rows that were removed from the form
            if ($product && !empty($submittedIds)) {
                $placeholders = implode(',', array_fill(0, count($submittedIds), '?'));
                $delParams = array_merge([$productId], $submittedIds);
                $pdo->prepare("DELETE FROM product_variants WHERE product_id=? AND id NOT IN ($placeholders)")->execute($delParams);
            } elseif ($product && empty($submittedIds)) {
                // All variants removed
                $pdo->prepare("DELETE FROM product_variants WHERE product_id=?")->execute([$productId]);
            }

            // Gallery / secondary images (product_images table)
            // 1) Remove any existing images the admin checked for deletion
            $deleteImageIds = $_POST['delete_image'] ?? [];
            foreach ($deleteImageIds as $delId) {
                $delId = (int) $delId;
                if ($delId > 0) {
                    // Only delete images that belong to this product
                    $pdo->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?")->execute([$delId, $productId]);
                }
            }

            // 2) Figure out the next sort_order so new images append after existing ones
            $sortStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM product_images WHERE product_id = ?");
            $sortStmt->execute([$productId]);
            $nextSort = (int) $sortStmt->fetchColumn();

            // 3) Handle multiple uploaded gallery image files
            if (!empty($_FILES['gallery_images_file']['name'][0])) {
                $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $fileCount = count($_FILES['gallery_images_file']['name']);
                for ($gi = 0; $gi < $fileCount; $gi++) {
                    if ($_FILES['gallery_images_file']['error'][$gi] !== UPLOAD_ERR_OK) continue;
                    $origName = $_FILES['gallery_images_file']['name'][$gi];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExt, true)) continue;
                    $newName = uniqid('gal_') . '.' . $ext;
                    if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);
                    if (move_uploaded_file($_FILES['gallery_images_file']['tmp_name'][$gi], UPLOAD_PATH . $newName)) {
                        $imgIns = $pdo->prepare("INSERT INTO product_images (product_id, image_path, alt_text, sort_order) VALUES (?,?,?,?)");
                        $imgIns->execute([$productId, UPLOAD_URL . $newName, $name, $nextSort]);
                        $nextSort++;
                    }
                }
            }

            // 4) Handle additional gallery image URLs (repeatable rows)
            $galleryUrls = $_POST['gallery_image_url'] ?? [];
            foreach ($galleryUrls as $gUrl) {
                $gUrl = trim($gUrl);
                if ($gUrl === '') continue;
                $imgIns = $pdo->prepare("INSERT INTO product_images (product_id, image_path, alt_text, sort_order) VALUES (?,?,?,?)");
                $imgIns->execute([$productId, $gUrl, $name, $nextSort]);
                $nextSort++;
            }

            redirect('admin/products.php?saved=1');
        }
    }
}

$pageTitle = $product ? 'Edit Product' : 'Add Product';
require_once __DIR__ . '/includes/header.php';

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$variants = [];
$galleryImages = [];
if ($product) {
    $vstmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
    $vstmt->execute([$product['id']]);
    $variants = $vstmt->fetchAll();

    $gstmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
    $gstmt->execute([$product['id']]);
    $galleryImages = $gstmt->fetchAll();
}
?>
<?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div><?php endif; ?>

<div class="admin-panel">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-row">
      <div class="form-group"><label>Product Name *</label><input class="form-control" name="name" required value="<?= clean($product['name'] ?? $_POST['name'] ?? '') ?>"></div>
      <div class="form-group"><label>SKU *</label><input class="form-control" name="sku" required value="<?= clean($product['sku'] ?? $_POST['sku'] ?? '') ?>"></div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Category *</label>
        <select name="category_id" class="form-control" required>
          <option value="">Select category</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($product['category_id'] ?? null) == $c['id'] ? 'selected' : '' ?>><?= clean($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Product Type</label>
        <select name="product_type" class="form-control">
          <option value="Physical" <?= ($product['product_type'] ?? '') === 'Physical' ? 'selected' : '' ?>>Physical</option>
          <option value="Digital" <?= ($product['product_type'] ?? '') === 'Digital' ? 'selected' : '' ?>>Digital</option>
        </select>
      </div>
    </div>
    <div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="4"><?= clean($product['description'] ?? '') ?></textarea></div>
    <div class="form-row">
      <div class="form-group"><label>Price *</label><input class="form-control" type="number" step="0.01" name="price" required value="<?= clean($product['price'] ?? '') ?>"></div>
      <div class="form-group"><label>Old Price</label><input class="form-control" type="number" step="0.01" name="old_price" value="<?= clean($product['old_price'] ?? '') ?>"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Stock *</label><input class="form-control" type="number" name="stock" required value="<?= clean($product['stock'] ?? 0) ?>"></div>
      <div class="form-group">
        <label>Badge</label>
        <select name="badge" class="form-control">
          <option value="">None</option>
          <?php foreach (['NEW','HOT','SALE'] as $b): ?>
            <option value="<?= $b ?>" <?= ($product['badge'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Main Image URL</label><input class="form-control" name="main_image_url" placeholder="https://..." value="<?= clean($product['main_image'] ?? '') ?>"></div>
      <div class="form-group"><label>Or Upload Image</label><input class="form-control" type="file" name="main_image_file" accept="image/*"></div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
          <option value="active" <?= ($product['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="form-group" style="display:flex; align-items:center; gap:8px; margin-top:26px;">
        <input type="checkbox" name="featured" id="featured" <?= !empty($product['featured']) ? 'checked' : '' ?>>
        <label for="featured" style="margin:0;">Feature on Homepage</label>
      </div>
    </div>

    <h4 style="margin:22px 0 10px;">Additional Images / Gallery (optional)</h4>
    <p style="font-size:13px; color:var(--text-soft); margin-bottom:12px;">Add extra photos for this product — different angles, colors, or what's included. These show as a thumbnail strip on the product page.</p>

    <?php if ($galleryImages): ?>
      <div style="display:flex; flex-wrap:wrap; gap:14px; margin-bottom:16px;">
        <?php foreach ($galleryImages as $gimg): ?>
          <div style="width:110px;">
            <img src="<?= clean($gimg['image_path']) ?>" style="width:110px; height:110px; object-fit:cover; border-radius:10px; border:1px solid var(--border);">
            <label style="display:flex; align-items:center; gap:6px; font-size:12px; margin-top:6px; color:var(--danger);">
              <input type="checkbox" name="delete_image[]" value="<?= $gimg['id'] ?>"> Remove
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="form-group">
      <label>Upload New Gallery Images (you can select multiple files at once)</label>
      <input class="form-control" type="file" name="gallery_images_file[]" accept="image/*" multiple>
    </div>

    <label style="font-size:13px; font-weight:700; display:block; margin:14px 0 8px;">Or Add Gallery Images by URL</label>
    <div id="galleryUrlRows">
      <div class="form-group">
        <input class="form-control" name="gallery_image_url[]" placeholder="https://... (another product photo)">
      </div>
    </div>
    <button type="button" class="btn btn-outline btn-sm" id="addGalleryUrlRow">+ Add Another Image URL</button>

    <h4 style="margin:22px 0 6px;">Variants <span style="font-size:12px;font-weight:400;color:var(--text-soft);">(optional — set stock to 0 to mark as Out of Stock)</span></h4>

    <div id="variantRows">
      <?php if ($variants): foreach ($variants as $v):
        $isOos = ((int)$v['stock'] <= 0 || $v['status'] === 'out_of_stock');
      ?>
        <div class="form-row variant-row" style="grid-template-columns:1fr 1fr 1fr 1fr auto; align-items:center; border:1px solid <?= $isOos ? '#e74c3c55' : 'var(--border)' ?>; border-radius:8px; padding:10px; margin-bottom:8px; background:<?= $isOos ? '#fff0ef' : 'var(--surface)' ?>;">
          <input type="hidden" name="variant_id[]" value="<?= $v['id'] ?>">
          <div>
            <label style="font-size:11px;color:var(--text-soft);display:block;margin-bottom:3px;">Variant Type</label>
            <input class="form-control" name="variant_name[]" placeholder="e.g. Size" value="<?= clean($v['variant_name']) ?>">
          </div>
          <div>
            <label style="font-size:11px;color:var(--text-soft);display:block;margin-bottom:3px;">Value</label>
            <input class="form-control" name="variant_value[]" placeholder="e.g. Large" value="<?= clean($v['variant_value']) ?>">
          </div>
          <div>
            <label style="font-size:11px;color:var(--text-soft);display:block;margin-bottom:3px;">Price (optional)</label>
            <input class="form-control" name="variant_price[]" type="number" step="0.01" placeholder="Price" value="<?= clean($v['price']) ?>">
          </div>
          <div>
            <label style="font-size:11px;color:var(--text-soft);display:block;margin-bottom:3px;">
              Stock
              <?php if ($isOos): ?>
                <span style="background:#e74c3c;color:#fff;font-size:10px;border-radius:4px;padding:1px 6px;margin-left:4px;">OUT OF STOCK</span>
              <?php else: ?>
                <span style="background:#27ae60;color:#fff;font-size:10px;border-radius:4px;padding:1px 6px;margin-left:4px;">IN STOCK</span>
              <?php endif; ?>
            </label>
            <input class="form-control variant-stock-input" name="variant_stock[]" type="number" placeholder="Stock" value="<?= clean($v['stock']) ?>" style="border-color:<?= $isOos ? '#e74c3c' : '' ?>;" oninput="updateVariantStatus(this)">
          </div>
          <button type="button" class="btn btn-sm" onclick="removeVariantRow(this)" style="background:#e74c3c;color:#fff;padding:6px 10px;border-radius:6px;align-self:flex-end;">✕</button>
        </div>
      <?php endforeach; else: ?>
        <div class="form-row variant-row" style="grid-template-columns:1fr 1fr 1fr 1fr auto; align-items:center; border:1px solid var(--border); border-radius:8px; padding:10px; margin-bottom:8px; background:var(--surface);">
          <input type="hidden" name="variant_id[]" value="">
          <div>
            <label style="font-size:11px;color:var(--text-soft);display:block;margin-bottom:3px;">Variant Type</label>
            <input class="form-control" name="variant_name[]" placeholder="e.g. Size">
          </div>
          <div>
            <label style="font-size:11px;color:var(--text-soft);display:block;margin-bottom:3px;">Value</label>
            <input class="form-control" name="variant_value[]" placeholder="e.g. Large">
          </div>
          <div>
            <label style="font-size:11px;color:var(--text-soft);display:block;margin-bottom:3px;">Price (optional)</label>
            <input class="form-control" name="variant_price[]" type="number" step="0.01" placeholder="Price">
          </div>
          <div>
            <label style="font-size:11px;color:var(--text-soft);display:block;margin-bottom:3px;">Stock</label>
            <input class="form-control variant-stock-input" name="variant_stock[]" type="number" placeholder="Stock" oninput="updateVariantStatus(this)">
          </div>
          <button type="button" class="btn btn-sm" onclick="removeVariantRow(this)" style="background:#e74c3c;color:#fff;padding:6px 10px;border-radius:6px;align-self:flex-end;">✕</button>
        </div>
      <?php endif; ?>
    </div>
    <button type="button" class="btn btn-outline btn-sm" id="addVariantRow">+ Add Variant Row</button>

    <div style="margin-top:24px;">
      <button type="submit" class="btn btn-primary"><?= $product ? 'Update Product' : 'Create Product' ?></button>
      <a href="<?= BASE_URL ?>admin/products.php" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>

<script>
// Dynamically update stock badge color when admin changes the stock number
function updateVariantStatus(input) {
  const row = input.closest('.variant-row');
  const stock = parseInt(input.value) || 0;
  const label = input.previousElementSibling || input.parentElement.querySelector('label');
  const badge = row.querySelector('label span[style*="background"]');
  if (stock <= 0) {
    row.style.borderColor = '#e74c3c55';
    row.style.background = '#fff0ef';
    input.style.borderColor = '#e74c3c';
    if (badge) { badge.textContent = 'OUT OF STOCK'; badge.style.background = '#e74c3c'; }
  } else {
    row.style.borderColor = 'var(--border)';
    row.style.background = 'var(--surface)';
    input.style.borderColor = '';
    if (badge) { badge.textContent = 'IN STOCK'; badge.style.background = '#27ae60'; }
  }
}

// Remove a variant row from the DOM
function removeVariantRow(btn) {
  const wrap = document.getElementById('variantRows');
  if (wrap.querySelectorAll('.variant-row').length <= 1) {
    // Clear fields instead of removing if it's the last row
    btn.closest('.variant-row').querySelectorAll('input:not([type=hidden])').forEach(i => i.value = '');
    btn.closest('.variant-row').querySelector('input[type=hidden]').value = '';
  } else {
    btn.closest('.variant-row').remove();
  }
}

// Add a new blank variant row
document.getElementById('addVariantRow').addEventListener('click', function () {
  const wrap = document.getElementById('variantRows');
  const newRow = document.createElement('div');
  newRow.className = 'form-row variant-row';
  newRow.style.cssText = 'grid-template-columns:1fr 1fr 1fr 1fr auto; align-items:center; border:1px solid var(--border); border-radius:8px; padding:10px; margin-bottom:8px; background:var(--surface);';
  newRow.innerHTML = `
    <input type="hidden" name="variant_id[]" value="">
    <div>
      <label style="font-size:11px;color:var(--text-soft);display:block;margin-bottom:3px;">Variant Type</label>
      <input class="form-control" name="variant_name[]" placeholder="e.g. Size">
    </div>
    <div>
      <label style="font-size:11px;color:var(--text-soft);display:block;margin-bottom:3px;">Value</label>
      <input class="form-control" name="variant_value[]" placeholder="e.g. Large">
    </div>
    <div>
      <label style="font-size:11px;color:var(--text-soft);display:block;margin-bottom:3px;">Price (optional)</label>
      <input class="form-control" name="variant_price[]" type="number" step="0.01" placeholder="Price">
    </div>
    <div>
      <label style="font-size:11px;color:var(--text-soft);display:block;margin-bottom:3px;">Stock</label>
      <input class="form-control variant-stock-input" name="variant_stock[]" type="number" placeholder="Stock" oninput="updateVariantStatus(this)">
    </div>
    <button type="button" class="btn btn-sm" onclick="removeVariantRow(this)" style="background:#e74c3c;color:#fff;padding:6px 10px;border-radius:6px;align-self:flex-end;">✕</button>
  `;
  wrap.appendChild(newRow);
});

document.getElementById('addGalleryUrlRow').addEventListener('click', function () {
  const wrap = document.getElementById('galleryUrlRows');
  const row = wrap.firstElementChild.cloneNode(true);
  row.querySelectorAll('input').forEach(i => i.value = '');
  wrap.appendChild(row);
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>