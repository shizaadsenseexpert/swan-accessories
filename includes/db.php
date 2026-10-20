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
    seed_new_products($pdo);
    update_generic_descriptions($pdo);
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
        'bangle 5.jpeg' => 'Filigree Gold Kundan Bangle Pair',

        'bracelet 1.jpeg' => 'Vintage Floral Gemstone Bracelet',
        'bracelet 2.jpeg' => 'Ruby Red Crystal Bracelet',
        'bracelet 3.jpeg' => 'Mother-of-Pearl Clover Bracelet',
        'bracelet 4.jpeg' => 'Turquoise Stone Bracelet',
        'bracelet 5.jpeg' => 'Malachite Clover Bracelet',
        'bracelet 6.jpeg' => 'Multicolor Gemstone Bracelet',
        'bracelet 7.jpeg' => 'Turquoise Crystal Peace Sign Bracelet',
        'bracelet 8.jpeg' => 'Emerald Green Crystal Gold Chain Bracelet',
        'bracelet 9.jpeg' => 'Pink & Pearl Blossom Bracelet',
        'bracelet 10.jpeg' => 'Yellow Daisy Gold Cuff Bracelet',
        'bracelet 11.jpeg' => 'Rose Quartz & Pearl Beaded Bracelet Stack',

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
        'earring 12.jpeg' => 'Ivory & Charcoal Marble Enamel Drop Earrings',
        'earring 13.jpeg' => 'Gold Crystal Double Circle Earrings',
        'earring 14.jpeg' => 'Silver Square Hoop Earrings',
        'earring 15.jpeg' => 'Antique Gold Chandbali Earrings',

        'pendant 1.jpeg' => 'Golden Leaf Vine Ribbon Necklace',
        'pendant 2.jpeg' => 'Golden Beaded Rope Necklace',
        'pendant 3.jpeg' => 'Vintage Silver Owl Pendant',
        'pendant 4.jpeg' => 'Red Retro Camera Pendant',
        'pendant 5.jpeg' => 'Double Pink Heart Crystal Pendant',
        'pendant 6.jpeg' => 'Best Friends Split Heart Pendant Set',
        'pendant 7.jpeg' => 'Love Beyond the Moon & Stars Pendant',
        'pendant 8.jpeg' => 'Oxidized Silver Floral Chandbali Necklace',
        'pendant 9.jpeg' => 'Best Friends Three-Piece Split Heart Pendant Set',
        'pendant 10.jpeg' => 'Sister Love You to the Moon Pendant',

        'ring 1.jpeg' => 'Pearl Cluster Gold Statement Ring',
        'ring 2.jpeg' => 'Red Heart Enamel Fan Ring',
        'ring 3.jpeg' => 'Dainty Gold Stackable Ring Set',
        'ring 4.jpeg' => 'Emerald Crystal Flower Ring',
        'ring 5.jpeg' => 'Ruby Red Sunburst Cocktail Ring',
        'ring 6.jpeg' => 'Hot Pink Baguette Crystal Band Ring',
        'ring 7.jpeg' => 'Minimal Gold Heart & Bar Ring Set',
        'ring 8.jpeg' => 'Gold Chevron & Stacking Band Ring Set',
    ];
}

// Short description for each seeded product, keyed by photo filename.
function product_description_map(): array {
    return [
        'bangle 1.jpeg' => 'Gold-tone bangles studded with sparkling crystals. A festive set that adds instant shine to party and wedding outfits.',
        'bangle 2.jpeg' => 'Gold bangles finished with delicate beading. Lightweight and easy to stack for everyday or festive wear.',
        'bangle 3.jpeg' => 'Traditional kundan bangles set with colourful stones. Pairs beautifully with bright shalwar kameez and bridal wear.',
        'bangle 4.jpeg' => 'Gold bangles accented with bold black stones. A striking contrast that suits both eastern and western outfits.',
        'bangle 5.jpeg' => 'A pair of intricate gold filigree bangles with kundan detailing. Classic craftsmanship for weddings and Eid.',

        'bracelet 1.jpeg' => 'A vintage-inspired bracelet with floral gemstone accents. Adds a soft, romantic touch to any look.',
        'bracelet 2.jpeg' => 'Rich ruby-red crystals on a sleek bracelet. A bold pop of colour for evenings and celebrations.',
        'bracelet 3.jpeg' => 'Mother-of-pearl clover motifs with a gentle shimmer. Elegant and minimal for daily wear.',
        'bracelet 4.jpeg' => 'A bracelet featuring calming turquoise stones. Brings a fresh, boho feel to casual outfits.',
        'bracelet 5.jpeg' => 'Deep green malachite clover charms on a fine chain. Polished, timeless and easy to layer.',
        'bracelet 6.jpeg' => 'A colourful mix of gemstones on one bracelet. Fun, vibrant and perfect with simple outfits.',
        'bracelet 7.jpeg' => 'Turquoise crystals with a playful peace sign charm. A cheerful everyday piece.',
        'bracelet 8.jpeg' => 'A gold chain bracelet set with emerald-green crystals. Adds a touch of luxury to any occasion.',
        'bracelet 9.jpeg' => 'Pink blossoms and pearls on a dainty bracelet. Sweet, feminine and ideal as a gift.',
        'bracelet 10.jpeg' => 'A gold cuff decorated with bright yellow daisies. A sunny statement piece for summer.',
        'bracelet 11.jpeg' => 'A stack of rose quartz and pearl beaded bracelets. Wear them together or style them separately.',

        'earring 1.jpeg' => 'Oxidized silver jhumkas finished with pearl drops. A traditional favourite for kurtas and festive wear.',
        'earring 2.jpeg' => 'Hoops wrapped in pearls and crystals. Light, elegant and suitable for day or night.',
        'earring 3.jpeg' => 'Dainty dragonfly studs set with teal crystals. A delicate pop of colour for everyday wear.',
        'earring 4.jpeg' => 'Gold chain-link drops with a modern edge. Effortlessly chic with both casual and formal outfits.',
        'earring 5.jpeg' => 'Small lightning bolt studs with a fun, edgy vibe. Perfect for everyday styling.',
        'earring 6.jpeg' => 'Cute owl-shaped studs for a playful touch. Lightweight and comfortable all day.',
        'earring 7.jpeg' => 'Drop earrings inspired by colourful peacock feathers. Eye-catching and festive.',
        'earring 8.jpeg' => 'Square crystal drops that catch the light beautifully. A polished choice for parties and dinners.',
        'earring 9.jpeg' => 'Pearl studs shaped into a bow wreath. Graceful and perfect for formal occasions.',
        'earring 10.jpeg' => 'Floral drop earrings with black stones. A bold yet elegant statement.',
        'earring 11.jpeg' => 'Drop earrings with a sleek black marble finish. Modern and minimal.',
        'earring 12.jpeg' => 'Enamel drops in ivory and charcoal marble tones. Understated style that goes with everything.',
        'earring 13.jpeg' => 'Interlinked gold circles set with crystals. A refined everyday-to-evening pair.',
        'earring 14.jpeg' => 'Silver hoops with a contemporary square shape. Clean, modern and easy to wear.',
        'earring 15.jpeg' => 'Antique gold chandbalis with traditional detailing. Made for weddings, mehndis and Eid.',

        'pendant 1.jpeg' => 'A gold necklace with a leaf and vine ribbon design. Graceful and nature-inspired.',
        'pendant 2.jpeg' => 'A golden beaded rope necklace with rich texture. Elevates both plain and embellished outfits.',
        'pendant 3.jpeg' => 'A vintage-style silver owl pendant full of character. A charming piece for everyday wear.',
        'pendant 4.jpeg' => 'A fun red retro camera pendant. Perfect for photography lovers and quirky styles.',
        'pendant 5.jpeg' => 'Two pink crystal hearts on a dainty chain. A sweet, romantic gift.',
        'pendant 6.jpeg' => 'A split heart pendant set to share with your best friend. Each of you keeps one half.',
        'pendant 7.jpeg' => 'A moon and stars pendant that says love beyond limits. A meaningful gift for someone special.',
        'pendant 8.jpeg' => 'An oxidized silver necklace with a floral chandbali design. Traditional charm for festive outfits.',
        'pendant 9.jpeg' => 'A three-piece split heart set for your best friends. One heart, three keepers.',
        'pendant 10.jpeg' => 'A heartfelt "love you to the moon" pendant for your sister. A gift she will treasure.',

        'ring 1.jpeg' => 'A gold statement ring topped with a cluster of pearls. Classy and eye-catching.',
        'ring 2.jpeg' => 'A fan-shaped ring with red heart enamel details. Playful and full of charm.',
        'ring 3.jpeg' => 'A set of dainty gold rings made for stacking. Mix and match across your fingers.',
        'ring 4.jpeg' => 'A flower ring set with emerald-green crystals. A fresh, feminine statement.',
        'ring 5.jpeg' => 'A ruby-red cocktail ring with a sunburst design. Bold glamour for special nights.',
        'ring 6.jpeg' => 'A band lined with hot pink baguette crystals. A vibrant everyday sparkle.',
        'ring 7.jpeg' => 'Minimal gold rings with heart and bar details. Subtle, modern and easy to stack.',
        'ring 8.jpeg' => 'A gold chevron ring with matching stacking bands. Wear them together or on their own.',
    ];
}

// Fills in the description for products still using the old generic
// placeholder text, without touching descriptions an admin has written.
function update_generic_descriptions(PDO $pdo): void {
    $descriptions = product_description_map();
    $rows = $pdo->query("SELECT id, description, image_path FROM products")->fetchAll(PDO::FETCH_ASSOC);
    $update = $pdo->prepare("UPDATE products SET description = :description WHERE id = :id");

    foreach ($rows as $row) {
        if (trim($row['description']) !== '' && !preg_match('/^A beautiful piece from our .+ collection\.$/', trim($row['description']))) continue;
        $filename = strtolower(basename($row['image_path']));
        if (isset($descriptions[$filename])) {
            $update->execute([':description' => $descriptions[$filename], ':id' => $row['id']]);
        }
    }
}

function seed_categories(): array {
    return [
        'bangles'   => ['label' => 'Bangles',   'price' => 450],
        'bracelet'  => ['label' => 'Bracelets', 'price' => 650],
        'earrings'  => ['label' => 'Earrings',  'price' => 350],
        'pendants'  => ['label' => 'Pendants',  'price' => 550],
        'rings'     => ['label' => 'Rings',     'price' => 400],
    ];
}

// Products added after the store was first set up. Existing databases get
// any of these that aren't already a product: every pendant and ring photo, and only
// the listed newer photos in the older categories.
function seed_new_products(PDO $pdo): void {
    $categories = seed_categories();
    seed_products($pdo, ['pendants' => $categories['pendants']], true);
    seed_products($pdo, ['rings' => $categories['rings']], true);
    seed_products($pdo, ['earrings' => $categories['earrings']], true,
        ['earring 12.jpeg', 'earring 13.jpeg', 'earring 14.jpeg', 'earring 15.jpeg']);
    seed_products($pdo, ['bracelet' => $categories['bracelet']], true,
        ['bracelet 7.jpeg', 'bracelet 8.jpeg', 'bracelet 9.jpeg', 'bracelet 10.jpeg', 'bracelet 11.jpeg']);
    seed_products($pdo, ['bangles' => $categories['bangles']], true, ['bangle 5.jpeg']);
}

function seed_products(PDO $pdo, ?array $categories = null, bool $skipExisting = false, ?array $onlyFiles = null): void {
    $categories = $categories ?? seed_categories();
    $names = product_name_map();
    $descriptions = product_description_map();

    $stmt = $pdo->prepare("
        INSERT INTO products (category, name, price, description, image_path, is_active)
        VALUES (:category, :name, :price, :description, :image_path, 1)
    ");
    $exists = $pdo->prepare("SELECT 1 FROM products WHERE image_path = :image_path");

    foreach ($categories as $cat => $info) {
        $dir = BASE_PATH . '/assets/images/products/' . $cat;
        if (!is_dir($dir)) continue;
        $files = scandir($dir);
        natsort($files);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) continue;
            if ($onlyFiles !== null && !in_array($file, $onlyFiles, true)) continue;

            $imagePath = 'assets/images/products/' . $cat . '/' . $file;
            if ($skipExisting) {
                $exists->execute([':image_path' => $imagePath]);
                if ($exists->fetchColumn()) continue;
            }

            $name = $names[strtolower($file)] ?? ucwords(trim(pathinfo($file, PATHINFO_FILENAME)));
            $stmt->execute([
                ':category'    => $cat,
                ':name'        => $name,
                ':price'       => $info['price'],
                ':description' => $descriptions[strtolower($file)] ?? 'A beautiful piece from our ' . $info['label'] . ' collection.',
                ':image_path'  => $imagePath,
            ]);
        }
    }
}
