<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';
$pageTitle = 'Checkout';

$cart = cart_details();
if (empty($cart['items'])) {
    header('Location: cart.php');
    exit;
}

$errors = [];
$old = ['name' => '', 'phone' => '', 'address' => '', 'city' => '', 'notes' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name'] = trim($_POST['name'] ?? '');
    $old['phone'] = trim($_POST['phone'] ?? '');
    $old['address'] = trim($_POST['address'] ?? '');
    $old['city'] = trim($_POST['city'] ?? '');
    $old['notes'] = trim($_POST['notes'] ?? '');
    $payment = $_POST['payment_method'] ?? 'cod';

    if ($old['name'] === '') $errors[] = 'Please enter your full name.';
    if ($old['phone'] === '' || !preg_match('/^[0-9+\-\s]{7,15}$/', $old['phone'])) $errors[] = 'Please enter a valid phone number.';
    if ($old['address'] === '') $errors[] = 'Please enter your delivery address.';
    if ($old['city'] === '') $errors[] = 'Please enter your city.';
    if ($payment !== 'cod') $errors[] = 'Online payment is not available yet — please select Cash on Delivery.';

    if (empty($errors)) {
        $pdo = get_db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_name, phone, address, city, notes, payment_method, status, total)
                VALUES (:name, :phone, :address, :city, :notes, 'cod', 'pending', :total)
            ");
            $stmt->execute([
                ':name' => $old['name'],
                ':phone' => $old['phone'],
                ':address' => $old['address'],
                ':city' => $old['city'],
                ':notes' => $old['notes'],
                ':total' => $cart['total'],
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, price, qty, subtotal)
                VALUES (:order_id, :product_id, :product_name, :price, :qty, :subtotal)
            ");
            foreach ($cart['items'] as $item) {
                $itemStmt->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $item['product']['id'],
                    ':product_name' => $item['product']['name'],
                    ':price' => $item['product']['price'],
                    ':qty' => $item['qty'],
                    ':subtotal' => $item['subtotal'],
                ]);
            }

            $pdo->commit();
            cart_clear();
            header('Location: order-success.php?order=' . $orderId);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong placing your order. Please try again.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="checkout-page">
  <div>
    <h1>Checkout</h1>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?>
      </div>
    <?php endif; ?>

    <form action="checkout.php" method="post" style="margin-top:24px;">
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="text" id="phone" name="phone" value="<?= e($old['phone']) ?>" placeholder="03xx-xxxxxxx" required>
        </div>
        <div class="form-group">
          <label for="city">City</label>
          <input type="text" id="city" name="city" value="<?= e($old['city']) ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label for="address">Delivery Address</label>
        <textarea id="address" name="address" rows="3" required><?= e($old['address']) ?></textarea>
      </div>
      <div class="form-group">
        <label for="notes">Order Notes (optional)</label>
        <textarea id="notes" name="notes" rows="2"><?= e($old['notes']) ?></textarea>
      </div>

      <label style="display:block; font-size:0.85rem; margin-bottom:8px; color:var(--ink-soft);">Payment Method</label>
      <div class="payment-options">
        <label class="payment-option">
          <input type="radio" name="payment_method" value="cod" checked> Cash on Delivery
        </label>
        <label class="payment-option disabled">
          <input type="radio" name="payment_method" value="online" disabled> Online Payment (coming soon)
        </label>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Place Order</button>
    </form>
  </div>

  <div class="order-summary">
    <h3>Order Summary</h3>
    <?php foreach ($cart['items'] as $item): ?>
      <div class="line">
        <span><?= e($item['product']['name']) ?> × <?= (int)$item['qty'] ?></span>
        <span><?= money($item['subtotal']) ?></span>
      </div>
    <?php endforeach; ?>
    <div class="total">
      <span>Total</span>
      <span><?= money($cart['total']) ?></span>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
