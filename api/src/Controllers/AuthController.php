<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Password;
use App\Auth\SessionAuth;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\Logger;
use App\Support\RateLimiter;

final class AuthController
{
    public function csrf(Request $request, array $params): Response
    {
        return Response::json(['csrf_token' => SessionAuth::csrfToken()]);
    }

    public function login(Request $request, array $params): Response
    {
        $key = 'login:' . $request->ip;
        if (!RateLimiter::hit($key, 10, 300)) {
            return Response::error('rate_limited', 'Demasiados intentos', 429);
        }

        // CSRF tambien en login: mitiga POST forzado desde otro origen
        $csrf = $request->header('X-CSRF-Token') ?? (string) $request->input('_csrf', '');
        if (!SessionAuth::validateCsrf($csrf)) {
            return Response::error('csrf_invalid', 'Token CSRF invalido o ausente', 403);
        }

        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        if ($email === '' || $password === '') {
            return Response::error('validation_error', 'Datos no validos', 422, [
                'email' => 'Requerido',
                'password' => 'Requerido',
            ]);
        }

        $stmt = Database::pdo()->prepare(
            'SELECT id, email, nombre, rol, password_hash, activo FROM usuarios WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        // Mensaje generico: no revelar si el email existe
        if (!$row || !(int) $row['activo'] || !Password::verify($password, (string) $row['password_hash'])) {
            Logger::warning('login_failed', ['email' => $email, 'ip' => $request->ip]);
            return Response::error('invalid_credentials', 'Credenciales incorrectas', 401);
        }

        SessionAuth::login([
            'id' => (int) $row['id'],
            'email' => (string) $row['email'],
            'nombre' => (string) $row['nombre'],
            'rol' => (string) $row['rol'],
        ]);

        return Response::json([
            'user' => [
                'id' => (int) $row['id'],
                'email' => $row['email'],
                'nombre' => $row['nombre'],
                'rol' => $row['rol'],
            ],
            'csrf_token' => SessionAuth::csrfToken(),
        ]);
    }

    public function logout(Request $request, array $params): Response
    {
        SessionAuth::logout();
        return Response::noContent();
    }

    public function me(Request $request, array $params): Response
    {
        $user = SessionAuth::user();
        if ($user === null) {
            return Response::error('unauthenticated', 'Sesion requerida', 401);
        }
        return Response::json(['user' => $user, 'csrf_token' => SessionAuth::csrfToken()]);
    }

    public function patchMe(Request $request, array $params): Response
    {
        $user = SessionAuth::user();
        if ($user === null) {
            return Response::error('unauthenticated', 'Sesion requerida', 401);
        }

        $actual = (string) $request->input('password_actual', '');
        $nueva = (string) $request->input('password_nueva', '');
        if (strlen($nueva) < 10) {
            return Response::error('validation_error', 'Datos no validos', 422, [
                'password_nueva' => 'Minimo 10 caracteres',
            ]);
        }

        $stmt = Database::pdo()->prepare('SELECT password_hash FROM usuarios WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hash = (string) $stmt->fetchColumn();
        if (!Password::verify($actual, $hash)) {
            return Response::error('validation_error', 'Datos no validos', 422, [
                'password_actual' => 'Incorrecta',
            ]);
        }

        Database::pdo()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')
            ->execute([Password::hash($nueva), $user['id']]);

        return Response::json(['ok' => true]);
    }
}
