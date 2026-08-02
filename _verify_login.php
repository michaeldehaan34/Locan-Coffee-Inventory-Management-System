<?php

/**
 * Temporary verification script — dispatches a real GET /login request
 * through the Laravel HTTP kernel and prints the HTTP status + key markers.
 * Deleted after verification.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/login', 'GET');

try {
    $response = $kernel->handle($request);

    $status = $response->getStatusCode();
    $content = (string) $response->getContent();

    echo 'STATUS: ' . $status . PHP_EOL;
    echo 'LENGTH: ' . strlen($content) . PHP_EOL;
    echo 'HAS_LOGIN_CARD: ' . (str_contains($content, 'login-card') ? 'yes' : 'no') . PHP_EOL;
    echo 'HAS_BRANDING: ' . (str_contains($content, 'LOTRA') ? 'yes' : 'no') . PHP_EOL;
    echo 'HAS_LOGO: ' . (str_contains($content, 'static/images/lotra_logo.png') ? 'yes' : 'no') . PHP_EOL;
    echo 'HAS_BARISTA_OPTGROUP: ' . (str_contains($content, 'optgroup label="Barista"') ? 'yes' : 'no') . PHP_EOL;
    echo 'HAS_MANAGER_OPTGROUP: ' . (str_contains($content, 'optgroup label="Manager"') ? 'yes' : 'no') . PHP_EOL;
} catch (Throwable $e) {
    echo 'EXCEPTION: ' . get_class($e) . PHP_EOL;
    echo 'MESSAGE: ' . $e->getMessage() . PHP_EOL;
    echo 'FILE: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    exit(1);
}

