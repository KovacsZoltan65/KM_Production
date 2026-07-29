<?php

$publicPath = dirname(__DIR__).DIRECTORY_SEPARATOR.'public';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = $publicPath.str_replace('/', DIRECTORY_SEPARATOR, urldecode($path));

if (
    getenv('E2E_INTENTIONAL_HTTP_500') === '1'
    && $path === '/e2e-intentional-http-500'
) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Intentional E2E HTTP failure.';

    return true;
}

if ($path !== '/' && is_file($file)) {
    return false;
}

require_once $publicPath.DIRECTORY_SEPARATOR.'index.php';
