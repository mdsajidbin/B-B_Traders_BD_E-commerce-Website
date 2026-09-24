<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$pageTitle = 'Home Posters';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $id = (int) ($_POST['id'] ?? 0);
    $eyebrow = trim($_POST['eyebrow'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $button_text = trim($_POST['button_text'] ?? '');
    $button_link = trim($_POST['button_link'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $badge_1 = trim($_POST['badge_1'] ?? '');
    $badge_2 = trim($_POST['badge_2'] ?? '');
    $sort_order = (int) ($_POST['sort_order'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    if ($title === '') {
        $errors[] = 'Poster title is required.';
    }
    if ($image_url === '') {
        $errors[] = 'Poster image URL is required.';
    }

    if (empty($errors)) {
        $button_link = normalize_frontend_link($button_link, BASE_URL . 'shop.php');
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE home_posters SET eyebrow=?, title=?, subtitle=?, button_text=?, button_link=?, image_url=?, badge_1=?, badge_2=?, sort_order=?, status=? WHERE id=?");
            $stmt->execute([$eyebrow, $title, $subtitle, $button_text, $button_link, $image_url, $badge_1, $badge_2, $sort_order, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO home_posters (eyebrow, title, subtitle, button_text, button_link, image_url, badge_1, badge_2, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$eyebrow, $title, $subtitle, $button_text, $button_link, $image_url, $badge_1, $badge_2, $sort_order, $status]);
        }
        redirect('admin/sliders.php?saved=1');
    }
}

if (isset($_GET['delete']) && verify_csrf($_GET['csrf_token'] ?? '')) {
    $pdo->prepare("DELETE FROM home_posters WHERE id = ?")->execute([(int) $_GET['delete']]);
    redirect('admin/sliders.php?deleted=1');
}

require_once __DIR__ . '/includes/header.php';
$posters = $pdo->query("SELECT * FROM home_posters ORDER BY sort_order ASC, id ASC")->fetchAll();
$editing = null;
if (!empty($_GET['edit'])) {
    $editing = $pdo->prepare("SELECT * FROM home_posters WHERE id = ? LIMIT 1");
    $editing->execute([(int) $_GET['edit']]);
    $editing = $editing->fetch();
}
?>

<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Poster saved.</div><?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?><div class="alert alert-success">Poster removed.</div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div><?php endif; ?>

<div class="shop-layout" style="grid-template-columns: 1fr 1.7fr;">
  <div class="admin-panel">
    <h4 style="margin-bottom:14px;"><?= $editing ? 'Edit Poster' : 'Add Home Poster' ?></h4>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
      <div class="form-group"><label>Eyebrow</label><input class="form-control" name="eyebrow" value="<?= clean($editing['eyebrow'] ?? '') ?>" placeholder="B&B TRADERS BD"></div>
      <div class="form-group"><label>Title</label><input class="form-control" name="title" value="<?= clean($editing['title'] ?? '') ?>" required></div>
      <div class="form-group"><label>Subtitle</label><textarea class="form-control" name="subtitle" rows="3"><?= clean($editing['subtitle'] ?? '') ?></textarea></div>
      <div class="form-row">
        <div class="form-group"><label>Button Text</label><input class="form-control" name="button_text" value="<?= clean($editing['button_text'] ?? 'Shop Now') ?>"></div>
        <div class="form-group"><label>Button Link</label><input class="form-control" name="button_link" value="<?= clean($editing['button_link'] ?? '/shop.php') ?>"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Badge 1</label><input class="form-control" name="badge_1" value="<?= clean($editing['badge_1'] ?? '') ?>" placeholder="Exclusive Deals"></div>
        <div class="form-group"><label>Badge 2</label><input class="form-control" name="badge_2" value="<?= clean($editing['badge_2'] ?? '') ?>" placeholder="Up to 40% Off"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Sort Order</label><input class="form-control" type="number" name="sort_order" value="<?= (int) ($editing['sort_order'] ?? 0) ?>"></div>
        <div class="form-group">
          <label>Status</label>
          <select class="form-control" name="status">
            <option value="active" <?= (($editing['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= (($editing['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label>Image URL</label><input class="form-control" name="image_url" type="url" value="<?= clean($editing['image_url'] ?? '') ?>" required placeholder="https://images.unsplash.com/..."></div>
      <button type="submit" class="btn btn-primary btn-block"><?= $editing ? 'Update Poster' : 'Save Poster' ?></button>
    </form>
  </div>

  <div class="admin-panel">
    <table class="data-table">
      <thead>
        <tr>
          <th>Preview</th>
          <th>Title</th>
          <th>Status</th>
          <th>Order</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posters as $poster): ?>
          <tr>
            <td><img src="<?= clean($poster['image_url']) ?>" alt="<?= clean($poster['title']) ?>" style="width:80px; height:52px; object-fit:cover; border-radius:8px; border:1px solid var(--border);"></td>
            <td><?= clean($poster['title']) ?></td>
            <td><span class="status-pill status-<?= clean($poster['status']) ?>"><?= clean($poster['status']) ?></span></td>
            <td><?= (int) $poster['sort_order'] ?></td>
            <td>
              <div style="display:flex; gap:8px;">
                <a href="<?= BASE_URL ?>admin/sliders.php?edit=<?= (int) $poster['id'] ?>" class="icon-btn" title="Edit">✎</a>
                <a href="<?= BASE_URL ?>admin/sliders.php?delete=<?= (int) $poster['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="icon-btn" title="Delete" onclick="return confirm('Delete this poster?')">&times;</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
