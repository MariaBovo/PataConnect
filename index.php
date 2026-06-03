<?php
// Main router

// Built-in PHP server router handling static files
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    if (is_file(__DIR__ . $path)) {
        return false;
    }
}

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$views_dir = __DIR__ . '/views';

// Redirect any request ending in .php to its clean URL counterpart if the file exists
if (substr($request_uri, -4) === '.php') {
    $clean_uri = substr($request_uri, 0, -4);
    $clean_file = $views_dir . $clean_uri . '.php';
    if (is_file($clean_file)) {
        $query_string = $_SERVER['QUERY_STRING'] ?? '';
        $redirect_url = $clean_uri . ($query_string !== '' ? '?' . $query_string : '');
        header('Location: ' . $redirect_url, true, 301);
        exit;
    }
}

// Base case: root
if ($request_uri === '/') {
    require $views_dir . '/index.php';
    exit;
}

// Map the request URI to a file in views
$file_path = $views_dir . $request_uri;

// 1. Check if the exact PHP file exists (if extension is omitted, e.g. /login -> views/login.php)
if (is_file($file_path . '.php')) {
    require $file_path . '.php';
    exit;
}

// 2. Check if the file path exactly matches a PHP file (e.g. /login.php -> views/login.php)
if (is_file($file_path)) {
    $ext = pathinfo($file_path, PATHINFO_EXTENSION);
    if ($ext === 'php') {
        require $file_path;
        exit;
    }
}

// 3. Check if it's a directory and has an index.php
if (is_dir($file_path) && is_file($file_path . '/index.php')) {
    require $file_path . '/index.php';
    exit;
}

// 404 Not Found
http_response_code(404);
echo "404 Not Found";