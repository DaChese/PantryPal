<?php
/*
 * Router for PHP built-in server (used on Railway).
 * Serves static files directly and routes everything else to PHP.
 */

$requestUri  = $_SERVER['REQUEST_URI'];
$filePath    = __DIR__ . parse_url($requestUri, PHP_URL_PATH);

// If the request maps to a real file (css, js, images, etc.), serve it directly
if (is_file($filePath)) {
    return false; // let the built-in server handle it
}

// Otherwise route to the requested PHP file or fall back to index.php
$phpFile = $filePath;
if (!is_file($phpFile)) {
    $phpFile = __DIR__ . '/index.php';
}

require $phpFile;
