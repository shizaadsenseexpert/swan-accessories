<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';

$id = (int)($_GET['id'] ?? 0);
$product = get_product($id);
if (!$product || !$product['is_active']) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty-state">Sorry, we couldn\'t find that product.<br><a class="btn btn-primary" style="margin-top:20px" href="shop">Back to Shop</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $product['name'];
$related = array_filter(get_products($product['category']), fn($p) => $p['id'] != $product['id']);
$related = array_slice($related, 0, 4);

require __DIR__ . '/includes/header.php';
?>

<div class="product-detail">
  <div class="image-wrap">
    <img src="<?= e($product['image_path']) ?>" alt="<?= e($product['name']) ?>">
  </div>
  <div>
    <span class="cat-tag"><?= e(category_label($product['category'])) ?></span>
    <h1><?= e($product['name']) ?></h1>
    <div class="price"><?= money($product['price']) ?></div>
    <div class="material-badge">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
      <?= e($product['material']) ?> &middot; Rust &amp; Tarnish Resistant
    </div>
    <p class="desc"><?= e($product['description']) ?></p>

    <form action="cart" method="post">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
      <div class="qty-row">
        <label for="qty">Quantity</label>
        <input type="number" id="qty" name="qty" value="1" min="1" max="20">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Add to Cart</button>
    </form>
  </div>
</div>

<?php if (!empty($related)): ?>
<div class="section-heading">
  <h2>You may also like</h2>
</div>
<div class="product-grid">
  <?php foreach ($related as $p): ?>
    <div class="product-card">
      <a class="thumb" href="product?id=<?= (int)$p['id'] ?>">
        <img src="<?= e($p['image_path']) ?>" alt="<?= e($p['name']) ?>">
      </a>
      <div class="info">
        <span class="cat-tag"><?= e(category_label($p['category'])) ?></span>
        <h3><a href="product?id=<?= (int)$p['id'] ?>"><?= e($p['name']) ?></a></h3>
        <div class="material-tag"><?= e($p['material']) ?></div>
        <div class="price"><?= money($p['price']) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
