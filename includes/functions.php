<?php
require_once __DIR__ . '/db.php';

// Delivery defaults. config.php can override these, but the live server keeps
// its own config.php (deploys never overwrite it), so the site must still work
// when they're missing there.
if (!defined('DELIVERY_FEE')) define('DELIVERY_FEE', 200);
if (!defined('FREE_DELIVERY_MIN')) define('FREE_DELIVERY_MIN', 2500);

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
    $product = get_product($productId);
    if (!$product || !has_price($product)) return;
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

function delivery_fee(int $subtotal): int {
    return $subtotal >= FREE_DELIVERY_MIN ? 0 : DELIVERY_FEE;
}

// A product with no price (0) is shown without one and can't be added to
// the cart; customers can ask about it on WhatsApp instead.
function has_price(array $product): bool {
    return (int)$product['price'] > 0;
}

function price_html(array $product): string {
    return has_price($product) ? money((int)$product['price']) : '';
}

function ask_price_link(array $product): string {
    return whatsapp_link('Hi! What is the price of ' . $product['name'] . '? ' . site_url(product_url($product)));
}

function site_url(string $path): string {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') return $path;
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $host . $path;
}

// Small note shown on every product photo.
function color_note(): string {
    return '<span class="color-note">Product colour may vary slightly due to shoot lighting</span>';
}

function delivery_label(?int $fee): string {
    return $fee === 0 ? 'Free' : money((int)$fee);
}

function delivery_announcement(): string {
    return 'Delivery charges ' . money(DELIVERY_FEE) . ' per order'
        . '  ·  FREE delivery on orders of ' . money(FREE_DELIVERY_MIN) . ' or more';
}

function cart_details(): array {
    $items = [];
    $subtotal = 0;
    foreach (cart_items() as $productId => $qty) {
        $product = get_product((int)$productId);
        if (!$product || !$product['is_active'] || !has_price($product)) continue;
        $lineTotal = $product['price'] * $qty;
        $subtotal += $lineTotal;
        $items[] = [
            'product'  => $product,
            'qty'      => $qty,
            'subtotal' => $lineTotal,
        ];
    }
    $delivery = empty($items) ? 0 : delivery_fee($subtotal);
    return [
        'items'    => $items,
        'subtotal' => $subtotal,
        'delivery' => $delivery,
        'total'    => $subtotal + $delivery,
    ];
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

function slugify(string $text): string {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $slug !== '' ? $slug : 'item';
}

// URL slug for each category key (e.g. /bracelets shows the 'bracelet' category).
function category_slug(string $cat): string {
    return $cat === 'bracelet' ? 'bracelets' : $cat;
}

function category_from_slug(string $slug): ?string {
    foreach (all_categories() as $cat) {
        if (category_slug($cat) === $slug) {
            return $cat;
        }
    }
    return null;
}

function category_url(string $cat): string {
    return '/' . category_slug($cat);
}

function product_url(array $product): string {
    return '/product/' . (int)$product['id'] . '-' . slugify($product['name']);
}

function asset_url(string $path): string {
    $path = ltrim($path, '/');
    // Version tag so browsers fetch a photo again after it's replaced.
    $file = BASE_PATH . '/' . $path;
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return '/' . $path . $version;
}

function redirect_permanent(string $url): void {
    header('Location: ' . $url, true, 301);
    exit;
}
