<?php

declare(strict_types=1);

namespace App\Support;

use App\Database;
use PDO;

/**
 * Rate limit simple en BD: clave = ruta + IP.
 * Suficiente sin Redis para un centro educativo.
 */
final class RateLimiter
{
    public static function hit(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM rate_limits WHERE ventana_inicio < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? SECOND)')
            ->execute([$windowSeconds * 2]);

        $stmt = $pdo->prepare(
            'SELECT id, intentos, ventana_inicio FROM rate_limits WHERE clave = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $pdo->prepare('INSERT INTO rate_limits (clave, intentos, ventana_inicio) VALUES (?, 1, UTC_TIMESTAMP())')
                ->execute([$key]);
            return true;
        }

        $inicio = strtotime((string) $row['ventana_inicio'] . ' UTC') ?: time();
        if ((time() - $inicio) > $windowSeconds) {
            $pdo->prepare('INSERT INTO rate_limits (clave, intentos, ventana_inicio) VALUES (?, 1, UTC_TIMESTAMP())')
                ->execute([$key]);
            return true;
        }

        if ((int) $row['intentos'] >= $maxAttempts) {
            return false;
        }

        $pdo->prepare('UPDATE rate_limits SET intentos = intentos + 1 WHERE id = ?')
            ->execute([(int) $row['id']]);
        return true;
    }
}
