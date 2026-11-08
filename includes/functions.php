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
    $labels = ['bangles' => 'Bangles', 'bracelet' => 'Bracelets', 'earrings' => 'Earrings', 'ear-cuffs' => 'Ear Cuffs', 'pendants' => 'Pendants', 'rings' => 'Rings', 'hair-accessories' => 'Hair Accessories'];
    return $labels[$cat] ?? ucfirst($cat);
}

function all_categories(): array {
    return ['bangles', 'bracelet', 'earrings', 'ear-cuffs', 'pendants', 'rings', 'hair-accessories'];
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

// Buy Now: make sure the product is in the cart with at least $qty, without
// adding it a second time if the shopper already has it there.
function cart_ensure(int $productId, int $qty): void {
    $product = get_product($productId);
    if (!$product || !has_price($product)) return;
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $_SESSION['cart'][$productId] = max($_SESSION['cart'][$productId] ?? 0, $qty);
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

// Add to Cart / Buy Now buttons for a product. On a product card it's just the
// two buttons; on the product page it also has a quantity box.
function buy_buttons(array $product, bool $withQty = false): string {
    if (!has_price($product)) {
        $class = $withQty ? 'btn btn-primary btn-block' : 'btn btn-outline btn-block';
        return '<a class="' . $class . '" href="' . e(ask_price_link($product)) . '" target="_blank" rel="noopener">Ask on WhatsApp</a>';
    }
    $html = '<form action="/cart" method="post" class="buy-form">'
        . '<input type="hidden" name="action" value="add">'
        . '<input type="hidden" name="product_id" value="' . (int)$product['id'] . '">';
    if ($withQty) {
        $html .= '<div class="qty-row"><label for="qty">Quantity</label>'
            . '<input type="number" id="qty" name="qty" value="1" min="1" max="20"></div>';
    }
    $html .= '<div class="buy-actions">'
        . '<button type="submit" class="btn btn-outline">Add to Cart</button>'
        . '<button type="submit" name="buy_now" value="1" class="btn btn-primary">Buy Now</button>'
        . '</div></form>';
    return $html;
}

// Where to send the shopper back to after a no-JavaScript Add to Cart.
function back_url(): string {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $parts = $ref !== '' ? parse_url($ref) : false;
    $refHost = ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');
    if ($parts && $refHost === ($_SERVER['HTTP_HOST'] ?? '')) {
        $path = $parts['path'] ?? '/';
        if (strpos($path, '/cart') !== 0 && strpos($path, '/checkout') !== 0) {
            return $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
        }
    }
    return '/shop';
}

// Small note shown on every product photo.
function color_note(): string {
    return '<span class="color-note"><span class="color-note-long">Product colour may vary slightly due to shoot lighting</span>'
        . '<span class="color-note-short">Colour may vary due to lighting</span></span>';
}

function delivery_label(?int $fee): string {
    return $fee === 0 ? 'Free' : money((int)$fee);
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
