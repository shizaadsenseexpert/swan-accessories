<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (admin_logged_in()) {
    header('Location: ./');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['is_admin'] = true;
        header('Location: ./');
        exit;
    }
    $error = 'Incorrect username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Jost:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div style="text-align:center; margin-bottom:24px;">
      <span class="brand-mark">Swan</span> <span class="brand-sub">Admin</span>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-error" style="margin:0 0 18px;"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>
  </div>
</div>
</body>
</html>
