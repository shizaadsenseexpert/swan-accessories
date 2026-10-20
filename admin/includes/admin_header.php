<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — Admin' : 'Admin' ?> — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="admin-brand"><span class="brand-mark">Swan</span><span class="brand-sub">Admin</span></div>
    <nav>
      <a href="./" class="<?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
      <a href="orders" class="<?= ($activeNav ?? '') === 'orders' ? 'active' : '' ?>">Orders</a>
      <a href="products" class="<?= ($activeNav ?? '') === 'products' ? 'active' : '' ?>">Products</a>
      <a href="../" target="_blank">View Site &rarr;</a>
      <form action="logout" method="post" class="admin-logout">
        <button type="submit">Log out</button>
      </form>
    </nav>
  </aside>
  <div class="admin-main">
