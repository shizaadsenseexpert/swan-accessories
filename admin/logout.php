<?php
require_once __DIR__ . '/includes/auth.php';
$_SESSION['is_admin'] = false;
session_destroy();
header('Location: login');
exit;
