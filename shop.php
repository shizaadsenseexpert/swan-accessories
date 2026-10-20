<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';

$cat = $_GET['cat'] ?? null;
if ($cat !== null && !in_array($cat, all_categories(), true)) {
    $cat = null;
}
$pageTitle = $cat ? category_label($cat) : 'Shop All';
$products = get_products($cat);

require __DIR__ . '/includes/header.php';
?>

<div class="section-heading">
  <h1><?= $cat ? e(category_label($cat)) : 'Shop All' ?></h1>
  <p><?= count($products) ?> piece<?= count($products) === 1 ? '' : 's' ?></p>
</div>

<div class="filter-bar">
  <a href="shop" class="<?= $cat === null ? 'active' : '' ?>">All</a>
  <?php foreach (all_categories() as $c): ?>
    <a href="shop?cat=<?= e($c) ?>" class="<?= $cat === $c ? 'active' : '' ?>"><?= e(category_label($c)) ?></a>
  <?php endforeach; ?>
</div>

<?php if (empty($products)): ?>
  <div class="empty-state">No products found in this category yet.</div>
<?php else: ?>
<div class="product-grid">
  <?php foreach ($products as $p): ?>
    <div class="product-card">
      <a class="thumb" href="product?id=<?= (int)$p['id'] ?>">
        <img src="<?= e($p['image_path']) ?>" alt="<?= e($p['name']) ?>">
      </a>
      <div class="info">
        <span class="cat-tag"><?= e(category_label($p['category'])) ?></span>
        <h3><a href="product?id=<?= (int)$p['id'] ?>"><?= e($p['name']) ?></a></h3>
        <div class="price"><?= money($p['price']) ?></div>
        <form action="cart" method="post">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <button type="submit" class="btn btn-outline btn-block">Add to Cart</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
