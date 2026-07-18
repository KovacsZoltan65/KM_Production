<?php

$publicPath = dirname(__DIR__).DIRECTORY_SEPARATOR.'public';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = $publicPath.str_replace('/', DIRECTORY_SEPARATOR, urldecode($path));

if ($path !== '/' && is_file($file)) {
    return false;
}

require_once $publicPath.DIRECTORY_SEPARATOR.'index.php';
