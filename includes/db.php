<?php
require_once __DIR__ . '/config.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $isNew = !file_exists(DB_PATH);
    if (!is_dir(dirname(DB_PATH))) {
        mkdir(dirname(DB_PATH), 0775, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($isNew) {
        init_schema($pdo);
        seed_products($pdo);
    } else {
        migrate_schema($pdo);
    }

    return $pdo;
}

function migrate_schema(PDO $pdo): void {
    $columns = $pdo->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_ASSOC);
    $hasMaterial = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'material') $hasMaterial = true;
    }
    if (!$hasMaterial) {
        $pdo->exec("ALTER TABLE products ADD COLUMN material TEXT NOT NULL DEFAULT 'Stainless Steel'");
    }

    rename_generic_products($pdo);
    seed_missing_categories($pdo);
}

// Renames any product still using the old auto-generated "Bangle 1" style
// name to a proper descriptive name, without touching names an admin
// has already customized.
function rename_generic_products(PDO $pdo): void {
    $names = product_name_map();
    $rows = $pdo->query("SELECT id, name, image_path FROM products")->fetchAll(PDO::FETCH_ASSOC);
    $update = $pdo->prepare("UPDATE products SET name = :name WHERE id = :id");

    foreach ($rows as $row) {
        if (!preg_match('/^(Bangle|Bracelet|Earring) \d+$/i', $row['name'])) continue;
        $filename = strtolower(basename($row['image_path']));
        if (isset($names[$filename])) {
            $update->execute([':name' => $names[$filename], ':id' => $row['id']]);
        }
    }
}

function init_schema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category TEXT NOT NULL,
            name TEXT NOT NULL,
            price INTEGER NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            material TEXT NOT NULL DEFAULT 'Stainless Steel',
            image_path TEXT NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
    ");

    $pdo->exec("
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            address TEXT NOT NULL,
            city TEXT NOT NULL,
            notes TEXT NOT NULL DEFAULT '',
            payment_method TEXT NOT NULL DEFAULT 'cod',
            status TEXT NOT NULL DEFAULT 'pending',
            total INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
    ");

    $pdo->exec("
        CREATE TABLE order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            product_id INTEGER,
            product_name TEXT NOT NULL,
            price INTEGER NOT NULL,
            qty INTEGER NOT NULL,
            subtotal INTEGER NOT NULL
        );
    ");
}

function product_name_map(): array {
    return [
        'bangle 1.jpeg' => 'Crystal Studded Gold Bangle Set',
        'bangle 2.jpeg' => 'Beaded Gold Bangle Set',
        'bangle 3.jpeg' => 'Kundan Multicolor Stone Bangle Set',
        'bangle 4.jpeg' => 'Black Stone Gold Bangle Set',

        'bracelet 1.jpeg' => 'Vintage Floral Gemstone Bracelet',
        'bracelet 2.jpeg' => 'Ruby Red Crystal Bracelet',
        'bracelet 3.jpeg' => 'Mother-of-Pearl Clover Bracelet',
        'bracelet 4.jpeg' => 'Turquoise Stone Bracelet',
        'bracelet 5.jpeg' => 'Malachite Clover Bracelet',
        'bracelet 6.jpeg' => 'Multicolor Gemstone Bracelet',

        'earring 1.jpeg' => 'Oxidized Silver Pearl Jhumka Earrings',
        'earring 2.jpeg' => 'Pearl & Crystal Beaded Hoop Earrings',
        'earring 3.jpeg' => 'Teal Crystal Dragonfly Stud Earrings',
        'earring 4.jpeg' => 'Gold Chain Link Drop Earrings',
        'earring 5.jpeg' => 'Lightning Bolt Stud Earrings',
        'earring 6.jpeg' => 'Owl Stud Earrings',
        'earring 7.jpeg' => 'Peacock Feather Drop Earrings',
        'earring 8.jpeg' => 'Square Crystal Drop Earrings',
        'earring 9.jpeg' => 'Pearl Bow Wreath Stud Earrings',
        'earring 10.jpeg' => 'Black Stone Floral Drop Earrings',
        'earring 11.jpeg' => 'Black Marble Drop Earrings',

        'pendant 1.jpeg' => 'Golden Leaf Vine Ribbon Necklace',
        'pendant 2.jpeg' => 'Golden Beaded Rope Necklace',
    ];
}

function seed_categories(): array {
    return [
        'bangles'   => ['label' => 'Bangles',   'price' => 450],
        'bracelet'  => ['label' => 'Bracelets', 'price' => 650],
        'earrings'  => ['label' => 'Earrings',  'price' => 350],
        'pendants'  => ['label' => 'Pendants',  'price' => 550],
    ];
}

// Seeds any category that has no products yet, so categories added after
// the store was first set up (e.g. Pendants) appear on existing databases.
function seed_missing_categories(PDO $pdo): void {
    $count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = :category");
    $missing = [];
    foreach (seed_categories() as $cat => $info) {
        $count->execute([':category' => $cat]);
        if ((int)$count->fetchColumn() === 0) $missing[$cat] = $info;
    }
    if (!empty($missing)) seed_products($pdo, $missing);
}

function seed_products(PDO $pdo, ?array $categories = null): void {
    $categories = $categories ?? seed_categories();
    $names = product_name_map();

    $stmt = $pdo->prepare("
        INSERT INTO products (category, name, price, description, image_path, is_active)
        VALUES (:category, :name, :price, :description, :image_path, 1)
    ");

    foreach ($categories as $cat => $info) {
        $dir = BASE_PATH . '/assets/images/products/' . $cat;
        if (!is_dir($dir)) continue;
        $files = scandir($dir);
        natsort($files);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) continue;

            $name = $names[strtolower($file)] ?? ucwords(trim(pathinfo($file, PATHINFO_FILENAME)));
            $stmt->execute([
                ':category'    => $cat,
                ':name'        => $name,
                ':price'       => $info['price'],
                ':description' => 'A beautiful piece from our ' . $info['label'] . ' collection.',
                ':image_path'  => 'assets/images/products/' . $cat . '/' . $file,
            ]);
        }
    }
}
