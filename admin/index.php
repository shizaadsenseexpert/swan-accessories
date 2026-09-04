<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pdo = get_db();
$totalOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$revenue = (int)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$productCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

$recent = $pdo->query('SELECT * FROM orders ORDER BY id DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1>Dashboard</h1>
</div>

<div class="stat-row">
  <div class="stat-card"><div class="num"><?= $totalOrders ?></div><div class="label">Total Orders</div></div>
  <div class="stat-card"><div class="num"><?= $pendingOrders ?></div><div class="label">Pending Orders</div></div>
  <div class="stat-card"><div class="num"><?= money($revenue) ?></div><div class="label">Revenue</div></div>
  <div class="stat-card"><div class="num"><?= $productCount ?></div><div class="label">Products</div></div>
</div>

<div class="admin-card">
  <h3>Recent Orders</h3>
  <?php if (empty($recent)): ?>
    <p style="color:var(--ink-soft);">No orders yet.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead><tr><th>#</th><th>Customer</th><th>Phone</th><th>Total</th><th>Status</th><th>Placed</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($recent as $o): ?>
        <tr>
          <td>#<?= (int)$o['id'] ?></td>
          <td><?= e($o['customer_name']) ?></td>
          <td><?= e($o['phone']) ?></td>
          <td><?= money($o['total']) ?></td>
          <td><span class="status-badge status-<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
          <td><?= e($o['created_at']) ?></td>
          <td><a href="order.php?id=<?= (int)$o['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
