<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pdo = get_db();
$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

$sql = 'SELECT * FROM orders';
$params = [];
if (in_array($statusFilter, $validStatuses, true)) {
    $sql .= ' WHERE status = :status';
    $params[':status'] = $statusFilter;
}
$sql .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Orders';
$activeNav = 'orders';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1>Orders</h1>
</div>

<div class="filter-bar" style="justify-content:flex-start; padding:0 0 20px;">
  <a href="orders" class="<?= $statusFilter === '' ? 'active' : '' ?>">All</a>
  <?php foreach ($validStatuses as $s): ?>
    <a href="orders?status=<?= e($s) ?>" class="<?= $statusFilter === $s ? 'active' : '' ?>"><?= ucfirst($s) ?></a>
  <?php endforeach; ?>
</div>

<div class="admin-card">
  <?php if (empty($orders)): ?>
    <p style="color:var(--ink-soft);">No orders found.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead><tr><th>#</th><th>Customer</th><th>City</th><th>Total</th><th>Status</th><th>Placed</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td>#<?= (int)$o['id'] ?></td>
          <td><?= e($o['customer_name']) ?></td>
          <td><?= e($o['city']) ?></td>
          <td><?= money($o['total']) ?></td>
          <td><span class="status-badge status-<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
          <td><?= e($o['created_at']) ?></td>
          <td><a href="order?id=<?= (int)$o['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
