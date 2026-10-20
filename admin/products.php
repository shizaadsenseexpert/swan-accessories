<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = :id');
    $stmt->execute([':id' => $id]);
    header('Location: products');
    exit;
}

$cat = $_GET['cat'] ?? null;
if ($cat !== null && !in_array($cat, all_categories(), true)) $cat = null;
$products = get_products($cat, false);

$pageTitle = 'Products';
$activeNav = 'products';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1>Products</h1>
  <a href="product-edit" class="btn btn-primary">+ Add Product</a>
</div>

<div class="filter-bar" style="justify-content:flex-start; padding:0 0 20px;">
  <a href="products" class="<?= $cat === null ? 'active' : '' ?>">All</a>
  <?php foreach (all_categories() as $c): ?>
    <a href="products?cat=<?= e($c) ?>" class="<?= $cat === $c ? 'active' : '' ?>"><?= e(category_label($c)) ?></a>
  <?php endforeach; ?>
</div>

<div class="admin-card">
  <table class="admin-table">
    <thead><tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($products as $p): ?>
        <tr>
          <td><img src="<?= e($p['image_path']) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:8px;"></td>
          <td><?= e($p['name']) ?></td>
          <td><?= e(category_label($p['category'])) ?></td>
          <td><?= money($p['price']) ?></td>
          <td>
            <span class="status-badge <?= $p['is_active'] ? 'status-delivered' : 'status-cancelled' ?>">
              <?= $p['is_active'] ? 'Active' : 'Hidden' ?>
            </span>
          </td>
          <td class="admin-actions">
            <a href="product-edit?id=<?= (int)$p['id'] ?>">Edit</a>
            <form method="post">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button type="submit" class="link-btn"><?= $p['is_active'] ? 'Hide' : 'Unhide' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
