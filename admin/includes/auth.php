<?php
require_once __DIR__ . '/../../includes/config.php';

function admin_logged_in(): bool {
    return !empty($_SESSION['is_admin']);
}

function require_admin(): void {
    if (!admin_logged_in()) {
        header('Location: login');
        exit;
    }
}
