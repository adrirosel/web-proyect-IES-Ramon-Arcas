<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * Hash Argon2id (o bcrypt si el runtime no lo soporta).
 * Nunca almacenar ni registrar contraseñas en claro.
 */
final class Password
{
    public static function hash(string $plain): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_hash($plain, $algo);
    }

    public static function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
