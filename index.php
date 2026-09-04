<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';
$pageTitle = 'Home';

$productsByCategory = [];
foreach (all_categories() as $cat) {
    $productsByCategory[$cat] = get_products($cat);
}

$collage = [];
foreach ($productsByCategory as $cat => $prods) {
    if (!empty($prods)) $collage[] = $prods[0];
}

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="hero-inner">
    <div class="hero-copy">
      <h1>Grace, in every detail.</h1>
      <p><?= e(SITE_TAGLINE) ?> Handpicked bangles, bracelets and earrings for the everyday and the unforgettable.</p>
      <div style="display:flex; gap:14px; flex-wrap:wrap;">
        <a href="shop.php" class="btn btn-primary">Shop the Collection</a>
        <a href="<?= e(whatsapp_link('Hi! I would like to know more about your collection.')) ?>" target="_blank" rel="noopener" class="btn btn-outline">Ask on WhatsApp</a>
      </div>
    </div>
    <?php if (!empty($collage)): ?>
    <div class="hero-collage">
      <?php foreach (array_slice($collage, 0, 3) as $i => $p): ?>
        <img src="<?= e($p['image_path']) ?>" alt="<?= e($p['name']) ?>" class="collage-img collage-img-<?= $i + 1 ?>">
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<div class="category-grid">
  <?php foreach ($productsByCategory as $cat => $prods): if (empty($prods)) continue; ?>
    <a class="category-card" href="#cat-<?= e($cat) ?>">
      <img src="<?= e($prods[0]['image_path']) ?>" alt="<?= e(category_label($cat)) ?>">
      <span class="label"><?= e(category_label($cat)) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<?php foreach ($productsByCategory as $cat => $prods): if (empty($prods)) continue; ?>
  <div class="section-heading" id="cat-<?= e($cat) ?>">
    <h2><?= e(category_label($cat)) ?></h2>
    <p><?= count($prods) ?> piece<?= count($prods) === 1 ? '' : 's' ?> · <?= e($prods[0]['material']) ?></p>
  </div>
  <div class="product-grid">
    <?php foreach ($prods as $p): ?>
      <div class="product-card">
        <a class="thumb" href="product.php?id=<?= (int)$p['id'] ?>">
          <img src="<?= e($p['image_path']) ?>" alt="<?= e($p['name']) ?>">
        </a>
        <div class="info">
          <span class="cat-tag"><?= e(category_label($p['category'])) ?></span>
          <h3><a href="product.php?id=<?= (int)$p['id'] ?>"><?= e($p['name']) ?></a></h3>
          <div class="material-tag"><?= e($p['material']) ?></div>
          <div class="price"><?= money($p['price']) ?></div>
          <form action="cart.php" method="post">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
            <button type="submit" class="btn btn-outline btn-block">Add to Cart</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
