<?php

declare(strict_types=1);

/**
 * Front controller: unico punto de entrada HTTP de la API.
 * Carga entorno, registra rutas y despacha con formato de error unico.
 */

use App\Auth\SessionAuth;
use App\Config;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Support\Logger;

require dirname(__DIR__) . '/vendor/autoload.php';

// Carga .env simple (sin dependencia externa) desde la raiz del monorepo o api/
$envCandidates = [
    dirname(__DIR__, 2) . '/.env',
    dirname(__DIR__) . '/.env',
];
foreach ($envCandidates as $envFile) {
    if (!is_file($envFile)) {
        continue;
    }
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\"'");
        $_ENV[$k] = $v;
        putenv("$k=$v");
    }
    break;
}

date_default_timezone_set(Config::get('APP_TIMEZONE', 'UTC') ?? 'UTC');
SessionAuth::start();

// CORS: same-origin en Docker; lista blanca si hay front en otro puerto
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = Config::corsOrigins();
if ($origin !== '' && in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer-when-downgrade');

$router = new Router();
$register = require dirname(__DIR__) . '/routes/api.php';
$register($router);

$request = Request::fromGlobals();

try {
    $response = $router->dispatch($request);
} catch (Throwable $e) {
    Logger::error('uncaught', ['message' => $e->getMessage()]);
    $response = Response::error(
        'server_error',
        Config::bool('APP_DEBUG', false) ? $e->getMessage() : 'Error interno',
        500
    );
}

$response->send();
