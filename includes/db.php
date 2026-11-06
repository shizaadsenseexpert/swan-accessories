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
        // Seeding already used the sheet prices.
        mark_update_applied($pdo, 'inventory-prices-2026-11');
    } else {
        migrate_schema($pdo);
    }

    return $pdo;
}

function migrate_schema(PDO $pdo): void {
    ensure_deleted_products_table($pdo);
    $columns = $pdo->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_ASSOC);
    $hasMaterial = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'material') $hasMaterial = true;
    }
    if (!$hasMaterial) {
        $pdo->exec("ALTER TABLE products ADD COLUMN material TEXT NOT NULL DEFAULT 'Stainless Steel'");
    }
    ensure_order_delivery_column($pdo);

    rename_generic_products($pdo);
    seed_new_products($pdo);
    apply_price_updates($pdo);
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

// Photos of products an admin deleted, so seeding doesn't add them back.
function ensure_deleted_products_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS deleted_products (image_path TEXT PRIMARY KEY)");
}

function delete_product(PDO $pdo, int $id): void {
    $product = get_product($id);
    if (!$product) return;

    $pdo->prepare("INSERT OR IGNORE INTO deleted_products (image_path) VALUES (:image_path)")
        ->execute([':image_path' => $product['image_path']]);
    $pdo->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $id]);

    // Remove photos uploaded through admin once no other product uses them.
    if (strpos(basename($product['image_path']), 'prod_') === 0) {
        $inUse = $pdo->prepare("SELECT 1 FROM products WHERE image_path = :image_path");
        $inUse->execute([':image_path' => $product['image_path']]);
        $file = BASE_PATH . '/' . $product['image_path'];
        if (!$inUse->fetchColumn() && is_file($file)) {
            unlink($file);
        }
    }
}

// Delivery charge per order. Orders placed before delivery charges existed
// keep NULL here, so their pages don't show a delivery line.
function ensure_order_delivery_column(PDO $pdo): void {
    $columns = $pdo->query("PRAGMA table_info(orders)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('delivery_fee', $columns, true)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN delivery_fee INTEGER");
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
            delivery_fee INTEGER,
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

    ensure_deleted_products_table($pdo);
}

// Prices from the "Swan Accessories - Inventory" sheet. New installs use them
// straight away; existing stores get them once (see apply_price_updates), so
// prices changed later in the admin panel are never overwritten.
function product_price_map(): array {
    return [
        'bangle 1.jpeg' => 1100,
        'bangle 2.jpeg' => 1200,
        'bangle 3.jpeg' => 1200,
        'bangle 4.jpeg' => 1200,
        'bangle 5.jpeg' => 800,
        'bracelet 1.jpeg' => 650,
        'bracelet 2.jpeg' => 650,
        'bracelet 3.jpeg' => 800,
        'bracelet 4.jpeg' => 650,
        'bracelet 5.jpeg' => 800,
        'bracelet 6.jpeg' => 650,
        'bracelet 7.jpeg' => 650,
        'bracelet 8.jpeg' => 800,
        'bracelet 9.jpeg' => 800,
        'bracelet 10.jpeg' => 800,
        'bracelet 11.jpeg' => 650,
        'earring 1.jpeg' => 650,
        'earring 2.jpeg' => 700,
        'earring 3.jpeg' => 500,
        'earring 4.jpeg' => 650,
        'earring 5.jpeg' => 500,
        'earring 6.jpeg' => 500,
        'earring 7.jpeg' => 500,
        'earring 8.jpeg' => 500,
        'earring 9.jpeg' => 500,
        'earring 10.jpeg' => 800,
        'earring 11.jpeg' => 500,
        'earring 12.jpeg' => 500,
        'earring 13.jpeg' => 500,
        'earring 14.jpeg' => 500,
        'earring 15.jpeg' => 550,
        'pendant 1.jpeg' => 550,
        'pendant 2.jpeg' => 750,
        'pendant 3.jpeg' => 550,
        'pendant 4.jpeg' => 500,
        'pendant 5.jpeg' => 800,
        'pendant 6.jpeg' => 650,
        'pendant 7.jpeg' => 500,
        'pendant 8.jpeg' => 800,
        'pendant 9.jpeg' => 650,
        'pendant 10.jpeg' => 500,
        'ring 1.jpeg' => 500,
        'ring 2.jpeg' => 500,
        'ring 3.jpeg' => 650,
        'ring 4.jpeg' => 650,
        'ring 5.jpeg' => 650,
        'ring 6.jpeg' => 650,
        'ring 7.jpeg' => 650,
        'ring 8.jpeg' => 650,
    ];
}

function ensure_applied_updates_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS applied_updates (name TEXT PRIMARY KEY, applied_at TEXT NOT NULL DEFAULT (datetime('now')))");
}

function mark_update_applied(PDO $pdo, string $name): void {
    ensure_applied_updates_table($pdo);
    $pdo->prepare("INSERT OR IGNORE INTO applied_updates (name) VALUES (:name)")->execute([':name' => $name]);
}

function apply_price_updates(PDO $pdo): void {
    $update = 'inventory-prices-2026-11';
    ensure_applied_updates_table($pdo);
    $done = $pdo->prepare("SELECT 1 FROM applied_updates WHERE name = :name");
    $done->execute([':name' => $update]);
    if ($done->fetchColumn()) return;

    $prices = product_price_map();
    $rows = $pdo->query("SELECT id, image_path FROM products")->fetchAll(PDO::FETCH_ASSOC);
    $set = $pdo->prepare("UPDATE products SET price = :price WHERE id = :id");
    foreach ($rows as $row) {
        $filename = strtolower(basename($row['image_path']));
        if (isset($prices[$filename])) {
            $set->execute([':price' => $prices[$filename], ':id' => $row['id']]);
        }
    }
    mark_update_applied($pdo, $update);
}

function product_name_map(): array {
    return [
        'bangle 1.jpeg' => 'Crystal Studded Gold Bangle Set',
        'bangle 2.jpeg' => 'Beaded Gold Bangle Set',
        'bangle 3.jpeg' => 'Kundan Multicolor Stone Bangle Set',
        'bangle 4.jpeg' => 'Black Stone Gold Bangle Set',
        'bangle 5.jpeg' => 'Filigree Gold Kundan Bangle Pair',
        'bangle 6.jpeg' => 'Multicolor Enamel Kundan Bangle Set',
        'bangle 7.jpeg' => 'Antique Gold Floral Crystal Bangle Set',
        'bangle 8.jpeg' => 'Pastel Enamel Opal Stone Kada',

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
        'bracelet 12.jpeg' => 'White Daisy & Pearl Gold Bracelet',

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
        'earring 16.jpeg' => 'Pearl & Black Crystal Wrapped Hoop Earrings',
        'earring 17.jpeg' => 'Crystal Pinwheel Flower Stud Earrings',
        'earring 18.jpeg' => 'Pearl Cascade Drop Earrings',
        'earring 19.jpeg' => 'Turquoise Spike Gold Drop Earrings',
        'earring 20.jpeg' => 'Freshwater Pearl Stack Drop Earrings',
        'earring 21.jpeg' => 'Geometric Enamel Triangle Drop Earrings',
        'earring 22.jpeg' => 'Sleek Gold Spike Drop Earrings',

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
        'pendant 11.jpeg' => 'Mom Love You to the Moon & Back Pendant',
        'pendant 12.jpeg' => 'Crystal Bezel Curved Bar Necklace',
        'pendant 13.jpeg' => 'Red Stone Heart Pendant',
        'pendant 14.jpeg' => 'Turquoise Stone Heart Pendant',
        'pendant 15.jpeg' => 'Black Feather Pendant Necklace',
        'pendant 16.jpeg' => 'Pink & Blue Stick Figure Puzzle Pendant',
        'pendant 17.jpeg' => 'Peach Stick Figure Puzzle Pendant',
        'pendant 18.jpeg' => 'Pink Beaded Rope Necklace',
        'pendant 19.jpeg' => 'Layered Turquoise Stone Chain Necklace',
        'pendant 20.jpeg' => 'Mother-of-Pearl Clover Station Necklace',
        'pendant 21.jpeg' => 'Black Clover Station Necklace',
        'pendant 22.jpeg' => 'Mother-of-Pearl Rectangle Pendant',
        'pendant 23.jpeg' => 'Black Onyx Rectangle Pendant',
        'pendant 24.jpeg' => 'Three-Layer Pearl Necklace',
        'pendant 25.jpeg' => 'Black Leaf Statement Bib Necklace',
        'pendant 26.jpeg' => 'Navy Blue Clover Station Necklace',
        'pendant 27.jpeg' => 'Double-Row Crystal Solitaire Drop Necklace',

        'ring 1.jpeg' => 'Pearl Cluster Gold Statement Ring',
        'ring 2.jpeg' => 'Red Heart Enamel Fan Ring',
        'ring 3.jpeg' => 'Dainty Gold Stackable Ring Set',
        'ring 4.jpeg' => 'Emerald Crystal Flower Ring',
        'ring 5.jpeg' => 'Ruby Red Sunburst Cocktail Ring',
        'ring 6.jpeg' => 'Hot Pink Baguette Crystal Band Ring',
        'ring 7.jpeg' => 'Minimal Gold Heart & Bar Ring Set',
        'ring 8.jpeg' => 'Gold Chevron & Stacking Band Ring Set',
        'ring 9.jpeg' => 'Emerald Green Baguette Crystal Band Ring',
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
        'bangle 6.jpeg' => 'Gold bangles studded with kundan stones in bright pink, green, blue and yellow enamel rings. A colourful set for mehndis, weddings and Eid.',
        'bangle 7.jpeg' => 'Antique gold bangles with crystal flowers and leaf motifs on a dark filigree base. Traditional elegance for festive outfits.',
        'bangle 8.jpeg' => 'A wide silver-tone kada in pastel yellow, pink and turquoise enamel, set with soft opal stones and hearts. A fun, playful statement piece.',

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
        'bracelet 12.jpeg' => 'White enamel daisies with sunny yellow centres linked by pearls on a gold chain. Fresh, feminine and perfect for everyday wear.',

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
        'earring 16.jpeg' => 'Gold hoops wire-wrapped with pearls and faceted black crystals. A striking classic contrast for parties and dawats.',
        'earring 17.jpeg' => 'Gold studs with clear crystal petals arranged in a pinwheel flower. Sparkly yet subtle for everyday and evening wear.',
        'earring 18.jpeg' => 'Delicate gold chains with cascading pearls under a crystal stud. Graceful and perfect for weddings and formal events.',
        'earring 19.jpeg' => 'Turquoise spike drops topped with ribbed gold cones. A bold boho pop of colour for casual outfits.',
        'earring 20.jpeg' => 'Four freshwater-style pearls stacked below a gold ball stud, finished with a smoky crystal bead. Elegant for day and evening.',
        'earring 21.jpeg' => 'Triangle drops in white, blush, sand and black enamel under an emerald-green stud. A modern, artsy statement.',
        'earring 22.jpeg' => 'Long polished gold spikes hanging from marquise studs. Minimal, sleek and effortlessly chic.',

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
        'pendant 11.jpeg' => 'A silver crescent moon engraved "I love you to the moon and back" with a gold "Mom" heart charm. A heartfelt gift for Mother\'s Day or her birthday.',
        'pendant 12.jpeg' => 'A curved bar of bezel-set crystals on a fine chain. Minimal sparkle that sits beautifully at the collarbone.',
        'pendant 13.jpeg' => 'A polished red stone heart with natural veining on a slim chain. A bold, romantic pop of colour.',
        'pendant 14.jpeg' => 'A turquoise stone heart with natural veining on a slim chain. A fresh, boho touch for everyday outfits.',
        'pendant 15.jpeg' => 'A detailed black feather pendant on a matching dark chain. Edgy and effortless for casual looks.',
        'pendant 16.jpeg' => 'A puzzle-piece pendant in pink and blue with a cute stick figure. Pair it with the peach piece to share with your best friend.',
        'pendant 17.jpeg' => 'A peach puzzle-piece pendant with a smiling stick figure. Matches the pink and blue piece as a best friends pair.',
        'pendant 18.jpeg' => 'A thick rope necklace woven from pink crystal beads with gold thread. A vibrant statement for weddings, mehndis and Eid.',
        'pendant 19.jpeg' => 'Three layered antique-tone chains, each with a turquoise stone at the centre. A boho statement that needs no other jewellery.',
        'pendant 20.jpeg' => 'A gold chain with three mother-of-pearl clover motifs in beaded frames. Soft shimmer for everyday elegance.',
        'pendant 21.jpeg' => 'A gold chain with three glossy black clover motifs in beaded frames. Classic contrast for day or evening.',
        'pendant 22.jpeg' => 'A rectangular mother-of-pearl pendant framed in gold on a box chain. Minimal, modern and easy to layer.',
        'pendant 23.jpeg' => 'A sleek black rectangular pendant framed in gold on a fine chain. Bold minimalism for any outfit.',
        'pendant 24.jpeg' => 'Three graduated strands of lustrous pearls finished with gold accents. Timeless grace for weddings, dawats and Eid.',
        'pendant 25.jpeg' => 'A bold bib necklace of textured black leaves on a dark chain. A dramatic statement that pairs well with plain outfits.',
        'pendant 26.jpeg' => 'A gold chain with five deep navy clover motifs in beaded frames. Rich colour with classic charm.',
        'pendant 27.jpeg' => 'Two rows of sparkling crystals meeting at a large solitaire-style drop. Red-carpet glamour for weddings and formal nights.',

        'ring 1.jpeg' => 'A gold statement ring topped with a cluster of pearls. Classy and eye-catching.',
        'ring 2.jpeg' => 'A fan-shaped ring with red heart enamel details. Playful and full of charm.',
        'ring 3.jpeg' => 'A set of dainty gold rings made for stacking. Mix and match across your fingers.',
        'ring 4.jpeg' => 'A flower ring set with emerald-green crystals. A fresh, feminine statement.',
        'ring 5.jpeg' => 'A ruby-red cocktail ring with a sunburst design. Bold glamour for special nights.',
        'ring 6.jpeg' => 'A band lined with hot pink baguette crystals. A vibrant everyday sparkle.',
        'ring 7.jpeg' => 'Minimal gold rings with heart and bar details. Subtle, modern and easy to stack.',
        'ring 8.jpeg' => 'A gold chevron ring with matching stacking bands. Wear them together or on their own.',
        'ring 9.jpeg' => 'A gold band lined with emerald-green baguette crystals. Rich colour and sparkle for festive and everyday wear.',
    ];
}

// Writes a short description from a product's name and category, for
// products that aren't in product_description_map() (e.g. added in admin).
function generate_product_description(string $name, string $category): string {
    $n = strtolower($name);
    $features = [
        '/kundan/'          => 'traditional kundan stones',
        '/pearl/'           => 'lustrous pearls',
        '/oxidi[sz]ed/'     => 'an antique oxidized finish',
        '/enamel/'          => 'glossy enamel detailing',
        '/crystal/'         => 'sparkling crystals',
        '/zircon/'          => 'sparkling zircons',
        '/stone/'           => 'eye-catching stones',
        '/butterfly/'       => 'a delicate butterfly motif',
        '/heart/'           => 'a sweet heart motif',
        '/flower|floral|daisy|\brose\b(?! gold)/' => 'a pretty floral design',
        '/leaf/'            => 'a graceful leaf design',
        '/moon/'            => 'a dreamy moon motif',
        '/\bstars?\b/'      => 'a shining star motif',
        '/rose gold/'       => 'a soft rose gold tone',
        '/gold/'            => 'a warm gold tone',
        '/silver/'          => 'a polished silver tone',
    ];
    $picked = [];
    foreach ($features as $pattern => $phrase) {
        if (preg_match($pattern, $n)) {
            $picked[] = $phrase;
            // "rose gold" already covers the gold tone
            if ($pattern === '/rose gold/') unset($features['/gold/']);
        }
        if (count($picked) === 2) break;
    }

    $lines = [
        'bangles'  => 'Easy to stack and comfortable for festive or everyday wear.',
        'bracelet' => 'A graceful finishing touch for your wrist, day or night.',
        'earrings' => 'Lightweight and comfortable enough to wear all day.',
        'pendants' => 'Hangs beautifully on its chain and makes a thoughtful gift.',
        'rings'    => 'A stylish accent to wear on its own or stacked.',
    ];
    $second = preg_match('/kundan|chandbali|jhumka|bridal|polki|jadau/', $n)
        ? 'Perfect for weddings, mehndis and Eid.'
        : ($lines[$category] ?? 'A lovely addition to your jewellery collection.');

    if (empty($picked)) {
        return trim($name) . ' from our ' . category_label($category) . ' collection. ' . $second;
    }
    return trim($name) . ' featuring ' . implode(' and ', $picked) . '. ' . $second;
}

function is_placeholder_description(string $description): bool {
    $description = trim($description);
    return $description === '' || preg_match('/^A beautiful piece from our .+ collection\.$/', $description) === 1;
}

// Fills in the description for every product that is blank or still using
// the old generic placeholder text, without touching descriptions an admin
// has written.
function update_generic_descriptions(PDO $pdo): void {
    $descriptions = product_description_map();
    $rows = $pdo->query("SELECT id, name, category, description, image_path FROM products")->fetchAll(PDO::FETCH_ASSOC);
    $update = $pdo->prepare("UPDATE products SET description = :description WHERE id = :id");

    foreach ($rows as $row) {
        if (!is_placeholder_description($row['description'])) continue;
        $filename = strtolower(basename($row['image_path']));
        $description = $descriptions[$filename] ?? generate_product_description($row['name'], $row['category']);
        $update->execute([':description' => $description, ':id' => $row['id']]);
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
        ['earring 12.jpeg', 'earring 13.jpeg', 'earring 14.jpeg', 'earring 15.jpeg',
         'earring 16.jpeg', 'earring 17.jpeg', 'earring 18.jpeg', 'earring 19.jpeg',
         'earring 20.jpeg', 'earring 21.jpeg', 'earring 22.jpeg']);
    seed_products($pdo, ['bracelet' => $categories['bracelet']], true,
        ['bracelet 7.jpeg', 'bracelet 8.jpeg', 'bracelet 9.jpeg', 'bracelet 10.jpeg', 'bracelet 11.jpeg',
         'bracelet 12.jpeg']);
    seed_products($pdo, ['bangles' => $categories['bangles']], true,
        ['bangle 5.jpeg', 'bangle 6.jpeg', 'bangle 7.jpeg', 'bangle 8.jpeg']);
}

function seed_products(PDO $pdo, ?array $categories = null, bool $skipExisting = false, ?array $onlyFiles = null): void {
    $categories = $categories ?? seed_categories();
    $names = product_name_map();
    $descriptions = product_description_map();
    $prices = product_price_map();

    $stmt = $pdo->prepare("
        INSERT INTO products (category, name, price, description, image_path, is_active)
        VALUES (:category, :name, :price, :description, :image_path, 1)
    ");
    $exists = $pdo->prepare("
        SELECT 1 FROM products WHERE image_path = :image_path
        UNION SELECT 1 FROM deleted_products WHERE image_path = :image_path
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
                ':price'       => $prices[strtolower($file)] ?? $info['price'],
                ':description' => $descriptions[strtolower($file)] ?? generate_product_description($name, $cat),
                ':image_path'  => $imagePath,
            ]);
        }
    }
}
