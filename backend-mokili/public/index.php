<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Railway's edge terminates TLS and proxies plain HTTP to this container.
// Laravel's TrustProxies middleware runs before anything we can prepend
// in bootstrap/app.php (it has fixed framework priority), so mutating
// the request inside a middleware always lost that race and got
// overwritten back to "http". Setting $_SERVER here runs before Laravel
// even captures the Request, so there's nothing left to overwrite it.
$appUrl = $_SERVER['APP_URL'] ?? (getenv('APP_URL') ?: '');
if (str_starts_with((string) $appUrl, 'https://')) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
