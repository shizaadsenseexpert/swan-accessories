<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' . SITE_NAME : SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= rtrim($basePath ?? '', '/') ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="header-inner">
    <a class="brand" href="<?= $basePath ?? '' ?>/">
      <span class="brand-mark">Swan</span><span class="brand-sub">Accessories</span>
    </a>
    <nav class="main-nav" id="mainNav">
      <a href="<?= $basePath ?? '' ?>/">Home</a>
      <a href="<?= $basePath ?? '' ?>/shop">Shop</a>
      <a href="<?= $basePath ?? '' ?>/shop?cat=bangles">Bangles</a>
      <a href="<?= $basePath ?? '' ?>/shop?cat=bracelet">Bracelets</a>
      <a href="<?= $basePath ?? '' ?>/shop?cat=earrings">Earrings</a>
      <a href="<?= $basePath ?? '' ?>/shop?cat=pendants">Pendants</a>
      <a href="<?= $basePath ?? '' ?>/shop?cat=rings">Rings</a>
    </nav>
    <div class="header-actions">
      <a class="cart-link" href="<?= $basePath ?? '' ?>/cart">
        Cart
        <span class="cart-count"><?= cart_count() ?></span>
      </a>
      <button type="button" class="menu-toggle" id="menuToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="mainNav">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>
<main>
