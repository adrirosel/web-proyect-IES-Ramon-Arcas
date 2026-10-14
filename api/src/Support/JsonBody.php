<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Extrae solo claves permitidas del body (mass-assignment seguro).
 */
final class JsonBody
{
    /**
     * @param array<string, mixed> $body
     * @param list<string> $allowed
     * @return array<string, mixed>
     */
    public static function only(array $body, array $allowed): array
    {
        $out = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $body)) {
                $out[$key] = $body[$key];
            }
        }
        return $out;
    }
}
