<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';
$pageTitle = 'Your Cart';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'add') {
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        cart_add($productId, $qty);
    } elseif ($action === 'update') {
        $qty = max(0, (int)($_POST['qty'] ?? 0));
        cart_set_qty($productId, $qty);
    } elseif ($action === 'remove') {
        cart_remove($productId);
    }

    header('Location: cart');
    exit;
}

$cart = cart_details();
require __DIR__ . '/includes/header.php';
?>

<div class="cart-page">
  <h1>Your Cart</h1>

  <?php if (empty($cart['items'])): ?>
    <div class="empty-state">
      Your cart is empty.<br>
      <a class="btn btn-primary" style="margin-top:20px" href="shop">Start Shopping</a>
    </div>
  <?php else: ?>
    <?php foreach ($cart['items'] as $item): $p = $item['product']; ?>
      <div class="cart-row">
        <img src="<?= e($p['image_path']) ?>" alt="<?= e($p['name']) ?>">
        <div>
          <div class="name"><?= e($p['name']) ?></div>
          <div class="cat"><?= e(category_label($p['category'])) ?> · <?= money($p['price']) ?></div>
        </div>
        <form action="cart" method="post" style="display:flex; align-items:center; gap:8px;">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <input type="number" name="qty" value="<?= (int)$item['qty'] ?>" min="0" max="20" onchange="this.form.submit()">
        </form>
        <div><?= money($item['subtotal']) ?></div>
        <form action="cart" method="post">
          <input type="hidden" name="action" value="remove">
          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <button type="submit" class="remove">Remove</button>
        </form>
      </div>
    <?php endforeach; ?>

    <div class="cart-summary">
      <span>Total</span>
      <span><?= money($cart['total']) ?></span>
    </div>

    <div style="margin-top:30px; display:flex; gap:14px; flex-wrap:wrap;">
      <a href="shop" class="btn btn-outline">Continue Shopping</a>
      <a href="checkout" class="btn btn-primary">Proceed to Checkout</a>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
