<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Log minimo a fichero: errores y eventos de seguridad, sin secretos.
 */
final class Logger
{
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        // Nunca registrar passwords/tokens aunque vengan en context
        unset($context['password'], $context['password_hash'], $context['token'], $context['csrf']);
        $line = sprintf(
            "[%s] %s %s %s\n",
            gmdate('c'),
            $level,
            $message,
            $context === [] ? '' : json_encode($context, JSON_UNESCAPED_UNICODE)
        );
        @file_put_contents($dir . '/app.log', $line, FILE_APPEND);
    }
}
