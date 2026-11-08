<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';

// Old links like /shop?cat=rings move to /rings.
if (isset($_GET['cat'])) {
    redirect_permanent(in_array($_GET['cat'], all_categories(), true) ? category_url($_GET['cat']) : '/shop');
}

$cat = isset($_GET['category']) ? category_from_slug($_GET['category']) : null;
if (isset($_GET['category']) && $cat === null) {
    redirect_permanent('/shop');
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
  <a href="/shop" class="<?= $cat === null ? 'active' : '' ?>">All</a>
  <?php foreach (all_categories() as $c): ?>
    <a href="<?= e(category_url($c)) ?>" class="<?= $cat === $c ? 'active' : '' ?>"><?= e(category_label($c)) ?></a>
  <?php endforeach; ?>
</div>

<?php if (empty($products)): ?>
  <div class="empty-state">
    <?php if ($cat): ?>
      New <?= e(category_label($cat)) ?> are coming soon.<br>
      <a class="btn btn-outline" style="margin-top:20px" href="<?= e(whatsapp_link('Hi! Do you have any ' . category_label($cat) . '?')) ?>" target="_blank" rel="noopener">Ask on WhatsApp</a>
    <?php else: ?>
      No products found yet.
    <?php endif; ?>
  </div>
<?php else: ?>
<div class="product-grid">
  <?php foreach ($products as $p): ?>
    <div class="product-card">
      <a class="thumb" href="<?= e(product_url($p)) ?>">
        <img src="<?= e(asset_url($p['image_path'])) ?>" alt="<?= e($p['name']) ?>">
        <?= color_note() ?>
      </a>
      <div class="info">
        <span class="cat-tag"><?= e(category_label($p['category'])) ?></span>
        <h3><a href="<?= e(product_url($p)) ?>"><?= e($p['name']) ?></a></h3>
        <div class="price"><?= price_html($p) ?></div>
        <?= buy_buttons($p) ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
