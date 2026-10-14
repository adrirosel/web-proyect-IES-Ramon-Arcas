<?php

declare(strict_types=1);

/**
 * Aplica migraciones .sql en orden. Tabla schema_migrations evita reaplicar.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Database;

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

$pdo = Database::pdo();
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(190) NOT NULL UNIQUE,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$dir = dirname(__DIR__) . '/database/migrations';
$files = glob($dir . '/*.sql') ?: [];
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE filename = ?');
    $check->execute([$name]);
    if ($check->fetch()) {
        echo "skip $name\n";
        continue;
    }
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("No se pudo leer $name");
    }
    $pdo->exec($sql);
    $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (?)')->execute([$name]);
    echo "applied $name\n";
}

echo "OK\n";
