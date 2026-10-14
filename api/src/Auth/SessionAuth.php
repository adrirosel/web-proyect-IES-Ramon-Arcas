<?php

declare(strict_types=1);

namespace App\Auth;

use App\Config;
use App\Database;
use App\Support\Logger;

/**
 * Sesion por cookie propia de la web (no compartida con intranet).
 * Regenera el id al login para mitigar fixation.
 */
final class SessionAuth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = Config::bool('SESSION_SECURE', false);
        session_name(Config::get('SESSION_NAME', 'ies_web_session') ?? 'ies_web_session');
        session_set_cookie_params([
            'lifetime' => Config::int('SESSION_LIFETIME_MINUTES', 120) * 60,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['_csrf'];
    }

    public static function validateCsrf(?string $token): bool
    {
        self::start();
        $sessionToken = $_SESSION['_csrf'] ?? null;
        if (!is_string($sessionToken) || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    /** @param array{id:int,email:string,nombre:string,rol:string} $user */
    public static function login(array $user): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_rol'] = $user['rol'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_nombre'] = $user['nombre'];
        // Nuevo CSRF tras login
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        Logger::info('login_ok', ['user_id' => $user['id']]);
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
    }

    /** @return array{id:int,email:string,nombre:string,rol:string}|null */
    public static function user(): ?array
    {
        self::start();
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        // Revalidar en BD: usuario desactivado pierde la sesion
        $stmt = Database::pdo()->prepare(
            'SELECT id, email, nombre, rol, activo FROM usuarios WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int) $_SESSION['user_id']]);
        $row = $stmt->fetch();
        if (!$row || !(int) $row['activo']) {
            self::logout();
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'email' => (string) $row['email'],
            'nombre' => (string) $row['nombre'],
            'rol' => (string) $row['rol'],
        ];
    }
}
