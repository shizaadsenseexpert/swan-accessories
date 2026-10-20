<?php
// Router for PHP's built-in server (Start Website.bat) so clean URLs like /shop
// work locally the same way .htaccess makes them work on the live server.
if (php_sapi_name() !== 'cli-server') {
    http_response_code(404);
    exit;
}

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

if (is_dir($file)) {
    $target = rtrim($file, '/') . '/index.php';
} else {
    $target = $file . '.php';
}

if (!is_file($target)) {
    http_response_code(404);
    echo 'Page not found';
    return true;
}

$_SERVER['SCRIPT_NAME'] = substr($target, strlen(__DIR__));
$_SERVER['SCRIPT_FILENAME'] = $target;
chdir(dirname($target));
require $target;
