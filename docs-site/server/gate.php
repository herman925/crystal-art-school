<?php
/**
 * Serves the built site to signed-in readers only.
 *
 * Every request lands here. A signed-out visitor is sent to the login page and no file is
 * read, so pages, assets and the search index are all behind the same door - which is the
 * point of serving through PHP rather than pointing the web root at the build.
 */

require __DIR__ . '/auth.php';

docs_boot();

const DOCS_ROOT = __DIR__ . '/_private';

$types = [
    'html' => 'text/html; charset=utf-8',
    'css'  => 'text/css; charset=utf-8',
    'js'   => 'text/javascript; charset=utf-8',
    'json' => 'application/json; charset=utf-8',
    'svg'  => 'image/svg+xml',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'ico'  => 'image/x-icon',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'txt'  => 'text/plain; charset=utf-8',
    'map'  => 'application/json; charset=utf-8',
];

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = rawurldecode($path);

if (!docs_is_signed_in()) {
    header('Location: /login.php?next=' . rawurlencode($path));
    exit;
}

/* Resolve inside the build directory and confirm the result is still inside it, so a
   path of ../../.env cannot escape. */
$candidate = DOCS_ROOT . $path;

if (is_dir($candidate)) {
    $candidate = rtrim($candidate, '/') . '/index.html';
} elseif (!is_file($candidate) && is_file($candidate . '.html')) {
    $candidate = $candidate . '.html';   // cleanUrls: /courses serves courses.html
}

$real = realpath($candidate);
$base = realpath(DOCS_ROOT);

if ($real === false || $base === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    $real = $base . DIRECTORY_SEPARATOR . '404.html';

    if (!is_file($real)) {
        header('Content-Type: text/plain; charset=utf-8');
        exit("Not found\n");
    }
}

$ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));

header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

/* Hashed asset filenames may be cached; pages must not be, or signing out would still
   leave the last page readable from the browser's cache. */
if ($ext === 'html') {
    header('Cache-Control: private, no-store');
} else {
    header('Cache-Control: private, max-age=604800');
}

readfile($real);
