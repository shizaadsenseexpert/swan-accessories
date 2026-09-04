<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pdo = get_db();
$id = (int)($_GET['id'] ?? 0);
$validStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'] ?? '';
    if (in_array($newStatus, $validStatuses, true)) {
        $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $newStatus, ':id' => $id]);
    }
    header('Location: order.php?id=' . $id);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
$stmt->execute([':id' => $id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit;
}

$itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id');
$itemStmt->execute([':id' => $id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Order #' . $order['id'];
$activeNav = 'orders';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1>Order #<?= (int)$order['id'] ?></h1>
  <a href="orders.php">&larr; Back to Orders</a>
</div>

<div class="admin-card">
  <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:20px; margin-bottom:20px;">
    <div>
      <p style="margin:4px 0;"><strong><?= e($order['customer_name']) ?></strong></p>
      <p style="margin:4px 0; color:var(--ink-soft);"><?= e($order['phone']) ?></p>
      <p style="margin:4px 0; color:var(--ink-soft);"><?= e($order['address']) ?>, <?= e($order['city']) ?></p>
      <?php if (!empty($order['notes'])): ?>
        <p style="margin:4px 0; color:var(--ink-soft);">Note: <?= e($order['notes']) ?></p>
      <?php endif; ?>
      <p style="margin:4px 0; color:var(--ink-soft);">Placed: <?= e($order['created_at']) ?></p>
      <p style="margin:4px 0; color:var(--ink-soft);">Payment: <?= strtoupper(e($order['payment_method'])) ?></p>
    </div>
    <form method="post" style="display:flex; align-items:start; gap:10px;">
      <select name="status">
        <?php foreach ($validStatuses as $s): ?>
          <option value="<?= e($s) ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Update Status</button>
    </form>
  </div>

  <table class="admin-table">
    <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= e($item['product_name']) ?></td>
          <td><?= money($item['price']) ?></td>
          <td><?= (int)$item['qty'] ?></td>
          <td><?= money($item['subtotal']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="order-summary" style="box-shadow:none; padding:16px 0 0;">
    <div class="total"><span>Total</span><span><?= money($order['total']) ?></span></div>
  </div>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
