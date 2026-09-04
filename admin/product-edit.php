<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pdo = get_db();
$id = (int)($_GET['id'] ?? 0);
$product = $id ? get_product($id) : null;
$isNew = $product === null;

$errors = [];
$old = $product ?? ['name' => '', 'category' => 'bangles', 'price' => '', 'description' => '', 'material' => 'Stainless Steel', 'image_path' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name'] = trim($_POST['name'] ?? '');
    $old['category'] = $_POST['category'] ?? 'bangles';
    $old['price'] = (int)($_POST['price'] ?? 0);
    $old['description'] = trim($_POST['description'] ?? '');
    $old['material'] = trim($_POST['material'] ?? '') ?: 'Stainless Steel';

    if ($old['name'] === '') $errors[] = 'Please enter a product name.';
    if (!in_array($old['category'], all_categories(), true)) $errors[] = 'Please choose a valid category.';
    if ($old['price'] <= 0) $errors[] = 'Please enter a valid price.';

    $imagePath = $old['image_path'];
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $errors[] = 'Image must be jpg, jpeg, png or webp.';
        } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'There was a problem uploading the image.';
        } else {
            $destDir = BASE_PATH . '/assets/images/products/' . $old['category'];
            if (!is_dir($destDir)) mkdir($destDir, 0775, true);
            $filename = uniqid('prod_') . '.' . $ext;
            $destPath = $destDir . '/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                $imagePath = 'assets/images/products/' . $old['category'] . '/' . $filename;
            } else {
                $errors[] = 'Failed to save uploaded image.';
            }
        }
    } elseif ($isNew) {
        $errors[] = 'Please choose an image for this product.';
    }

    if (empty($errors)) {
        if ($isNew) {
            $stmt = $pdo->prepare("
                INSERT INTO products (category, name, price, description, material, image_path, is_active)
                VALUES (:category, :name, :price, :description, :material, :image_path, 1)
            ");
            $stmt->execute([
                ':category' => $old['category'],
                ':name' => $old['name'],
                ':price' => $old['price'],
                ':description' => $old['description'],
                ':material' => $old['material'],
                ':image_path' => $imagePath,
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE products SET category = :category, name = :name, price = :price,
                       description = :description, material = :material, image_path = :image_path
                WHERE id = :id
            ");
            $stmt->execute([
                ':category' => $old['category'],
                ':name' => $old['name'],
                ':price' => $old['price'],
                ':description' => $old['description'],
                ':material' => $old['material'],
                ':image_path' => $imagePath,
                ':id' => $id,
            ]);
        }
        header('Location: products.php');
        exit;
    }
}

$pageTitle = $isNew ? 'Add Product' : 'Edit Product';
$activeNav = 'products';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= $isNew ? 'Add Product' : 'Edit Product' ?></h1>
  <a href="products.php">&larr; Back to Products</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-error" style="margin:0 0 20px;">
    <?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?>
  </div>
<?php endif; ?>

<div class="admin-card" style="max-width:600px;">
  <form method="post" enctype="multipart/form-data">
    <div class="form-group">
      <label for="name">Product Name</label>
      <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="category">Category</label>
        <select id="category" name="category">
          <?php foreach (all_categories() as $c): ?>
            <option value="<?= e($c) ?>" <?= $old['category'] === $c ? 'selected' : '' ?>><?= e(category_label($c)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="price">Price (PKR)</label>
        <input type="number" id="price" name="price" value="<?= e((string)$old['price']) ?>" min="1" required>
      </div>
    </div>
    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description" rows="3"><?= e($old['description']) ?></textarea>
    </div>
    <div class="form-group">
      <label for="material">Material</label>
      <input type="text" id="material" name="material" value="<?= e($old['material']) ?>" placeholder="Stainless Steel">
    </div>
    <div class="form-group">
      <label for="image">Product Image <?= $isNew ? '' : '(leave empty to keep current)' ?></label>
      <?php if (!$isNew && $old['image_path']): ?>
        <img src="../<?= e($old['image_path']) ?>" style="width:90px;height:90px;object-fit:cover;border-radius:10px;margin-bottom:10px;">
      <?php endif; ?>
      <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
    </div>
    <button type="submit" class="btn btn-primary btn-block"><?= $isNew ? 'Add Product' : 'Save Changes' ?></button>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
