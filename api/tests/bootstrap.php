<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// Tests unitarios de soporte no requieren BD; los de integración la usan si está disponible.
$envFile = dirname(__DIR__, 2) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\"'");
        putenv(trim($k) . '=' . trim($v, " \t\"'"));
    }
}

$_ENV['APP_ENV'] = 'testing';
