<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';
$pageTitle = 'Order Confirmed';

$orderId = (int)($_GET['order'] ?? 0);
$pdo = get_db();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: ./');
    exit;
}

$itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id');
$itemStmt->execute([':id' => $orderId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/includes/header.php';
?>

<div class="success-page">
  <div class="success-icon">&#10003;</div>
  <h1>Thank you, <?= e($order['customer_name']) ?>!</h1>
  <p style="color:var(--ink-soft); margin-bottom:30px;">
    Your order <strong>#<?= (int)$order['id'] ?></strong> has been placed and will be delivered to your address.
    Pay <strong><?= money($order['total']) ?></strong> in cash when it arrives.
  </p>

  <div class="admin-card" style="text-align:left;">
    <h3>Order Details</h3>
    <?php foreach ($items as $item): ?>
      <div class="order-summary" style="box-shadow:none; padding:0; margin-bottom:0;">
        <div class="line">
          <span><?= e($item['product_name']) ?> × <?= (int)$item['qty'] ?></span>
          <span><?= money($item['subtotal']) ?></span>
        </div>
      </div>
    <?php endforeach; ?>
    <div class="order-summary" style="box-shadow:none; padding:0;">
      <div class="total"><span>Total</span><span><?= money($order['total']) ?></span></div>
    </div>
    <hr style="border:none; border-top:1px solid var(--line); margin:18px 0;">
    <p style="margin:4px 0;"><strong>Deliver to:</strong> <?= e($order['address']) ?>, <?= e($order['city']) ?></p>
    <p style="margin:4px 0;"><strong>Phone:</strong> <?= e($order['phone']) ?></p>
  </div>

  <a href="shop" class="btn btn-primary" style="margin-top:20px;">Continue Shopping</a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
