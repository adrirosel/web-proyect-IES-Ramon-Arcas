<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

/**
 * Conexion PDO unica (lazy). Prepared statements son la via por defecto
 * para evitar inyeccion SQL en valores; columnas de ORDER BY van aparte (whitelist).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = Config::get('DB_HOST', '127.0.0.1');
        $port = Config::get('DB_PORT', '3306');
        $name = Config::get('DB_NAME', 'ies_web');
        $user = Config::get('DB_USER', 'ies_web');
        $pass = Config::get('DB_PASSWORD', '');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // Fechas en UTC en BD; la presentacion se ajusta en capa superior
            self::$pdo->exec("SET time_zone = '+00:00'");
        } catch (PDOException $e) {
            throw new PDOException('No se pudo conectar a la base de datos', (int) $e->getCode(), $e);
        }

        return self::$pdo;
    }

    /** Permite inyectar PDO en tests sin tocar getenv de produccion. */
    public static function setPdo(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}
