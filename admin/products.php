<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle') {
        $stmt = $pdo->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute([':id' => $id]);
    } elseif ($action === 'delete') {
        delete_product($pdo, $id);
        $_SESSION['admin_flash'] = 'Product deleted.';
    }
    $back = $_POST['cat'] ?? '';
    header('Location: products' . (in_array($back, all_categories(), true) ? '?cat=' . $back : ''));
    exit;
}

$cat = $_GET['cat'] ?? null;
if ($cat !== null && !in_array($cat, all_categories(), true)) $cat = null;
$products = get_products($cat, false);

$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);

$pageTitle = 'Products';
$activeNav = 'products';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <div>
    <h1>Products</h1>
    <p class="admin-sub"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></p>
  </div>
  <a href="product-edit" class="btn btn-primary btn-sm">+ Add Product</a>
</div>

<?php if ($flash): ?>
  <div class="alert alert-success admin-alert"><?= e($flash) ?></div>
<?php endif; ?>

<div class="admin-toolbar">
  <div class="admin-tabs">
    <a href="products" class="<?= $cat === null ? 'active' : '' ?>">All</a>
    <?php foreach (all_categories() as $c): ?>
      <a href="products?cat=<?= e($c) ?>" class="<?= $cat === $c ? 'active' : '' ?>"><?= e(category_label($c)) ?></a>
    <?php endforeach; ?>
  </div>
  <input type="search" id="productSearch" class="admin-search" placeholder="Search products…" aria-label="Search products">
</div>

<div class="admin-card admin-card-flush">
  <?php if (empty($products)): ?>
    <p class="admin-empty">No products yet. <a href="product-edit">Add your first product</a>.</p>
  <?php else: ?>
  <table class="admin-table product-table">
    <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($products as $p): ?>
        <tr data-name="<?= e(strtolower($p['name'])) ?>">
          <td>
            <div class="product-cell">
              <img src="<?= e(asset_url($p['image_path'])) ?>" alt="">
              <span><?= e($p['name']) ?></span>
            </div>
          </td>
          <td data-label="Category"><?= e(category_label($p['category'])) ?></td>
          <td data-label="Price"><?= money($p['price']) ?></td>
          <td data-label="Status">
            <span class="status-badge <?= $p['is_active'] ? 'status-delivered' : 'status-cancelled' ?>">
              <?= $p['is_active'] ? 'Active' : 'Hidden' ?>
            </span>
          </td>
          <td class="col-actions">
            <div class="admin-actions">
              <a href="product-edit?id=<?= (int)$p['id'] ?>" class="action-btn">Edit</a>
              <form method="post">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="cat" value="<?= e($cat ?? '') ?>">
                <button type="submit" class="action-btn"><?= $p['is_active'] ? 'Hide' : 'Show' ?></button>
              </form>
              <form method="post" onsubmit="return confirm('Delete &quot;<?= e(addslashes($p['name'])) ?>&quot;? This cannot be undone.');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="cat" value="<?= e($cat ?? '') ?>">
                <button type="submit" class="action-btn action-danger">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p class="admin-empty" id="noMatches" hidden>No products match your search.</p>
  <?php endif; ?>
</div>

<script>
(function () {
  var input = document.getElementById('productSearch');
  if (!input) return;
  var rows = document.querySelectorAll('.product-table tbody tr');
  var none = document.getElementById('noMatches');
  input.addEventListener('input', function () {
    var q = input.value.trim().toLowerCase(), shown = 0;
    rows.forEach(function (row) {
      var match = row.getAttribute('data-name').indexOf(q) !== -1;
      row.hidden = !match;
      if (match) shown++;
    });
    if (none) none.hidden = shown !== 0;
  });
})();
</script>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
