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

// Editions are built separately: Crystal's assets/search contain no administrator material.
$docs_root = __DIR__ . '/_private/' . (docs_is_admin() ? 'admin' : 'crystal');

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
if (str_contains($path, "\0")) { http_response_code(400); exit("Bad request\n"); }
if (!docs_is_admin() && preg_match('#^/+admin(?:/|$|\.html)#i', $path)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: private, no-store');
    exit("This section is available to Herman's guide account only.\n");
}
$candidate = $docs_root . $path;

if (is_dir($candidate)) {
    $candidate = rtrim($candidate, '/') . '/index.html';
} elseif (!is_file($candidate) && is_file($candidate . '.html')) {
    $candidate = $candidate . '.html';   // cleanUrls: /courses serves courses.html
}

$real = realpath($candidate);
$base = realpath($docs_root);
if ($base === false) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Not found\n");
}

if ($real === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
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

// Recheck the account for every resource, including admin chunks and search data.
header('Cache-Control: private, no-store, max-age=0, must-revalidate');
header('Pragma: no-cache');
header('Vary: Cookie');

readfile($real);
