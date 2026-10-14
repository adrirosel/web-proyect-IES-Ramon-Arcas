<?php

declare(strict_types=1);

namespace App;

/**
 * Lee variables de entorno con valores por defecto seguros para desarrollo.
 * Centralizar evita esparcir getenv() y facilita tests con putenv().
 */
final class Config
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);
        return $value === null ? $default : (int) $value;
    }

    /** @return list<string> */
    public static function corsOrigins(): array
    {
        $raw = self::get('CORS_ORIGINS', 'http://localhost:8080') ?? '';
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
