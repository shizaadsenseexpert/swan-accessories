<?php
require_once __DIR__ . '/db.php';

function money(int $amount): string {
    return SITE_CURRENCY_SYMBOL . number_format($amount);
}

function category_label(string $cat): string {
    $labels = ['bangles' => 'Bangles', 'bracelet' => 'Bracelets', 'earrings' => 'Earrings', 'pendants' => 'Pendants', 'rings' => 'Rings'];
    return $labels[$cat] ?? ucfirst($cat);
}

function all_categories(): array {
    return ['bangles', 'bracelet', 'earrings', 'pendants', 'rings'];
}

function get_products(?string $category = null, bool $activeOnly = true): array {
    $pdo = get_db();
    $sql = 'SELECT * FROM products WHERE 1=1';
    $params = [];
    if ($category !== null) {
        $sql .= ' AND category = :category';
        $params[':category'] = $category;
    }
    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_product(int $id): ?array {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function cart_items(): array {
    return $_SESSION['cart'] ?? [];
}

function cart_add(int $productId, int $qty = 1): void {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
}

function cart_set_qty(int $productId, int $qty): void {
    if ($qty <= 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId] = $qty;
    }
}

function cart_remove(int $productId): void {
    unset($_SESSION['cart'][$productId]);
}

function cart_clear(): void {
    $_SESSION['cart'] = [];
}

function cart_count(): int {
    $total = 0;
    foreach (cart_items() as $qty) $total += $qty;
    return $total;
}

function cart_details(): array {
    $items = [];
    $total = 0;
    foreach (cart_items() as $productId => $qty) {
        $product = get_product((int)$productId);
        if (!$product || !$product['is_active']) continue;
        $subtotal = $product['price'] * $qty;
        $total += $subtotal;
        $items[] = [
            'product'  => $product,
            'qty'      => $qty,
            'subtotal' => $subtotal,
        ];
    }
    return ['items' => $items, 'total' => $total];
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function whatsapp_link(string $message = ''): string {
    $url = 'https://wa.me/' . SITE_WHATSAPP_NUMBER;
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}
