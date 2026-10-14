<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Genera slugs URL-friendly con soporte de tildes y ñ.
 */
final class Slug
{
    public static function from(string $text): string
    {
        $text = trim(mb_strtolower($text, 'UTF-8'));
        $map = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-') ?: 'item';
    }
}
